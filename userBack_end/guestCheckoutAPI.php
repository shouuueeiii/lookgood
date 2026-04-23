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

if (empty($_SESSION['guest_id'])) {
    $_SESSION['guest_id'] = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Logged-in users should use save-order.php']);
    exit;
}

$guestId = $_SESSION['guest_id'];

$conn->query("
    CREATE TABLE IF NOT EXISTS guest_checkout (
        id           INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        guest_id     VARCHAR(64)  NOT NULL,
        client_order_ref VARCHAR(80) NULL,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        full_name    VARCHAR(100) NULL,
        email        VARCHAR(100) NULL,
        phone        VARCHAR(20)  NULL,
        address      TEXT         NULL,
        zip          VARCHAR(10)  NULL,
        payment_method VARCHAR(50) NULL,
        shipping_fee DECIMAL(10,2) DEFAULT 0,
        status       ENUM('pending','processing','shipped','out_for_delivery','delivered','completed','cancelled','returned') NOT NULL DEFAULT 'pending',
        created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_client_ref (client_order_ref),
        INDEX idx_guest_id (guest_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

function pushAdminNotificationGuest(mysqli $conn, string $type, string $title, string $message, array $data, string $sourceKey): void {
    $conn->query("
        CREATE TABLE IF NOT EXISTS admin_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(30) NOT NULL DEFAULT 'status',
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            data_json TEXT NULL,
            source_key VARCHAR(191) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_admin_notifications_source (source_key)
        )
    ");
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

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$order = $input['order'] ?? [];

$clientRef     = trim((string)($order['clientOrderRef'] ?? ''));
$items         = $order['items'] ?? [];
$customer      = $order['customer'] ?? [];
$paymentMethod = trim((string)($order['paymentMethod'] ?? ''));
$shippingFee   = (float)($order['shippingFee'] ?? 0);
$total         = (float)($order['total'] ?? 0);

if ($clientRef === '' || !is_array($items) || empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid order payload']);
    exit;
}

$fullName = trim((string)($customer['fullName'] ?? ''));
$email    = trim((string)($customer['email'] ?? ''));
$phone    = trim((string)($customer['phone'] ?? ''));
$addressParts = array_filter([
    trim((string)($customer['address1'] ?? '')),
    trim((string)($customer['address2'] ?? '')),
    trim((string)($customer['city'] ?? '')),
    trim((string)($customer['province'] ?? '')),
    trim((string)($customer['region'] ?? '')),
], static fn($v) => $v !== '');
$address = implode(', ', $addressParts);
$zip     = trim((string)($customer['zip'] ?? ''));

if ($fullName === '' || $email === '' || $phone === '' || $address === '' || $zip === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing customer details']);
    exit;
}

// Idempotency — don't double-save the same client_order_ref
$existing = $conn->prepare('SELECT id FROM guest_checkout WHERE client_order_ref = ? LIMIT 1');
if ($existing) {
    $existing->bind_param('s', $clientRef);
    $existing->execute();
    if ($row = $existing->get_result()->fetch_assoc()) {
        $existing->close();
        echo json_encode(['success' => true, 'order_id' => (int)$row['id'], 'already_saved' => true]);
        exit;
    }
    $existing->close();
}

$conn->begin_transaction();

try {
    $insert = $conn->prepare(
        'INSERT INTO guest_checkout
            (guest_id, client_order_ref, total_amount, full_name, email, phone, address, zip, payment_method, shipping_fee, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$insert) throw new Exception('Failed to prepare guest order insert');

    $status = 'pending';
    $insert->bind_param('ssdsssssdss',
        $guestId, $clientRef, $total,
        $fullName, $email, $phone, $address, $zip,
        $paymentMethod, $shippingFee, $status
    );
    if (!$insert->execute()) throw new Exception('Failed to save guest order');

    $orderId = (int)$insert->insert_id;
    $insert->close();


    $conn->query("
        CREATE TABLE IF NOT EXISTS guest_order_items (
            id         INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id   INT          NOT NULL,
            product_id VARCHAR(255) NOT NULL,
            quantity   INT          NOT NULL DEFAULT 1,
            price      DECIMAL(10,2) NOT NULL,
            INDEX idx_order_id (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $itemStmt = $conn->prepare(
        'INSERT INTO guest_order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)'
    );
    if (!$itemStmt) throw new Exception('Failed to prepare guest order item insert');

    $stockCheck = $conn->prepare('SELECT stock FROM products WHERE product_id = ? LIMIT 1 FOR UPDATE');
    $stockUpd   = $conn->prepare('UPDATE products SET stock = GREATEST(CAST(stock AS SIGNED) - ?, 0) WHERE product_id = ?');
    if (!$stockCheck || !$stockUpd) throw new Exception('Failed to prepare stock statements');

    foreach ($items as $item) {
        $productId = trim((string)($item['id'] ?? ''));
        $quantity  = max(1, (int)($item['quantity'] ?? 1));
        $price     = (float)($item['price'] ?? 0);

        if ($productId === '' || $price <= 0) throw new Exception('Invalid order item data');

        $stockCheck->bind_param('s', $productId);
        $stockCheck->execute();
        $stockRow = $stockCheck->get_result()->fetch_assoc();
        $currentStock = (int)($stockRow['stock'] ?? 0);

        if ($currentStock < $quantity) {
            throw new Exception("Insufficient stock for '{$productId}'. Available: {$currentStock}, Requested: {$quantity}");
        }

        $itemStmt->bind_param('isid', $orderId, $productId, $quantity, $price);
        if (!$itemStmt->execute()) throw new Exception('Failed to save guest order item');

        $stockUpd->bind_param('is', $quantity, $productId);
        if (!$stockUpd->execute()) throw new Exception('Failed to update product stock');
    }

    $stockCheck->close();
    $stockUpd->close();
    $itemStmt->close();

    $conn->commit();

    pushAdminNotificationGuest(
        $conn, 'order',
        'New Guest Order',
        'Guest order #' . $orderId . ' placed by ' . $fullName . ' amounting to P' . number_format($total, 2) . '.',
        ['orderId' => (string)$orderId, 'guestId' => $guestId, 'customer' => $fullName, 'status' => 'pending'],
        'guest_order:new:' . $orderId
    );

    echo json_encode(['success' => true, 'order_id' => $orderId]);

} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>