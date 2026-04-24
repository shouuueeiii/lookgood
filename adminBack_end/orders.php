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

// ─── Ensure guest_checkout has all columns needed by the admin orders view ───
$conn->query("
    CREATE TABLE IF NOT EXISTS guest_checkout (
        id               INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
        guest_id         VARCHAR(64)   NOT NULL,
        client_order_ref VARCHAR(80)   NULL,
        total_amount     DECIMAL(10,2) NOT NULL DEFAULT 0,
        full_name        VARCHAR(100)  NULL,
        email            VARCHAR(100)  NULL,
        phone            VARCHAR(20)   NULL,
        address          TEXT          NULL,
        zip              VARCHAR(10)   NULL,
        payment_method   VARCHAR(50)   NULL,
        shipping_fee     DECIMAL(10,2) DEFAULT 0,
        status           ENUM('pending','processing','shipped','out_for_delivery','delivered','completed','cancelled','returned') NOT NULL DEFAULT 'pending',
        created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_guest_id (guest_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
foreach ([
    'payment_status'     => "ALTER TABLE guest_checkout ADD COLUMN payment_status     VARCHAR(20)  NULL",
    'shipping_method'    => "ALTER TABLE guest_checkout ADD COLUMN shipping_method    VARCHAR(80)  NULL",
    'tracking_number'    => "ALTER TABLE guest_checkout ADD COLUMN tracking_number    VARCHAR(100) NULL",
    'estimated_delivery' => "ALTER TABLE guest_checkout ADD COLUMN estimated_delivery VARCHAR(80)  NULL",
    'payment_confirmed_at' => "ALTER TABLE guest_checkout ADD COLUMN payment_confirmed_at DATETIME NULL",
    'guest_order_id'     => "ALTER TABLE guest_checkout ADD COLUMN guest_order_id     VARCHAR(80)  NULL",
] as $_col => $_alterSql) {
    $colExists = $conn->query("SHOW COLUMNS FROM guest_checkout LIKE '$_col'");
    if ($colExists && $colExists->num_rows === 0) {
        $conn->query($_alterSql);
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?: [];
    $orderId      = (int)($payload['id'] ?? 0);
    $newStatusRaw = strtolower(trim((string)($payload['status'] ?? '')));
    $source       = (string)($payload['source'] ?? 'user'); // 'user' | 'guest'

    $allowed = [
        'pending', 'processing', 'shipped', 'out_for_delivery',
        'delivered', 'completed', 'cancelled', 'returned', 'refunded'
    ];

    if ($orderId <= 0 || !in_array($newStatusRaw, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid payload']);
        exit();
    }

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

    $shippingDeliveryMap = [
        'standard shipping' => '3–5 business days',
        'free shipping'     => '5–7 business days',
        'express shipping'  => '1–2 business days',
    ];

    if ($source === 'guest') {
        // ── Read existing shipping_method from guest_checkout ─────────────
        $estDelivery = null;
        $smStmt = $conn->prepare('SELECT shipping_method FROM guest_checkout WHERE id = ?');
        if ($smStmt) {
            $smStmt->bind_param('i', $orderId);
            $smStmt->execute();
            $smStmt->bind_result($existingShippingMethod);
            $smStmt->fetch();
            $smStmt->close();
            $smKey = strtolower(trim((string)($existingShippingMethod ?? '')));
            $estDelivery = $shippingDeliveryMap[$smKey] ?? null;
        }

        $update = $conn->prepare(
            'UPDATE guest_checkout SET status = ?, payment_status = ?, estimated_delivery = ? WHERE id = ?'
        );
        if (!$update) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to prepare update']);
            exit();
        }
        $update->bind_param('sssi', $newStatusRaw, $newPaymentStatus, $estDelivery, $orderId);
        $ok = $update->execute();
        $update->close();

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to update guest order']);
            exit();
        }

        pushAdminNotification(
            $conn, 'status', 'Guest Order Status Updated',
            'Guest Order #' . $orderId . ' status changed to ' . strtoupper($newStatusRaw) . '.',
            ['orderId' => (string)$orderId, 'status' => $newStatusRaw, 'source' => 'guest'],
            'guest:status:' . $orderId . ':' . $newStatusRaw
        );

        echo json_encode(['success' => true, 'paymentStatus' => $newPaymentStatus]);
        exit();
    }

    // ─── Registered user order ────────────────────────────────────────────
    // ─── Derive estimated_delivery from shipping_method ───────────────────
    $estDelivery = null;
    $smStmt = $conn->prepare('SELECT shipping_method FROM checkout WHERE order_id = ?');
    if ($smStmt) {
        $smStmt->bind_param('i', $orderId);
        $smStmt->execute();
        $smStmt->bind_result($existingShippingMethod);
        $smStmt->fetch();
        $smStmt->close();

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

// ─── GET: fetch all orders (registered users + guests) ───────────────────────

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

// ── Unified query: registered users UNION guests ──────────────────────────────
$sql = "
    SELECT
        c.order_id          AS order_id,
        c.created_at        AS created_at,
        c.total_amount      AS total_amount,
        c.full_name         AS full_name,
        c.email             AS email,
        c.phone             AS phone,
        c.address           AS address,
        c.zip               AS zip,
        c.payment_method    AS payment_method,
        c.payment_status    AS payment_status,
        c.payment_confirmed_at AS payment_confirmed_at,
        c.status            AS status,
        c.shipping_fee      AS shipping_fee,
        c.shipping_method   AS shipping_method,
        c.tracking_number   AS tracking_number,
        c.estimated_delivery AS estimated_delivery,
        COALESCE(
            NULLIF(TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))), ''),
            NULLIF(u.username, ''),
            c.full_name,
            c.email,
            CONCAT('User #', c.user_id)
        ) AS customer_name,
        'user'              AS source,
        NULL                AS guest_order_ref,
        GROUP_CONCAT(
            CONCAT(COALESCE(p.name, oi.product_id), ' x', COALESCE(oi.quantity, 1))
            ORDER BY oi.id SEPARATOR ', '
        ) AS product_list
    FROM checkout c
    LEFT JOIN users u  ON u.user_id = c.user_id
    LEFT JOIN order_items oi ON oi.order_id = c.order_id
    LEFT JOIN products p ON p.product_id = oi.product_id
    GROUP BY
        c.order_id, c.created_at, c.total_amount, c.full_name,
        c.email, c.phone, c.address, c.zip,
        c.payment_method, c.payment_status, c.status, c.shipping_fee,
        c.shipping_method, c.tracking_number, c.estimated_delivery,
        c.payment_confirmed_at, customer_name

    UNION ALL

    SELECT
        gc.id               AS order_id,
        gc.created_at       AS created_at,
        gc.total_amount     AS total_amount,
        gc.full_name        AS full_name,
        gc.email            AS email,
        gc.phone            AS phone,
        gc.address          AS address,
        gc.zip              AS zip,
        gc.payment_method   AS payment_method,
        COALESCE(gc.payment_status, 'Pending') AS payment_status,
        gc.payment_confirmed_at AS payment_confirmed_at,
        gc.status           AS status,
        gc.shipping_fee     AS shipping_fee,
        COALESCE(gc.shipping_method, '') AS shipping_method,
        COALESCE(gc.tracking_number, '') AS tracking_number,
        COALESCE(gc.estimated_delivery, '') AS estimated_delivery,
        COALESCE(NULLIF(TRIM(gc.full_name), ''), gc.email, CONCAT('Guest #', gc.id)) AS customer_name,
        'guest'             AS source,
        COALESCE(gc.guest_order_id, gc.client_order_ref, CONCAT('GUEST-', gc.id)) AS guest_order_ref,
        NULL                AS product_list
    FROM guest_checkout gc

    ORDER BY created_at DESC, order_id DESC
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
    $source      = (string)($row['source'] ?? 'user');
    $statusLabel = normalizeOrderStatus((string)($row['status'] ?? 'pending'));

    // ── payment_status ─────────────────────────────────────────────────────
    $rawPayStatus = trim((string)($row['payment_status'] ?? ''));
    if ($rawPayStatus === '') {
        $s = strtolower($row['status'] ?? 'pending');
        $rawPayStatus = in_array($s, ['shipped','out_for_delivery','delivered','completed']) ? 'Paid' : 'Pending';
    }
    $paymentStatus = ucfirst(strtolower($rawPayStatus));

    // ── shipping method ─────────────────────────────────────────────────────
    $rawShipping   = trim((string)($row['shipping_method'] ?? ''));
    $shippingLabel = normalizeShippingMethod($rawShipping);

    // ── estimated delivery ──────────────────────────────────────────────────
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

    // ── Items: registered orders use order_items; guests use guest_order_items ──
    $items = [];
    if ($source === 'user') {
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
    } else {
        // guest_order_items.order_id = guest_checkout.id
        $gItemStmt = $conn->prepare(
            "SELECT goi.product_id, goi.quantity, goi.price,
                    COALESCE(p.name, goi.product_id) AS product_name,
                    COALESCE(p.image, '')             AS product_image
             FROM guest_order_items goi
             LEFT JOIN products p ON p.product_id = goi.product_id
             WHERE goi.order_id = ?
             ORDER BY goi.id ASC"
        );
        if ($gItemStmt) {
            $gItemStmt->bind_param('i', $orderId);
            $gItemStmt->execute();
            $gItemResult = $gItemStmt->get_result();
            while ($item = $gItemResult->fetch_assoc()) {
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
            $gItemStmt->close();
        }
    }

    // ── Customer name label (append Guest badge) ───────────────────────────
    $customerName = (string)($row['customer_name'] ?? 'Customer');
    $isGuest      = $source === 'guest';

    // ── Build the order ID string ───────────────────────────────────────────
    // Guest orders: use the actual guest_order_id from DB (e.g. GUEST-20260421-CDE7A)
    // Registered orders: use standard LG-XXXXXX format
    $orderIdStr = $isGuest
        ? (string)($row['guest_order_ref'] ?? ('GUEST-' . $orderId))
        : 'LG-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);

    $orders[] = [
        'id'                 => $isGuest ? 'g' . $orderId : (string)$orderId,
        'orderId'            => $orderId,
        'order_id'           => $orderIdStr,
        'source'             => $source,
        'isGuest'            => $isGuest,
        'customerName'       => $customerName . ($isGuest ? ' (Guest)' : ''),
        'customerEmail'      => (string)($row['email'] ?? ''),
        'customerPhone'      => (string)($row['phone'] ?? ''),
        'product'            => (string)($row['product_list'] ?: ($items ? implode(', ', array_map(fn($i) => $i['name'] . ' x' . $i['qty'], $items)) : 'No items')),
        'items'              => $items,
        'status'             => $statusLabel,
        'date'               => date('M d, Y h:i A', strtotime((string)$row['created_at'])),
        'total'              => (float)$row['total_amount'],
        'paymentStatus'      => $paymentStatus,
        'paymentMethod'      => (string)($row['payment_method'] ?: 'GCash'),
        'shippingMethod'     => $shippingLabel,
        'estimatedDelivery'  => $estDelivery,
        'trackingNumber'     => (string)($row['tracking_number'] ?? ''),
        'paymentConfirmedAt' => !empty($row['payment_confirmed_at'])
                                ? date('M d, Y h:i A', strtotime((string)$row['payment_confirmed_at']))
                                : '',
        'shippingAddress'    => (string)($row['address'] ?? ''),
        'zip'                => (string)($row['zip'] ?? ''),
        'shippingFee'        => (float)($row['shipping_fee'] ?? 0),
    ];
}

$stmt->close();
echo json_encode($orders);
?>