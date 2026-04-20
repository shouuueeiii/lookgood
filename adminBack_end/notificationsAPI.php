<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_admin.php';
requireAdmin();

header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);

function hasColumn(mysqli $conn, string $table, string $column): bool {
    $stmt = $conn->prepare(
        "SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1"
    );
    if (!$stmt) return false;
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function ensureNotificationsSchema(mysqli $conn): void {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS admin_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(30) NOT NULL DEFAULT 'status',
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            data_json TEXT NULL,
            source_key VARCHAR(191) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_admin_notifications_source (source_key)
        )"
    );

    if (!hasColumn($conn, 'admin_notifications', 'source_key')) {
        $conn->query("ALTER TABLE admin_notifications ADD COLUMN source_key VARCHAR(191) NULL AFTER data_json");
    }
    $conn->query("ALTER TABLE admin_notifications ADD UNIQUE INDEX uq_admin_notifications_source (source_key)");
}

function insertNotification(
    mysqli $conn,
    string $type,
    string $title,
    string $message,
    array $data = [],
    ?string $sourceKey = null,
    ?string $createdAt = null
): bool {
    if (trim($message) === '') return false;

    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) $json = '{}';

    $createdAt = $createdAt ?: date('Y-m-d H:i:s');
    $sourceKey = ($sourceKey !== null && trim($sourceKey) !== '') ? trim($sourceKey) : null;

    if ($sourceKey !== null) {
        $stmt = $conn->prepare(
            'INSERT INTO admin_notifications (type, title, message, data_json, source_key, is_read, created_at)
             VALUES (?, ?, ?, ?, ?, 0, ?)
             ON DUPLICATE KEY UPDATE id = id'
        );
        if (!$stmt) return false;
        $stmt->bind_param('ssssss', $type, $title, $message, $json, $sourceKey, $createdAt);
        $ok = $stmt->execute();
        $stmt->close();
        return (bool)$ok;
    }

    $stmt = $conn->prepare(
        'INSERT INTO admin_notifications (type, title, message, data_json, is_read, created_at)
         VALUES (?, ?, ?, ?, 0, ?)'
    );
    if (!$stmt) return false;
    $stmt->bind_param('sssss', $type, $title, $message, $json, $createdAt);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool)$ok;
}

function syncFromExistingData(mysqli $conn): void {
    $feedbackCommentColumn = hasColumn($conn, 'feedback', 'feedback_comment') ? 'feedback_comment' : (hasColumn($conn, 'feedback', 'comment') ? 'comment' : null);
    $usersPk = hasColumn($conn, 'users', 'user_id') ? 'user_id' : 'id';

    // Orders, payment, cancel/return, status changes
    $orders = $conn->query(
        "SELECT c.order_id, c.created_at, c.total_amount, c.payment_method, c.status,
                COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))), ''), c.full_name, c.email, CONCAT('User #', c.user_id)) AS customer_name
         FROM checkout c
         LEFT JOIN users u ON u.user_id = c.user_id
         ORDER BY c.created_at DESC
         LIMIT 1000"
    );
    if ($orders) {
        while ($o = $orders->fetch_assoc()) {
            $orderId = (int)$o['order_id'];
            $status = strtolower(trim((string)($o['status'] ?? 'pending')));
            $paymentMethod = trim((string)($o['payment_method'] ?? '')); 
            $customer = (string)($o['customer_name'] ?? 'Customer');
            $amountText = number_format((float)($o['total_amount'] ?? 0), 2);
            $createdAt = (string)($o['created_at'] ?? date('Y-m-d H:i:s'));

            insertNotification(
                $conn,
                'order',
                'New Order Placed',
                'Order #' . $orderId . ' placed by ' . $customer . ' amounting to P' . $amountText . '.',
                ['orderId' => (string)$orderId, 'status' => $status],
                'order:new:' . $orderId,
                $createdAt
            );

            if ($paymentMethod !== '') {
                insertNotification(
                    $conn,
                    'payment',
                    'Payment Updated',
                    'Order #' . $orderId . ' payment via ' . $paymentMethod . ' is now ' . strtoupper($status) . '.',
                    ['orderId' => (string)$orderId, 'paymentMethod' => $paymentMethod, 'status' => $status],
                    'payment:' . $orderId . ':' . $status,
                    $createdAt
                );
            }

            if ($status === 'cancelled') {
                insertNotification(
                    $conn,
                    'cancel',
                    'Order Cancelled',
                    'Order #' . $orderId . ' was cancelled.',
                    ['orderId' => (string)$orderId],
                    'order:cancel:' . $orderId,
                    $createdAt
                );
            }

            if ($status === 'returned') {
                insertNotification(
                    $conn,
                    'return',
                    'Order Returned',
                    'Order #' . $orderId . ' was returned.',
                    ['orderId' => (string)$orderId],
                    'order:return:' . $orderId,
                    $createdAt
                );
            }

            if ($status !== '' && $status !== 'pending') {
                insertNotification(
                    $conn,
                    'status',
                    'Order Status Updated',
                    'Order #' . $orderId . ' status changed to ' . strtoupper($status) . '.',
                    ['orderId' => (string)$orderId, 'status' => $status],
                    'order:status:' . $orderId . ':' . $status,
                    $createdAt
                );
            }
        }
    }

    // User messages
    $messages = $conn->query(
        "SELECT m.id, m.sender_id, m.message, m.created_at,
                COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))), ''), u.username, CONCAT('User #', m.sender_id)) AS sender_name
         FROM messages m
         LEFT JOIN users u ON u.user_id = m.sender_id
         WHERE COALESCE(m.sender_type, 'user') = 'user'
         ORDER BY m.created_at DESC
         LIMIT 1000"
    );
    if ($messages) {
        while ($m = $messages->fetch_assoc()) {
            $msgId = (int)$m['id'];
            $sender = (string)($m['sender_name'] ?? 'User');
            $text = trim((string)($m['message'] ?? ''));
            $preview = substr($text, 0, 120);
            $createdAt = (string)($m['created_at'] ?? date('Y-m-d H:i:s'));
            $isReturn = preg_match('/\b(return|refund)\b/i', $text) === 1;

            insertNotification(
                $conn,
                $isReturn ? 'return' : 'message',
                $isReturn ? 'Order Return Message' : 'New User Message',
                $sender . ': ' . $preview,
                ['messageId' => (string)$msgId, 'senderId' => (string)$m['sender_id']],
                'message:' . $msgId,
                $createdAt
            );
        }
    }

    // Feedback submissions
    if ($feedbackCommentColumn !== null) {
        $feedback = $conn->query(
        "SELECT f.id, f.product_id, f.rating, f.$feedbackCommentColumn AS feedback_comment, f.created_at,
                COALESCE(p.name, f.product_id) AS product_name,
                COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))), ''), u.username, CONCAT('User #', f.user_id)) AS customer_name
         FROM feedback f
         LEFT JOIN users u ON u.$usersPk = f.user_id
         LEFT JOIN products p ON f.product_id COLLATE utf8mb4_general_ci = p.product_id
         ORDER BY f.created_at DESC
         LIMIT 1000"
        );
        if ($feedback) {
            while ($f = $feedback->fetch_assoc()) {
                $fid = (int)$f['id'];
                $createdAt = (string)($f['created_at'] ?? date('Y-m-d H:i:s'));
                $comment = trim((string)($f['feedback_comment'] ?? ''));
                insertNotification(
                    $conn,
                    'feedback',
                    'New Feedback Submitted',
                    (string)$f['customer_name'] . ' rated ' . (string)$f['product_name'] . ' (' . (int)$f['rating'] . '/5).' . ($comment !== '' ? (' Comment: "' . substr($comment, 0, 120) . '"') : ''),
                    [
                        'feedbackId' => (string)$fid,
                        'productId' => (string)$f['product_id'],
                        'rating' => (int)$f['rating'],
                        'comment' => $comment,
                        'userName' => (string)$f['customer_name'],
                        'date' => $createdAt
                    ],
                    'feedback:' . $fid,
                    $createdAt
                );
            }
        }
    }

    // Product events + stock alerts
    $products = $conn->query(
        "SELECT product_id, name, stock, created_at
         FROM products
         ORDER BY created_at DESC
         LIMIT 1000"
    );
    if ($products) {
        while ($p = $products->fetch_assoc()) {
            $pid = (string)$p['product_id'];
            $name = (string)$p['name'];
            $stock = (int)($p['stock'] ?? 0);
            $createdAt = (string)($p['created_at'] ?? date('Y-m-d H:i:s'));

            insertNotification(
                $conn,
                'product',
                'Product Added',
                $name . ' (' . $pid . ') is available in catalog.',
                ['productId' => $pid],
                'product:add:' . $pid,
                $createdAt
            );

            if ($stock <= 0) {
                insertNotification(
                    $conn,
                    'stock',
                    'Out of Stock',
                    $name . ' (' . $pid . ') is out of stock.',
                    ['productId' => $pid, 'stock' => $stock],
                    'product:out:' . $pid,
                    date('Y-m-d H:i:s')
                );
            } elseif ($stock < 10) {
                insertNotification(
                    $conn,
                    'stock',
                    'Low Stock Alert',
                    $name . ' (' . $pid . ') is low on stock: ' . $stock . ' remaining.',
                    ['productId' => $pid, 'stock' => $stock],
                    'product:low:' . $pid,
                    date('Y-m-d H:i:s')
                );
            }
        }
    }
}

ensureNotificationsSchema($conn);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 100;
    $onlyUnread = isset($_GET['unread']) && $_GET['unread'] === '1';
    syncFromExistingData($conn);

    $sql = 'SELECT id, type, title, message, data_json, source_key, is_read, created_at FROM admin_notifications';
    if ($onlyUnread) {
        $sql .= ' WHERE is_read = 0';
    }
    $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . $limit;

    $result = $conn->query($sql);
    $items = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $decoded = [];
            if (!empty($row['data_json'])) {
                $tmp = json_decode($row['data_json'], true);
                if (is_array($tmp)) {
                    $decoded = $tmp;
                }
            }
            $items[] = [
                'id' => (int)$row['id'],
                'type' => (string)$row['type'],
                'title' => (string)$row['title'],
                'message' => (string)$row['message'],
                'data' => $decoded,
                'sourceKey' => (string)($row['source_key'] ?? ''),
                'read' => (bool)$row['is_read'],
                'timestamp' => (string)$row['created_at'],
            ];
        }
    }

    echo json_encode(['notifications' => $items]);
    exit;
}

$rawBody = file_get_contents('php://input');
$jsonBody = json_decode($rawBody, true);
$action = $_POST['action'] ?? ($jsonBody['action'] ?? '');

if ($method === 'POST') {
    if ($action === 'reset') {
        $conn->query('TRUNCATE TABLE admin_notifications');
        if (isset($_GET['sync']) && $_GET['sync'] === '1') {
            syncFromExistingData($conn);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'sync') {
        syncFromExistingData($conn);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'create') {
        $type = trim((string)($_POST['type'] ?? ($jsonBody['type'] ?? 'status')));
        $title = trim((string)($_POST['title'] ?? ($jsonBody['title'] ?? 'Notification')));
        $message = trim((string)($_POST['message'] ?? ($jsonBody['message'] ?? '')));
        $data = $_POST['data'] ?? ($jsonBody['data'] ?? []);
        $sourceKey = trim((string)($_POST['sourceKey'] ?? ($jsonBody['sourceKey'] ?? '')));
        $createdAt = trim((string)($_POST['createdAt'] ?? ($jsonBody['createdAt'] ?? '')));

        if ($message === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Message is required']);
            exit;
        }

        if (!is_array($data)) {
            $data = [];
        }

        $ok = insertNotification(
            $conn,
            $type,
            $title,
            $message,
            $data,
            ($sourceKey !== '' ? $sourceKey : null),
            ($createdAt !== '' ? $createdAt : null)
        );

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['error' => 'Insert failed']);
            exit;
        }

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'mark_read') {
        $id = (int)($_POST['id'] ?? ($jsonBody['id'] ?? 0));
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid id']);
            exit;
        }

        $stmt = $conn->prepare('UPDATE admin_notifications SET is_read = 1 WHERE id = ?');
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Prepare failed']);
            exit;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'mark_all_read') {
        $conn->query('UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0');
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>