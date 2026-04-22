<?php
/**
 * userBack_end/guestAddToCart.php
 * Stores / updates / removes guest cart items in the guest_carts table.
 * Uses guest_id from session (UUID assigned on first visit).
 *
 * POST body (JSON):
 *   { "action": "add",    "product_id": "LGF-M-001-26", "quantity": 1 }
 *   { "action": "remove", "product_id": "LGF-M-001-26" }
 *   { "action": "clear"  }
 * GET → returns all cart items for this guest
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

// Assign a guest_id UUID if not already set
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

$guestId = $_SESSION['guest_id'];

// Ensure guest_carts table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS guest_carts (
        id         INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        guest_id   VARCHAR(64)  NOT NULL,
        product_id VARCHAR(255) NOT NULL,
        quantity   INT          NOT NULL DEFAULT 1,
        added_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_guest_product (guest_id, product_id),
        INDEX idx_guest_id (guest_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── GET: return cart items for this guest ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare(
        "SELECT gc.product_id, gc.quantity,
                p.name, p.price, p.image
         FROM guest_carts gc
         LEFT JOIN products p ON p.product_id = gc.product_id
         WHERE gc.guest_id = ?
         ORDER BY gc.added_at ASC"
    );
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Query failed']);
        exit;
    }
    $stmt->bind_param('s', $guestId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'items' => $rows]);
    exit;
}

// ── POST: add / remove / clear ───────────────────────────────────────────────
$input     = json_decode(file_get_contents('php://input'), true) ?: [];
$action    = trim($input['action'] ?? 'add');
$productId = trim($input['product_id'] ?? '');
$quantity  = max(1, (int)($input['quantity'] ?? 1));

if ($action === 'clear') {
    $stmt = $conn->prepare('DELETE FROM guest_carts WHERE guest_id = ?');
    $stmt->bind_param('s', $guestId);
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
    $stmt = $conn->prepare('DELETE FROM guest_carts WHERE guest_id = ? AND product_id = ?');
    $stmt->bind_param('ss', $guestId, $productId);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Item removed']);
    exit;
}

// action === 'add' — INSERT or increment quantity
$stmt = $conn->prepare(
    'INSERT INTO guest_carts (guest_id, product_id, quantity)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Query failed']);
    exit;
}
$stmt->bind_param('ssi', $guestId, $productId, $quantity);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Item added to cart', 'guest_id' => $guestId]);
?>