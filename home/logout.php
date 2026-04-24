<?php
require_once __DIR__ . '/../session_bootstrap.php';
lg_destroy_scoped_session('user');

// Clear the JS-readable login cookie
setcookie('lg_logged_in', '', [
    'expires'  => time() - 3600,
    'path'     => '/lookgood',
    'secure'   => false,
    'httponly' => false,
    'samesite' => 'Lax',
]);

// Redirect sa tamang homepage
header("Location: /lookgood/home/index.php");
exit();
?>