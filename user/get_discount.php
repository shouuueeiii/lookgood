<?php
header('Content-Type: application/json');
require_once '../config.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

$stmt = $conn->prepare("
    SELECT discountCode, carts, discountValue, minPurchase, maxDiscount, description
    FROM discount
    WHERE NOW() BETWEEN startDate AND endDate
    AND totalUsageLimit > 0
");
$stmt->execute();
$result = $stmt->get_result();

$vouchers = [];
while ($row = $result->fetch_assoc()) {
    $vouchers[] = [
        'code'          => $row['discountCode'],
        'type'          => $row['carts'],    
        'discountValue' => (float) $row['discountValue'],
        'minPurchase'   => (float) $row['minPurchase'],
        'maxDiscount'   => $row['maxDiscount'] !== null ? (float) $row['maxDiscount'] : null,
        'description'   => $row['description']
    ];
}

echo json_encode($vouchers);
$stmt->close();
$conn->close();