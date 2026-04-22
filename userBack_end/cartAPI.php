<?php
/**
 * userBack_end/cartAPI.php
 *
 * Unified cart API for logged-in users — reads/writes the `carts` table.
 *
 * GET  → { success, items: [...] }
 * POST → { action: "add"|"remove"|"update"|"clear", product_id, quantity }
 *       → { success, message }
 */

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

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// ── GET: return all cart items ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("
        SELECT c.product_id, c.quantity,
               p.name, p.price, p.image, p.stock
        FROM carts c
        LEFT JOIN products p ON p.product_id = c.product_id
        WHERE c.user_id = ?
        ORDER BY c.id ASC
    ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Query failed']);
        exit;
    }
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'items' => $rows]);
    exit;
}

// ── POST: mutations ───────────────────────────────────────────────────────────
$input     = json_decode(file_get_contents('php://input'), true) ?: [];
$action    = trim($input['action'] ?? 'add');
$productId = trim((string)($input['product_id'] ?? ''));
$quantity  = max(1, (int)($input['quantity'] ?? 1));

if ($action === 'clear') {
    $stmt = $conn->prepare('DELETE FROM carts WHERE user_id = ?');
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

if ($action === 'remove') {
    $stmt = $conn->prepare('DELETE FROM carts WHERE user_id = ? AND product_id = ?');
    $stmt->bind_param('ss', $userId, $productId);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Item removed']);
    exit;
}

if ($action === 'update') {
    // Set quantity to exact value (used by qty controls in cart.php)
    $stmt = $conn->prepare(
        'INSERT INTO carts (user_id, product_id, quantity)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)'
    );
    $stmt->bind_param('ssi', $userId, $productId, $quantity);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Quantity updated']);
    exit;
}

// action === 'add' — INSERT or increment
$stmt = $conn->prepare(
    'INSERT INTO carts (user_id, product_id, quantity)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Query failed']);
    exit;
}
$stmt->bind_param('ssi', $userId, $productId, $quantity);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Item added to cart']);
?>