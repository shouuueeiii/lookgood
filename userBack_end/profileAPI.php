<?php

require_once '../config.php';
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';

header('Content-Type: application/json');

function columnExists(mysqli $conn, string $table, string $column): bool {
    $stmt = $conn->prepare(
        "SELECT 1
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
         LIMIT 1"
    );
    if (!$stmt) return false;
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Must be logged in as user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// ── GET — return profile data ─────────────────────────────────────────────────
if ($method === 'GET') {
    $hasAddress = columnExists($conn, 'users', 'address');
    $hasCity = columnExists($conn, 'users', 'city');
    $hasProvince = columnExists($conn, 'users', 'province');
    $hasZip = columnExists($conn, 'users', 'zip_code');

    $selectAddress = $hasAddress ? 'address' : "'' AS address";
    $selectCity = $hasCity ? 'city' : "'' AS city";
    $selectProvince = $hasProvince ? 'province' : "'' AS province";
    $selectZip = $hasZip ? 'zip_code' : "'' AS zip_code";

    $sql = "SELECT user_id, first_name, last_name, username, email, phone, $selectAddress, $selectCity, $selectProvince, $selectZip, created_at FROM users WHERE user_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare profile query']);
        exit();
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit();
    }

    echo json_encode([
        'user_id'    => $row['user_id'],
        'firstName'  => $row['first_name'],
        'lastName'   => $row['last_name'],
        'username'   => $row['username'],
        'email'      => $row['email'],
        'phone'      => $row['phone'] ?? '',
        'address'    => $row['address'] ?? '',
        'city'       => $row['city'] ?? '',
        'province'   => $row['province'] ?? '',
        'zipCode'    => $row['zip_code'] ?? '',
        'memberSince'=> date('F Y', strtotime($row['created_at'])),
    ]);
    exit();
}

// ── POST — update profile ─────────────────────────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $field = trim($input['field'] ?? '');

    // --- Update personal info ---
    if ($field === 'profile') {
        $firstName = trim($input['firstName'] ?? '');
        $lastName  = trim($input['lastName']  ?? '');
        $username  = trim($input['username']  ?? '');
        $phone     = trim($input['phone']     ?? '');

        if (strlen($firstName) < 2 || strlen($lastName) < 2) {
            http_response_code(400);
            echo json_encode(['error' => 'Name too short']);
            exit();
        }

        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid username format']);
            exit();
        }

        // Check username uniqueness (allow own username)
        $dupStmt = $conn->prepare(
            "SELECT user_id FROM users WHERE username = ? AND user_id != ? LIMIT 1"
        );
        $dupStmt->bind_param('si', $username, $userId);
        $dupStmt->execute();
        $dupStmt->store_result();
        if ($dupStmt->num_rows > 0) {
            $dupStmt->close();
            http_response_code(409);
            echo json_encode(['error' => 'Username already taken']);
            exit();
        }
        $dupStmt->close();

        $stmt = $conn->prepare(
            "UPDATE users SET first_name=?, last_name=?, username=?, phone=? WHERE user_id=?"
        );
        $stmt->bind_param('ssssi', $firstName, $lastName, $username, $phone, $userId);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update profile']);
            exit();
        }

        // Refresh session name
        $_SESSION['first_name'] = $firstName;

        echo json_encode(['success' => true]);
        exit();
    }

    // --- Update address ---
    if ($field === 'address') {
        $address  = trim($input['address']  ?? '');
        $city     = trim($input['city']     ?? '');
        $province = trim($input['province'] ?? '');
        $zipCode  = trim($input['zipCode']  ?? '');

        $hasAddress = columnExists($conn, 'users', 'address');
        $hasCity = columnExists($conn, 'users', 'city');
        $hasProvince = columnExists($conn, 'users', 'province');
        $hasZip = columnExists($conn, 'users', 'zip_code');

        if (!$hasAddress || !$hasCity || !$hasProvince || !$hasZip) {
            http_response_code(400);
            echo json_encode(['error' => 'Address columns are missing in users table']);
            exit();
        }

        $stmt = $conn->prepare("UPDATE users SET address=?, city=?, province=?, zip_code=? WHERE user_id=?");
        $stmt->bind_param('ssssi', $address, $city, $province, $zipCode, $userId);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update address']);
            exit();
        }

        echo json_encode(['success' => true]);
        exit();
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown field']);
    exit();
}

echo json_encode(['error' => 'Method not allowed']);
?>
