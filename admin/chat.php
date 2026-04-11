<?php
session_start();
include '../config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    die("Invalid role.");
}

$admin_id = $_SESSION['email'];
$admin_name = $_SESSION['name'];

$partner_email = $_GET['user_email'] ?? null;
if (!$partner_email) die("User email missing");

$user = $conn->query("SELECT email, name FROM users WHERE email='$partner_email' LIMIT 1")->fetch_assoc();
$partner_name = $user['name'];

$stmt = $conn->prepare("
    SELECT id FROM conversations 
    WHERE user_id = ? AND admin_id = ? 
    LIMIT 1
");
$stmt->bind_param("ss", $partner_email, $admin_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $conversation = $res->fetch_assoc();
    $conversation_id = $conversation['id'];
} else {
    $stmt = $conn->prepare("INSERT INTO conversations (user_id, admin_id) VALUES (?, ?)");
    $stmt->bind_param("ss", $partner_email, $admin_id);
    $stmt->execute();
    $conversation_id = $stmt->insert_id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $msg = $_POST['message'];
    $stmt = $conn->prepare("
        INSERT INTO messages (conversation_id, sender_id, receiver_id, message)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $conversation_id, $admin_id, $partner_email, $msg);
    $stmt->execute();
}

$stmt = $conn->prepare("
    SELECT sender_id, message, created_at
    FROM messages
    WHERE conversation_id = ?
    ORDER BY created_at ASC
");
$stmt->bind_param("i", $conversation_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat with <?= htmlspecialchars($partner_name) ?></title>
<link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<h2>Chat with <?= htmlspecialchars($partner_name) ?></h2>

<div class="chat-box">
<?php
while ($row = $result->fetch_assoc()) {
    $class = ($row['sender_id'] === $admin_id) ? 'sent' : 'received';
    echo '<div class="message '.$class.'">';
    echo '<div>'.htmlspecialchars($row['message']).'</div>';
    echo '<div class="time">'.$row['created_at'].'</div>';
    echo '</div>';
}
?>
</div>

<form method="POST">
    <input type="text" name="message" placeholder="Type your message" required>
    <button type="submit">Send</button>
</form>
<a href="admin_page.php">Back to admin page</a>

</body>
</html>
