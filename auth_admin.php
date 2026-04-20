<?php


if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'admin');
require_once __DIR__ . '/session_bootstrap.php';

define('ROLE_HEAD',     'head');
define('ROLE_INV_ORD',  'inventory_orderAdmin');
define('ROLE_MSG_FB',   'message_feedbackAdmin');


function requireAdmin(string $redirectTo = 'New%20folder/Login/user-login.php'): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . $redirectTo);
        exit();
    }
}

function requireRole(array $allowedRoles, string $redirectTo = '../admin/dashboard.php'): void
{
    $position = $_SESSION['position'] ?? '';

    // Head admin always has full access
    if ($position === ROLE_HEAD) return;

    if (!in_array($position, $allowedRoles, true)) {
        header('Location: ' . $redirectTo . (str_contains($redirectTo, '?') ? '&' : '?') . 'access_denied=1');
        exit();
    }
}

function isHead(): bool
{
    return ($_SESSION['position'] ?? '') === ROLE_HEAD;
}

function isInventoryOrder(): bool
{
    $p = $_SESSION['position'] ?? '';
    return $p === ROLE_HEAD || $p === ROLE_INV_ORD;
}

function isMessageFeedback(): bool
{
    $p = $_SESSION['position'] ?? '';
    return $p === ROLE_HEAD || $p === ROLE_MSG_FB;
}

function canAccess(string $page): bool
{
    $position = $_SESSION['position'] ?? '';

    if ($position === ROLE_HEAD) return true;

    $matrix = [
        ROLE_INV_ORD => ['dashboard', 'product', 'orders', 'notifications'],
        ROLE_MSG_FB  => ['dashboard', 'messages', 'feedback', 'notifications'],
    ];

    return in_array($page, $matrix[$position] ?? [], true);
}

function getAdminPosition(): string
{
    return $_SESSION['position'] ?? '';
}
