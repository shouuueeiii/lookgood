<?php
// =============================================================
// adminBack_end/sale_product.php
// Applies / removes a sale on a product using the `sales` table.
//
// sales table schema:
//   sales_id    INT(11)       NOT NULL AUTO_INCREMENT
//   product_id  VARCHAR(255)  NOT NULL
//   sale_price  INT(11)       NOT NULL
//   start_date  DATE          NOT NULL
//   end_date    DATE          NOT NULL
//   sale_label  TEXT          YES NULL
// =============================================================

if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'admin');
require_once __DIR__ . '/../session_bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_admin.php';
requireAdmin();

header('Content-Type: application/json');

// ── Role guard ────────────────────────────────────────────────
$_pos = $_SESSION['position'] ?? '';
if ($_pos !== 'head' && $_pos !== 'inventory_orderAdmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request body']);
    exit();
}

$action    = trim($input['action']     ?? '');  // 'apply' | 'remove'
$productId = trim($input['product_id'] ?? '');

if (!$productId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'product_id is required']);
    exit();
}

// Verify product exists and get original price
$chk = $conn->prepare("SELECT product_id, name, price FROM products WHERE product_id = ? LIMIT 1");
$chk->bind_param('s', $productId);
$chk->execute();
$product = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$product) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit();
}

// =============================================================
// APPLY SALE — INSERT or UPDATE row in sales table
// =============================================================
if ($action === 'apply') {
    $salePrice = isset($input['sale_price']) ? (int)$input['sale_price'] : 0;
    $startDate = trim($input['sale_start_date'] ?? '');
    $endDate   = trim($input['sale_end_date']   ?? '');
    $saleLabel = trim($input['sale_label']      ?? '') ?: null;

    // Validate
    if ($salePrice <= 0) {
        echo json_encode(['success' => false, 'error' => 'A valid sale price is required']);
        exit();
    }
    if ($salePrice >= (int)$product['price']) {
        echo json_encode(['success' => false, 'error' => 'Sale price must be less than the original price (₱' . number_format($product['price']) . ')']);
        exit();
    }
    if (!$startDate || !$endDate) {
        echo json_encode(['success' => false, 'error' => 'Start date and end date are required']);
        exit();
    }
    if (strtotime($startDate) >= strtotime($endDate)) {
        echo json_encode(['success' => false, 'error' => 'End date must be after start date']);
        exit();
    }

    // Check if a sale already exists for this product
    $existing = $conn->prepare("SELECT sales_id FROM sales WHERE product_id = ? LIMIT 1");
    $existing->bind_param('s', $productId);
    $existing->execute();
    $existingRow = $existing->get_result()->fetch_assoc();
    $existing->close();

    if ($existingRow) {
        $stmt = $conn->prepare("
            UPDATE sales
            SET sale_price = ?, start_date = ?, end_date = ?, sale_label = ?
            WHERE product_id = ?
        ");
        $stmt->bind_param('issss', $salePrice, $startDate, $endDate, $saleLabel, $productId);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO sales (product_id, sale_price, start_date, end_date, sale_label)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sisss', $productId, $salePrice, $startDate, $endDate, $saleLabel);
    }

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode([
            'success'    => true,
            'message'    => 'Sale applied to ' . $product['name'],
            'on_sale'    => true,
            'sale_price' => $salePrice,
        ]);
    } else {
        $err = $stmt->error;
        $stmt->close();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'DB error: ' . $err]);
    }
    exit();
}

if ($action === 'remove') {
    $stmt = $conn->prepare("DELETE FROM sales WHERE product_id = ?");
    $stmt->bind_param('s', $productId);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode([
            'success' => true,
            'message' => 'Sale removed from ' . $product['name'],
            'on_sale' => false,
        ]);
    } else {
        $err = $stmt->error;
        $stmt->close();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'DB error: ' . $err]);
    }
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid action. Use "apply" or "remove"']);
