<?php

// Session must start BEFORE any output and BEFORE reading $_SESSION['user_id']
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once '../config.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

$conn->query("
    CREATE TABLE IF NOT EXISTS discount_usage (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        discountCode VARCHAR(100) NOT NULL,
        user_id      INT          NOT NULL,
        order_id     INT          NULL,
        used_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_du_code    (discountCode),
        INDEX idx_du_user    (user_id),
        INDEX idx_du_order   (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$colCheck = $conn->query("SHOW COLUMNS FROM discount LIKE 'perUserLimit'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE discount ADD COLUMN perUserLimit INT NULL AFTER totalUsageLimit");
}

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

$stmt = $conn->prepare("
    SELECT
        d.discountCode,
        d.carts,
        d.discountValue,
        d.minPurchase,
        d.maxDiscount,
        d.description,
        d.totalUsageLimit,
        d.perUserLimit,
        (SELECT COUNT(*) FROM discount_usage du
        WHERE du.discountCode = d.discountCode) AS totalUsed,
        (SELECT COUNT(*) FROM discount_usage du
        WHERE du.discountCode = d.discountCode
        AND du.user_id      = ?) AS userUsed
    FROM discount d
    WHERE NOW() BETWEEN d.startDate AND d.endDate
    AND d.totalUsageLimit > 0
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$vouchers = [];
while ($row = $result->fetch_assoc()) {
    $totalUsed = (int) $row['totalUsed'];
    $userUsed  = (int) $row['userUsed'];

    if ($totalUsed >= (int) $row['totalUsageLimit']) {
        continue; // fully redeemed globally
    }

    // Check per-user limit
    if ($row['perUserLimit'] !== null && $userUsed >= (int) $row['perUserLimit']) {
        continue; 
    }

    $vouchers[] = [
        'code'          => $row['discountCode'],
        'type'          => $row['carts'],           
        'discountValue' => (float) $row['discountValue'],
        'minPurchase'   => (float) $row['minPurchase'],
        'maxDiscount'   => $row['maxDiscount'] !== null ? (float) $row['maxDiscount'] : null,
        'description'   => $row['description'],
        // expose limits so the cart UI can show remaining uses if desired
        'remainingGlobal'  => max(0, (int) $row['totalUsageLimit'] - $totalUsed),
        'remainingForUser' => $row['perUserLimit'] !== null
                                ? max(0, (int) $row['perUserLimit'] - $userUsed)
                                : null,
    ];
}

echo json_encode($vouchers);
$stmt->close();
$conn->close();