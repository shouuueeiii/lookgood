<?php
require_once '../config.php';

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

function normalizeNullableFloat($value) {
    if ($value === null || $value === '') {
        return '';
    }
    return (float)$value;
}

function rowValue(array $row, array $keys, $default = null) {
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
            return $row[$key];
        }
    }
    return $default;
}

ensureProductDetailColumns($conn);

$result = $conn->query(
        "SELECT p.*,
                s.sales_id,
                s.sale_price,
                s.start_date  AS sale_start_date,
                s.end_date    AS sale_end_date,
                s.sale_label
         FROM products p
         LEFT JOIN sales s ON s.product_id = p.product_id
         ORDER BY
             CASE
                 WHEN p.product_id LIKE 'LGF-M-%' THEN 1
                 WHEN p.product_id LIKE 'LGF-W-%' THEN 2
                 WHEN p.product_id LIKE 'LGF-U-%' THEN 3
                 ELSE 4
             END ASC,
             CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(p.product_id, '-', 3), '-', -1) AS UNSIGNED) ASC,
             p.product_id ASC"
);

$products = [];

while ($row = $result->fetch_assoc()) {

    $rawCategory = strtolower(trim((string)($row['category'] ?? '')));
    if ($rawCategory === '') {
        $pid = strtolower((string)($row['product_id'] ?? ''));
        if (strpos($pid, 'lgf-m-') === 0 || strpos($pid, 'men-') === 0) {
            $rawCategory = 'male';
        } elseif (strpos($pid, 'lgf-w-') === 0 || strpos($pid, 'women-') === 0) {
            $rawCategory = 'female';
        } else {
            $rawCategory = 'unisex';
        }
    }

    // Main image
    $mainImage = !empty($row['image'])
        ? '../uploads/products/' . $row['image']
        : null;

    // Gallery images (stored as JSON array of filenames)
    $galleryImages = [];
    if (!empty($row['image_gallery'])) {
        $decoded = json_decode($row['image_gallery'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $filename) {
                $galleryImages[] = '../uploads/products/' . $filename;
            }
        }
    }

    // Full images array: main first, then gallery
    $images = array_filter(array_merge(
        $mainImage ? [$mainImage] : [],
        $galleryImages
    ));

    if (empty($images)) {
        $images = ['/global/jin.jpg'];
    }

    $products[] = [
        "id"           => $row['product_id'],
        "name"         => $row['name'],
        "description"  => $row['description'],
        "category"     => $rawCategory,
        "price"        => (float)$row['price'],
        "stock"        => (int)$row['stock'],
        "images"       => array_values($images),
        "image"        => array_values($images)[0],
        "frameWidth"   => normalizeNullableFloat(rowValue($row, ['frameWidth', 'frame_width'], null)),
        "frameHeight"  => normalizeNullableFloat(rowValue($row, ['frameHeight', 'frame_height'], null)),
        "templeLength" => normalizeNullableFloat(rowValue($row, ['templeLength', 'temple_length'], null)),
        "lensWidth"    => normalizeNullableFloat(rowValue($row, ['lensWidth', 'lens_width'], null)),
        "material"     => (string)rowValue($row, ['material'], ''),
        "color"        => (string)rowValue($row, ['color', 'frameColor', 'frame_color'], ''),
        "onSale"        => isset($row['sales_id']) && $row['sales_id'] !== null,
        "salePrice"     => isset($row['sale_price'])      && $row['sale_price']      !== null ? (int)$row['sale_price']      : null,
        "saleStartDate" => isset($row['sale_start_date']) && $row['sale_start_date'] !== null ? (string)$row['sale_start_date'] : null,
        "saleEndDate"   => isset($row['sale_end_date'])   && $row['sale_end_date']   !== null ? (string)$row['sale_end_date']   : null,
        "saleLabel"     => isset($row['sale_label'])      && $row['sale_label']      !== null ? (string)$row['sale_label']      : null,
    ];
}

header('Content-Type: application/json');
echo json_encode($products);
?>
