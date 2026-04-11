<?php
/**
 * userBack_end/profileAPI.php
 * Authenticated API — read and update the logged-in user's profile.
 * Used by: Actions/User/profile-account.js
 */
require_once '../config.php';
session_start();

header('Content-Type: application/json');

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// ── GET — return profile data ─────────────────────────────────────────────────
if ($method === 'GET') {
    $stmt = $conn->prepare(
        "SELECT user_id, first_name, last_name, username, email, phone,
                address, city, province, zip_code, created_at
         FROM users WHERE user_id = ? LIMIT 1"
    );
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

        $stmt = $conn->prepare(
            "UPDATE users SET address=?, city=?, province=?, zip_code=? WHERE user_id=?"
        );
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
