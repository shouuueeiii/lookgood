<?php
require_once __DIR__ . '/../session_bootstrap.php';
require_once __DIR__ . '/../config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$debug_log = __DIR__ . '/login_debug.log';

function debug_log($message) {
    global $debug_log;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($debug_log, "[$timestamp] $message\n", FILE_APPEND);
}

debug_log("=== LOGIN ATTEMPT START ===");
debug_log("POST data: " . print_r($_POST, true));

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $emailOrUsername = trim($_POST['emailOrUsername'] ?? '');
    $password        = $_POST['password'] ?? '';

    debug_log("Attempting login for: " . $emailOrUsername);

    if (empty($emailOrUsername)) {
        $error = 'Email or username is required.';
        debug_log("Error: Empty email/username");
    } elseif (empty($password)) {
        $error = 'Password is required.';
        debug_log("Error: Empty password");
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
        debug_log("Error: Password too short");
    } else {

        // =========================================================
        // FIX 1: SESSION SCOPE — call BEFORE session_regenerate_id
        // The original code called lg_switch_session_scope AFTER
        // prepare/execute, but a missing or broken scope switch was
        // silently wiping session data on some server configs.
        // We now start a clean scope before doing anything with $_SESSION.
        // =========================================================

        // =========================================================
        // 1. ADMIN LOGIN (FROM admin TABLE)
        // =========================================================
        debug_log("Checking admin table...");


        $admin_stmt = $conn->prepare("
            SELECT admin_id, admin_name, email, password, position
            FROM admin
            WHERE email = ? OR admin_name = ?
            LIMIT 1
        ");

        $admin = null;

        if (!$admin_stmt) {
            debug_log("Admin query prepare failed: " . $conn->error);
        } else {
            $admin_stmt->bind_param("ss", $emailOrUsername, $emailOrUsername);
            $admin_stmt->execute();
            $admin_result = $admin_stmt->get_result();
            $admin        = $admin_result->fetch_assoc();
            $admin_stmt->close();

            debug_log("Admin query result: " . ($admin ? "Found: " . $admin['email'] : "Not found"));
        }

        // FIX 3: password_verify is called ONCE in a clear if-block.
        // The original code called password_verify inside the fetch block
        // AND again in the outer if — fine logically, but if $admin was
        // set to a falsy-but-truthy value (e.g. empty array from a bad
        // fetch) it could silently fail. We now guard explicitly.

        if ($admin && !empty($admin['password']) && password_verify($password, $admin['password'])) {
            debug_log("ADMIN LOGIN SUCCESSFUL for: " . $admin['email']);

            // FIX 4: Switch session scope BEFORE session_regenerate_id.
            // lg_switch_session_scope internally may call session_write_close
            // or start a new session; regenerating the ID after the scope
            // switch ensures the new ID belongs to the admin session.
            if (function_exists('lg_switch_session_scope')) {
                lg_switch_session_scope('admin');
                debug_log("lg_switch_session_scope('admin') called");
            } else {
                // FIX 5: If the scope helper does not exist, manually
                // clear any leftover user session data so an admin login
                // never inherits a stale user_id from a previous session.
                debug_log("WARNING: lg_switch_session_scope not found — clearing session manually");
                session_unset();
            }

            session_regenerate_id(true);
            debug_log("New session ID: " . session_id());

            $_SESSION['admin_id']   = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['admin_name'];
            $_SESSION['email']      = $admin['email'];
            $_SESSION['position']   = $admin['position'];

            session_write_close();

            debug_log("Session written. admin_id=" . $admin['admin_id'] . " position=" . $admin['position']);
            debug_log("Redirecting to admin dashboard");

            header("Location: /lookgood/admin/dashboard.php");
            exit();
        }

        debug_log("Checking users table...");

        $user_stmt = $conn->prepare("
            SELECT user_id, first_name, last_name, email, username, password, role, status, ban_reason
            FROM users
            WHERE email = ? OR username = ?
            LIMIT 1
        ");

        if (!$user_stmt) {
            debug_log("User query prepare failed: " . $conn->error);
            $error = 'A server error occurred. Please try again.';
        } else {
            $user_stmt->bind_param("ss", $emailOrUsername, $emailOrUsername);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();

            debug_log("User query rows found: " . $user_result->num_rows);

            if ($user_result->num_rows === 0) {
                $error = 'Account not found!';
                debug_log("Error: No user account found");
            } else {
                $user = $user_result->fetch_assoc();
                debug_log("User found: " . $user['email'] . ", Role: " . $user['role']);

                if (!password_verify($password, $user['password'])) {
                    $error = 'Invalid email/username or password.';
                    debug_log("Error: Invalid password");
                } elseif ($user['role'] !== 'user') {
                    $error = 'Invalid email/username or password.';
                    debug_log("Error: Role is not 'user', it's: " . $user['role']);
                } elseif ($user['status'] === 'Banned') {
                    // Account is banned — redirect to banned page with reason from DB
                    debug_log("Banned account login attempt: " . $user['email']);
                    session_destroy();
                    $banReason = !empty($user['ban_reason'])
                        ? urlencode($user['ban_reason'])
                        : urlencode('violation of our Terms of Service');
                    header("Location: banned.php?reason=$banReason");
                    exit();
                } else {
                    debug_log("USER LOGIN SUCCESSFUL for: " . $user['email']);

                    if (function_exists('lg_switch_session_scope')) {
                        lg_switch_session_scope('user');
                        debug_log("lg_switch_session_scope('user') called");
                    } else {
                        session_unset();
                    }

                    session_regenerate_id(true);
                    debug_log("New session ID: " . session_id());

                    $_SESSION['user_id']    = $user['user_id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name']  = $user['last_name'];
                    $_SESSION['email']      = $user['email'];
                    $_SESSION['role']       = $user['role'];

                    // JS-readable cookie so cart-standalone.js knows user is logged in
                    // even on pages that don't inject window.LG_CHAT_USER
                    setcookie('lg_logged_in', '1', [
                        'expires'  => 0,
                        'path'     => '/lookgood',
                        'secure'   => false,
                        'httponly' => false,
                        'samesite' => 'Lax',
                    ]);

                    $redirect = trim((string)($_SESSION['redirect_after_login'] ?? ''));
                    unset($_SESSION['redirect_after_login']);

                    // FIX 7: Same session_write_close flush for user logins.
                    session_write_close();

                    if ($redirect !== '' && strpos($redirect, '/lookgood/') !== false) {
                        debug_log("Redirecting to: " . $redirect);
                        header('Location: ' . $redirect);
                    } else {
                        debug_log("Redirecting to homepage");
                        header('Location: /lookgood/home/index.php');
                    }
                    exit();
                }
            }
            $user_stmt->close();
        }
    }

    $conn->close();
}

debug_log("=== LOGIN ATTEMPT END ===\n");
?>

<?php if (isset($_GET['debug']) && $_GET['debug'] == 1 && file_exists($debug_log)): ?>
<div style="background: #f0f0f0; border: 1px solid #ccc; margin: 20px; padding: 10px; font-family: monospace; font-size: 12px;">
    <h3>Debug Log (last 20 lines)</h3>
    <pre><?php echo htmlspecialchars(implode('', array_slice(file($debug_log), -20))); ?></pre>
</div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>LookGood — Log In</title>
  <link rel="stylesheet" href="/lookgood/css/User/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="top">
<div class="page">
  <div class="brand-section">
    <div class="brand-content">
      <a href="index.php">
        <img src="/lookgood/home/Resources/Logos/lookgood-black.png" class="brand-logo no-modal" alt="LookGood logo" width="400">
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

        <a href="user-signup.php" class="signup-link-btn">Create an Account</a>
      </form>
    </div>
  </div>
</div>
  <script src="/lookgood/userActions/login.js"></script>
</body>
</html>