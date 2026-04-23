<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$data    = json_decode(file_get_contents('php://input'), true);
$name    = trim($data['name']    ?? '');
$email   = trim($data['email']   ?? '');
$subject = trim($data['subject'] ?? '');
$content = trim($data['content'] ?? '');

if (!$name || !$email || !$subject || !$content) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
    exit;
}

$name    = htmlspecialchars($name,    ENT_QUOTES, 'UTF-8');
$email   = htmlspecialchars($email,   ENT_QUOTES, 'UTF-8');
$subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$content = nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));

$htmlBody = <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f9f9f9;font-family:'DM Sans',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f9f9f9;padding:40px 0;">
  <tr><td align="center">
    <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
      <tr><td style="background:#111;padding:32px 40px;text-align:center;">
        <p style="margin:0;font-size:26px;font-weight:700;color:#fff;">New Message</p>
        <p style="margin:6px 0 0;font-size:13px;color:#aaa;">Via Contact Form</p>
      </td></tr>
      <tr><td style="padding:40px 40px 32px;">
        <p style="margin:0 0 20px;font-size:22px;font-weight:600;color:#111;text-align:center;">You have a new message!</p>
        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#555;margin-bottom:24px;">
          <tr>
            <td style="padding:8px 0;border-bottom:1px solid #f0f0f0;width:90px;color:#aaa;">From</td>
            <td style="padding:8px 0;border-bottom:1px solid #f0f0f0;color:#111;font-weight:600;">$name</td>
          </tr>
          <tr>
            <td style="padding:8px 0;border-bottom:1px solid #f0f0f0;color:#aaa;">Email</td>
            <td style="padding:8px 0;border-bottom:1px solid #f0f0f0;color:#111;">$email</td>
          </tr>
          <tr>
            <td style="padding:8px 0;color:#aaa;">Subject</td>
            <td style="padding:8px 0;color:#111;">$subject</td>
          </tr>
        </table>
        <div style="background:#f9f9f9;border-radius:12px;padding:20px 24px;font-size:14px;color:#333;line-height:1.7;">$content</div>
        <p style="margin:24px 0 0;font-size:13px;color:#888;text-align:center;">Reply to this email to respond to $name directly.</p>
      </td></tr>
      <tr><td style="background:#f5f5f5;padding:20px 40px;text-align:center;">
        <p style="margin:0;font-size:12px;color:#aaa;">© 2026 LookGood Frames · All rights reserved</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
HTML;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'bleubartolome@gmail.com';
    $mail->Password   = 'omch ejwm ewdi wzcp';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('bleubartolome@gmail.com', 'LookGood Frames');
    $mail->addAddress('bleubartolome@gmail.com', 'Bleu');
    $mail->addReplyTo($email, $name);
    $mail->isHTML(true);
    $mail->Subject = '[Contact Form] ' . $subject;
    $mail->Body    = $htmlBody;
    $mail->AltBody = "New message from: $name\nEmail: $email\nSubject: $subject\n\n$content";

    $mail->send();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('PHPMailer contact error: ' . $mail->ErrorInfo);
    echo json_encode(['success' => false, 'error' => 'Could not send email. Please try again.']);
}
exit;
?>