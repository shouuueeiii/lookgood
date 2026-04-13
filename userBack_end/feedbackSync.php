<?php
require_once '../config.php';
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

function pushAdminNotification(mysqli $conn, string $type, string $title, string $message, array $data, string $sourceKey, ?string $createdAt = null): void {
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
    $createdAt = $createdAt ?: date('Y-m-d H:i:s');
    $stmt = $conn->prepare(
        'INSERT INTO admin_notifications (type, title, message, data_json, source_key, is_read, created_at)
         VALUES (?, ?, ?, ?, ?, 0, ?)
         ON DUPLICATE KEY UPDATE id = id'
    );
    if (!$stmt) return;
    $stmt->bind_param('ssssss', $type, $title, $message, $payload, $sourceKey, $createdAt);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$userDisplayName = trim((string)(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')));
if ($userDisplayName === '') {
    $userDisplayName = (string)($_SESSION['email'] ?? ('User #' . $userId));
}
$input = json_decode(file_get_contents('php://input'), true);

$entries = $input['entries'] ?? [];
if (!is_array($entries) || count($entries) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'No feedback entries provided']);
    exit();
}

// Keep this table compatible with the admin feedback page query.
$conn->query(
    "CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        rating TINYINT NOT NULL,
        feedback_comment TEXT NULL,
        admin_reply TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_product (user_id, product_id)
    )"
);

function resolveFeedbackCommentColumn(mysqli $conn): string {
    $hasFeedbackComment = $conn->query("SHOW COLUMNS FROM feedback LIKE 'feedback_comment'");
    if ($hasFeedbackComment && $hasFeedbackComment->num_rows > 0) {
        return 'feedback_comment';
    }

    return 'comment';
}

function resolveCanonicalProductId(mysqli $conn, string $productId): ?string {
    $trimmed = trim($productId);
    if ($trimmed === '') {
        return null;
    }

    $byProductId = $conn->prepare('SELECT product_id FROM products WHERE product_id = ? LIMIT 1');
    if ($byProductId) {
        $byProductId->bind_param('s', $trimmed);
        $byProductId->execute();
        $row = $byProductId->get_result()->fetch_assoc();
        $byProductId->close();
        if ($row && isset($row['product_id'])) {
            return (string)$row['product_id'];
        }
    }

    // Backward compatibility: older orders/ratings may still carry products.id.
    if (ctype_digit($trimmed)) {
        $hasLegacyId = $conn->query("SHOW COLUMNS FROM products LIKE 'id'");
        if ($hasLegacyId && $hasLegacyId->num_rows > 0) {
            $legacyId = (int)$trimmed;
            $byLegacyId = $conn->prepare('SELECT product_id FROM products WHERE id = ? LIMIT 1');
            if ($byLegacyId) {
                $byLegacyId->bind_param('i', $legacyId);
                $byLegacyId->execute();
                $row = $byLegacyId->get_result()->fetch_assoc();
                $byLegacyId->close();
                if ($row && isset($row['product_id'])) {
                    return (string)$row['product_id'];
                }
            }
        }
    }

    return null;
}

$commentColumn = resolveFeedbackCommentColumn($conn);

$findFeedbackStmt = $conn->prepare(
    "SELECT id, rating, $commentColumn AS existing_comment FROM feedback WHERE user_id = ? AND product_id = ? LIMIT 1"
);
$updateStmt = $conn->prepare(
    "UPDATE feedback SET rating = ?, $commentColumn = ?, created_at = ? WHERE id = ?"
);
$insertStmt = $conn->prepare(
    "INSERT INTO feedback (user_id, product_id, rating, $commentColumn, created_at) VALUES (?, ?, ?, ?, ?)"
);

if (!$findFeedbackStmt || !$updateStmt || !$insertStmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare statements']);
    exit();
}

$imported = 0;
$updated = 0;
$skipped = 0;

foreach ($entries as $entry) {
    $rawProductId = trim((string)($entry['product_id'] ?? ''));
    $rating = (int)($entry['rating'] ?? 0);
    $comment = trim((string)($entry['comment'] ?? ''));
    $dateRaw = trim((string)($entry['date'] ?? ''));

    if ($rawProductId === '' || $rating < 1 || $rating > 5) {
        $skipped++;
        continue;
    }

    $productId = resolveCanonicalProductId($conn, $rawProductId);
    if ($productId === null || $productId === '') {
        $skipped++;
        continue;
    }

    if (strlen($comment) > 600) {
        $comment = substr($comment, 0, 600);
    }

    $timestamp = strtotime($dateRaw);
    $createdAt = $timestamp ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');

    $findFeedbackStmt->bind_param('is', $userId, $productId);
    $findFeedbackStmt->execute();
    $existing = $findFeedbackStmt->get_result()->fetch_assoc();

    if ($existing && isset($existing['id'])) {
        $feedbackId = (int)$existing['id'];
        $existingRating = (int)($existing['rating'] ?? 0);
        $existingComment = trim((string)($existing['existing_comment'] ?? ''));

        // Avoid unnecessary updates/notifications when values did not actually change.
        if ($existingRating === $rating && $existingComment === $comment) {
            continue;
        }

        $updateStmt->bind_param('issi', $rating, $comment, $createdAt, $feedbackId);
        $ok = $updateStmt->execute();
        if ($ok) {
            $updated++;
            $updateSourceKey = 'feedback:update:' . $feedbackId . ':' . sha1($rating . '|' . $comment . '|' . $createdAt);
            $commentPreview = trim($comment) !== '' ? (' Comment: "' . substr($comment, 0, 120) . '"') : '';
            pushAdminNotification(
                $conn,
                'feedback',
                'Feedback Updated',
                $userDisplayName . ' updated feedback for product ' . $productId . ' (' . $rating . '/5).' . $commentPreview,
                [
                    'feedbackId' => (string)$feedbackId,
                    'productId' => (string)$productId,
                    'rating' => $rating,
                    'comment' => $comment,
                    'userId' => (string)$userId,
                    'userName' => $userDisplayName,
                    'date' => $createdAt
                ],
                $updateSourceKey,
                $createdAt
            );
        } else {
            $skipped++;
        }
    } else {
        $insertStmt->bind_param('isiss', $userId, $productId, $rating, $comment, $createdAt);
        $ok = $insertStmt->execute();
        if ($ok) {
            $imported++;
            $newId = (int)$insertStmt->insert_id;
            $insertSourceKey = 'feedback:new:' . $newId . ':' . sha1($rating . '|' . $comment . '|' . $createdAt);
            $commentPreview = trim($comment) !== '' ? (' Comment: "' . substr($comment, 0, 120) . '"') : '';
            pushAdminNotification(
                $conn,
                'feedback',
                'New Feedback Submitted',
                $userDisplayName . ' submitted feedback for product ' . $productId . ' (' . $rating . '/5).' . $commentPreview,
                [
                    'feedbackId' => (string)$newId,
                    'productId' => (string)$productId,
                    'rating' => $rating,
                    'comment' => $comment,
                    'userId' => (string)$userId,
                    'userName' => $userDisplayName,
                    'date' => $createdAt
                ],
                $insertSourceKey,
                $createdAt
            );
        } else {
            $skipped++;
        }
    }
}

$findFeedbackStmt->close();
$updateStmt->close();
$insertStmt->close();

echo json_encode([
    'success' => ($imported + $updated) > 0,
    'imported' => $imported,
    'updated' => $updated,
    'skipped' => $skipped,
    'error' => (($imported + $updated) > 0) ? null : 'No feedback rows were saved. Ensure product_id exists in products and user session is active.'
]);
?>