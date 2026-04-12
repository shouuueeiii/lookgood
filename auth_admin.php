<?php
if (!defined('LG_SESSION_SCOPE')) {
    define('LG_SESSION_SCOPE', 'admin');
}
require_once __DIR__ . '/session_bootstrap.php';

function isLoggedIn() {
    return isset($_SESSION['email']) && isset($_SESSION['role']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function requireLogin($redirect = '../index.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit();
    }
}

function requireAdmin($redirect = '../index.php') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') return;

    // Recover admin session for fallback admin credential flow.
    $sessionEmail = trim((string)($_SESSION['email'] ?? ''));
    if ($sessionEmail !== '' && defined('ADMIN_EMAIL') && strcasecmp($sessionEmail, (string)ADMIN_EMAIL) === 0) {
        $_SESSION['role'] = 'admin';
        $_SESSION['user_id'] = (int)($_SESSION['user_id'] ?? 0);
        $_SESSION['first_name'] = (string)($_SESSION['first_name'] ?? 'Admin');
        $_SESSION['last_name'] = (string)($_SESSION['last_name'] ?? 'User');
        return;
    }

    // Recover admin role for DB-backed admin accounts if user_id exists.
    $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
    if ($sessionUserId > 0) {
        if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof mysqli)) {
            require __DIR__ . '/config.php';
        }

        $conn = $GLOBALS['conn'] ?? null;
        if ($conn instanceof mysqli) {
            $stmt = $conn->prepare('SELECT user_id, first_name, last_name, email, role FROM users WHERE user_id = ? LIMIT 1');
        } else {
            $stmt = false;
        }

        if ($stmt) {
            $stmt->bind_param('i', $sessionUserId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row && strtolower((string)($row['role'] ?? '')) === 'admin') {
                $_SESSION['user_id'] = (int)$row['user_id'];
                $_SESSION['first_name'] = (string)($row['first_name'] ?? '');
                $_SESSION['last_name'] = (string)($row['last_name'] ?? '');
                $_SESSION['email'] = (string)($row['email'] ?? '');
                $_SESSION['role'] = 'admin';
                return;
            }
        }
    }

    header("Location: $redirect");
    exit();
}
?>