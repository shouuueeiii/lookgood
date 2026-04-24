<?php
require_once __DIR__ . '/session_bootstrap.php';
lg_destroy_scoped_session('user');

header("Location: /lookgood/home/user-login.php");
exit();
?>