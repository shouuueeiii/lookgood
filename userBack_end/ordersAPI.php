<?php
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function ensureExtendedOrderStatus(mysqli $conn): void {
    $res = $conn->query("SHOW COLUMNS FROM checkout LIKE 'status'");
    if (!$res) return;
    $row = $res->fetch_assoc();
    if (!$row) return;

    $type = strtolower((string)($row['Type'] ?? ''));
    $hasCompleted = strpos($type, "'completed'") !== false;
    $hasCancelled = strpos($type, "'cancelled'") !== false;

    if (!$hasCompleted || !$hasCancelled) {
        $conn->query("ALTER TABLE checkout MODIFY COLUMN status ENUM('pending','processing','shipped','out_for_delivery','delivered','completed','cancelled') NOT NULL DEFAULT 'pending'");
    }
}

function normalizeImagePath(string $image): string {
    $img = trim($image);
    if ($img === '') return '/lookgood/New%20folder/Resources/Images/glasses1.png';
    if (preg_match('/^https?:\/\//i', $img)) return $img;
    if (strpos($img, '/lookgood/') === 0) return $img;
    if (strpos($img, 'uploads/') === 0) return '/lookgood/' . $img;
    return '/lookgood/uploads/products/' . ltrim($img, '/');
}

function mapStatusForProfile(string $dbStatus): string {
    $s = strtolower(trim($dbStatus));
    if ($s === 'pending') return 'paid';
    if ($s === 'processing') return 'processing';
    if ($s === 'shipped' || $s === 'out_for_delivery') return 'shipped';
    if ($s === 'delivered') return 'delivered';
    if ($s === 'completed') return 'completed';
    if ($s === 'cancelled') return 'cancelled';
    if ($s === 'refunded') return 'refunded';
    return 'paid';
}

ensureExtendedOrderStatus($conn);

if ($method === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?: [];
    $orderId = (int)($payload['order_id'] ?? 0);
    $action = strtolower(trim((string)($payload['action'] ?? '')));

    if ($orderId <= 0 || ($action !== 'cancel' && $action !== 'confirm')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid payload']);
        exit();
    }

    $lookup = $conn->prepare('SELECT status FROM checkout WHERE order_id = ? AND user_id = ? LIMIT 1');
    if (!$lookup) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to prepare lookup']);
        exit();
    }
    $lookup->bind_param('ii', $orderId, $userId);
    $lookup->execute();
    $row = $lookup->get_result()->fetch_assoc();
    $lookup->close();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit();
    }

    $current = strtolower(trim((string)$row['status']));
    $newStatus = null;

    if ($action === 'cancel') {
        if (!in_array($current, ['pending', 'processing'], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Only pending/processing orders can be cancelled']);
            exit();
        }
        $newStatus = 'cancelled';
    }

    if ($action === 'confirm') {
        if (!in_array($current, ['delivered', 'shipped', 'out_for_delivery'], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Order is not ready for confirmation']);
            exit();
        }
        $newStatus = 'completed';
    }

    $update = $conn->prepare('UPDATE checkout SET status = ? WHERE order_id = ? AND user_id = ?');
    if (!$update) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to prepare update']);
        exit();
    }
    $update->bind_param('sii', $newStatus, $orderId, $userId);
    $ok = $update->execute();
    $update->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update order']);
        exit();
    }

    echo json_encode(['success' => true]);
    exit();
}

$sql = "
    SELECT
        c.order_id,
        c.created_at,
        c.total_amount,
        c.status,
        oi.product_id,
        oi.quantity,
        oi.price,
        p.name AS product_name,
        p.image AS product_image
    FROM checkout c
    LEFT JOIN order_items oi ON oi.order_id = c.order_id
    LEFT JOIN products p ON p.product_id = oi.product_id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC, c.order_id DESC, oi.id ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare query']);
    exit();
}

$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();

$orders = [];
while ($row = $res->fetch_assoc()) {
    $rawId = (int)$row['order_id'];
    if (!isset($orders[$rawId])) {
        $orders[$rawId] = [
            'order_id' => 'LG-' . str_pad((string)$rawId, 6, '0', STR_PAD_LEFT),
            'raw_order_id' => (string)$rawId,
            'date' => date('M d, Y', strtotime((string)$row['created_at'])),
            'total' => (float)$row['total_amount'],
            'status' => mapStatusForProfile((string)$row['status']),
            'items' => []
        ];
    }

    if (!empty($row['product_id'])) {
        $orders[$rawId]['items'][] = [
            'id' => (string)$row['product_id'],
            'name' => (string)($row['product_name'] ?: $row['product_id']),
            'image' => normalizeImagePath((string)($row['product_image'] ?? '')),
            'qty' => max(1, (int)($row['quantity'] ?? 1)),
            'price' => (float)($row['price'] ?? 0)
        ];
    }
}

$stmt->close();

echo json_encode(array_values($orders));
