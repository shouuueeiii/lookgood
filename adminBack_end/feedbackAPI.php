<?php
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'admin');
require_once __DIR__ . '/../session_bootstrap.php';
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

function hasColumn(mysqli $conn, string $table, string $column): bool {
    $tableEsc = $conn->real_escape_string($table);
    $columnEsc = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `{$tableEsc}` LIKE '{$columnEsc}'");
    return $res && $res->num_rows > 0;
}

function ensureProductFeedbackTableSchema(mysqli $conn): void {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS product_feedback (
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT(10) UNSIGNED NOT NULL,
            product_id VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
            order_id INT(10) UNSIGNED NULL,
            rating TINYINT(1) NOT NULL,
            comment TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    );

    // Ensure admin_reply column exists for storing replies
    if (!hasColumn($conn, 'product_feedback', 'admin_reply')) {
        $conn->query("ALTER TABLE product_feedback ADD COLUMN admin_reply TEXT NULL");
    }
}

ensureProductFeedbackTableSchema($conn);

$method = $_SERVER['REQUEST_METHOD'];

// POST - save admin reply
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id    = (int)($input['id'] ?? 0);
    $reply = trim($input['reply'] ?? '');

    if (!$id || !$reply) {
        echo json_encode(['error' => 'Invalid input']); exit();
    }

    $stmt = $conn->prepare("UPDATE product_feedback SET admin_reply = ? WHERE id = ?");
    $stmt->bind_param('si', $reply, $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit();
}

// GET - list all feedback
$feedbacks = [];
$userPk = hasColumn($conn, 'users', 'user_id') ? 'user_id' : 'id';
$adminReplyExpr = hasColumn($conn, 'product_feedback', 'admin_reply')
    ? "COALESCE(f.admin_reply, '')"
    : "''";

$res = $conn->query(
    "SELECT
        f.id,
        TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) AS customer,
        COALESCE(NULLIF(TRIM(p.name), ''), CONCAT('Product ', f.product_id)) AS product,
        f.order_id,
        f.rating,
        f.comment,
        $adminReplyExpr AS reply,
        f.created_at AS date
     FROM product_feedback f
     LEFT JOIN users u ON f.user_id = u.$userPk
     LEFT JOIN products p ON f.product_id COLLATE utf8mb4_general_ci = p.product_id
     ORDER BY f.created_at DESC"
);

if (!$res) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load feedback']);
    exit();
}

while ($row = $res->fetch_assoc()) {
    $row['id']       = (int)$row['id'];
    $row['customer'] = trim((string)($row['customer'] ?? '')) !== '' ? trim((string)$row['customer']) : 'Customer';
    $row['product']  = (string)($row['product'] ?? 'Product');
    $row['order_id'] = $row['order_id'] !== null ? (int)$row['order_id'] : null;
    $row['rating']   = (int)$row['rating'];
    $row['comment']  = (string)($row['comment'] ?? '');
    $row['reply']    = $row['reply'] ?? '';
    $row['date']     = date('Y-m-d', strtotime($row['date']));
    $feedbacks[]     = $row;
}
echo json_encode($feedbacks);
?>