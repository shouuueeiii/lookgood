<?php
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';
require_once '../config.php';
require_once '../auth_user.php';
requireUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="css/profile.css"/>
</head>
<body>
    
    <section class="profile-info">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Guest'); ?>!</h2>
        <p>Email: <?= htmlspecialchars($_SESSION['email'] ?? 'Not logged in'); ?></p>
            <header>
        <h1>User Profile</h1>
        <nav>
            <a href="mainpage.php">HOME</a>
            <a href="../logout.php">LOGOUT</a>
            <a href="orders.php">ORDERS</a>
        </nav>
            </header>
    </section>
</body>
</html>