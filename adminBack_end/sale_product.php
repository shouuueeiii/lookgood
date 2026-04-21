<?php


require_once '../config.php';
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        exit();
    }

    $action    = trim($input['action']     ?? '');
    $productId = trim($input['product_id'] ?? '');

    if (!$productId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'product_id is required']);
        exit();
    }

    $chk = $conn->prepare("SELECT product_id, name, price FROM products WHERE product_id = ? LIMIT 1");
    $chk->bind_param('s', $productId);
    $chk->execute();
    $product = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        exit();
    }

    /* ── APPLY ─────────────────────────────────────────────────── */
    if ($action === 'apply') {

        $salePrice = isset($input['sale_price']) ? (int)$input['sale_price'] : 0;
        $startDate = trim($input['sale_start_date'] ?? '');
        $endDate   = trim($input['sale_end_date']   ?? '');
        $saleLabel = trim($input['sale_label']      ?? '') ?: null;

        // Validate sale price
        if ($salePrice <= 0) {
            echo json_encode(['success' => false, 'error' => 'A valid sale price is required']);
            exit();
        }
        if ($salePrice >= (int)$product['price']) {
            echo json_encode(['success' => false,
                'error' => 'Sale price must be less than the original price (₱' . number_format($product['price']) . ')']);
            exit();
        }

        if ($startDate && $endDate && strtotime($startDate) >= strtotime($endDate)) {
            echo json_encode(['success' => false, 'error' => 'End date must be after start date']);
            exit();
        }

        // Default dates to today / +30 days if not supplied
        if (!$startDate) $startDate = date('Y-m-d');
        if (!$endDate)   $endDate   = date('Y-m-d', strtotime('+30 days'));

        // INSERT or UPDATE the sales row
        $existing = $conn->prepare("SELECT sales_id FROM sales WHERE product_id = ? LIMIT 1");
        $existing->bind_param('s', $productId);
        $existing->execute();
        $existingRow = $existing->get_result()->fetch_assoc();
        $existing->close();

        if ($existingRow) {
            $stmt = $conn->prepare(
                "UPDATE sales SET sale_price = ?, start_date = ?, end_date = ?, sale_label = ?
                WHERE product_id = ?"
            );
            $stmt->bind_param('issss', $salePrice, $startDate, $endDate, $saleLabel, $productId);
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO sales (product_id, sale_price, start_date, end_date, sale_label)
                VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('sisss', $productId, $salePrice, $startDate, $endDate, $saleLabel);
        }

        if ($stmt->execute()) {
            $stmt->close();
            echo json_encode([
                'success'    => true,
                'message'    => 'Sale applied to ' . $product['name'],
                'on_sale'    => true,
                'sale_price' => $salePrice,
            ]);
        } else {
            $err = $stmt->error;
            $stmt->close();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'DB error: ' . $err]);
        }
        exit();
    }

    if ($action === 'remove') {

        $stmt = $conn->prepare("DELETE FROM sales WHERE product_id = ?");
        $stmt->bind_param('s', $productId);

        if ($stmt->execute()) {
            $stmt->close();
            echo json_encode([
                'success' => true,
                'message' => 'Sale removed from ' . $product['name'],
                'on_sale' => false,
            ]);
        } else {
            $err = $stmt->error;
            $stmt->close();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'DB error: ' . $err]);
        }
        exit();
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action. Use "apply" or "remove"']);
    exit();
}


function sp_buildPublicImageUrl(string $filename, string $fsBase, string $publicBase): ?string {
    $clean = trim($filename);
    if ($clean === '') return null;
    $clean    = ltrim($clean, '/\\');
    $fullPath = $fsBase . $clean;
    if (!is_file($fullPath)) return null;
    $segments = array_map('rawurlencode', explode('/', str_replace('\\', '/', $clean)));
    return $publicBase . implode('/', $segments);
}

function sp_formatProduct(array $row): array {
    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $appBase    = rtrim(str_replace('\\', '/', dirname(dirname($scriptName))), '/');
    $publicBase = ($appBase !== '' ? $appBase : '') . '/uploads/products/';
    $fsBase     = dirname(__DIR__) . '/uploads/products/';

    $mainImage = sp_buildPublicImageUrl((string)($row['image'] ?? ''), $fsBase, $publicBase);

    $gallery = [];
    if (!empty($row['image_gallery'])) {
        $decoded = json_decode((string)$row['image_gallery'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $f) {
                if (!is_string($f) || trim($f) === '') continue;
                $url = sp_buildPublicImageUrl($f, $fsBase, $publicBase);
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

    $origPrice   = (float)$row['price'];
    $salePrice   = (int)$row['sale_price'];
    $discountPct = $origPrice > 0
        ? (int)round((($origPrice - $salePrice) / $origPrice) * 100)
        : 0;

    return [
        'id'            => (string)$row['product_id'],
        'name'          => $row['name'],
        'description'   => $row['description'] ?? '',
        'category'      => $row['category'],
        'price'         => $origPrice,
        'stock'         => (int)$row['stock'],
        'image'         => $images[0],
        'images'        => $images,
        'onSale'        => true,
        'salePrice'     => $salePrice,
        'saleLabel'     => trim((string)($row['sale_label'] ?? '')),
        'saleStartDate' => (string)($row['sale_start_date'] ?? ''),
        'saleEndDate'   => (string)($row['sale_end_date']   ?? ''),
        'discountPct'   => $discountPct,
    ];
}

$stmt = $conn->prepare(
    "SELECT
        p.product_id, p.name, p.description, p.price, p.stock, p.category,
        p.image, p.image_gallery,
        s.sales_id,
        s.sale_price,
        s.start_date AS sale_start_date,
        s.end_date   AS sale_end_date,
        s.sale_label
    FROM products p
    INNER JOIN sales s ON s.product_id = p.product_id
    WHERE p.status = 'active'
    AND CURDATE() BETWEEN s.start_date AND s.end_date
    ORDER BY s.sales_id DESC"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Query prepare failed: ' . $conn->error]);
    exit();
}

$stmt->execute();
$result   = $stmt->get_result();
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = sp_formatProduct($row);
}

$stmt->close();
echo json_encode($products);
?>