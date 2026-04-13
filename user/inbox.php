<?php
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';
include '../config.php';
require_once '../auth_user.php';
requireUser();
include '../messages/inbox.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <a href = "chat.php?user_id=<?php echo $_SESSION['email']; ?>">Back to Chats</a>
    <a href = "mainpage.php">mainpage</a>
</body>
</html>