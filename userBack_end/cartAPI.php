<?php


if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
// pang display
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare(
        "SELECT c.product_id, c.quantity,
                p.name, p.price, p.image, p.stock
         FROM carts c
         LEFT JOIN products p ON p.product_id = c.product_id
         WHERE c.user_id = ?
         ORDER BY c.created_at ASC"
    );
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Query prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'items' => $rows]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input     = json_decode(file_get_contents('php://input'), true) ?: [];
$action    = trim($input['action']     ?? 'add');
$productId = trim($input['product_id'] ?? '');
$quantity  = max(1, (int)($input['quantity'] ?? 1));

if ($action === 'clear') {
    $stmt = $conn->prepare('DELETE FROM carts WHERE user_id = ?');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Query prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Cart cleared']);
    exit;
}

if ($productId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'product_id is required']);
    exit;
}

// alis na yung item sa cart
if ($action === 'remove') {
    $stmt = $conn->prepare('DELETE FROM carts WHERE user_id = ? AND product_id = ?');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Query prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('ss', $userId, $productId);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
    exit;
}

if ($action === 'update') {

    $stmt = $conn->prepare(
        "INSERT INTO carts (user_id, product_id, quantity)
        VALUES (?, ?, ?)
        ON UNIQUE KEY UPDATE quantity = VALUES(quantity)"
    );
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Query prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('ssi', $userId, $productId, $quantity);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update cart: ' . $stmt->error]);
        $stmt->close();
        exit;
    }
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Cart updated']);
    exit;
}
$stmt = $conn->prepare(
    "INSERT INTO carts (user_id, product_id, quantity)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)"
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Query prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param('ssi', $userId, $productId, $quantity);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to add to cart: ' . $stmt->error]);
    $stmt->close();
    exit;
}
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Item added to cart']);
exit;
?>