<?php
// productFeedbackAPI.php

header('Content-Type: application/json');

// Include database connection
include 'db.php';

// Get the product ID from the request
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

function resolveCanonicalProductId(mysqli $conn, string $rawId): ?string {
    $trimmed = trim($rawId);
    if ($trimmed === '') return null;

    // First try direct match on product_id
    $directMatch = $conn->prepare('SELECT product_id FROM products WHERE product_id = ? LIMIT 1');
    if ($directMatch) {
        $directMatch->bind_param('s', $trimmed);
        $directMatch->execute();
        $row = $directMatch->get_result()->fetch_assoc();
        $directMatch->close();
        if ($row && isset($row['product_id'])) {
            return (string)$row['product_id'];
        }
    }

    // If not found, try matching legacy ID (integer)
    if (ctype_digit($trimmed)) {
        $legacyId = (int)$trimmed;
        $legacyMatch = $conn->prepare('SELECT product_id FROM products WHERE id = ? LIMIT 1');
        if ($legacyMatch) {
            $legacyMatch->bind_param('i', $legacyId);
            $legacyMatch->execute();
            $row = $legacyMatch->get_result()->fetch_assoc();
            $legacyMatch->close();
            if ($row && isset($row['product_id'])) {
                return (string)$row['product_id'];
            }
        }
    }

    return null;
}

$canonicalProductId = resolveCanonicalProductId($conn, (string)$productId);
if ($canonicalProductId === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}

// Fetch feedback for the specified product
$stmt = $conn->prepare("
    SELECT id, product_id, user_id, rating, comment, created_at
    FROM product_feedback
    WHERE product_id = ?
    ORDER BY created_at DESC
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare statement']);
    $conn->close();
    exit;
}

$stmt->bind_param('s', $canonicalProductId);
$stmt->execute();
$result = $stmt->get_result();

$feedback = [];
while ($row = $result->fetch_assoc()) {
    $feedback[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($feedback);
?>