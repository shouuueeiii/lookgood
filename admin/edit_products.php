<?php
require_once '../config.php';
require_once 'auth.php';
requireAdmin();

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM products where id = $id");
$product = $result->fetch_assoc();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $status = $_POST['status'];
    $category = $_POST['category'];

    if(isset($_FILES['image']) &&$_FILES['image']['name'] != ""){
        $imageName = time().'_'. $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/products/'.$imageName);
    } else {
        $imageName = $product['image'];
    }
    

    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, status = ?, category = ?, image = ? WHERE id = ?");
    $stmt->bind_param("ssdisssi", $name, $desc, $price, $stock, $status, $category, $imageName, $id);
    $stmt->execute();

    header('Location: manage_product.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>Edit Product</h1>
    <form method = "POST" enctype="multipart/form-data">
        Name<input type = "text" name = "name" value = "<?= $product['name'] ?>" required><br>
        Description<input type = "text" name = "description"><br>
        Price<input type = "number" step="0.01" name = "price" value = "<?= $product['price'] ?>" required><br>
        Stock<input type = "number" name = "stock" value = "<?= $product['stock'] ?>" required><br>
        Status
        <select name = "status" required>
            <option value = "active" <?= $product['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
            <option value = "inactive" <?= $product['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select><br>
        Category
        <select name = "category" required>
            <option value = "male" <?= $product['category'] == 'male' ? 'selected' : ''; ?>>male</option>
            <option value = "female" <?= $product['category'] == 'female' ? 'selected' : ''; ?>>female</option>
            <option value = "unisex" <?= $product['category'] == 'unisex' ? 'selected' : ''; ?>>unisex</option>
        </select><br>
        Image<input type = "file" name = "image"><br>
        <input type = "submit" value = "Update Product">
    </form>
</body>
</html>