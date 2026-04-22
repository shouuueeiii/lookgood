<?php
/**
 * adminBack_end/bestSellersAPI.php
 * Returns the top 5 best-selling active products ranked by total units sold.
 * Joins order_items + checkout (completed orders only) + products + sales.
 * Public read endpoint — no auth required.
 */

require_once '../config.php';
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

/* ── helpers (shared pattern with sale_product.php) ─────────────── */
function bs_buildPublicImageUrl(string $filename, string $fsBase, string $publicBase): ?string {
    $clean = trim($filename);
    if ($clean === '') return null;
    $clean    = ltrim($clean, '/\\');
    $fullPath = $fsBase . $clean;
    if (!is_file($fullPath)) return null;
    $segments = array_map('rawurlencode', explode('/', str_replace('\\', '/', $clean)));
    return $publicBase . implode('/', $segments);
}

function bs_formatProduct(array $row): array {
    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $appBase    = rtrim(str_replace('\\', '/', dirname(dirname($scriptName))), '/');
    $publicBase = ($appBase !== '' ? $appBase : '') . '/uploads/products/';
    $fsBase     = dirname(__DIR__) . '/uploads/products/';

    $mainImage = bs_buildPublicImageUrl((string)($row['image'] ?? ''), $fsBase, $publicBase);

    $gallery = [];
    if (!empty($row['image_gallery'])) {
        $decoded = json_decode((string)$row['image_gallery'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $f) {
                if (!is_string($f) || trim($f) === '') continue;
                $url = bs_buildPublicImageUrl($f, $fsBase, $publicBase);
                if ($url !== null) $gallery[] = $url;
            }
        }
    }

    $images = array_values(array_unique(array_filter(
        array_merge($mainImage ? [$mainImage] : [], $gallery)
    )));

    if (empty($images)) {
        $images = [($appBase !== '' ? $appBase : '') . '/New%20folder/Resources/Images/glasses1.png'];
    }

    // sale info (may be null if product is not currently on sale)
    $onSale    = isset($row['sales_id']) && $row['sales_id'] !== null;
    $salePrice = $onSale ? (int)$row['sale_price'] : null;

    return [
        'id'          => (string)$row['product_id'],
        'name'        => (string)$row['name'],
        'price'       => (float)$row['price'],
        'stock'       => (int)$row['stock'],
        'category'    => (string)($row['category'] ?? ''),
        'image'       => $images[0],
        'images'      => $images,
        'unitsSold'   => (int)$row['units_sold'],
        'onSale'      => $onSale,
        'salePrice'   => $salePrice,
        'saleLabel'   => $onSale ? trim((string)($row['sale_label'] ?? '')) : null,
        'saleEndDate' => $onSale ? (string)($row['sale_end_date'] ?? '') : null,
    ];
}

/* ── query: top 5 by units sold across completed/delivered orders ── */
$stmt = $conn->prepare(
    "SELECT
        p.product_id, p.name, p.price, p.stock, p.category,
        p.image, p.image_gallery,
        COALESCE(SUM(oi.quantity), 0) AS units_sold,
        s.sales_id,
        s.sale_price,
        s.end_date   AS sale_end_date,
        s.sale_label
     FROM products p
     INNER JOIN order_items oi ON oi.product_id = p.product_id
     INNER JOIN checkout    c  ON c.order_id    = oi.order_id
     LEFT  JOIN sales       s  ON s.product_id  = p.product_id
                               AND CURDATE() BETWEEN s.start_date AND s.end_date
     WHERE p.status = 'active'
       AND c.status IN ('completed', 'delivered')
     GROUP BY p.product_id, p.name, p.price, p.stock, p.category,
              p.image, p.image_gallery,
              s.sales_id, s.sale_price, s.end_date, s.sale_label
     ORDER BY units_sold DESC
     LIMIT 5"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Query prepare failed: ' . $conn->error]);
    exit();
}

$stmt->execute();
$result   = $stmt->get_result();
$products = [];
$rank     = 1;

while ($row = $result->fetch_assoc()) {
    $p         = bs_formatProduct($row);
    $p['rank'] = $rank++;
    $products[] = $p;
}

$stmt->close();
echo json_encode($products);
?>