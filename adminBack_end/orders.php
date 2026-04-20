<?php
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'admin');
require_once __DIR__ . '/../session_bootstrap.php';
require_once '../config.php';

header('Content-Type: application/json');

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

$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
if (!isset($_SESSION['email']) || $role !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?: [];
    $orderId = (int)($payload['id'] ?? 0);
    $newStatusRaw = strtolower(trim((string)($payload['status'] ?? '')));

    $allowed = [
        'pending',
        'processing',
        'shipped',
        'out_for_delivery',
        'delivered',
        'completed',
        'cancelled',
        'returned'
    ];

    if ($orderId <= 0 || !in_array($newStatusRaw, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid payload']);
        exit();
    }

    $update = $conn->prepare('UPDATE checkout SET status = ? WHERE order_id = ?');
    if (!$update) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to prepare update']);
        exit();
    }

    $update->bind_param('si', $newStatusRaw, $orderId);
    $ok = $update->execute();
    $affected = $update->affected_rows;
    $update->close();

    if (!$ok || $affected < 0) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update order']);
        exit();
    }

    pushAdminNotification(
        $conn,
        'status',
        'Order Status Updated',
        'Order #' . $orderId . ' status changed to ' . strtoupper($newStatusRaw) . '.',
        ['orderId' => (string)$orderId, 'status' => $newStatusRaw],
        'order:status:' . $orderId . ':' . $newStatusRaw
    );

    if ($newStatusRaw === 'cancelled') {
        pushAdminNotification(
            $conn,
            'cancel',
            'Order Cancelled',
            'Order #' . $orderId . ' was cancelled.',
            ['orderId' => (string)$orderId],
            'order:cancel:' . $orderId
        );
    }

    if ($newStatusRaw === 'returned') {
        pushAdminNotification(
            $conn,
            'return',
            'Order Returned',
            'Order #' . $orderId . ' was returned.',
            ['orderId' => (string)$orderId],
            'order:return:' . $orderId
        );
    }

    pushAdminNotification(
        $conn,
        'payment',
        'Payment Updated',
        'Payment state for Order #' . $orderId . ' updated with order status ' . strtoupper($newStatusRaw) . '.',
        ['orderId' => (string)$orderId, 'status' => $newStatusRaw],
        'payment:' . $orderId . ':' . $newStatusRaw
    );

    echo json_encode(['success' => true]);
    exit();
}

function normalizeOrderStatus(string $status): string {
    $s = strtolower(trim($status));
    if ($s === 'out_for_delivery') return 'Shipped';
    if ($s === 'pending') return 'Pending';
    if ($s === 'processing') return 'Processing';
    if ($s === 'shipped') return 'Shipped';
    if ($s === 'delivered') return 'Delivered';
    if ($s === 'completed') return 'Delivered';
    if ($s === 'cancelled') return 'Pending';
    return 'Pending';
}

function inferPaymentStatus(string $status, string $paymentMethod): string {
    $s = strtolower(trim($status));
    $pm = strtolower(trim($paymentMethod));

    // Non-COD methods are typically captured online before order fulfillment.
    if ($pm !== '' && $pm !== 'cod' && $pm !== 'cash on delivery') return 'Paid';

    if ($s === 'pending' || $s === 'processing') return 'Pending';
    if ($s === 'shipped' || $s === 'out_for_delivery' || $s === 'delivered' || $s === 'completed') return 'Paid';
    return 'Pending';
}

function normalizePaymentMethod(string $method): string {
    $value = strtolower(trim($method));
    if ($value === '') return 'Unknown';
    if ($value === '0') return 'GCash';
    if (in_array($value, ['cod', 'cash on delivery', 'cash_on_delivery'], true)) return 'Cash on Delivery';
    if (in_array($value, ['gcash', 'g-cash'], true)) return 'GCash';
    if (in_array($value, ['card', 'credit card', 'debit card', 'credit/debit card'], true)) return 'Card';
    return ucwords(str_replace(['_', '-'], ' ', $value));
}

function normalizeShippingMethod(string $method, float $shippingFee): string {
    $value = strtolower(trim($method));
    if (in_array($value, ['free', 'free shipping'], true)) return 'Free Shipping';
    if (in_array($value, ['standard', 'standard shipping'], true)) return 'Standard Shipping';
    if (in_array($value, ['express', 'express shipping'], true)) return 'Express Shipping';

    if ($value !== '') return ucwords(str_replace(['_', '-'], ' ', $value));

    if ($shippingFee <= 0) return 'Free Shipping';
    if ($shippingFee <= 120) return 'Standard Shipping';
    return 'Express Shipping';
}

function formatEstimatedDelivery(string $value): string {
    $trimmed = trim($value);
    if ($trimmed === '') return '';
    $timestamp = strtotime($trimmed);
    if ($timestamp === false) return $trimmed;
    return date('M d, Y', $timestamp);
}

function buildEstimatedDelivery(string $rawValue, string $shippingMethod, string $createdAt): string {
    $formatted = formatEstimatedDelivery($rawValue);
    if ($formatted !== '') return $formatted;

    $method = strtolower(trim($shippingMethod));
    $baseTs = strtotime(trim($createdAt));
    if ($baseTs === false) $baseTs = time();

    $daysToAdd = 4;
    if ($method === 'free' || $method === 'free shipping') {
        $daysToAdd = 6;
    } elseif ($method === 'express' || $method === 'express shipping') {
        $daysToAdd = 1;
    }

    return date('M d, Y', strtotime('+' . $daysToAdd . ' days', $baseTs));
}

function buildTrackingNumber(string $rawTracking, int $orderId, string $clientRef): string {
    $tracking = trim($rawTracking);
    if ($tracking !== '') return $tracking;

    $ref = strtoupper(preg_replace('/[^A-Z0-9]/', '', $clientRef));
    if ($ref !== '') return 'TRK-' . substr($ref, -10);

    return 'TRK-' . str_pad((string)$orderId, 8, '0', STR_PAD_LEFT);
}

function formatPublicOrderId(int $orderId): string {
    return 'LG-' . str_pad((string)$orderId, 6, '0', STR_PAD_LEFT);
}

function firstExistingColumn(array $columns, array $candidates): ?string {
    foreach ($candidates as $candidate) {
        if (isset($columns[strtolower($candidate)])) return $candidate;
    }
    return null;
}

$checkoutColumns = [];
$columnsResult = $conn->query('SHOW COLUMNS FROM checkout');
if ($columnsResult) {
    while ($column = $columnsResult->fetch_assoc()) {
        $field = strtolower((string)($column['Field'] ?? ''));
        if ($field !== '') $checkoutColumns[$field] = true;
    }
}

$paymentColumn = firstExistingColumn($checkoutColumns, ['payment_method', 'mode_of_payment', 'payment_option']);
$shippingMethodColumn = firstExistingColumn($checkoutColumns, ['shipping_method', 'delivery_type', 'shipping_option']);
$courierColumn = firstExistingColumn($checkoutColumns, ['courier_service', 'courier', 'delivery_courier']);
$trackingColumn = firstExistingColumn($checkoutColumns, ['tracking_number', 'tracking_no', 'tracking_id', 'awb_number']);
$estimatedDeliveryColumn = firstExistingColumn($checkoutColumns, ['estimated_delivery', 'estimated_delivery_date', 'eta', 'delivery_eta']);
$deliveryNoteColumn = firstExistingColumn($checkoutColumns, ['delivery_note', 'note', 'special_note']);
$clientOrderRefColumn = firstExistingColumn($checkoutColumns, ['client_order_ref']);

$addressColumn = firstExistingColumn($checkoutColumns, ['address']);
$streetColumn = firstExistingColumn($checkoutColumns, ['street', 'address_line1']);
$barangayColumn = firstExistingColumn($checkoutColumns, ['barangay']);
$cityColumn = firstExistingColumn($checkoutColumns, ['city']);
$provinceColumn = firstExistingColumn($checkoutColumns, ['province']);
$regionColumn = firstExistingColumn($checkoutColumns, ['region']);

$emailColumn = firstExistingColumn($checkoutColumns, ['email']);
$zipColumn = firstExistingColumn($checkoutColumns, ['zip', 'postal_code']);
$shippingFeeColumn = firstExistingColumn($checkoutColumns, ['shipping_fee']);

$addressParts = [];
if ($streetColumn) $addressParts[] = "NULLIF(c.`{$streetColumn}`, '')";
if ($barangayColumn) $addressParts[] = "NULLIF(c.`{$barangayColumn}`, '')";
if ($cityColumn) $addressParts[] = "NULLIF(c.`{$cityColumn}`, '')";
if ($provinceColumn) $addressParts[] = "NULLIF(c.`{$provinceColumn}`, '')";
if ($regionColumn) $addressParts[] = "NULLIF(c.`{$regionColumn}`, '')";

$composedAddressExpr = empty($addressParts)
    ? 'NULL'
    : 'NULLIF(TRIM(CONCAT_WS(\', \', ' . implode(', ', $addressParts) . ')), \'\')';

$shippingAddressExpr = $addressColumn
    ? "COALESCE(NULLIF(TRIM(c.`{$addressColumn}`), ''), {$composedAddressExpr})"
    : $composedAddressExpr;

$paymentExpr = $paymentColumn ? "c.`{$paymentColumn}`" : "''";
$shippingMethodExpr = $shippingMethodColumn ? "c.`{$shippingMethodColumn}`" : "''";
$courierExpr = $courierColumn ? "c.`{$courierColumn}`" : "''";
$trackingExpr = $trackingColumn ? "c.`{$trackingColumn}`" : "''";
$estimatedDeliveryExpr = $estimatedDeliveryColumn ? "c.`{$estimatedDeliveryColumn}`" : "''";
$deliveryNoteExpr = $deliveryNoteColumn ? "c.`{$deliveryNoteColumn}`" : "''";
$emailExpr = $emailColumn ? "c.`{$emailColumn}`" : "''";
$zipExpr = $zipColumn ? "c.`{$zipColumn}`" : "''";
$shippingFeeExpr = $shippingFeeColumn ? "c.`{$shippingFeeColumn}`" : '0';
$cityExpr = $cityColumn ? "c.`{$cityColumn}`" : "''";
$provinceExpr = $provinceColumn ? "c.`{$provinceColumn}`" : "''";
$regionExpr = $regionColumn ? "c.`{$regionColumn}`" : "''";
$streetExpr = $streetColumn ? "c.`{$streetColumn}`" : "''";
$clientOrderRefExpr = $clientOrderRefColumn ? "c.`{$clientOrderRefColumn}`" : "''";


$sql = "
    SELECT
        c.order_id,
        c.created_at,
        c.total_amount,
        c.full_name,
        {$emailExpr} AS email,
        c.phone,
        {$shippingAddressExpr} AS shipping_address,
        {$streetExpr} AS street,
        {$cityExpr} AS city,
        {$provinceExpr} AS province,
        {$regionExpr} AS region,
        {$zipExpr} AS zip,
        {$paymentExpr} AS payment_method,
        {$shippingMethodExpr} AS shipping_method,
        {$courierExpr} AS courier_name,
        {$trackingExpr} AS tracking_number,
        {$estimatedDeliveryExpr} AS estimated_delivery,
        {$deliveryNoteExpr} AS delivery_note,
        {$clientOrderRefExpr} AS client_order_ref,
        c.status,
        {$shippingFeeExpr} AS shipping_fee,
        COALESCE(
            NULLIF(TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))), ''),
            NULLIF(u.username, ''),
            c.full_name,
            {$emailExpr},
            CONCAT('User #', c.user_id)
        ) AS customer_name
    FROM checkout c
    LEFT JOIN users u ON u.user_id = c.user_id
    ORDER BY c.created_at DESC, c.order_id DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare query']);
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $statusLabel = normalizeOrderStatus((string)($row['status'] ?? 'pending'));
    $orderId = (int)$row['order_id'];
    $paymentMethodLabel = normalizePaymentMethod((string)($row['payment_method'] ?? ''));
    $shippingFee = (float)($row['shipping_fee'] ?? 0);
    $shippingMethodLabel = normalizeShippingMethod((string)($row['shipping_method'] ?? ''), $shippingFee);

    $items = [];
    $itemStmt = $conn->prepare(
        "SELECT oi.product_id, oi.quantity, oi.price, COALESCE(p.name, oi.product_id) AS product_name, COALESCE(p.image, '') AS product_image
         FROM order_items oi
         LEFT JOIN products p ON p.product_id = oi.product_id
         WHERE oi.order_id = ?
         ORDER BY oi.id ASC"
    );
    if ($itemStmt) {
        $itemStmt->bind_param('i', $orderId);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();
        while ($item = $itemResult->fetch_assoc()) {
            $items[] = [
                'id' => (string)($item['product_id'] ?? ''),
                'name' => (string)($item['product_name'] ?? 'Item'),
                'qty' => (int)($item['quantity'] ?? 0),
                'price' => (float)($item['price'] ?? 0),
                'image' => !empty($item['product_image']) ? '../uploads/products/' . $item['product_image'] : '/global/jin.jpg'
            ];
        }
        $itemStmt->close();
    }

    $productList = 'No items';
    if (!empty($items)) {
        $parts = [];
        foreach ($items as $item) {
            $parts[] = $item['name'] . ' x' . max(1, (int)$item['qty']);
        }
        $productList = implode(', ', $parts);
    }

    $orders[] = [
        'id' => formatPublicOrderId($orderId),
        'orderId' => $orderId,
        'customerName' => (string)($row['customer_name'] ?? 'Customer'),
        'customerEmail' => (string)($row['email'] ?? ''),
        'customerPhone' => (string)($row['phone'] ?? ''),
        'product' => $productList,
        'items' => $items,
        'status' => $statusLabel,
        'date' => date('M d, Y h:i A', strtotime((string)$row['created_at'])),
        'total' => (float)$row['total_amount'],
        'paymentStatus' => inferPaymentStatus((string)($row['status'] ?? 'pending'), (string)($row['payment_method'] ?? '')),
        'paymentMethod' => $paymentMethodLabel,
        'shippingMethod' => $shippingMethodLabel,
        'shippingAddress' => (string)($row['shipping_address'] ?? ''),
        'addressLine1' => (string)($row['street'] ?? ''),
        'city' => (string)($row['city'] ?? ''),
        'province' => (string)($row['province'] ?? ''),
        'region' => (string)($row['region'] ?? ''),
        'zip' => (string)($row['zip'] ?? ''),
        'shippingFee' => $shippingFee,
        'courierName' => (string)($row['courier_name'] ?? ''),
        'trackingNumber' => buildTrackingNumber((string)($row['tracking_number'] ?? ''), $orderId, (string)($row['client_order_ref'] ?? '')),
        'estimatedDelivery' => buildEstimatedDelivery((string)($row['estimated_delivery'] ?? ''), (string)($row['shipping_method'] ?? ''), (string)($row['created_at'] ?? '')),
        'deliveryNote' => (string)($row['delivery_note'] ?? '')
    ];
}

$stmt->close();
echo json_encode($orders);
?>