<?php
require_once '../config.php';
require_once '../auth_admin.php';
requireAdmin();

function ensureProductDetailColumns(mysqli $conn): void {
    $definitions = [
        'frameHeight' => 'DECIMAL(6,2) NULL',
        'lensWidth' => 'DECIMAL(6,2) NULL',
        'color' => 'VARCHAR(100) NULL',
    ];

    foreach ($definitions as $column => $definition) {
        $escaped = $conn->real_escape_string($column);
        $exists = $conn->query("SHOW COLUMNS FROM products LIKE '{$escaped}'");
        if ($exists && $exists->num_rows === 0) {
            $conn->query("ALTER TABLE products ADD COLUMN `{$column}` {$definition}");
        }
    }
}

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Invalid request method';
    exit;
}

$prodID   = strtoupper(trim($_POST['addProductID'] ?? ''));
$name     = trim($_POST['addProductName'] ?? '');
$desc     = trim($_POST['addProductDescription'] ?? '');
$frame    = (float) ($_POST['addProductFrame'] ?? 0);
$frameHeight = (float) ($_POST['addProductFrameHeight'] ?? 0);
$lensWidth = (float) ($_POST['addProductLensWidth'] ?? 0);
$temple   = (float) ($_POST['addProductTemple'] ?? 0);
$material = trim($_POST['addProductMaterial'] ?? '');
$color    = trim($_POST['addProductColor'] ?? '');
$price    = (float) ($_POST['addProductPrice'] ?? 0);
$stock    = (int)   ($_POST['addProductStock'] ?? 0);
$category = trim($_POST['addProductCategory'] ?? '');

if ($prodID === '' || $name === '' || $category === '' || $price <= 0) {
    http_response_code(400);
    echo 'Missing or invalid required fields';
    exit;
}

if ($frame <= 0 || $frameHeight <= 0 || $lensWidth <= 0) {
    http_response_code(400);
    echo 'Frame Width, Frame Height, and Lens Width must be greater than 0';
    exit;
}

if ($color === '') {
    http_response_code(400);
    echo 'Color is required';
    exit;
}

ensureProductDetailColumns($conn);

$imageNames = [];
for ($i = 1; $i <= 4; $i++) {
    $key = 'addProductImage' . $i;
    if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK && $_FILES[$key]['name'] !== '') {
        $imageName = time() . '_' . $i . '_' . basename($_FILES[$key]['name']);
        if (move_uploaded_file($_FILES[$key]['tmp_name'], '../uploads/products/' . $imageName)) {
            $imageNames[] = $imageName;
        }
    }
}

$mainImage = $imageNames[0] ?? null;
$gallery   = count($imageNames) > 1 ? json_encode(array_slice($imageNames, 1)) : null;

$stmt = $conn->prepare(
    "INSERT INTO products (product_id, name, description, frameWidth, frameHeight, templeLength, lensWidth, material, color, price, stock, category, image, image_gallery)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    http_response_code(500);
    echo 'Prepare failed: ' . $conn->error;
    exit;
}

// s=prodID, s=name, s=desc, d=frameWidth, d=frameHeight, d=templeLength, d=lensWidth, s=material, s=color, d=price, i=stock, s=category, s=image, s=gallery
$stmt->bind_param("sssddddssdisss", $prodID, $name, $desc, $frame, $frameHeight, $temple, $lensWidth, $material, $color, $price, $stock, $category, $mainImage, $gallery);

if ($stmt->execute()) {
    pushAdminNotification(
        $conn,
        'product',
        'Product Added',
        $name . ' (' . $prodID . ') was added to the catalog.',
        ['productId' => $prodID],
        'product:add:' . $prodID
    );
    echo 'success';
} else {
    http_response_code(400);
    echo 'Insert failed: ' . $stmt->error;
}

$stmt->close();
?>

