<?php
if (!defined('LG_SESSION_SCOPE')) {
    define('LG_SESSION_SCOPE', 'user');
}
require_once __DIR__ . '/session_bootstrap.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) || (isset($_SESSION['email']) && isset($_SESSION['role']));
}

function isUser() {
    return isLoggedIn() && $_SESSION['role'] === 'user';
}

function requireLogin($redirect = '../New%20folder/Login/user-login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit();
    }
}

function requireUser($redirect = '../New%20folder/Login/user-login.php') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'user' && isset($_SESSION['user_id'])) return;

    $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
    if ($sessionUserId > 0) {
        require_once __DIR__ . '/config.php';
        $stmt = $conn->prepare('SELECT user_id, first_name, last_name, email, role FROM users WHERE user_id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $sessionUserId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row && (($row['role'] ?? '') === 'user')) {
                $_SESSION['user_id'] = (int)$row['user_id'];
                $_SESSION['first_name'] = (string)($row['first_name'] ?? '');
                $_SESSION['last_name'] = (string)($row['last_name'] ?? '');
                $_SESSION['email'] = (string)($row['email'] ?? '');
                $_SESSION['role'] = 'user';
                return;
            }
        }
    }

    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? null;
    header("Location: $redirect");
    exit();
}
?>