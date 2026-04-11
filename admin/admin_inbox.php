<?php
session_start();
include '../config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$me_email = $_SESSION['email'];
$me_name = $_SESSION['name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Inbox</title>
<style>
body { font-family: Arial; max-width: 800px; margin: auto; padding: 20px; }
h1 { text-align: center; color: #333; }
.admin-inbox { width: 100%; max-width: 600px; margin: auto; }
.conversation { border-bottom: 1px solid #ccc; padding: 10px 0; }
.name { font-weight: bold; }
.message { margin-top: 5px; color: #555; }
.time { float: right; font-size: 0.85em; color: #888; }
.open-chat { margin-top: 5px; display: inline-block; padding: 5px 10px; background: #007bff; color: #fff; border-radius: 3px; text-decoration: none; }
.open-chat:hover { background: #0056b3; }
</style>
</head>
<body>

<h1>Admin Inbox</h1>
<p>Logged in as: <?= htmlspecialchars($me_name) ?> (<?= htmlspecialchars($me_email) ?>)</p>

<div class="admin-inbox">

<?php

// natatransfer yung messages nung user to admin
$sql = "
SELECT u.email AS user_email, u.name, m.message, m.created_at
FROM messages m
JOIN users u ON u.email = m.sender_id
WHERE m.receiver_id = ? 
AND u.role = 'user'
ORDER BY m.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $me_email);
$stmt->execute();
$result = $stmt->get_result();

$latestMessages = [];
while ($row = $result->fetch_assoc()) {
    $email = $row['user_email'];
    if (!isset($latestMessages[$email])) {
        $latestMessages[$email] = $row; 
    }
}

// dinidisplay lang dito ung messages
if (count($latestMessages) > 0) {
    foreach ($latestMessages as $msg) {
        echo '<div class="conversation">';
        echo '<span class="name">' . htmlspecialchars($msg['name']) . '</span>';
        echo '<span class="time">' . $msg['created_at'] . '</span>';
        echo '<div class="message">' . htmlspecialchars($msg['message']) . '</div>';
        echo '<a class="open-chat" href="chat.php?user_email=' . urlencode($msg['user_email']) . '">Open Chat</a>';
        echo '</div>';
    }
} else {
    echo '<p>No messages from users yet.</p>';
}
?>

</div>
</body>
</html>
