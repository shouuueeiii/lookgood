<?php
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'admin');
require_once __DIR__ . '/../session_bootstrap.php';
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$res = $conn->query("SELECT COUNT(*) AS total_guests FROM guest_checkout");
$total = $res ? (int)$res->fetch_assoc()['total_guests'] : 0;

echo json_encode(['total_guests' => $total]);
?>