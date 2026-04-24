<?php
if (!defined('LG_SESSION_SCOPE')) {
    define('LG_SESSION_SCOPE', 'admin');
}
require_once __DIR__ . '/../session_bootstrap.php';

lg_destroy_scoped_session('admin');

header('Location: /lookgood/home/index.php');
exit();
