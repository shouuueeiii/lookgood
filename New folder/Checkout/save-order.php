<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../../session_bootstrap.php';
header('Content-Type: application/json');

// Try to load database connection
if (!@include_once __DIR__ . '/../db.php') {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Try to load auth functions
if (!@include_once dirname(dirname(__DIR__)) . '/auth_user.php') {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Auth module not found']);
    exit;
}

// Check auth but return JSON error instead of redirecting (API endpoint)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - User not logged in']);
    exit;
}

function pushAdminNotification(mysqli $conn, string $type, string $title, string $message, array $data, string $sourceKey): void {
    $conn->query("CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(30) NOT NULL DEFAULT 'status',
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        data_json TEXT NULL,
        source_key VARCHAR(191) NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_admin_notifications_source (source_key)
    )");
    $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($payload === false) $payload = '{}';
    $stmt = $conn->prepare(
        'INSERT INTO admin_notifications (type, title, message, data_json, source_key, is_read)
         VALUES (?, ?, ?, ?, ?, 0)
         ON DUPLICATE KEY UPDATE id = id'
    );
    if (!$stmt) return;
    $stmt->bind_param('sssss', $type, $title, $message, $payload, $sourceKey);
    $stmt->execute();
    $stmt->close();
}

function ensureClientOrderRefColumn(mysqli $conn): void {
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('checkout', 'checkOut') AND COLUMN_NAME = 'client_order_ref' LIMIT 1");
    if (!$stmt) return;
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    if (!$exists) {
        $conn->query("ALTER TABLE checkout ADD COLUMN client_order_ref VARCHAR(80) NULL AFTER user_id");
    }
}

// ── Ensure discount_usage tracking table exists ─────────────────────────────
function ensureDiscountUsageTable(mysqli $conn): void {
    $conn->query("
        CREATE TABLE IF NOT EXISTS discount_usage (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            discountCode VARCHAR(100) NOT NULL,
            user_id      INT          NOT NULL,
            order_id     INT          NULL,
            used_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_du_code  (discountCode),
            INDEX idx_du_user  (user_id),
            INDEX idx_du_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

ensureClientOrderRefColumn($conn);
ensureCheckoutLogisticsColumns($conn);
ensureDiscountUsageTable($conn);

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$order = $input['order'] ?? [];

$clientRef      = trim((string)($order['clientOrderRef'] ?? ''));
$items          = $order['items'] ?? [];
$customer       = $order['customer'] ?? [];
$paymentMethod  = trim((string)($order['paymentMethod'] ?? ''));
$shippingMethod = trim((string)($order['shippingMethod'] ?? ''));
$courierService = trim((string)($order['courierService'] ?? ''));
$shippingFee    = (float)($order['shippingFee'] ?? 0);
$subtotal       = (float)($order['subtotal'] ?? 0);
$discount       = (float)($order['discount'] ?? 0);
$total          = (float)($order['total'] ?? 0);
// ── Pull applied vouchers from the payload ───────────────────────────────────
$appliedVouchers = $order['appliedVouchers'] ?? [];   // array of {code, type, ...}

if ($clientRef === '' || !is_array($items) || empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid order payload']);
    exit;
}

$fullName = trim((string)($customer['fullName'] ?? ''));
$email    = trim((string)($customer['email'] ?? ($_SESSION['email'] ?? '')));
$phone    = trim((string)($customer['phone'] ?? ''));
$addressParts = array_filter([
    trim((string)($customer['address1'] ?? '')),
    trim((string)($customer['address2'] ?? '')),
    trim((string)($customer['city'] ?? '')),
    trim((string)($customer['province'] ?? '')),
    trim((string)($customer['region'] ?? ''))
], static fn($value) => $value !== '');
$address = implode(', ', $addressParts);
$zip     = trim((string)($customer['zip'] ?? ''));
$deliveryNote = trim((string)($customer['note'] ?? ''));

$estimatedDelivery = computeEstimatedDeliveryDate($shippingMethod);
$trackingNumber = buildInitialTrackingNumber($clientRef, $courierService);

if ($fullName === '' || $email === '' || $phone === '' || $address === '' || $zip === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing customer details']);
    exit;
}

$existing = $conn->prepare('SELECT order_id FROM checkout WHERE client_order_ref = ? LIMIT 1');
if (!$existing) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare lookup']);
    exit;
}
$existing->bind_param('s', $clientRef);
$existing->execute();
$existingResult = $existing->get_result();
if ($existingRow = $existingResult->fetch_assoc()) {
    $existing->close();
    echo json_encode(['success' => true, 'order_id' => (int)$existingRow['order_id'], 'already_saved' => true]);
    exit;
}
$existing->close();

$conn->begin_transaction();

try {
    $userId = (int)$_SESSION['user_id'];

    // ── 1. Insert order ──────────────────────────────────────────────────────
    $insert = $conn->prepare(
        'INSERT INTO checkout (user_id, client_order_ref, total_amount, full_name, email, phone, address, zip, payment_method, shipping_method, courier_service, estimated_delivery, tracking_number, delivery_note, shipping_fee, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$insert) {
        throw new Exception('Failed to prepare order insert');
    }

    $status = 'pending';
    $insert->bind_param('isdsssssssssssds', $userId, $clientRef, $total, $fullName, $email, $phone, $address, $zip, $paymentMethod, $shippingMethod, $courierService, $estimatedDelivery, $trackingNumber, $deliveryNote, $shippingFee, $status);
    if (!$insert->execute()) {
        throw new Exception('Failed to save order');
    }

    $orderId = (int)$insert->insert_id;
    $insert->close();

    // ── 2. Insert order items + deduct stock ─────────────────────────────────
    $itemStmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
    if (!$itemStmt) {
        throw new Exception('Failed to prepare order item insert');
    }

    $stockStmt = $conn->prepare('UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE product_id = ?');
    if (!$stockStmt) {
        throw new Exception('Failed to prepare stock update');
    }

    foreach ($items as $item) {
        $productId = trim((string)($item['id'] ?? ''));
        $quantity  = max(1, (int)($item['quantity'] ?? 1));
        $price     = (float)($item['price'] ?? 0);

        if ($productId === '' || $price <= 0) {
            throw new Exception('Invalid order item data');
        }

        $itemStmt->bind_param('isid', $orderId, $productId, $quantity, $price);
        if (!$itemStmt->execute()) {
            throw new Exception('Failed to save order item');
        }

        $stockStmt->bind_param('is', $quantity, $productId);
        if (!$stockStmt->execute()) {
            throw new Exception('Failed to update product stock');
        }
    }

    $itemStmt->close();
    $stockStmt->close();

    // ── 3. Record discount usage + enforce limits ────────────────────────────
    foreach ($appliedVouchers as $voucher) {
        $voucherCode = strtoupper(trim((string)($voucher['code'] ?? '')));
        if ($voucherCode === '') continue;

        // Lock the discount row so concurrent orders can't race past the limit
        $discStmt = $conn->prepare("
            SELECT totalUsageLimit, perUserLimit
            FROM discount
            WHERE discountCode = ?
              AND NOW() BETWEEN startDate AND endDate
            LIMIT 1
            FOR UPDATE
        ");
        if (!$discStmt) throw new Exception('Failed to prepare discount lookup');
        $discStmt->bind_param('s', $voucherCode);
        $discStmt->execute();
        $discRow = $discStmt->get_result()->fetch_assoc();
        $discStmt->close();

        if (!$discRow) {
            throw new Exception("Voucher '{$voucherCode}' is no longer valid.");
        }

        // Count how many times this voucher has been used globally
        $totalUsedStmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM discount_usage WHERE discountCode = ?');
        if (!$totalUsedStmt) throw new Exception('Failed to count global usage');
        $totalUsedStmt->bind_param('s', $voucherCode);
        $totalUsedStmt->execute();
        $totalUsed = (int)$totalUsedStmt->get_result()->fetch_assoc()['cnt'];
        $totalUsedStmt->close();

        if ($totalUsed >= (int)$discRow['totalUsageLimit']) {
            throw new Exception("Voucher '{$voucherCode}' has reached its total usage limit.");
        }

        if ($discRow['perUserLimit'] !== null) {
            $userUsedStmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM discount_usage WHERE discountCode = ? AND user_id = ?');
            if (!$userUsedStmt) throw new Exception('Failed to count user usage');
            $userUsedStmt->bind_param('si', $voucherCode, $userId);
            $userUsedStmt->execute();
            $userUsed = (int)$userUsedStmt->get_result()->fetch_assoc()['cnt'];
            $userUsedStmt->close();

            if ($userUsed >= (int)$discRow['perUserLimit']) {
                throw new Exception("You have already used voucher '{$voucherCode}' the maximum number of times.");
            }
        }

        $usageStmt = $conn->prepare('INSERT INTO discount_usage (discountCode, user_id, order_id) VALUES (?, ?, ?)');
        if (!$usageStmt) throw new Exception('Failed to prepare usage insert');
        $usageStmt->bind_param('sii', $voucherCode, $userId, $orderId);
        if (!$usageStmt->execute()) {
            throw new Exception("Failed to record usage for voucher '{$voucherCode}'");
        }
        $usageStmt->close();

        $deductStmt = $conn->prepare('UPDATE discount SET totalUsageLimit = GREATEST(totalUsageLimit - 1, 0) WHERE discountCode = ?');
        if (!$deductStmt) throw new Exception('Failed to prepare limit deduction');
        $deductStmt->bind_param('s', $voucherCode);
        $deductStmt->execute();
        $deductStmt->close();
    }

    $conn->commit();

    pushAdminNotification(
        $conn,
        'order',
        'New Order Placed',
        'Order #' . $orderId . ' placed by ' . $fullName . ' amounting to P' . number_format($total, 2) . '.',
        ['orderId' => (string)$orderId, 'customer' => $fullName, 'status' => 'pending'],
        'order:new:' . $orderId
    );

    echo json_encode(['success' => true, 'order_id' => $orderId]);

} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function addCheckoutColumnIfMissing(mysqli $conn, string $columnName, string $definition): void {
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('checkout', 'checkOut') AND COLUMN_NAME = ? LIMIT 1");
    if (!$stmt) return;
    $stmt->bind_param('s', $columnName);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    if (!$exists) {
        $conn->query("ALTER TABLE checkout ADD COLUMN {$columnName} {$definition}");
    }
}

function ensureCheckoutLogisticsColumns(mysqli $conn): void {
    addCheckoutColumnIfMissing($conn, 'shipping_method', "VARCHAR(80) NULL AFTER payment_method");
    addCheckoutColumnIfMissing($conn, 'courier_service', "VARCHAR(120) NULL AFTER shipping_method");
    addCheckoutColumnIfMissing($conn, 'estimated_delivery', "DATE NULL AFTER courier_service");
    addCheckoutColumnIfMissing($conn, 'tracking_number', "VARCHAR(120) NULL AFTER estimated_delivery");
    addCheckoutColumnIfMissing($conn, 'delivery_note', "TEXT NULL AFTER tracking_number");
}

function computeEstimatedDeliveryDate(string $shippingMethod): string {
    $method = strtolower(trim($shippingMethod));
    $daysToAdd = 4;
    if ($method === 'free' || $method === 'free shipping') {
        $daysToAdd = 6;
    } elseif ($method === 'standard' || $method === 'standard shipping') {
        $daysToAdd = 4;
    } elseif ($method === 'express' || $method === 'express shipping') {
        $daysToAdd = 1;
    }
    return date('Y-m-d', strtotime('+' . $daysToAdd . ' days'));
}

function buildInitialTrackingNumber(string $clientRef, string $courierService): string {
    $rawRef = strtoupper(preg_replace('/[^A-Z0-9]/', '', $clientRef));
    $suffix = $rawRef !== '' ? substr($rawRef, -10) : strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
    $courierCode = strtoupper(substr(preg_replace('/[^A-Z]/', '', $courierService), 0, 3));
    if ($courierCode === '') $courierCode = 'LGF';
    return $courierCode . '-' . $suffix;
}