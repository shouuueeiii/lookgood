<?php
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';
header('Content-Type: application/json');

$cart = $_SESSION['cart'] ?? [];
echo json_encode($cart);
?>