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
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';

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

function nullableFloat($value) {
    if ($value === null || $value === '') {
        return '';
    }
    return (float)$value;
}

function textValue($value): string {
    return trim((string)($value ?? ''));
}

function categoryColorFallback(string $category): string {
    $cat = strtolower(trim($category));
    if ($cat === 'male' || $cat === 'men') return 'Matte Black';
    if ($cat === 'female' || $cat === 'women') return 'Rose Gold';
    return 'Classic Black';
}

ensureProductDetailColumns($conn);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {

    // ── Single product by ?id= ────────────────────────────────────────────────
    if (!empty($_GET['id'])) {
        $id   = trim($_GET['id']);
        $stmt = $conn->prepare(
            "SELECT product_id, name, description, price, stock, category,
            image, image_gallery, frameWidth, frameHeight, templeLength, lensWidth, material, color, status
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
                                        image, image_gallery, frameWidth, frameHeight, templeLength, lensWidth, material, color, status
                        FROM products $where ORDER BY
                            CASE
                                WHEN product_id LIKE 'LGF-M-%' THEN 1
                                WHEN product_id LIKE 'LGF-W-%' THEN 2
                                WHEN product_id LIKE 'LGF-U-%' THEN 3
                                ELSE 4
                            END ASC,
                            CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(product_id, '-', 3), '-', -1) AS UNSIGNED) ASC,
                            product_id ASC";
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
    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $appBase = str_replace('\\', '/', dirname(dirname($scriptName)));
    $appBase = rtrim($appBase, '/');
    $base = ($appBase !== '' ? $appBase : '') . '/uploads/products/';
    $fsBase = dirname(__DIR__) . '/uploads/products/';

    $mainImage = buildPublicImageUrl((string)($row['image'] ?? ''), $fsBase, $base);

    $gallery = [];
    if (!empty($row['image_gallery'])) {
        $decoded = json_decode((string)$row['image_gallery'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $f) {
                if (!is_string($f) || trim($f) === '') {
                    continue;
                }
                $url = buildPublicImageUrl($f, $fsBase, $base);
                if ($url !== null) {
                    $gallery[] = $url;
                }
            }
        } else {
            // Backward compatibility for rows where image_gallery is a single filename.
            $single = trim((string)$row['image_gallery']);
            if ($single !== '') {
                $url = buildPublicImageUrl($single, $fsBase, $base);
                if ($url !== null) {
                    $gallery[] = $url;
                }
            }
        }
    }

    $images = array_values(array_unique(array_filter(
        array_merge($mainImage ? [$mainImage] : [], $gallery)
    )));

    if (empty($images)) {
        $images = [($appBase !== '' ? $appBase : '') . '/New%20folder/Resources/Images/glasses1.png'];
    }

    $frameWidth = textValue($row['frameWidth'] ?? '');
    $frameHeight = textValue($row['frameHeight'] ?? '');
    $templeLength = textValue($row['templeLength'] ?? '');
    $lensWidth = textValue($row['lensWidth'] ?? '');
    $material = textValue($row['material'] ?? '');
    $color = textValue($row['color'] ?? '');

    if ($frameHeight === '' && $frameWidth !== '') {
        $frameHeight = $frameWidth;
    }
    if ($lensWidth === '' && $frameWidth !== '') {
        $lensWidth = $frameWidth;
    }
    if ($color === '') {
        $color = categoryColorFallback((string)($row['category'] ?? ''));
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
        'frameWidth'   => $frameWidth,
        'frameHeight'  => nullableFloat($frameHeight),
        'templeLength' => $templeLength,
        'lensWidth'    => nullableFloat($lensWidth),
        'material'     => $material,
        'color'        => $color,
    ];
}

function buildPublicImageUrl(string $filename, string $fsBase, string $publicBase): ?string
{
    $clean = trim($filename);
    if ($clean === '') {
        return null;
    }

    $clean = ltrim($clean, '/\\');
    $fullPath = $fsBase . $clean;
    if (!is_file($fullPath)) {
        return null;
    }

    $segments = array_map('rawurlencode', explode('/', str_replace('\\', '/', $clean)));
    return $publicBase . implode('/', $segments);
}
?>