<?php
// Suppress all PHP errors/warnings from leaking into JSON output
error_reporting(0);
ob_start();

require_once '../config.php';
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';

ob_end_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$userDisplayName = trim((string)(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')));
if ($userDisplayName === '') $userDisplayName = (string)($_SESSION['email'] ?? ('User #' . $userId));

$conn->query("
    CREATE TABLE IF NOT EXISTS product_feedback (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id    INT UNSIGNED NOT NULL,
        product_id VARCHAR(50)  NOT NULL,
        order_id   INT UNSIGNED NULL,
        rating     TINYINT(1)   NOT NULL,
        comment    TEXT         NULL,
        created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pf_user (user_id),
        INDEX idx_pf_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Safely add order_id column if missing
$colCheck = $conn->query("SHOW COLUMNS FROM product_feedback LIKE 'order_id'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE product_feedback ADD COLUMN order_id INT UNSIGNED NULL AFTER product_id");
}

$idxCheck = $conn->query("SHOW INDEX FROM product_feedback WHERE Key_name = 'uq_order_product'");
if ($idxCheck && $idxCheck->num_rows === 0) {
    $conn->query("ALTER TABLE product_feedback ADD UNIQUE KEY uq_order_product (user_id, order_id, product_id)");
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare(
        'SELECT order_id, product_id, rating, comment
         FROM product_feedback
         WHERE user_id = ?'
    );
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $ratings = [];
    while ($row = $res->fetch_assoc()) {
        $key = ($row['order_id']
            ? 'LG-' . str_pad((string)$row['order_id'], 6, '0', STR_PAD_LEFT)
            : 'no-order')
            . '|' . $row['product_id'];
        $ratings[$key] = [
            'rating'  => (int)$row['rating'],
            'comment' => $row['comment'] ?? '',
        ];
    }
    $stmt->close();
    echo json_encode($ratings);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input        = json_decode(file_get_contents('php://input'), true) ?? [];
    $rawOrderId   = trim((string)($input['order_id']   ?? ''));
    $rawProductId = trim((string)($input['product_id'] ?? ''));
    $rating       = (int)($input['rating']  ?? 0);
    $comment      = trim((string)($input['comment'] ?? ''));

    if ($rawProductId === '' || $rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid rating data']);
        exit();
    }

    $orderId = null;
    if ($rawOrderId !== '') {
        $numericStr = preg_replace('/[^0-9]/', '', $rawOrderId);
        if ($numericStr !== '') $orderId = (int)$numericStr;
    }

    if ($orderId !== null) {
        $chk = $conn->prepare('SELECT order_id FROM checkout WHERE order_id = ? AND user_id = ? LIMIT 1');
        if ($chk) {
            $chk->bind_param('ii', $orderId, $userId);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows === 0) {
                $chk->close();
                http_response_code(403);
                echo json_encode(['error' => 'Order not found or not yours']);
                exit();
            }
            $chk->close();
        }
    }

    $productId = $rawProductId;
    $prodStmt = $conn->prepare('SELECT product_id FROM products WHERE product_id = ? LIMIT 1');
    if ($prodStmt) {
        $prodStmt->bind_param('s', $rawProductId);
        $prodStmt->execute();
        $prodRow = $prodStmt->get_result()->fetch_assoc();
        $prodStmt->close();
        if ($prodRow) $productId = (string)$prodRow['product_id'];
    }

    if (strlen($comment) > 600) $comment = substr($comment, 0, 600);

    $upsert = $conn->prepare("
        INSERT INTO product_feedback (user_id, product_id, order_id, rating, comment)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)
    ");
    if (!$upsert) {
        http_response_code(500);
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    $upsert->bind_param('isids', $userId, $productId, $orderId, $rating, $comment);
    $ok = $upsert->execute();
    $upsert->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['error' => 'Save failed: ' . $conn->error]);
        exit();
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS feedback (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            user_id          INT NOT NULL,
            product_id       VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
            rating           TINYINT NOT NULL,
            feedback_comment TEXT NULL,
            admin_reply      TEXT NULL,
            created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_product (user_id, product_id)
        )
    ");
    $adminUpsert = $conn->prepare("
        INSERT INTO feedback (user_id, product_id, rating, feedback_comment)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE rating = VALUES(rating), feedback_comment = VALUES(feedback_comment)
    ");
    if ($adminUpsert) {
        $adminUpsert->bind_param('isis', $userId, $productId, $rating, $comment);
        $adminUpsert->execute();
        $adminUpsert->close();
    }

    echo json_encode(['success' => true]);
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>