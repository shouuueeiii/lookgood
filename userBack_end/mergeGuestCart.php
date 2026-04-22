<?php
/**
 * userBack_end/mergeGuestCart.php
 *
 * Called right after a successful user login (via fetch from login.js or
 * directly from login.php before the redirect).
 *
 * Logic:
 *   1. Requires an active user session (user_id must exist).
 *   2. Reads guest_id from the session.
 *   3. For every row in guest_carts for that guest_id:
 *        - If the product is already in the user's carts row → ADD quantities.
 *        - Otherwise → INSERT a new row.
 *   4. Deletes all guest_carts rows for that guest_id.
 *   5. Returns JSON { success, merged } where merged = number of items moved.
 *
 * GET  → { success: true, merged: 0 }  (safe no-op when nothing to merge)
 * POST → same (POST is also accepted so it can be called via fetch easily)
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';
header('Content-Type: application/json');

if (!@include_once __DIR__ . '/../config.php') {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Must be a logged-in user
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// If no guest session exists, nothing to merge
$guestId = $_SESSION['guest_id'] ?? null;
if (!$guestId) {
    echo json_encode(['success' => true, 'merged' => 0]);
    exit;
}

// Ensure guest_carts table exists (safe guard)
$conn->query("
    CREATE TABLE IF NOT EXISTS guest_carts (
        id         INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        guest_id   VARCHAR(64)  NOT NULL,
        product_id VARCHAR(255) NOT NULL,
        quantity   INT          NOT NULL DEFAULT 1,
        added_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_guest_product (guest_id, product_id),
        INDEX idx_guest_id (guest_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Ensure carts table has VARCHAR product_id (in case it was INT before)
// We read from it using a string bind, so just fetch the guest rows first.
$fetchStmt = $conn->prepare(
    'SELECT product_id, quantity FROM guest_carts WHERE guest_id = ?'
);
if (!$fetchStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Query prepare failed']);
    exit;
}
$fetchStmt->bind_param('s', $guestId);
$fetchStmt->execute();
$guestItems = $fetchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$fetchStmt->close();

if (empty($guestItems)) {
    // Nothing in guest cart — clear the guest_id and return
    unset($_SESSION['guest_id']);
    echo json_encode(['success' => true, 'merged' => 0]);
    exit;
}

$conn->begin_transaction();

try {
    $merged = 0;

    // Check if carts.product_id is INT or VARCHAR so we bind correctly.
    // We'll try a safe string-based upsert — MySQL handles implicit casting.
    $checkStmt = $conn->prepare(
        'SELECT quantity FROM carts WHERE user_id = ? AND product_id = ? LIMIT 1'
    );
    $updateStmt = $conn->prepare(
        'UPDATE carts SET quantity = ? WHERE user_id = ? AND product_id = ?'
    );
    $insertStmt = $conn->prepare(
        'INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)'
    );

    if (!$checkStmt || !$updateStmt || !$insertStmt) {
        throw new Exception('Failed to prepare cart statements: ' . $conn->error);
    }

    foreach ($guestItems as $item) {
        $productId   = (string)$item['product_id'];
        $guestQty    = (int)$item['quantity'];

        // Check existing cart row for this user + product
        $checkStmt->bind_param('ss', $userId, $productId);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();

        if ($existing) {
            // Merge: add guest quantity to existing quantity
            $newQty = (int)$existing['quantity'] + $guestQty;
            $updateStmt->bind_param('iss', $newQty, $userId, $productId);
            $updateStmt->execute();
        } else {
            // New item for this user
            $insertStmt->bind_param('ssi', $userId, $productId, $guestQty);
            $insertStmt->execute();
        }

        $merged++;
    }

    $checkStmt->close();
    $updateStmt->close();
    $insertStmt->close();

    // Delete guest cart rows
    $delStmt = $conn->prepare('DELETE FROM guest_carts WHERE guest_id = ?');
    if (!$delStmt) throw new Exception('Failed to prepare delete: ' . $conn->error);
    $delStmt->bind_param('s', $guestId);
    $delStmt->execute();
    $delStmt->close();

    $conn->commit();

    // Clear guest_id from session — they're now a logged-in user
    unset($_SESSION['guest_id']);

    echo json_encode(['success' => true, 'merged' => $merged]);

} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>