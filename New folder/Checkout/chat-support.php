<?php
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../../session_bootstrap.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

function has_column(mysqli $conn, string $table, string $column): bool
{
    $tableEsc = $conn->real_escape_string($table);
    $columnEsc = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `{$tableEsc}` LIKE '{$columnEsc}'");
    return $res && $res->num_rows > 0;
}

function get_user_pk(mysqli $conn): string
{
    return has_column($conn, 'users', 'user_id') ? 'user_id' : 'id';
}

function get_admin_id(mysqli $conn, string $userPk): int
{
    if (has_column($conn, 'users', 'role')) {
        $sql = "SELECT {$userPk} AS id FROM users WHERE LOWER(role) = 'admin' ORDER BY {$userPk} ASC LIMIT 1";
        $res = $conn->query($sql);
        $row = $res ? $res->fetch_assoc() : null;
        if ($row && isset($row['id'])) {
            return (int)$row['id'];
        }
    }

    if (has_column($conn, 'users', 'email')) {
        $sql = "SELECT {$userPk} AS id FROM users WHERE LOWER(email) LIKE 'admin@%' ORDER BY {$userPk} ASC LIMIT 1";
        $res = $conn->query($sql);
        $row = $res ? $res->fetch_assoc() : null;
        if ($row && isset($row['id'])) {
            return (int)$row['id'];
        }
    }

    return 0;
}

function find_or_create_chat_user(mysqli $conn, string $userPk, string $email, string $name, int $requestedUserId = 0): int
{
    if ($requestedUserId > 0) {
        $roleExpr = has_column($conn, 'users', 'role') ? ', role' : ", '' AS role";
        $byId = $conn->prepare("SELECT {$userPk} AS id{$roleExpr} FROM users WHERE {$userPk} = ? LIMIT 1");
        if ($byId) {
            $byId->bind_param('i', $requestedUserId);
            $byId->execute();
            $idRow = $byId->get_result()->fetch_assoc();
            $byId->close();
            if ($idRow && isset($idRow['id'])) {
                $role = strtolower(trim((string)($idRow['role'] ?? '')));
                if ($role === 'admin') {
                    throw new Exception('Admin account cannot be used as chat sender.');
                }
                return (int)$idRow['id'];
            }
        }
    }

    $find = $conn->prepare("SELECT {$userPk} AS id FROM users WHERE email = ? LIMIT 1");
    if (!$find) {
        throw new Exception('Unable to search user by email: ' . $conn->error);
    }
    $find->bind_param('s', $email);
    $find->execute();
    $row = $find->get_result()->fetch_assoc();
    $find->close();
    if ($row && isset($row['id'])) {
        return (int)$row['id'];
    }

    $cleanName = trim($name) !== '' ? trim($name) : 'Guest User';
    $parts = preg_split('/\s+/', $cleanName);
    $firstName = $parts[0] ?? 'Guest';
    $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'User';

    $base = preg_replace('/[^a-z0-9_]/i', '', strstr($email, '@', true) ?: 'guest');
    if ($base === '') {
        $base = 'guest';
    }
    $username = strtolower($base) . '_' . substr(md5($email . microtime(true)), 0, 6);

    $columns = [];
    $values = [];
    $types = '';

    if (has_column($conn, 'users', 'first_name')) {
        $columns[] = 'first_name';
        $values[] = $firstName;
        $types .= 's';
    }
    if (has_column($conn, 'users', 'last_name')) {
        $columns[] = 'last_name';
        $values[] = $lastName;
        $types .= 's';
    }
    if (has_column($conn, 'users', 'name')) {
        $columns[] = 'name';
        $values[] = $cleanName;
        $types .= 's';
    }
    if (has_column($conn, 'users', 'email')) {
        $columns[] = 'email';
        $values[] = $email;
        $types .= 's';
    }
    if (has_column($conn, 'users', 'username')) {
        $columns[] = 'username';
        $values[] = $username;
        $types .= 's';
    }
    if (has_column($conn, 'users', 'password')) {
        $columns[] = 'password';
        $values[] = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $types .= 's';
    }
    if (has_column($conn, 'users', 'role')) {
        $columns[] = 'role';
        $values[] = 'user';
        $types .= 's';
    }
    if (has_column($conn, 'users', 'status')) {
        $columns[] = 'status';
        $values[] = 'Active';
        $types .= 's';
    }
    if (has_column($conn, 'users', 'email_verified')) {
        $columns[] = 'email_verified';
        $values[] = 1;
        $types .= 'i';
    }

    if (count($columns) === 0) {
        throw new Exception('Users table has no writable columns for guest chat identity.');
    }

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $sql = 'INSERT INTO users (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')';
    $insert = $conn->prepare($sql);
    if (!$insert) {
        throw new Exception('Unable to create chat user: ' . $conn->error);
    }
    $insert->bind_param($types, ...$values);
    if (!$insert->execute()) {
        throw new Exception('Unable to create chat user: ' . $insert->error);
    }
    $newId = (int)$insert->insert_id;
    $insert->close();

    return $newId;
}

function resolve_existing_chat_user_id(mysqli $conn, string $userPk, string $email, int $requestedUserId = 0): int
{
    if ($requestedUserId > 0) {
        $roleExpr = has_column($conn, 'users', 'role') ? ', role' : ", '' AS role";
        $byId = $conn->prepare("SELECT {$userPk} AS id{$roleExpr} FROM users WHERE {$userPk} = ? LIMIT 1");
        if ($byId) {
            $byId->bind_param('i', $requestedUserId);
            $byId->execute();
            $idRow = $byId->get_result()->fetch_assoc();
            $byId->close();
            if ($idRow && isset($idRow['id'])) {
                $role = strtolower(trim((string)($idRow['role'] ?? '')));
                return $role === 'admin' ? 0 : (int)$idRow['id'];
            }
        }
    }

    if ($email !== '') {
        $find = $conn->prepare("SELECT {$userPk} AS id FROM users WHERE email = ? LIMIT 1");
        if ($find) {
            $find->bind_param('s', $email);
            $find->execute();
            $row = $find->get_result()->fetch_assoc();
            $find->close();
            if ($row && isset($row['id'])) {
                return (int)$row['id'];
            }
        }
    }

    return 0;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = trim((string)($input['action'] ?? ''));
$requestedUserId = (int)($input['user_id'] ?? 0);
$email = trim((string)($input['email'] ?? ''));
$message = trim((string)($input['message'] ?? ''));
$name = trim((string)($input['name'] ?? 'Guest User'));

if ($action !== 'send_message' && $action !== 'fetch_messages') {
    echo json_encode(['success' => false, 'error' => 'Invalid action.']);
    exit;
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
    exit;
}

try {
    $userPk = get_user_pk($conn);
    $adminId = get_admin_id($conn, $userPk);

    if ($adminId <= 0) {
        throw new Exception('Unable to resolve admin account.');
    }

    if ($action === 'fetch_messages') {
        $chatUserId = resolve_existing_chat_user_id($conn, $userPk, $email, $requestedUserId);
        if ($chatUserId <= 0) {
            echo json_encode(['success' => true, 'messages' => []]);
            exit;
        }

        $hasSenderType = has_column($conn, 'messages', 'sender_type');
        $msgSql = "SELECT m.id, m.sender_id, m.receiver_id, m.message, m.created_at"
            . ($hasSenderType ? ', m.sender_type' : '')
            . " FROM messages m
                WHERE (m.sender_id = ? AND m.receiver_id = ?)
                   OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_at ASC, m.id ASC";
        $msgStmt = $conn->prepare($msgSql);
        if (!$msgStmt) {
            throw new Exception('Failed to prepare message fetch: ' . $conn->error);
        }
        $msgStmt->bind_param('iiii', $chatUserId, $adminId, $adminId, $chatUserId);
        $msgStmt->execute();
        $result = $msgStmt->get_result();

        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $senderType = $hasSenderType
                ? strtolower((string)($row['sender_type'] ?? ''))
                : ((int)$row['sender_id'] === $adminId ? 'admin' : 'user');
            if ($senderType !== 'admin' && $senderType !== 'user') {
                $senderType = ((int)$row['sender_id'] === $adminId) ? 'admin' : 'user';
            }

            $messages[] = [
                'id' => (int)$row['id'],
                'text' => (string)($row['message'] ?? ''),
                'sender_type' => $senderType,
                'time' => date('h:i A', strtotime((string)$row['created_at']))
            ];
        }
        $msgStmt->close();

        echo json_encode([
            'success' => true,
            'user_id' => $chatUserId,
            'admin_id' => $adminId,
            'messages' => $messages
        ]);
        exit;
    }

    if ($message === '') {
        echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
        exit;
    }

    $senderUserId = find_or_create_chat_user($conn, $userPk, $email, $name, $requestedUserId);

    $hasSenderType = has_column($conn, 'messages', 'sender_type');
    $hasIsRead = has_column($conn, 'messages', 'is_read');

    $cols = ['sender_id', 'receiver_id', 'message'];
    $vals = [$senderUserId, $adminId, $message];
    $types = 'iis';

    if ($hasSenderType) {
        $cols[] = 'sender_type';
        $vals[] = 'user';
        $types .= 's';
    }
    if ($hasIsRead) {
        $cols[] = 'is_read';
        $vals[] = 0;
        $types .= 'i';
    }

    $sql = 'INSERT INTO messages (' . implode(', ', $cols) . ', created_at) VALUES ('
        . implode(', ', array_fill(0, count($cols), '?')) . ', NOW())';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare message insert: ' . $conn->error);
    }
    $stmt->bind_param($types, ...$vals);
    if (!$stmt->execute()) {
        throw new Exception('Failed to save message: ' . $stmt->error);
    }

    $messageId = (int)$stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'id' => $messageId,
        'sender_user_id' => $senderUserId,
        'receiver_admin_id' => $adminId,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
