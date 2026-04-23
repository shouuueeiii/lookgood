<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';
header('Content-Type: application/json');

if (!@include_once __DIR__ . '/../config.php') {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

if (!@include_once __DIR__ . '/../auth_user.php') {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Auth module not found']);
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    $guestCheckoutPath = __DIR__ . '/../userBack_end/guestCheckoutAPI.php';
    if (file_exists($guestCheckoutPath)) {
        require $guestCheckoutPath;
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized - User not logged in']);
    }
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

function ensureColumns(mysqli $conn): void {
    // client_order_ref
    $s = $conn->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'checkout' AND COLUMN_NAME = 'client_order_ref' LIMIT 1");
    if ($s) { $s->execute(); $s->store_result(); if ($s->num_rows === 0) $conn->query("ALTER TABLE checkout ADD COLUMN client_order_ref VARCHAR(80) NULL AFTER user_id"); $s->close(); }

    // payment_status
    $s = $conn->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'checkout' AND COLUMN_NAME = 'payment_status' LIMIT 1");
    if ($s) { $s->execute(); $s->store_result(); if ($s->num_rows === 0) $conn->query("ALTER TABLE checkout ADD COLUMN payment_status VARCHAR(20) NOT NULL DEFAULT 'Pending' AFTER payment_method"); $s->close(); }

    // shipping_method
    $s = $conn->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'checkout' AND COLUMN_NAME = 'shipping_method' LIMIT 1");
    if ($s) { $s->execute(); $s->store_result(); if ($s->num_rows === 0) $conn->query("ALTER TABLE checkout ADD COLUMN shipping_method VARCHAR(50) NOT NULL DEFAULT 'Standard Shipping' AFTER shipping_fee"); $s->close(); }

    // tracking_number
    $s = $conn->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'checkout' AND COLUMN_NAME = 'tracking_number' LIMIT 1");
    if ($s) { $s->execute(); $s->store_result(); if ($s->num_rows === 0) $conn->query("ALTER TABLE checkout ADD COLUMN tracking_number VARCHAR(100) NULL AFTER shipping_method"); $s->close(); }

    // estimated_delivery
    $s = $conn->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'checkout' AND COLUMN_NAME = 'estimated_delivery' LIMIT 1");
    if ($s) { $s->execute(); $s->store_result(); if ($s->num_rows === 0) $conn->query("ALTER TABLE checkout ADD COLUMN estimated_delivery VARCHAR(100) NULL AFTER tracking_number"); $s->close(); }

    // payment_confirmed_at
    $s = $conn->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'checkout' AND COLUMN_NAME = 'payment_confirmed_at' LIMIT 1");
    if ($s) { $s->execute(); $s->store_result(); if ($s->num_rows === 0) $conn->query("ALTER TABLE checkout ADD COLUMN payment_confirmed_at DATETIME NULL AFTER payment_status"); $s->close(); }
}

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

// ── Map shipping method value → human label + estimated delivery ────────────
function resolveShipping(string $raw): array {
    $key = strtolower(trim($raw));
    $map = [
        'free'     => ['label' => 'Free Shipping',     'eta' => '5–7 business days'],
        'standard' => ['label' => 'Standard Shipping', 'eta' => '3–5 business days'],
        'express'  => ['label' => 'Express Shipping',  'eta' => '1–2 business days'],
        // also accept full labels in case they come through
        'free shipping'     => ['label' => 'Free Shipping',     'eta' => '5–7 business days'],
        'standard shipping' => ['label' => 'Standard Shipping', 'eta' => '3–5 business days'],
        'express shipping'  => ['label' => 'Express Shipping',  'eta' => '1–2 business days'],
    ];
    return $map[$key] ?? ['label' => 'Standard Shipping', 'eta' => '3–5 business days'];
}

ensureColumns($conn);
ensureDiscountUsageTable($conn);

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$order = $input['order'] ?? [];

$clientRef      = trim((string)($order['clientOrderRef'] ?? ''));
$items          = $order['items'] ?? [];
$customer       = $order['customer'] ?? [];
$paymentMethod  = trim((string)($order['paymentMethod'] ?? ''));
$shippingFee    = (float)($order['shippingFee'] ?? 0);
$subtotal       = (float)($order['subtotal'] ?? 0);
$discount       = (float)($order['discount'] ?? 0);
$total          = (float)($order['total'] ?? 0);
$appliedVouchers = $order['appliedVouchers'] ?? [];

// ── Shipping method + estimated delivery ────────────────────────────────────
$rawShipping   = trim((string)($order['shippingMethod'] ?? 'standard'));
$shipping      = resolveShipping($rawShipping);
$shippingLabel = $shipping['label'];   // e.g. "Standard Shipping"
$estDelivery   = $shipping['eta'];     // e.g. "3–5 business days"

// ── Payment status ──────────────────────────────────────────────────────────
// The user lands on success.php only after PayMongo redirects them back,
// which means PayMongo accepted the GCash payment.
// We mark it as Paid immediately upon save.
// (For COD you'd keep it Pending — add that branch if needed later.)
$paymentStatus = 'Paid';

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
], static fn($v) => $v !== '');
$address = implode(', ', $addressParts);
$zip     = trim((string)($customer['zip'] ?? ''));

if ($fullName === '' || $email === '' || $phone === '' || $address === '' || $zip === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing customer details']);
    exit;
}

// Idempotency check — if already saved, return the existing order_id
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

    // ── 1. Insert order ──────────────────────────────────────────────────
    // FIX: now includes payment_status, shipping_method, estimated_delivery
    $insert = $conn->prepare(
        'INSERT INTO checkout
            (user_id, client_order_ref, total_amount, full_name, email, phone,
             address, zip, payment_method, payment_status, payment_confirmed_at,
             shipping_fee, shipping_method, estimated_delivery, status)
         VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)'
    );
    if (!$insert) {
        throw new Exception('Failed to prepare order insert: ' . $conn->error);
    }

    $status = 'pending'; // order fulfillment status starts pending
    $insert->bind_param(
        'isdsssssssdsss',
        $userId,          // i
        $clientRef,       // s
        $total,           // d
        $fullName,        // s
        $email,           // s
        $phone,           // s
        $address,         // s
        $zip,             // s
        $paymentMethod,   // s
        $paymentStatus,   // s  ← 'Paid'
        $shippingFee,     // d
        $shippingLabel,   // s  ← 'Standard Shipping' / 'Express Shipping' / 'Free Shipping'
        $estDelivery,     // s  ← '3–5 business days' etc.
        $status           // s  ← 'pending'
    );
    if (!$insert->execute()) {
        throw new Exception('Failed to save order: ' . $insert->error);
    }

    $orderId = (int)$insert->insert_id;
    $insert->close();

    // ── 2. Insert order items + deduct stock ─────────────────────────────
    $itemStmt = $conn->prepare(
        'INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)'
    );
    if (!$itemStmt) throw new Exception('Failed to prepare order item insert');

    $stockCheckStmt = $conn->prepare(
        'SELECT stock FROM products WHERE product_id = ? LIMIT 1 FOR UPDATE'
    );
    if (!$stockCheckStmt) throw new Exception('Failed to prepare stock check');

    $stockStmt = $conn->prepare(
        'UPDATE products SET stock = GREATEST(CAST(stock AS SIGNED) - ?, 0) WHERE product_id = ?'
    );
    if (!$stockStmt) throw new Exception('Failed to prepare stock update');

    foreach ($items as $item) {
        $productId = trim((string)($item['id'] ?? ''));
        $quantity  = max(1, (int)($item['quantity'] ?? 1));
        $price     = (float)($item['price'] ?? 0);

        if ($productId === '' || $price <= 0) {
            throw new Exception('Invalid order item data');
        }

        $stockCheckStmt->bind_param('s', $productId);
        $stockCheckStmt->execute();
        $stockRow     = $stockCheckStmt->get_result()->fetch_assoc();
        $currentStock = (int)($stockRow['stock'] ?? 0);

        if ($currentStock < $quantity) {
            throw new Exception("Insufficient stock for product '{$productId}'. Available: {$currentStock}, Requested: {$quantity}");
        }

        $itemStmt->bind_param('isid', $orderId, $productId, $quantity, $price);
        if (!$itemStmt->execute()) throw new Exception('Failed to save order item');

        $stockStmt->bind_param('is', $quantity, $productId);
        if (!$stockStmt->execute()) throw new Exception('Failed to update product stock');
    }

    $stockCheckStmt->close();
    $itemStmt->close();
    $stockStmt->close();

    // ── 3. Record discount usage + enforce limits ────────────────────────
    foreach ($appliedVouchers as $voucher) {
        $voucherCode = strtoupper(trim((string)($voucher['code'] ?? '')));
        if ($voucherCode === '') continue;

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

        if (!$discRow) throw new Exception("Voucher '{$voucherCode}' is no longer valid.");

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
        if (!$usageStmt->execute()) throw new Exception("Failed to record usage for voucher '{$voucherCode}'");
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
        'Order #' . $orderId . ' placed by ' . $fullName . ' — ₱' . number_format($total, 2) . ' via ' . strtoupper($paymentMethod) . ' (PAID).',
        ['orderId' => (string)$orderId, 'customer' => $fullName, 'status' => 'pending', 'paymentStatus' => 'Paid'],
        'order:new:' . $orderId
    );

    // Push PAID payment notification — clearly not PENDING
    pushAdminNotification(
        $conn,
        'payment',
        'Payment Confirmed',
        'Order #' . $orderId . ' payment via ' . strtoupper($paymentMethod) . ' is now PAID.',
        ['orderId' => (string)$orderId, 'paymentStatus' => 'Paid'],
        'payment:confirmed:' . $orderId
    );

    echo json_encode(['success' => true, 'order_id' => $orderId]);

} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}