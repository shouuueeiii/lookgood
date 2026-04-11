<?php
session_start();
require_once '../config.php';
require_once 'auth.php';
if (!isset($_SESSION['email']) ||!isset($_SESSION['role']) ||$_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <h1>Admin Panel <?=$_SESSION['name']; ?></h1>
        <div class="admin-options">
            <a href="manage_users.php" class="admin-button">Manage Users</a>
            <a href="manage_product.php" class="admin-button">Manage Products</a>
            <a href="view_orders.php" class="admin-button">View Orders</a>
            <a href="site_settings.php" class="admin-button">Site Settings</a>
            <a href="AdminProfile.php" class="admin-button">Profile</a>
        </div>
    </div>
</body>
</html>