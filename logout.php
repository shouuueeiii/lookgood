<?php
require_once __DIR__ . '/session_bootstrap.php';
lg_destroy_scoped_session('user');

// ✅ Tamang redirect (nasa loob ng Homepage folder ang index.php)
header("Location: Homepage/index.php");
exit();
?>