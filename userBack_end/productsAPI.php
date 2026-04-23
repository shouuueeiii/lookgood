<?php

error_reporting(0);
ob_start();
require_once '../config.php';
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';
ob_end_clean();

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

    if (!empty($_GET['id'])) {
        $id   = trim($_GET['id']);
        $stmt = $conn->prepare(
            "SELECT p.product_id, p.name, p.description, p.price, p.stock, p.category,
            p.image, p.image_gallery, p.frameWidth, p.frameHeight, p.templeLength, p.lensWidth, p.material, p.color, p.status,
            s.sales_id, s.sale_price, s.start_date AS sale_start_date, s.end_date AS sale_end_date, s.sale_label,
            COALESCE((
                SELECT SUM(oi.quantity)
                FROM order_items oi
                INNER JOIN checkout co ON co.order_id = oi.order_id
                WHERE oi.product_id = p.product_id
                AND co.status NOT IN ('cancelled','refunded')
            ), 0) AS sold_count
            FROM products p
            LEFT JOIN sales s ON s.product_id = p.product_id
            WHERE p.product_id = ? AND p.status = 'active'"
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

    $where  = "WHERE p.status = 'active'";
    $types  = '';
    $params = [];

    if (!empty($_GET['category'])) {
        $cat    = trim($_GET['category']);
        $where .= " AND p.category = ?";
        $types   = 's';
        $params[] = $cat;
    }

        $sql  = "SELECT p.product_id, p.name, p.description, p.price, p.stock, p.category,
                                        p.image, p.image_gallery, p.frameWidth, p.frameHeight, p.templeLength, p.lensWidth, p.material, p.color, p.status,
                                        s.sales_id, s.sale_price, s.start_date AS sale_start_date, s.end_date AS sale_end_date, s.sale_label
                        FROM products p
                        LEFT JOIN sales s ON s.product_id = p.product_id
                        $where ORDER BY
                            CASE
                                WHEN p.product_id LIKE 'LGF-M-%' THEN 1
                                WHEN p.product_id LIKE 'LGF-W-%' THEN 2
                                WHEN p.product_id LIKE 'LGF-U-%' THEN 3
                                ELSE 4
                            END ASC,
                            CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(p.product_id, '-', 3), '-', -1) AS UNSIGNED) ASC,
                            p.product_id ASC";
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

    $today = date('Y-m-d');
    $onSale = isset($row['sales_id']) && $row['sales_id'] !== null
        && isset($row['sale_start_date']) && $row['sale_start_date'] <= $today
        && isset($row['sale_end_date'])   && $row['sale_end_date']   >= $today;

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
        'soldCount'    => (int)($row['sold_count'] ?? 0),
        'onSale'       => $onSale,
        'salePrice'    => ($onSale && isset($row['sale_price'])) ? (int)$row['sale_price'] : null,
        'saleStartDate'=> isset($row['sale_start_date']) ? (string)$row['sale_start_date'] : null,
        'saleEndDate'  => isset($row['sale_end_date'])   ? (string)$row['sale_end_date']   : null,
        'saleLabel'    => isset($row['sale_label'])      ? (string)$row['sale_label']       : null,
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