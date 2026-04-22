<?php
require_once __DIR__ . '/session_bootstrap.php';
lg_destroy_scoped_session('user');

header("Location: New%20folder/Login/user-login.php");
exit();
?>