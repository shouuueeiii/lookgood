<?php
session_start();
header('Content-Type: application/json');

require_once($_SERVER['DOCUMENT_ROOT'] . '/lookgood/config.php');

function ensureUsersPhoneColumn(mysqli $conn): void {
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER email");
    }
    if ($result) $result->free();
}
ensureUsersPhoneColumn($conn);

$input = json_decode(file_get_contents('php://input'), true);
$otp_input = trim($input['otp'] ?? '');

if (empty($_SESSION['pending_user']) || empty($_SESSION['registration_otp'])) {
    echo json_encode(['success' => false, 'error' => 'Session expired. Please start over.']);
    exit;
}

if (!isset($_SESSION['pending_user']['phone'])) {
    echo json_encode(['success' => false, 'error' => 'Missing phone number. Please go back and register again.']);
    exit;
}

if (empty($otp_input) || !preg_match('/^\d{6}$/', $otp_input)) {
    echo json_encode(['success' => false, 'error' => 'Please enter a valid 6‑digit code.']);
    exit;
}

if (time() > ($_SESSION['registration_otp_expiry'] ?? 0)) {
    echo json_encode(['success' => false, 'error' => 'Code has expired. Please go back and request a new one.']);
    exit;
}

if ($otp_input != $_SESSION['registration_otp']) {
    echo json_encode(['success' => false, 'error' => 'Incorrect code. Please try again.']);
    exit;
}

$u = $_SESSION['pending_user'];

// Final duplicate check (email, username, phone)
$check = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR username = ? OR phone = ? LIMIT 1");
$check->bind_param("sss", $u['email'], $u['username'], $u['phone']);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    $check->close();
    echo json_encode(['success' => false, 'error' => 'Email, username, or phone number was just taken. Please go back and try different details.']);
    exit;
}
$check->close();

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, username, password, email_verified) VALUES (?, ?, ?, ?, ?, ?, 1)");
$stmt->bind_param("ssssss", $u['firstName'], $u['lastName'], $u['email'], $u['phone'], $u['username'], $u['password']);

if ($stmt->execute()) {
    // Clear registration session data
    unset($_SESSION['pending_user'], $_SESSION['registration_otp'],
          $_SESSION['registration_otp_expiry'], $_SESSION['registration_email'],
          $_SESSION['last_otp_request'], $_SESSION['captcha_code']);
    
    // Optional: automatically log in the user
    // $_SESSION['user_id'] = $conn->insert_id;
    
    echo json_encode(['success' => true]);
} else {
    error_log('Account creation failed: ' . $conn->error);
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
}
$stmt->close();
exit;
?>