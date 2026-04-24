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

// ── Avatar upload / remove (multipart or action param) ───────────────────────
$action = $_GET['action'] ?? '';

if ($action === 'upload_avatar' && $method === 'POST') {
    // Check upload error code first for better error messages
    if (!isset($_FILES['avatar'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No file received. Check server upload_max_filesize and post_max_size settings.']);
        exit();
    }
    $uploadErr = $_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($uploadErr !== UPLOAD_ERR_OK) {
        $uploadErrMessages = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit (upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server missing temp folder.',
            UPLOAD_ERR_CANT_WRITE => 'Server failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
        ];
        http_response_code(400);
        echo json_encode(['error' => $uploadErrMessages[$uploadErr] ?? 'Upload error code ' . $uploadErr]);
        exit();
    }

    $file     = $_FILES['avatar'];
    $allowed  = ['image/jpeg', 'image/jpg', 'image/png'];
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Only JPG and PNG files are allowed. Got: ' . $mimeType]);
        exit();
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File must be under 2 MB. Got: ' . round($file['size'] / 1024) . ' KB']);
        exit();
    }

    // Resolve upload dir relative to document root so the URL is consistent
    $docRoot   = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
    $uploadDir = $docRoot . '/lookgood/uploads/avatars/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'Could not create upload directory. Check server write permissions.']);
            exit();
        }
    }

    $ext      = $mimeType === 'image/png' ? 'png' : 'jpg';
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
    $destPath = $uploadDir . $filename;

    // Auto-add avatar_url column if it doesn't exist yet
    if (!columnExists($conn, 'users', 'avatar_url')) {
        $conn->query("ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) NULL DEFAULT NULL");
    }

    // Remove old avatar file if present
    $oldStmt = $conn->prepare("SELECT avatar_url FROM users WHERE user_id = ? LIMIT 1");
    if ($oldStmt) {
        $oldStmt->bind_param('i', $userId);
        $oldStmt->execute();
        $oldRow = $oldStmt->get_result()->fetch_assoc();
        $oldStmt->close();
        if (!empty($oldRow['avatar_url'])) {
            $oldFile = $docRoot . $oldRow['avatar_url'];
            if (file_exists($oldFile)) @unlink($oldFile);
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to move uploaded file. Check folder permissions on: ' . $uploadDir]);
        exit();
    }

    $avatarUrl = '/lookgood/uploads/avatars/' . $filename;

    $upd = $conn->prepare("UPDATE users SET avatar_url = ? WHERE user_id = ?");
    if ($upd) {
        $upd->bind_param('si', $avatarUrl, $userId);
        $upd->execute();
        $upd->close();
    }

    echo json_encode(['avatar_url' => $avatarUrl]);
    exit();
}

if ($action === 'remove_avatar' && $method === 'POST') {
    if (columnExists($conn, 'users', 'avatar_url')) {
        $oldStmt = $conn->prepare("SELECT avatar_url FROM users WHERE user_id = ? LIMIT 1");
        if ($oldStmt) {
            $oldStmt->bind_param('i', $userId);
            $oldStmt->execute();
            $oldRow = $oldStmt->get_result()->fetch_assoc();
            $oldStmt->close();
            if (!empty($oldRow['avatar_url'])) {
                $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
                $oldFile = $docRoot . $oldRow['avatar_url'];
                if (file_exists($oldFile)) @unlink($oldFile);
            }
        }
        $upd = $conn->prepare("UPDATE users SET avatar_url = NULL WHERE user_id = ?");
        if ($upd) {
            $upd->bind_param('i', $userId);
            $upd->execute();
            $upd->close();
        }
    }
    echo json_encode(['success' => true]);
    exit();
}

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

    $hasAvatar = columnExists($conn, 'users', 'avatar_url');
    $selectAvatar = $hasAvatar ? 'avatar_url' : "'' AS avatar_url";

    $sql = "SELECT user_id, first_name, last_name, username, email, phone, $selectAddress, $selectCity, $selectProvince, $selectZip, $selectAvatar, created_at FROM users WHERE user_id = ? LIMIT 1";
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
        'avatar'     => $row['avatar_url'] ?? '',
        'memberSince'=> !empty($row['created_at']) ? date('F Y', strtotime($row['created_at'])) : null,
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

    // --- Change password ---
    if ($field === 'change_password') {
        $currentPwd = $input['current_password'] ?? '';
        $newPwd     = $input['new_password']     ?? '';

        if (strlen($newPwd) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'New password must be at least 8 characters']);
            exit();
        }

        // Fetch stored password hash
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ? LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($currentPwd, $row['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Current password is incorrect']);
            exit();
        }

        $newHash = password_hash($newPwd, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $upd->bind_param('si', $newHash, $userId);
        $ok = $upd->execute();
        $upd->close();

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update password']);
            exit();
        }

        echo json_encode(['success' => true]);
        exit();
    }


    if ($field === 'delete_account') {

        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete account']);
            exit();
        }

        session_unset();
        session_destroy();

        echo json_encode(['success' => true]);
        exit();
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown field']);
    exit();
}

echo json_encode(['error' => 'Method not allowed']);
?>