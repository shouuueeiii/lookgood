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

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?: [];
    $orderId     = (int)($payload['id'] ?? 0);
    $newStatusRaw = strtolower(trim((string)($payload['status'] ?? '')));

    $allowed = [
        'pending',
        'processing',
        'shipped',
        'out_for_delivery',
        'delivered',
        'completed',
        'cancelled',
        'returned',
        'refunded'
    ];

    if ($orderId <= 0 || !in_array($newStatusRaw, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid payload']);
        exit();
    }

    // ─── Derive payment_status from the new order status ──────────────────
    // Only mark Paid when order is actually progressing (Shipped/Delivered).
    // Refunded gets its own status. Cancelled/Returned = Failed.
    $paymentStatusMap = [
        'pending'          => 'Pending',
        'processing'       => 'Pending',
        'shipped'          => 'Paid',
        'out_for_delivery' => 'Paid',
        'delivered'        => 'Paid',
        'completed'        => 'Paid',
        'cancelled'        => 'Failed',
        'returned'         => 'Failed',
        'refunded'         => 'Refunded',
    ];
    $newPaymentStatus = $paymentStatusMap[$newStatusRaw] ?? 'Pending';

    // ─── Derive estimated_delivery from shipping_method ───────────────────
    // We read the existing shipping_method from DB so we don't overwrite it.
    $estDelivery = null;
    $smStmt = $conn->prepare('SELECT shipping_method FROM checkout WHERE order_id = ?');
    if ($smStmt) {
        $smStmt->bind_param('i', $orderId);
        $smStmt->execute();
        $smStmt->bind_result($existingShippingMethod);
        $smStmt->fetch();
        $smStmt->close();

        $shippingDeliveryMap = [
            'standard shipping' => '3–5 business days',
            'free shipping'     => '5–7 business days',
            'express shipping'  => '1–2 business days',
        ];
        $smKey = strtolower(trim((string)($existingShippingMethod ?? '')));
        $estDelivery = $shippingDeliveryMap[$smKey] ?? null;
    }

    // ─── Update order status + payment_status + estimated_delivery ────────
    $update = $conn->prepare(
        'UPDATE checkout SET status = ?, payment_status = ?, estimated_delivery = ? WHERE order_id = ?'
    );
    if (!$update) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to prepare update']);
        exit();
    }

    $update->bind_param('sssi', $newStatusRaw, $newPaymentStatus, $estDelivery, $orderId);
    $ok = $update->execute();
    $affected = $update->affected_rows;
    $update->close();

    if (!$ok || $affected < 0) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update order']);
        exit();
    }

    // ─── Notifications ────────────────────────────────────────────────────
    pushAdminNotification(
        $conn, 'status', 'Order Status Updated',
        'Order #' . $orderId . ' status changed to ' . strtoupper($newStatusRaw) . '.',
        ['orderId' => (string)$orderId, 'status' => $newStatusRaw],
        'order:status:' . $orderId . ':' . $newStatusRaw
    );

    if ($newStatusRaw === 'cancelled') {
        pushAdminNotification($conn, 'cancel', 'Order Cancelled',
            'Order #' . $orderId . ' was cancelled.',
            ['orderId' => (string)$orderId], 'order:cancel:' . $orderId);
    }
    if ($newStatusRaw === 'returned') {
        pushAdminNotification($conn, 'return', 'Order Returned',
            'Order #' . $orderId . ' was returned.',
            ['orderId' => (string)$orderId], 'order:return:' . $orderId);
    }
    if ($newStatusRaw === 'refunded') {
        pushAdminNotification($conn, 'refund', 'Order Refunded',
            'Order #' . $orderId . ' was refunded.',
            ['orderId' => (string)$orderId], 'order:refund:' . $orderId);
    }
    pushAdminNotification(
        $conn, 'payment', 'Payment Updated',
        'Payment for Order #' . $orderId . ' is now ' . strtoupper($newPaymentStatus) . '.',
        ['orderId' => (string)$orderId, 'paymentStatus' => $newPaymentStatus],
        'payment:' . $orderId . ':' . $newStatusRaw
    );

    echo json_encode(['success' => true, 'paymentStatus' => $newPaymentStatus]);
    exit();
}

// ─── GET: fetch all orders ────────────────────────────────────────────────────

function normalizeOrderStatus(string $status): string {
    $s = strtolower(trim($status));
    $map = [
        'pending'          => 'Pending',
        'processing'       => 'Processing',
        'shipped'          => 'Shipped',
        'out_for_delivery' => 'Shipped',
        'delivered'        => 'Delivered',
        'completed'        => 'Delivered',
        'cancelled'        => 'Cancelled',
        'returned'         => 'Cancelled',
        'refunded'         => 'Cancelled',
    ];
    return $map[$s] ?? 'Pending';
}

function normalizeShippingMethod(string $raw): string {
    $s = strtolower(trim($raw));
    $map = [
        'standard shipping' => 'Standard Shipping (3–5 days)',
        'free shipping'     => 'Free Shipping (5–7 days)',
        'express shipping'  => 'Express Shipping (1–2 days)',
    ];
    return $map[$s] ?? ($raw ?: 'Standard Shipping (3–5 days)');
}

// ── Main query — now includes payment_status, shipping_method,
//                tracking_number, estimated_delivery ──────────────────────────
$sql = "
    SELECT
        c.order_id,
        c.created_at,
        c.total_amount,
        c.full_name,
        c.email,
        c.phone,
        c.address,
        c.zip,
        c.payment_method,
        c.payment_status,
        c.payment_confirmed_at,
        c.status,
        c.shipping_fee,
        c.shipping_method,
        c.tracking_number,
        c.estimated_delivery,
        COALESCE(
            NULLIF(TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))), ''),
            NULLIF(u.username, ''),
            c.full_name,
            c.email,
            CONCAT('User #', c.user_id)
        ) AS customer_name,
        GROUP_CONCAT(
            CONCAT(COALESCE(p.name, oi.product_id), ' x', COALESCE(oi.quantity, 1))
            ORDER BY oi.id SEPARATOR ', '
        ) AS product_list
    FROM checkout c
    LEFT JOIN users u ON u.user_id = c.user_id
    LEFT JOIN order_items oi ON oi.order_id = c.order_id
    LEFT JOIN products p ON p.product_id = oi.product_id
    GROUP BY
        c.order_id, c.created_at, c.total_amount, c.full_name,
        c.email, c.phone, c.address, c.zip,
        c.payment_method, c.payment_status, c.status, c.shipping_fee,
        c.shipping_method, c.tracking_number, c.estimated_delivery,
        c.payment_confirmed_at,
        customer_name
    ORDER BY c.created_at DESC, c.order_id DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare query: ' . $conn->error]);
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orderId     = (int)$row['order_id'];
    $statusLabel = normalizeOrderStatus((string)($row['status'] ?? 'pending'));

    // ── payment_status: use stored value; fall back to inferring only if NULL/empty
    $rawPayStatus = trim((string)($row['payment_status'] ?? ''));
    if ($rawPayStatus === '') {
        // Legacy rows that existed before migration — infer from order status
        $s = strtolower($row['status'] ?? 'pending');
        $rawPayStatus = in_array($s, ['shipped','out_for_delivery','delivered','completed']) ? 'Paid' : 'Pending';
    }
    // Normalise capitalisation
    $paymentStatus = ucfirst(strtolower($rawPayStatus));

    // ── shipping method: use stored value from checkout
    $rawShipping   = trim((string)($row['shipping_method'] ?? ''));
    $shippingLabel = normalizeShippingMethod($rawShipping);

    // ── estimated delivery: use stored value, derive from method if missing
    $estDelivery = trim((string)($row['estimated_delivery'] ?? ''));
    if ($estDelivery === '') {
        $smKey = strtolower($rawShipping);
        $estMap = [
            'standard shipping' => '3–5 business days',
            'free shipping'     => '5–7 business days',
            'express shipping'  => '1–2 business days',
        ];
        $estDelivery = $estMap[$smKey] ?? '3–5 business days';
    }

    // ── Items sub-query ───────────────────────────────────────────────────
    $items = [];
    $itemStmt = $conn->prepare(
        "SELECT oi.product_id, oi.quantity, oi.price,
                COALESCE(p.name, oi.product_id) AS product_name,
                COALESCE(p.image, '')            AS product_image
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
                'id'    => (string)($item['product_id'] ?? ''),
                'name'  => (string)($item['product_name'] ?? 'Item'),
                'qty'   => (int)($item['quantity'] ?? 0),
                'price' => (float)($item['price'] ?? 0),
                'image' => !empty($item['product_image'])
                            ? '../uploads/products/' . $item['product_image']
                            : '/global/jin.jpg'
            ];
        }
        $itemStmt->close();
    }

    $orders[] = [
        'id'               => (string)$orderId,
        'orderId'          => $orderId,
        'order_id'         => 'LG-' . str_pad($orderId, 6, '0', STR_PAD_LEFT),
        'customerName'     => (string)($row['customer_name'] ?? 'Customer'),
        'customerEmail'    => (string)($row['email'] ?? ''),
        'customerPhone'    => (string)($row['phone'] ?? ''),
        'product'          => (string)($row['product_list'] ?: 'No items'),
        'items'            => $items,
        'status'           => $statusLabel,
        'date'             => date('M d, Y h:i A', strtotime((string)$row['created_at'])),
        'total'            => (float)$row['total_amount'],
      
        'paymentStatus'    => $paymentStatus,
        'paymentMethod'    => (string)($row['payment_method'] ?: 'GCash'),
        'shippingMethod'   => $shippingLabel,
        'estimatedDelivery'=> $estDelivery,
        'trackingNumber'       => (string)($row['tracking_number'] ?? ''),
        'paymentConfirmedAt'   => !empty($row['payment_confirmed_at'])
                                    ? date('M d, Y h:i A', strtotime((string)$row['payment_confirmed_at']))
                                    : '',
        'shippingAddress'  => (string)($row['address'] ?? ''),
        'zip'              => (string)($row['zip'] ?? ''),
        'shippingFee'      => (float)($row['shipping_fee'] ?? 0),
    ];
}

$stmt->close();
echo json_encode($orders);
?>