<?php
/**
 * adminBack_end/guestAPI.php
 * Returns the total number of distinct guest visitors
 * (counted from the guest_checkout table).
 *
 * GET → { "total_guests": 42 }
 */

if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'admin');
require_once __DIR__ . '/../session_bootstrap.php';
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$conn->query("
    CREATE TABLE IF NOT EXISTS guest_checkout (
        id           INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        guest_id     VARCHAR(64)  NOT NULL,
        client_order_ref VARCHAR(80) NULL,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        full_name    VARCHAR(100) NULL,
        email        VARCHAR(100) NULL,
        phone        VARCHAR(20)  NULL,
        address      TEXT         NULL,
        zip          VARCHAR(10)  NULL,
        payment_method VARCHAR(50) NULL,
        shipping_fee DECIMAL(10,2) DEFAULT 0,
        status       ENUM('pending','processing','shipped','out_for_delivery','delivered','completed','cancelled','returned') NOT NULL DEFAULT 'pending',
        created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_guest_id (guest_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$res = $conn->query("SELECT COUNT(DISTINCT guest_id) AS total_guests FROM guest_checkout");
$total = $res ? (int)$res->fetch_assoc()['total_guests'] : 0;

echo json_encode(['total_guests' => $total]);
?>