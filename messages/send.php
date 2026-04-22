<?php
include '../config.php';
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';

function pushAdminNotification(mysqli $conn, string $type, string $title, string $message, array $data, string $sourceKey): void {
	$conn->query("CREATE TABLE IF NOT EXISTS admin_notifications (
		id INT AUTO_INCREMENT PRIMARY KEY,
		type VARCHAR(30) NOT NULL DEFAULT 'status',
		title VARCHAR(255) NOT NULL,
		message TEXT NOT NULL,
		data_json TEXT NULL,
		source_key VARCHAR(191) NULL,
		is_read TINYINT(1) NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY uq_admin_notifications_source (source_key)
	)");
	$payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	if ($payload === false) $payload = '{}';
	$stmt = $conn->prepare(
		'INSERT INTO admin_notifications (type, title, message, data_json, source_key, is_read)
		 VALUES (?, ?, ?, ?, ?, 0)
		 ON DUPLICATE KEY UPDATE id = id'
	);
	if (!$stmt) return;
	$stmt->bind_param('sssss', $type, $title, $message, $payload, $sourceKey);
	$stmt->execute();
	$stmt->close();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];
$message = $_POST['message'];

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, sender_type) VALUES (?, ?, ?, 'user')");
$stmt->bind_param("iis", $sender_id, $receiver_id, $message);
$stmt->execute();

if (($stmt->affected_rows ?? 0) > 0) {
	$msgId = (int)$stmt->insert_id;
	pushAdminNotification(
		$conn,
		'message',
		'New User Message',
		'User #' . $sender_id . ': ' . substr((string)$message, 0, 120),
		['messageId' => (string)$msgId, 'senderId' => (string)$sender_id],
		'message:' . $msgId
	);
}

$stmt->close();
?>