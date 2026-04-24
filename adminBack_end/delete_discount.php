<?php
require_once '../config.php';
require_once '../auth_admin.php';
requireAdmin();

// ── Role-based access control ─────────────────────────────────

$_pos = $_SESSION['position'] ?? '';
if ($_pos !== 'head' && $_pos !== 'inventory_orderAdmin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: insufficient permissions']);
    exit();
}


function pushAdminNotification(mysqli $conn, string $type, string $title, string $message, array $data, string $sourceKey): void {
    $conn->query("CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(30) NOT NULL DEFAULT 'status',
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        data_json TEXT NULL,
        source_key VARCHAR(191) NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_admin_notifications_source (source_key)
    )");
    $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($payload === false) $payload = '{}';
    $stmt = $conn->prepare(
        'INSERT INTO admin_notifications (type, title, message, data_json, source_key, is_read)
         VALUES (?, ?, ?, ?, ?, 0)
         ON DUPLICATE KEY UPDATE id = id'
    );
    if (!$stmt) return;
    $stmt->bind_param('sssss', $type, $title, $message, $payload, $sourceKey);
    $stmt->execute();
    $stmt->close();
}

$id = $_GET['product_id'] ?? '';
if (empty($id)) {
    echo json_encode(['error' => 'Missing product ID']);
    exit();
}

// Fetch image filenames before deleting
$stmt = $conn->prepare("SELECT name, image, image_gallery FROM products WHERE product_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$row    = $result->fetch_assoc();
$productName = $id;

if ($row) {
    $productName = (string)($row['name'] ?? $id);
    // Delete main image
    if (!empty($row['image'])) {
        $path = '../uploads/products/' . $row['image'];
        if (file_exists($path)) unlink($path);
    }
    // Delete gallery images
    if (!empty($row['image_gallery'])) {
        $gallery = json_decode($row['image_gallery'], true);
        if (is_array($gallery)) {
            foreach ($gallery as $filename) {
                $path = '../uploads/products/' . $filename;
                if (file_exists($path)) unlink($path);
            }
        }
    }
}

$stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();

if (($stmt->affected_rows ?? 0) > 0) {
    pushAdminNotification(
        $conn,
        'product',
        'Product Deleted',
        $productName . ' (' . $id . ') was deleted from the catalog.',
        ['productId' => $id],
        'product:delete:' . $id . ':' . date('YmdHi')
    );
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
exit();
?>
