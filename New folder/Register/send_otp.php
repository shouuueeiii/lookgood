<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function ensureUsersPhoneColumn(mysqli $conn): void {
    $check = $conn->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'phone' LIMIT 1");
    if (!$check) {
        return;
    }

    $check->execute();
    $check->store_result();
    $exists = $check->num_rows > 0;
    $check->close();

    if (!$exists) {
        $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER email");
    }
}

ensureUsersPhoneColumn($conn);

$input = json_decode(file_get_contents('php://input'), true);
$isResend = !empty($_SESSION['pending_user']);

// Rate limiting
if (isset($_SESSION['last_otp_request']) && (time() - $_SESSION['last_otp_request']) < 60) {
    echo json_encode(['success' => false, 'error' => 'Please wait 1 minute before requesting another code.']);
    exit;
}

// ========== CAPTCHA VALIDATION PARA SA LAHAT (first time o resend) ==========
$captcha = strtolower(trim($input['captcha'] ?? ''));
if (empty($captcha)) {
    echo json_encode(['success' => false, 'error' => 'Please complete the CAPTCHA.']);
    exit;
}
if (!isset($_SESSION['captcha_code']) || $captcha !== strtolower($_SESSION['captcha_code'])) {
    unset($_SESSION['captcha_code']);
    echo json_encode(['success' => false, 'error' => 'Incorrect CAPTCHA. Please try again.', 'refreshCaptcha' => true]);
    exit;
}
unset($_SESSION['captcha_code']); // ubos na, gamitin lang once

// ========== FIRST REQUEST (full validation) ==========
if (!$isResend) {
    $firstName = trim($input['firstName'] ?? '');
    $lastName  = trim($input['lastName'] ?? '');
    $email     = trim($input['email'] ?? '');
    $phone     = trim($input['phone'] ?? '');    
    $username  = trim($input['username'] ?? '');
    $password  = $input['password'] ?? '';
    $confirm   = $input['confirmPassword'] ?? '';

    // Field validations (same as dati, pero ALISIN mo na ang CAPTCHA dito)
    if (!$firstName || !$lastName || !$email || !$phone || !$username || !$password || !$confirm) {
        echo json_encode(['success' => false, 'error' => 'All fields are required.']); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']); exit;
    }
    if (!preg_match('/^[0-9+\-\s()]{8,20}$/', $phone)) {
        echo json_encode(['success' => false, 'error' => 'Please enter a valid phone number (8-20 digits, +, -, spaces allowed).']); exit;
    }
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        echo json_encode(['success' => false, 'error' => 'Username must be 3–20 characters (letters, numbers, underscore).']); exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters.']); exit;
    }
    if ($password !== $confirm) {
        echo json_encode(['success' => false, 'error' => 'Passwords do not match.']); exit;
    }

    // Duplicate checks (email, username, phone)
    $checkEmail = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) {
        $checkEmail->close();
        echo json_encode(['success' => false, 'error' => 'That email is already registered.']); exit;
    }
    $checkEmail->close();

    $checkUser = $conn->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
    $checkUser->bind_param("s", $username);
    $checkUser->execute();
    $checkUser->store_result();
    if ($checkUser->num_rows > 0) {
        $checkUser->close();
        echo json_encode(['success' => false, 'error' => 'That username is already taken.']); exit;
    }
    $checkUser->close();

    $checkPhone = $conn->prepare("SELECT user_id FROM users WHERE phone = ? LIMIT 1");
    $checkPhone->bind_param("s", $phone);
    $checkPhone->execute();
    $checkPhone->store_result();
    if ($checkPhone->num_rows > 0) {
        $checkPhone->close();
        echo json_encode(['success' => false, 'error' => 'That phone number is already registered.']); exit;
    }
    $checkPhone->close();

    // Store pending user
    $_SESSION['pending_user'] = [
        'firstName' => $firstName,
        'lastName'  => $lastName,
        'email'     => $email,
        'phone'     => $phone,
        'username'  => $username,
        'password'  => password_hash($password, PASSWORD_DEFAULT),
    ];
} else {
    // RESEND: tignan lang kung may pending user pa
    if (empty($_SESSION['pending_user']['email'])) {
        echo json_encode(['success' => false, 'error' => 'Session expired. Please register again.']); exit;
    }
    // Optional: kung gusto mong i-validate ang email na pinasa laban sa pending email
    $submittedEmail = trim($input['email'] ?? '');
    if ($submittedEmail && $submittedEmail !== $_SESSION['pending_user']['email']) {
        echo json_encode(['success' => false, 'error' => 'Email mismatch. Please start over.']); exit;
    }
}

// ========== Dito na mag-generate ng OTP at magpadala ng email (same as dati) ==========
$u = $_SESSION['pending_user'];
$fullName = trim($u['firstName'] . ' ' . $u['lastName']);
$email = $u['email'];
$otp = rand(100000, 999999);

$_SESSION['registration_otp'] = $otp;
$_SESSION['registration_otp_expiry'] = time() + 900;
$_SESSION['registration_email'] = $email;
$_SESSION['last_otp_request'] = time();

// ... the rest of your email sending code (unchanged)

// Generate OTP and send email
$u = $_SESSION['pending_user'];
$fullName = trim($u['firstName'] . ' ' . $u['lastName']);
$email = $u['email'];
$otp = rand(100000, 999999);

$_SESSION['registration_otp'] = $otp;
$_SESSION['registration_otp_expiry'] = time() + 900;
$_SESSION['registration_email'] = $email;
$_SESSION['last_otp_request'] = time();

// Build HTML email with styled digits (unchanged)
$digits = '';
foreach (str_split((string)$otp) as $d) {
    $digits .= "<span style=\"display:inline-block;width:44px;height:52px;line-height:52px;text-align:center;
                font-size:26px;font-weight:700;background:#f5f5f5;border:1.5px solid #e0e0e0;
                border-radius:8px;margin:0 4px;color:#111;font-family:'DM Sans',Arial,sans-serif;\">$d</span>";
}

$htmlBody = <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f9f9f9;font-family:'DM Sans',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f9f9f9;padding:40px 0;">
  <tr><td align="center">
    <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
      <tr><td style="background:#111;padding:32px 40px;text-align:center;">
          <p style="margin:0;font-size:26px;font-weight:700;color:#fff;">Verification Code</p>
          <p style="margin:6px 0 0;font-size:13px;color:#aaa;">Creating an Account</p>
        </td>
      </tr>
       <tr><td style="padding:40px 40px 32px;">
          <p style="margin:0 0 8px;font-size:22px;font-weight:600;color:#111;text-align:center;">Verify your email, {$fullName}</p>
          <p style="margin:0 0 28px;font-size:15px;color:#555;text-align:center;">Use the code below to complete your account registration.</p>
            <p style="margin:0 0 28px;font-size:15px;color:#555;text-align:center;">It expires in <strong>15 minutes</strong>.</p>
          <div style="text-align:center;margin:0 0 28px;">{$digits}</div>
          <p style="margin:0;font-size:13px;color:#888;text-align:center;">Didn't sign up? You can safely ignore this email.</p>
        </td>
      </tr>
       <tr><td style="background:#f5f5f5;padding:20px 40px;text-align:center;">
          <p style="margin:0;font-size:12px;color:#aaa;">© 2026 LookGood Frames · All rights reserved</p>
        </td>
      </table>
    </table>
    </td>
  </tr>
</table>
</body></html>
HTML;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'bleubartolome@gmail.com'; //'lookgoodframes.ph@gmail.com';
    $mail->Password   ='omch ejwm ewdi wzcp';  //'jkdp hfft sklu aoht';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('bleubartolome@gmail.com', 'LookGood Frames');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'LookGood Verification Code';
    $mail->Body    = $htmlBody;
    $mail->AltBody = "Hi $fullName,\n\nYour LookGood verification code is: $otp\n\nExpires in 15 minutes.\nIf you didn't sign up, ignore this email.";

    $mail->send();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('PHPMailer error: ' . $mail->ErrorInfo);
    if (!$isResend) {
        unset($_SESSION['pending_user'], $_SESSION['registration_otp'],
              $_SESSION['registration_otp_expiry'], $_SESSION['registration_email'],
              $_SESSION['last_otp_request']);
    }
    echo json_encode(['success' => false, 'error' => 'Could not send email. Please try again.']);
}
exit;
?>