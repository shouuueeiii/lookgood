<?php
/**
 * userBack_end/productsAPI.php
 * Public API — returns active products from the database.
 * Used by: Actions/User/products-page.js, product-detail.js, index.js
 *
 * GET ?id=<product_id>       → single product
 * GET ?category=<cat>        → filtered list
 * GET (no params)            → all active products
 */
require_once '../config.php';
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {

    // ── Single product by ?id= ────────────────────────────────────────────────
    if (!empty($_GET['id'])) {
        $id   = trim($_GET['id']);
        $stmt = $conn->prepare(
            "SELECT product_id, name, description, price, stock, category,
            image_gallery, frameWidth, templeLength, material, status
             FROM products
             WHERE product_id = ? AND status = 'active'"
        );
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            exit();
        }

        echo json_encode(formatProduct($row));
        exit();
    }

    $where  = "WHERE status = 'active'";
    $types  = '';
    $params = [];

    if (!empty($_GET['category'])) {
        $cat    = trim($_GET['category']);
        $where .= " AND category = ?";
        $types   = 's';
        $params[] = $cat;
    }

    $sql  = "SELECT product_id, name, description, price, stock, category,
                    image_gallery, frameWidth, templeLength, material, status
            FROM products $where ORDER BY product_id DESC";
    $stmt = $conn->prepare($sql);

    if ($types) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result   = $stmt->get_result();
    $products = [];

    while ($row = $result->fetch_assoc()) {
        $products[] = formatProduct($row);
    }

    $stmt->close();
    echo json_encode($products);
    exit();
}

echo json_encode(['error' => 'Method not allowed']);

function formatProduct(array $row): array {
    $base = '../uploads/products/';

    $mainImage = !empty($row['image_gallery']) ? $base . $row['image_gallery'] : null;

    $gallery = [];
    if (!empty($row['image_gallery'])) {
        $decoded = json_decode($row['image_gallery'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $f) {
                $gallery[] = $base . $f;
            }
        }
    }

    $images = array_values(array_filter(
        array_merge($mainImage ? [$mainImage] : [], $gallery)
    ));

    if (empty($images)) {
        $images = ['../Resources/Images/glasses1.png'];
    }

    return [
        'id'           => (string)$row['product_id'],
        'name'         => $row['name'],
        'description'  => $row['description'] ?? '',
        'category'     => $row['category'],
        'price'        => (float) $row['price'],
        'stock'        => (int) $row['stock'],
        'image'        => $images[0],
        'images'       => $images,
        'frameWidth'   => $row['frameWidth']   ?? '',
        'templeLength' => $row['templeLength'] ?? '',
        'material'     => $row['material']     ?? '',
    ];
}
?>