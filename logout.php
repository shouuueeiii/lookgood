<?php
session_start();
$_SESSION = [];
session_destroy();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ✅ Tamang redirect (nasa loob ng Homepage folder ang index.php)
header("Location: Homepage/index.php");
exit();
?>