<?php
require_once '../config.php';
require_once 'auth.php';

requireAdmin();

$result = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>

<h2>Manage Products</h2>
<a href="add_products.php">Add New Product</a>
<a href="admin_page.php" style="margin-left:20px;">← Back to Admin Page</a>
<table border="1" cellpadding="5">
    <tr>
        <th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Status</th><th>Category</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= $row['name'] ?></td>
        <td>₱<?= $row['price'] ?></td>
        <td><?= $row['stock'] ?></td>
        <td><?= $row['status'] ?></td>
        <td><?= $row['category'] ?></td>
        <td>
            <a href="edit_products.php?id=<?= $row['id'] ?>">Edit</a> |
            <a href="delete_products.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete this product?');">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
