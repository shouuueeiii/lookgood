<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';
require_once '../auth_admin.php';
requireAdmin();

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
$frameWidth   = isset($_POST['frameWidth'])   ? (float)$_POST['frameWidth']   : $row['frame_width'];
$templeLength = isset($_POST['templeLength']) ? (float)$_POST['templeLength'] : $row['temple_length'];
$material     = $_POST['material'] ?? $row['material'];

// Image handling: image1 → main "image", image2-4 → "image_gallery" JSON
$uploadDir    = '../uploads/products/';
$newImages    = [];

for ($i = 1; $i <= 4; $i++) {
    $key = "image$i";
    if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK && $_FILES[$key]['name'] != '') {
        $cleanName  = str_replace(' ', '_', basename($_FILES[$key]['name']));
        $newName    = time() . "_" . $i . "_" . $cleanName;
        if (move_uploaded_file($_FILES[$key]['tmp_name'], $uploadDir . $newName)) {
            $newImages[$i] = $newName; // keyed by slot (1-4)
        }
    }
}

// Decide final main image and gallery
$mainImage = isset($newImages[1]) ? $newImages[1] : $row['image'];

// Existing gallery filenames (without path prefix)
$existingGallery = [];
if (!empty($row['image_gallery'])) {
    $decoded = json_decode($row['image_gallery'], true);
    if (is_array($decoded)) {
        $existingGallery = $decoded; // these are already bare filenames
    }
}

// Slots 2-4: use new upload if provided, otherwise keep existing
$gallerySlots = [];
for ($i = 2; $i <= 4; $i++) {
    if (isset($newImages[$i])) {
        $gallerySlots[] = $newImages[$i];
    } else {
        $existingIndex  = $i - 2; // slot2→index0, slot3→index1, slot4→index2
        if (isset($existingGallery[$existingIndex])) {
            $gallerySlots[] = $existingGallery[$existingIndex];
        }
    }
}

$gallery = !empty($gallerySlots) ? json_encode($gallerySlots) : $row['image_gallery'];

// Update
$stmt = $conn->prepare("
    UPDATE products
    SET name = ?, description = ?, price = ?, stock = ?, category = ?,
        frame_width = ?, temple_length = ?, material = ?,
        image = ?, image_gallery = ?
    WHERE product_id = ?
");
// s,s,d,i,s,d,d,s,s,s,s = "ssdisddssss"
$stmt->bind_param("ssdisddssss",
    $name, $description, $price, $stock, $category,
    $frameWidth, $templeLength, $material,
    $mainImage, $gallery,
    $id
);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "error: " . $stmt->error;
}
?>
