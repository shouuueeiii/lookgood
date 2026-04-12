<?php
// forgot-password.php - no session, with database, original UI
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$email_input = $_POST['email'] ?? $_GET['email'] ?? '';
$showSuccess = false;

// Ensure the reset table exists before any cleanup or OTP work runs.
function ensurePasswordResetTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
                email VARCHAR(255) NOT NULL PRIMARY KEY,
                otp VARCHAR(6) NOT NULL,
                expires INT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
}

// Clean expired OTPs
function cleanupExpiredOTPs($conn) {
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE expires < ?");
    $now = time();
    $stmt->bind_param("i", $now);
    $stmt->execute();
    $stmt->close();
}
ensurePasswordResetTable($conn);
cleanupExpiredOTPs($conn);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---------- SEND OTP ----------
    if (isset($_POST['action']) && $_POST['action'] === 'send_otp') {
        $email = trim($_POST['email'] ?? '');
        $email_input = $email;

        if (empty($email)) {
            $error = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if email exists in users table
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 0) {
                $error = 'No account found with that email.';
            } else {
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expires = time() + 900; // 15 minutes

                // Store OTP in database (replace if exists)
                $stmt2 = $conn->prepare("REPLACE INTO password_resets (email, otp, expires) VALUES (?, ?, ?)");
                $stmt2->bind_param("ssi", $email, $otp, $expires);
                $stmt2->execute();
                $stmt2->close();

                // Build email HTML with pretty digits
                $digits = '';
                foreach (str_split($otp) as $d) {
                    $digits .= "<span style=\"display:inline-block;width:44px;height:52px;line-height:52px;text-align:center;font-size:26px;font-weight:700;background:#f5f5f5;border:1.5px solid #e0e0e0;border-radius:8px;margin:0 4px;color:#111;font-family:'DM Sans',Arial,sans-serif;\">$d</span>";
                }
                $htmlBody = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='margin:0;padding:0;background:#f9f9f9;font-family:Arial,sans-serif;'><table width='100%' cellpadding='0' cellspacing='0' style='background:#f9f9f9;padding:40px 0;'><tr><td align='center'><table width='520' cellpadding='0' cellspacing='0' style='background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);'><tr><td style='background:#111;padding:32px 40px;text-align:center;'><p style='margin:0;font-size:26px;font-weight:700;color:#fff;'>LookGood</p><p style='margin:6px 0 0;font-size:13px;color:#aaa;'>LOOKING GOOD HAS NEVER BEEN THIS CLEAR</p></td></tr><tr><td style='padding:40px 40px 32px;'><p style='margin:0 0 8px;font-size:22px;font-weight:600;color:#111;'>Reset your password</p><p style='margin:0 0 28px;font-size:15px;color:#555;'>Use the code below to reset your LookGood password. It expires in <strong>15 minutes</strong>.</p><div style='text-align:center;margin:0 0 28px;'>$digits</div><p style='margin:0;font-size:13px;color:#888;text-align:center;'>Didn't request this? You can safely ignore this email.</p></td></td></tr></table></td></tr></table></body></html>";

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'bleubartolome@gmail.com';
                    $mail->Password = 'omch ejwm ewdi wzcp';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->setFrom('bleubartolome@gmail.com', 'LookGood');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Your LookGood password reset code';
                    $mail->Body = $htmlBody;
                    $mail->AltBody = "Your LookGood password reset code is: $otp\n\nExpires in 15 minutes.";
                    $mail->send();

                    // Redirect to step 2
                    header("Location: forgot-password.php?step=2&email=" . urlencode($email));
                    exit;
                } catch (Exception $e) {
                    error_log('Mail error: ' . $mail->ErrorInfo);
                    $error = 'Could not send email. Please try again.';
                    // Delete the OTP record
                    $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                    $del->bind_param("s", $email);
                    $del->execute();
                    $del->close();
                }
            }
            $stmt->close();
        }
    }
    // ---------- VERIFY OTP ----------
    elseif ($_POST['action'] === 'verify_otp') {
        $email = trim($_POST['email'] ?? '');
        $digits = $_POST['otp_digit'] ?? [];
        $entered = '';
        for ($i = 0; $i < 6; $i++) {
            $entered .= trim($digits[$i] ?? '');
        }

        if (empty($email)) {
            $error = 'Session lost. Please request a new code.';
            $step = 1;
        } elseif (strlen($entered) !== 6 || !ctype_digit($entered)) {
            $error = 'Please enter all 6 digits.';
            $step = 2;
        } else {
            $stmt = $conn->prepare("SELECT otp, expires FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (time() > $row['expires']) {
                    $error = 'OTP has expired. Please request a new one.';
                    $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                    $del->bind_param("s", $email);
                    $del->execute();
                    $del->close();
                    $step = 1;
                } elseif ($entered === $row['otp']) {
                    $step = 3;
                } else {
                    $error = 'Incorrect OTP. Please try again.';
                    $step = 2;
                }
            } else {
                $error = 'No OTP request found. Please request a new code.';
                $step = 1;
            }
            $stmt->close();
        }
        $email_input = $email;
    }
    // ---------- RESET PASSWORD ----------
    elseif ($_POST['action'] === 'reset_password') {
        $email = trim($_POST['email'] ?? '');
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($email)) {
            $error = 'Session lost. Please restart.';
            $step = 1;
        } elseif (strlen($newPass) < 6) {
            $error = 'Password must be at least 6 characters.';
            $step = 3;
        } elseif ($newPass !== $confirmPass) {
            $error = 'Passwords do not match.';
            $step = 3;
        } else {
            // Verify OTP still valid
            $stmt = $conn->prepare("SELECT expires FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $valid = false;
            if ($row = $result->fetch_assoc()) {
                if (time() <= $row['expires']) $valid = true;
            }
            $stmt->close();

            if (!$valid) {
                $error = 'OTP expired or invalid. Please restart.';
                $step = 1;
            } else {
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $upd->bind_param("ss", $hashed, $email);
                $upd->execute();
                $upd->close();

                // Delete OTP record
                $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                $del->bind_param("s", $email);
                $del->execute();
                $del->close();

                $showSuccess = true;
                $step = 3;
            }
        }
        $email_input = $email;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>LookGood — Forgot Password</title>
    <link rel="stylesheet" href="../../css/User/forgot-password.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Spectral:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="page">

    <div class="brand-section">
        <div class="brand-content">
            <a href="../Homepage/index.php">
                <img src="../Resources/Logos/lookgood-black.png" class="brand-logo" alt="LookGood logo"
                    onerror="this.src='https://placehold.co/400x120?text=LookGood'">
            </a>
            <p class="brand-tagline">Looking good has never been this clear.</p>
        </div>
    </div>

    <div class="login-section">
        <div class="login-box">

            <?php if (!$showSuccess): ?>
                <div class="step-indicator">
                    <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'done' : '' ?>">
                        <?= $step > 1 ? '<i class="fas fa-check"></i>' : '1' ?>
                    </div>
                    <div class="step-line <?= $step > 1 ? 'done' : '' ?>"></div>
                    <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'done' : '' ?>">
                        <?= $step > 2 ? '<i class="fas fa-check"></i>' : '2' ?>
                    </div>
                    <div class="step-line <?= $step > 2 ? 'done' : '' ?>"></div>
                    <div class="step <?= $step >= 3 ? 'active' : '' ?>">3</div>
                </div>
            <?php endif; ?>

            <?php if ($showSuccess): ?>
                <div class="success-message-box">
                    <i class="fas fa-check-circle"></i>
                    <h3>Password Changed!</h3>
                    <p>Your password has been successfully reset.<br>You can now log in with your new password.</p>
                    <p>Redirecting to login page in <span id="countdown">3</span>s...</p>
                </div>
                <script>
                    let seconds = 3;
                    const el = document.getElementById('countdown');
                    const t  = setInterval(() => {
                        seconds--;
                        if (el) el.textContent = seconds;
                        if (seconds <= 0) { clearInterval(t); window.location.href = 'user-login.php?reset=1'; }
                    }, 1000);
                </script>

            <?php elseif ($step === 1): ?>
                <h2 class="login-title">Forgot Password</h2>
                <p class="login-subtitle">Enter your registered email and we'll send you a verification code.</p>
                <form method="POST" action="" novalidate id="sendOtpForm">
                    <input type="hidden" name="action" value="send_otp">
                    <div class="float-group">
                        <input type="email" class="float-input" id="email" name="email" placeholder=" " required
                            autocomplete="email" value="<?= htmlspecialchars($email_input) ?>">
                        <label class="float-label">Email Address</label>
                    </div>
                    <?php if ($error): ?>
                        <div class="error-message show">
                            <i class="fas fa-circle-exclamation"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="loginbtn" id="sendOtpBtn">
                        <span class="btn-text">Send Verification Code</span>
                        <span class="btn-loading" style="display:none;"><i class="fas fa-spinner fa-spin"></i> Sending...</span>
                        <i class="fas fa-paper-plane" style="margin-left:8px;"></i>
                    </button>
                </form>

            <?php elseif ($step === 2): ?>
                <h2 class="login-title">Check Your Email</h2>
                <p class="login-subtitle">
                    We sent a 6-digit code to <strong><?= htmlspecialchars($email_input) ?></strong>.
                    It expires in <strong>15 minutes</strong>.
                </p>
                <form method="POST" action="" novalidate id="otpForm">
                    <input type="hidden" name="action" value="verify_otp">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email_input) ?>">
                    <div class="otp-group">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <input type="text" class="otp-box"
                                name="otp_digit[<?= $i ?>]"
                                maxlength="1" inputmode="numeric" pattern="[0-9]"
                                autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>"
                                data-index="<?= $i ?>">
                        <?php endfor; ?>
                    </div>
                    <?php if ($error): ?>
                        <div class="error-message show">
                            <i class="fas fa-circle-exclamation"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="loginbtn" id="otpSubmitBtn" disabled>Verify Code</button>
                </form>
                <p class="resend-text">Didn't receive it?
                <form method="POST" action="" style="display:inline;" id="resendForm">
                    <input type="hidden" name="action" value="send_otp">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email_input) ?>">
                    <button type="submit" class="link-btn" id="resendBtn">Resend Code</button>
                </form></p>

            <?php elseif ($step === 3): ?>
                <h2 class="login-title">Set New Password</h2>
                <p class="login-subtitle">Choose a strong new password for your account.</p>
                <form method="POST" action="" novalidate>
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email_input) ?>">
                    <div class="float-group">
                        <input type="password" class="float-input" id="new_password" name="new_password"
                            placeholder=" " required autocomplete="new-password">
                        <label class="float-label">New Password</label>
                        <button type="button" class="toggle-pw" data-target="new_password" aria-label="Show password">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    <div class="float-group">
                        <input type="password" class="float-input" id="confirm_password" name="confirm_password"
                            placeholder=" " required autocomplete="new-password">
                        <label class="float-label">Confirm Password</label>
                        <button type="button" class="toggle-pw" data-target="confirm_password" aria-label="Show password">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    <div class="strength-wrap">
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <span class="strength-label" id="strengthLabel"></span>
                    </div>
                    <?php if ($error): ?>
                        <div class="error-message show">
                            <i class="fas fa-circle-exclamation"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="loginbtn">Reset Password</button>
                </form>
            <?php endif; ?>

            <?php if (!$showSuccess): ?>
                <div class="separator"><hr><span>Remember your password?</span><hr></div>
                <a href="user-login.php?clear_fp=1" class="signup-link-btn">Back to Log In</a>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="../../Actions/User/forgot-password.js"></script>
<script>
    // OTP box UX: auto-advance, backspace, paste
    document.querySelectorAll('.otp-box').forEach((box, idx, boxes) => {
        box.addEventListener('input', () => {
            box.value = box.value.replace(/\D/g, '').slice(-1);
            if (box.value && idx < boxes.length - 1) boxes[idx + 1].focus();
            const allFilled = [...boxes].every(b => b.value.length === 1);
            const btn = document.getElementById('otpSubmitBtn');
            if (btn) btn.disabled = !allFilled;
        });
        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !box.value && idx > 0) boxes[idx - 1].focus();
        });
        box.addEventListener('paste', (e) => {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            [...paste].slice(0, 6).forEach((ch, i) => { if (boxes[idx + i]) boxes[idx + i].value = ch; });
            boxes[Math.min(idx + paste.length, boxes.length - 1)].focus();
            const allFilled = [...boxes].every(b => b.value.length === 1);
            const btn = document.getElementById('otpSubmitBtn');
            if (btn) btn.disabled = !allFilled;
        });
    });

    document.getElementById('sendOtpForm')?.addEventListener('submit', function () {
        const btn = document.getElementById('sendOtpBtn');
        btn.querySelector('.btn-text').style.display    = 'none';
        btn.querySelector('.btn-loading').style.display = 'inline';
        btn.querySelector('.fa-paper-plane').style.display = 'none';
        btn.disabled = true;
    });

    document.getElementById('resendForm')?.addEventListener('submit', function () {
        const btn = document.getElementById('resendBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        btn.disabled  = true;
    });
</script>
</body>
</html>