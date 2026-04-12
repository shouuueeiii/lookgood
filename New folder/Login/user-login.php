<?php
require_once __DIR__ . '/../../session_bootstrap.php';
require_once '../../config.php';

$error = '';

// --- MOCK BANNED ACCOUNT (for testing ban page) ---
// This works without any database columns
$mock_banned_email = 'banned@example.com';
$mock_banned_pass = 'banned123';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrUsername = trim($_POST['emailOrUsername'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($emailOrUsername)) {
        $error = 'Email or username is required.';
    } elseif (empty($password)) {
        $error = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // FIRST: Check if it's the mock banned account
        if ($emailOrUsername === $mock_banned_email && $password === $mock_banned_pass) {
            session_destroy();
            $reason = urlencode('fraudulent activity and repeated policy violations');
            header("Location: banned.php?reason=$reason");
            exit();
        }
        if($emailOrUsername === ADMIN_EMAIL && password_verify($password, ADMIN_PASSWORD)) {
          lg_switch_session_scope('admin');
            session_regenerate_id(true);

            $_SESSION['user_id']    = 0; 
            $_SESSION['first_name'] = 'Admin';
            $_SESSION['last_name']  = '';
            $_SESSION['email']      = ADMIN_EMAIL;
            $_SESSION['role']       = 'admin';

            header("Location: ../../admin/dashboard.php");
            exit();
        } else {
            $error = 'Invalid email/username or password.';
        }

        // Normal database login (without status/ban_reason columns)
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, role 
                                FROM users 
                                WHERE email = ? OR username = ? 
                                LIMIT 1");
        $stmt->bind_param("ss", $emailOrUsername, $emailOrUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = 'Account not found!';
        } else {
            $user = $result->fetch_assoc();
            if (!password_verify($password, $user['password'])) {
                $error = 'Invalid email/username or password.';
            } elseif ($user['role'] !== 'user') {
                $error = 'Invalid email/username or password.';
            } else {
                // Normal login success
              lg_switch_session_scope('user');
                $_SESSION['user_id']    = $user['user_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name']  = $user['last_name'];
                $_SESSION['email']      = $user['email'];
                $_SESSION['role']       = $user['role'];

              $redirect = trim((string)($_SESSION['redirect_after_login'] ?? ''));
              unset($_SESSION['redirect_after_login']);
              if ($redirect !== '' && strpos($redirect, '/lookgood/') !== false) {
                header('Location: ' . $redirect);
              } else {
                header('Location: ../Homepage/index.php');
              }
                exit;
            }
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>LookGood — Log In</title>
  <link rel="stylesheet" href="../../css/User/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="top">
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
      <h2 class="login-title">Welcome back!</h2>
      <p class="login-subtitle">Log in to your LookGood account</p>

      <form id="loginForm" method="POST" action="" novalidate>
        <div class="float-group">
          <input type="text" class="float-input" id="emailOrUsername" name="emailOrUsername"
                 placeholder=" " required autocomplete="username"
                 value="<?= htmlspecialchars($_POST['emailOrUsername'] ?? '') ?>">
          <label class="float-label">Email or Username</label>
        </div>

        <div class="float-group">
          <input type="password" class="float-input" id="password" name="password"
                 placeholder=" " required autocomplete="current-password">
          <label class="float-label">Password</label>
          <button type="button" class="toggle-pw" id="togglePassword" aria-label="Show password">
            <i class="fas fa-eye-slash"></i>
          </button>
        </div>

        <div class="login-options">
          <label class="remember-me">
            <input type="checkbox" name="remember"> Remember me
          </label>
          <a href="forgot-password.php?fresh=1" class="forgot-link">Forgot password?</a>
        </div>

        <div id="formFeedback" class="error-message <?= $error ? 'show' : '' ?>">
          <i class="fas fa-circle-exclamation"></i>
          <span id="errorText"><?= htmlspecialchars($error) ?></span>
        </div>

        <button type="submit" class="loginbtn">Log In</button>

        <div class="separator">
          <hr><span>Don't have an account yet?</span><hr>
        </div>

        <a href="../Register/user-signup.php" class="signup-link-btn">Create an Account</a>
      </form>
    </div>
  </div>
</div>
  <script src="../../Actions/User/login.js" ></script>
</body>
</html>