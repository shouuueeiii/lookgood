<?php
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';
require_once '../config.php';
require_once '../auth_user.php';
requireUser();

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];

$stmt = $conn->prepare("DELETE FROM carts WHERE user_id=? AND product_id=?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();

header('Location: mainpage.php'); 
exit;
