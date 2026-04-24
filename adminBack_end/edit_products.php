<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';
require_once '../auth_admin.php';
requireAdmin();

// ── Role-based access control ─────────────────────────────────
$_pos = $_SESSION['position'] ?? '';
if ($_pos !== 'head' && $_pos !== 'inventory_orderAdmin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: insufficient permissions']);
    exit();
}


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

function getRowValue(array $row, array $keys, $default = '') {
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && $row[$key] !== null) {
            return $row[$key];
        }
    }
    return $default;
}

ensureProductDetailColumns($conn);

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

if (!isset($_POST['id'])) {
    echo "error: missing id";
    exit();
}

$id = $_POST['id'];

// Fetch existing product
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo "error: product not found";
    exit();
}

$row = $result->fetch_assoc();

// Scalar fields
$name         = $_POST['name']        ?? $row['name'];
$description  = $_POST['description'] ?? $row['description'];
$category     = $_POST['category']    ?? $row['category'];
$price        = isset($_POST['price'])        ? (float)$_POST['price']        : $row['price'];
$stock        = isset($_POST['stock'])        ? (int)$_POST['stock']          : $row['stock'];
$frameWidth   = isset($_POST['frameWidth'])   ? (float)$_POST['frameWidth']   : (float)getRowValue($row, ['frameWidth', 'frame_width'], 0);
$frameHeight  = isset($_POST['frameHeight'])  ? (float)$_POST['frameHeight']  : (float)getRowValue($row, ['frameHeight', 'frame_height'], 0);
$templeLength = isset($_POST['templeLength']) ? (float)$_POST['templeLength'] : (float)getRowValue($row, ['templeLength', 'temple_length'], 0);
$lensWidth    = isset($_POST['lensWidth'])    ? (float)$_POST['lensWidth']    : (float)getRowValue($row, ['lensWidth', 'lens_width'], 0);
$material     = $_POST['material'] ?? getRowValue($row, ['material'], '');
$color        = trim($_POST['color'] ?? (string)getRowValue($row, ['color'], ''));

if ($frameWidth <= 0 || $frameHeight <= 0 || $lensWidth <= 0) {
    echo "error: frame width, frame height, and lens width must be greater than 0";
    exit();
}

if ($color === '') {
    echo "error: color is required";
    exit();
}

$uploadDir    = '../uploads/products/';
$newImages    = [];

for ($i = 1; $i <= 4; $i++) {
    $key = "image$i";
    if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK && $_FILES[$key]['name'] != '') {
        $cleanName  = str_replace(' ', '_', basename($_FILES[$key]['name']));
        $newName    = time() . "_" . $i . "_" . $cleanName;
        if (move_uploaded_file($_FILES[$key]['tmp_name'], $uploadDir . $newName)) {
            $newImages[$i] = $newName; 
        }
    }
}

$mainImage = isset($newImages[1]) ? $newImages[1] : $row['image'];

$existingGallery = [];
if (!empty($row['image_gallery'])) {
    $decoded = json_decode($row['image_gallery'], true);
    if (is_array($decoded)) {
        $existingGallery = $decoded; 
    }
}

$gallerySlots = [];
for ($i = 2; $i <= 4; $i++) {
    if (isset($newImages[$i])) {
        $gallerySlots[] = $newImages[$i];
    } else {
        $existingIndex  = $i - 2; 
        if (isset($existingGallery[$existingIndex])) {
            $gallerySlots[] = $existingGallery[$existingIndex];
        }
    }
}

$gallery = !empty($gallerySlots) ? json_encode($gallerySlots) : $row['image_gallery'];


$stmt = $conn->prepare("
    UPDATE products
    SET name = ?, description = ?, price = ?, stock = ?, category = ?,
        frameWidth = ?, frameHeight = ?, templeLength = ?, lensWidth = ?, material = ?, color = ?,
        image = ?, image_gallery = ?
    WHERE product_id = ?
");
$stmt->bind_param("ssdisddddsssss",
    $name, $description, $price, $stock, $category,
    $frameWidth, $frameHeight, $templeLength, $lensWidth, $material, $color,
    $mainImage, $gallery,
    $id
);

if ($stmt->execute()) {
    pushAdminNotification(
        $conn,
        'product',
        'Product Updated',
        $name . ' (' . $id . ') details were updated.',
        ['productId' => $id],
        'product:update:' . $id . ':' . date('YmdHi')
    );
    echo "success";
} else {
    echo "error: " . $stmt->error;
}
?>
