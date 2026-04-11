<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// DELETE user
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'Invalid ID']); exit(); }
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit();
}

// POST - ban/unban user
if ($method === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true);
    $id     = (int)($input['id'] ?? 0);
    $status = $input['status'] ?? '';
    if (!$id || !in_array($status, ['Active', 'Inactive', 'Banned'])) {
        echo json_encode(['error' => 'Invalid input']); exit();
    }
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit();
}

// GET - list users
$users = [];
$res = $conn->query("
    SELECT id, name, email, number, status, created_at
    FROM users
    WHERE role = 'customer'
    ORDER BY created_at DESC
");
while ($row = $res->fetch_assoc()) {
    $users[] = $row;
}
echo json_encode($users);
?>
