<?php
/**
 * userBack_end/wishlistAPI.php
 * Authenticated API — manage the logged-in user's wishlist.
 * Used by: Actions/User/profile-wishlist.js, products-page.js, product-detail.js
 */
require_once '../config.php';
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// Ensure wishlist table exists
$conn->query(
    "CREATE TABLE IF NOT EXISTS wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id VARCHAR(50) NOT NULL,
        added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_wishlist (user_id, product_id)
    )"
);

if ($method === 'GET') {
    $stmt = $conn->prepare(
        "SELECT p.product_id, p.name, p.price, p.stock, p.image, p.category
         FROM wishlist w
         JOIN products p ON w.product_id = p.product_id
         WHERE w.user_id = ?
         ORDER BY w.added_at DESC"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id'       => (string) $row['product_id'],
            'name'     => $row['name'],
            'price'    => (float) $row['price'],
            'stock'    => (int) $row['stock'],
            'image'    => !empty($row['image'])
                            ? '../uploads/products/' . $row['image']
                            : '../Resources/Images/glasses1.png',
            'category' => $row['category'],
        ];
    }

    echo json_encode($items);
    exit();
}

// ── POST — toggle (add/remove) a product in the wishlist ─────────────────────
if ($method === 'POST') {
    $input     = json_decode(file_get_contents('php://input'), true);
    $productId = trim($input['product_id'] ?? '');

    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'product_id required']);
        exit();
    }

    // Check if already in wishlist
    $chk = $conn->prepare(
        "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1"
    );
    $chk->bind_param('is', $userId, $productId);
    $chk->execute();
    $chk->store_result();
    $exists = $chk->num_rows > 0;
    $chk->close();

    if ($exists) {
        // Remove
        $del = $conn->prepare(
            "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?"
        );
        $del->bind_param('is', $userId, $productId);
        $del->execute();
        $del->close();
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Add
        $ins = $conn->prepare(
            "INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)"
        );
        $ins->bind_param('is', $userId, $productId);
        $ins->execute();
        $ins->close();
        echo json_encode(['success' => true, 'action' => 'added']);
    }
    exit();
}

// ── DELETE — remove a product from wishlist ───────────────────────────────────
if ($method === 'DELETE') {
    $input     = json_decode(file_get_contents('php://input'), true);
    $productId = trim($input['product_id'] ?? '');

    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'product_id required']);
        exit();
    }

    $del = $conn->prepare(
        "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?"
    );
    $del->bind_param('is', $userId, $productId);
    $del->execute();
    $del->close();

    echo json_encode(['success' => true]);
    exit();
}

echo json_encode(['error' => 'Method not allowed']);
?>
