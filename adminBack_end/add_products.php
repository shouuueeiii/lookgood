<?php
require_once '../config.php';
require_once '../auth_admin.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prodID   = $_POST['addProductID'];
    $name     = $_POST['addProductName'];
    $desc     = $_POST['addProductDescription'];
    $frame    = (float) $_POST['addProductFrame'];
    $temple   = (float) $_POST['addProductTemple'];
    $material = $_POST['addProductMaterial'];
    $price    = (float) $_POST['addProductPrice'];
    $stock    = (int)   $_POST['addProductStock'];
    $category = $_POST['addProductCategory'];

    // Upload images 1–4; image1 = main image, 2–4 go into image_gallery JSON
    $imageNames = [];
    for ($i = 1; $i <= 4; $i++) {
        $key = 'addProductImage' . $i;
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK && $_FILES[$key]['name'] != '') {
            $imageName = time() . '_' . $i . '_' . basename($_FILES[$key]['name']);
            move_uploaded_file($_FILES[$key]['tmp_name'], '../uploads/products/' . $imageName);
            $imageNames[] = $imageName;
        }
    }

    $mainImage = $imageNames[0] ?? NULL;
    $gallery   = count($imageNames) > 1 ? json_encode(array_slice($imageNames, 1)) : NULL;

    $stmt = $conn->prepare(
        "INSERT INTO products (product_id, name, description, frameWidth, templeLength, material, price, stock, category, image, image_gallery)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    // s=prodID, s=name, s=desc, d=frame, d=temple, s=material, d=price, i=stock, s=category, s=mainImage, s=gallery
    $stmt->bind_param("sssddsdisss", $prodID, $name, $desc, $frame, $temple, $material, $price, $stock, $category, $mainImage, $gallery);
    $stmt->execute();

    echo "success";
}
?>

