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
    $orderId = (int)($payload['id'] ?? 0);
    $newStatusRaw = strtolower(trim((string)($payload['status'] ?? '')));
    $orderSource  = strtolower(trim((string)($payload['orderSource'] ?? 'regular')));

    $allowed = [
        'pending', 'processing', 'shipped', 'out_for_delivery',
        'delivered', 'completed', 'cancelled', 'returned'
    ];

    if ($orderId <= 0 || !in_array($newStatusRaw, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid payload']);
        exit();
    }

    $table = ($orderSource === 'guest') ? 'guest_checkout' : 'checkout';
    $idCol  = ($orderSource === 'guest') ? 'id'             : 'order_id';

    $update = $conn->prepare("UPDATE {$table} SET status = ? WHERE {$idCol} = ?");
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

    $prefix = ($orderSource === 'guest') ? 'guest_order' : 'order';

    pushAdminNotification(
        $conn, 'status',
        'Order Status Updated',
        'Order #' . $orderId . ' status changed to ' . strtoupper($newStatusRaw) . '.',
        ['orderId' => (string)$orderId, 'status' => $newStatusRaw, 'source' => $orderSource],
        $prefix . ':status:' . $orderId . ':' . $newStatusRaw
    );

    if ($newStatusRaw === 'cancelled') {
        pushAdminNotification($conn, 'cancel', 'Order Cancelled',
            'Order #' . $orderId . ' was cancelled.',
            ['orderId' => (string)$orderId],
            $prefix . ':cancel:' . $orderId
        );
    }

    if ($newStatusRaw === 'returned') {
        pushAdminNotification($conn, 'return', 'Order Returned',
            'Order #' . $orderId . ' was returned.',
            ['orderId' => (string)$orderId],
            $prefix . ':return:' . $orderId
        );
    }

    pushAdminNotification($conn, 'payment', 'Payment Updated',
        'Payment state for Order #' . $orderId . ' updated with order status ' . strtoupper($newStatusRaw) . '.',
        ['orderId' => (string)$orderId, 'status' => $newStatusRaw],
        'payment:' . $prefix . ':' . $orderId . ':' . $newStatusRaw
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

function inferPaymentStatus(string $status): string {
    $s = strtolower(trim($status));
    if ($s === 'pending' || $s === 'processing') return 'Pending';
    if ($s === 'shipped' || $s === 'out_for_delivery' || $s === 'delivered' || $s === 'completed') return 'Paid';
    return 'Pending';
}

$sql = "
    SELECT
        c.order_id,
        CONCAT('#', LPAD(c.order_id, 5, '0')) AS display_order_id,
        c.created_at,
        c.total_amount,
        c.full_name,
        c.email,
        c.phone,
        c.address,
        c.zip,
        c.payment_method,
        c.status,
        c.shipping_fee,
        'regular' AS order_source,
        COALESCE(
            NULLIF(TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))), ''),
            NULLIF(u.username, ''),
            c.full_name,
            c.email,
            CONCAT('User #', c.user_id)
        ) AS customer_name,
        GROUP_CONCAT(CONCAT(COALESCE(p.name, oi.product_id), ' x', COALESCE(oi.quantity, 1)) ORDER BY oi.id SEPARATOR ', ') AS product_list
    FROM checkout c
    LEFT JOIN users u ON u.user_id = c.user_id
    LEFT JOIN order_items oi ON oi.order_id = c.order_id
    LEFT JOIN products p ON p.product_id = oi.product_id
    GROUP BY
        c.order_id, display_order_id, c.created_at, c.total_amount,
        c.full_name, c.email, c.phone, c.address, c.zip,
        c.payment_method, c.status, c.shipping_fee, order_source, customer_name

    UNION ALL

    SELECT
        gc.id AS order_id,
        COALESCE(gc.guest_order_id, CONCAT('GUEST-', LPAD(gc.id, 5, '0'))) AS display_order_id,
        gc.created_at,
        gc.total_amount,
        gc.full_name,
        gc.email,
        gc.phone,
        gc.address,
        gc.zip,
        gc.payment_method,
        gc.status,
        gc.shipping_fee,
        'guest' AS order_source,
        COALESCE(gc.full_name, gc.email, CONCAT('Guest #', gc.id)) AS customer_name,
        GROUP_CONCAT(CONCAT(COALESCE(p.name, goi.product_id), ' x', COALESCE(goi.quantity, 1)) ORDER BY goi.id SEPARATOR ', ') AS product_list
    FROM guest_checkout gc
    LEFT JOIN guest_order_items goi ON goi.order_id = gc.id
    LEFT JOIN products p ON p.product_id = goi.product_id
    GROUP BY
        gc.id, display_order_id, gc.created_at, gc.total_amount,
        gc.full_name, gc.email, gc.phone, gc.address, gc.zip,
        gc.payment_method, gc.status, gc.shipping_fee, order_source, customer_name

    ORDER BY created_at DESC, order_id DESC
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
    $orderId       = (int)$row['order_id'];
    $displayId     = (string)($row['display_order_id'] ?? ('#' . str_pad($orderId, 5, '0', STR_PAD_LEFT)));
    $orderSource   = (string)($row['order_source'] ?? 'regular');
    $isGuest       = ($orderSource === 'guest');

    $items = [];

    if ($isGuest) {
        $itemStmt = $conn->prepare(
            "SELECT goi.product_id, goi.quantity, goi.price,
                    COALESCE(p.name, goi.product_id) AS product_name,
                    COALESCE(p.image, '') AS product_image
             FROM guest_order_items goi
             LEFT JOIN products p ON p.product_id = goi.product_id
             WHERE goi.order_id = ?
             ORDER BY goi.id ASC"
        );
    } else {
        $itemStmt = $conn->prepare(
            "SELECT oi.product_id, oi.quantity, oi.price,
                    COALESCE(p.name, oi.product_id) AS product_name,
                    COALESCE(p.image, '') AS product_image
             FROM order_items oi
             LEFT JOIN products p ON p.product_id = oi.product_id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC"
        );
    }

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
                'image' => !empty($item['product_image']) ? '../uploads/products/' . $item['product_image'] : '/global/jin.jpg'
            ];
        }
        $itemStmt->close();
    }

    $orders[] = [
        'id'              => $displayId,
        'orderId'         => $orderId,
        'orderSource'     => $orderSource,
        'isGuest'         => $isGuest,
        'customerName'    => (string)($row['customer_name'] ?? 'Customer'),
        'customerEmail'   => (string)($row['email'] ?? ''),
        'customerPhone'   => (string)($row['phone'] ?? ''),
        'product'         => (string)($row['product_list'] ?: 'No items'),
        'items'           => $items,
        'status'          => $statusLabel,
        'date'            => date('M d, Y h:i A', strtotime((string)$row['created_at'])),
        'total'           => (float)$row['total_amount'],
        'paymentStatus'   => inferPaymentStatus((string)($row['status'] ?? 'pending')),
        'paymentMethod'   => (string)($row['payment_method'] ?: 'Gcash'),
        'shippingMethod'  => 'Standard Delivery',
        'shippingAddress' => (string)($row['address'] ?? ''),
        'zip'             => (string)($row['zip'] ?? ''),
        'shippingFee'     => (float)($row['shipping_fee'] ?? 0)
    ];
}

$stmt->close();
echo json_encode($orders);
?>