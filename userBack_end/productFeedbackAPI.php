<?php

error_reporting(0);
ob_start();
require_once '../config.php';
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';
ob_end_clean();

header('Content-Type: application/json');


$rawId = trim($_GET['product_id'] ?? $_GET['id'] ?? '');

if ($rawId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'product_id is required']);
    exit;
}

// Resolve canonical product_id
function resolveProductId(mysqli $conn, string $raw): ?string {
    $stmt = $conn->prepare('SELECT product_id FROM products WHERE product_id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $raw);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) return (string)$row['product_id'];
    }
    return null;
}

$productId = resolveProductId($conn, $rawId);
if ($productId === null) {

    echo json_encode([
        'avg'       => 0,
        'count'     => 0,
        'breakdown' => [5=>0,4=>0,3=>0,2=>0,1=>0],
        'reviews'   => [],
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT pf.id, pf.rating, pf.comment, pf.created_at,
            u.first_name, u.last_name
    FROM   product_feedback pf
    LEFT JOIN users u ON u.user_id = pf.user_id
    WHERE  pf.product_id = ?
    ORDER  BY pf.created_at DESC
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]);
    exit;
}

$stmt->bind_param('s', $productId);
$stmt->execute();
$result = $stmt->get_result();

$reviews     = [];
$breakdown   = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];
$totalRating = 0;
$count       = 0;

$palette = ['#6c63ff','#e91e8c','#00b09b','#f7971e','#2196f3','#9c27b0','#ff5722','#4caf50'];

while ($row = $result->fetch_assoc()) {
    $rating = max(1, min(5, (int)$row['rating']));
    $totalRating += $rating;
    $count++;
    if (isset($breakdown[$rating])) $breakdown[$rating]++;

    $firstName   = trim($row['first_name'] ?? '');
    $lastName    = trim($row['last_name']  ?? '');
    $displayName = $firstName
        ? ($firstName . ($lastName ? ' ' . strtoupper($lastName[0]) . '.' : ''))
        : 'Anonymous';

    $color   = $palette[$row['id'] % count($palette)];
    $dateStr = !empty($row['created_at']) ? date('F j, Y', strtotime($row['created_at'])) : '';

    $reviews[] = [
        'name'     => $displayName,
        'rating'   => $rating,
        'date'     => $dateStr,
        'text'     => $row['comment'] ?: '',
        'color'    => $color,
        'verified' => true,
        'response' => null,
    ];
}
$stmt->close();

$avg = $count > 0 ? round($totalRating / $count, 1) : 0;

echo json_encode([
    'avg'       => $avg,
    'count'     => $count,
    'breakdown' => $breakdown,
    'reviews'   => $reviews,
]);
?>