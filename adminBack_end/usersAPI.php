<?php
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'admin');
require_once __DIR__ . '/../session_bootstrap.php';
require_once '../config.php';

// Only admins can manage users
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

function has_column(mysqli $conn, string $table, string $column): bool {
    $tableEsc = $conn->real_escape_string($table);
    $columnEsc = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `{$tableEsc}` LIKE '{$columnEsc}'");
    return $res && $res->num_rows > 0;
}

$hasPhoneColumn = has_column($conn, 'users', 'phone');
$hasStatusColumn = has_column($conn, 'users', 'status');

// ── GET: Fetch all users ──────────────────────────────────────────────────────
if ($method === 'GET') {
    $phoneExpr = $hasPhoneColumn ? 'u.phone' : "''";
    $statusExpr = $hasStatusColumn
        ? "CASE WHEN LOWER(COALESCE(u.status, 'active')) = 'banned' THEN 'Banned' WHEN LOWER(COALESCE(u.status, 'active')) = 'inactive' THEN 'Inactive' ELSE 'Active' END"
        : "CASE WHEN bs.user_id IS NOT NULL THEN 'Banned' ELSE 'Active' END";

    $result = $conn->query(" 
        SELECT
            u.user_id,
            u.user_id AS id,
            TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) AS name,
            u.email,
            {$phoneExpr} AS number,
            {$statusExpr} AS status
        FROM users u
        LEFT JOIN banned_sessions bs ON bs.user_id = u.user_id
        WHERE LOWER(COALESCE(u.role, 'user')) = 'user'
        ORDER BY u.created_at DESC
    ");

    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch users']);
        exit();
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        if (trim((string)$row['name']) === '') {
            $row['name'] = (string)$row['email'];
        }
        $users[] = $row;
    }

    echo json_encode($users);
    exit();
}

// ── POST: Ban a user ──────────────────────────────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
    $reason = isset($input['reason']) ? trim($input['reason']) : 'Banned by admin';

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid user ID']);
        exit();
    }

    // Check user exists and is not already banned
    $stmt = $conn->prepare("SELECT user_id, status, role FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit();
    }

    if ($user['role'] === 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Cannot ban an admin account']);
        exit();
    }

    $affected = 0;
    if ($hasStatusColumn) {
        $stmt = $conn->prepare("UPDATE users SET status = 'banned' WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
    }

    // Destroy any active session for the banned user so they are
    // immediately logged out if they are currently online.
    // (Requires session storage accessible server-side; for file-based
    //  sessions this is handled by regenerating the session store.)
    // We store the banned user ID in a blacklist table for session checks.
    $banStmt = $conn->prepare('INSERT IGNORE INTO banned_sessions (user_id, banned_at) VALUES (?, NOW())');
    if ($banStmt) {
        $banStmt->bind_param('i', $userId);
        $banStmt->execute();
        if ($banStmt->affected_rows > 0) {
            $affected = 1;
        }
        $banStmt->close();
    }

    if ($affected > 0) {
        echo json_encode(['success' => true, 'message' => 'User banned successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to ban user']);
    }
    exit();
}

// ── DELETE: Remove a user ─────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid user ID']);
        exit();
    }

    // Prevent deleting admin accounts
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit();
    }

    if ($user['role'] === 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Cannot delete an admin account']);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected > 0) {
        $cleanup = $conn->prepare('DELETE FROM banned_sessions WHERE user_id = ?');
        if ($cleanup) {
            $cleanup->bind_param('i', $userId);
            $cleanup->execute();
            $cleanup->close();
        }
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete user']);
    }
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>