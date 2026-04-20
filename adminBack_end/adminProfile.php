<?php
// =============================================================
// adminProfile.php
// Handles GET (fetch) and POST (update) for the admin profile.
// Table columns: admin_id, admin_name, email, password, position
// =============================================================

require_once '../config.php';
session_start();

header('Content-Type: application/json');

// ── Auth guard ────────────────────────────────────────────────
// Support both session key styles used across the codebase
$admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;

if (!$admin_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// =============================================================
// GET — fetch current admin profile
// =============================================================
if ($method === 'GET') {

    $stmt = $conn->prepare("
        SELECT admin_id, admin_name, email, position
        FROM admin
        WHERE admin_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'DB prepare failed']);
        exit();
    }

    $stmt->bind_param('s', $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin  = $result->fetch_assoc();
    $stmt->close();

    if (!$admin) {
        http_response_code(404);
        echo json_encode(['error' => 'Admin not found']);
        exit();
    }

    echo json_encode(['success' => true, 'admin' => $admin]);
    exit();
}

// =============================================================
// POST — update admin profile
// =============================================================
if ($method === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request body']);
        exit();
    }

    $name        = trim($input['admin_name']       ?? '');
    $email       = trim($input['email']            ?? '');
    $newPassword = trim($input['password']         ?? '');
    $oldPassword = trim($input['current_password'] ?? '');

    // Nothing provided
    if (!$name && !$email && !$newPassword) {
        echo json_encode(['error' => 'No data to update']);
        exit();
    }

    if ($newPassword) {
        if (!$oldPassword) {
            echo json_encode(['error' => 'Current password is required to set a new password']);
            exit();
        }

        if (strlen($newPassword) < 8) {
            echo json_encode(['error' => 'New password must be at least 8 characters']);
            exit();
        }

        $chk = $conn->prepare("SELECT password FROM admin WHERE admin_id = ? LIMIT 1");
        $chk->bind_param('s', $admin_id);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$row || !password_verify($oldPassword, $row['password'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Current password is incorrect']);
            exit();
        }
    }

    // ── Email uniqueness check ────────────────────────────────
    if ($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'Invalid email address']);
            exit();
        }

        $eChk = $conn->prepare("SELECT admin_id FROM admin WHERE email = ? AND admin_id != ? LIMIT 1");
        $eChk->bind_param('ss', $email, $admin_id);
        $eChk->execute();
        $eRow = $eChk->get_result()->fetch_assoc();
        $eChk->close();

        if ($eRow) {
            echo json_encode(['error' => 'That email is already used by another admin']);
            exit();
        }
    }

    // ── Build dynamic UPDATE ──────────────────────────────────
    $fields = [];
    $params = [];
    $types  = '';

    if ($name) {
        $fields[] = 'admin_name = ?';
        $params[]  = $name;
        $types    .= 's';
    }

    if ($email) {
        $fields[] = 'email = ?';
        $params[]  = $email;
        $types    .= 's';
    }

    if ($newPassword) {
        $hashed   = password_hash($newPassword, PASSWORD_DEFAULT);
        $fields[] = 'password = ?';
        $params[]  = $hashed;
        $types    .= 's';
    }

    $types   .= 's';
    $params[] = $admin_id;

    $sql  = 'UPDATE admin SET ' . implode(', ', $fields) . ' WHERE admin_id = ?';
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'DB prepare failed']);
        exit();
    }

    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $stmt->close();

        $fetch = $conn->prepare("SELECT admin_id, admin_name, email, position FROM admin WHERE admin_id = ? LIMIT 1");
        $fetch->bind_param('s', $admin_id);
        $fetch->execute();
        $updated = $fetch->get_result()->fetch_assoc();
        $fetch->close();

        // Keep session name in sync
        if ($name) $_SESSION['admin_name'] = $name;

        echo json_encode(['success' => true, 'admin' => $updated]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Update failed: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);