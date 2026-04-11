<?php
session_start();

// Destroy session
$_SESSION = [];
session_destroy();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect sa tamang homepage
header("Location: Homepage/index.php");
exit();
?>