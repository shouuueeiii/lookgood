<?php
require_once __DIR__ . '/../session_bootstrap.php';
lg_destroy_scoped_session('user');

// Redirect sa tamang homepage
header("Location: Homepage/index.php");
exit();
?>