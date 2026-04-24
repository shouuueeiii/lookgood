<?php

require_once '../config.php';
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';

header('Content-Type: application/json');

// Must be logged in as user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

$conn->query("
    CREATE TABLE IF NOT EXISTS user_addresses (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id       INT UNSIGNED NOT NULL,
        label         VARCHAR(30)  NOT NULL DEFAULT 'Home',
        address_line1 VARCHAR(255) NOT NULL,
        address_line2 VARCHAR(255) NOT NULL DEFAULT '',
        city          VARCHAR(100) NOT NULL,
        province      VARCHAR(100) NOT NULL,
        zip_code      CHAR(4)      NOT NULL,
        region        VARCHAR(20)  NOT NULL DEFAULT '',
        delivery_notes VARCHAR(255) NOT NULL DEFAULT '',
        is_default    TINYINT(1)   NOT NULL DEFAULT 0,
        created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        CONSTRAINT fk_ua_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

if ($method === 'GET') {
    $stmt = $conn->prepare(
        "SELECT id, label, address_line1, address_line2, city, province, zip_code, region, delivery_notes, is_default, created_at
         FROM user_addresses
         WHERE user_id = ?
         ORDER BY is_default DESC, created_at ASC"
    );
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Query prepare failed']);
        exit();
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $addresses = [];
    while ($row = $result->fetch_assoc()) {
        $addresses[] = [
            'id'            => (int) $row['id'],
            'label'         => $row['label'],
            'address_line1' => $row['address_line1'],
            'address_line2' => $row['address_line2'],
            'city'          => $row['city'],
            'province'      => $row['province'],
            'zip_code'      => $row['zip_code'],
            'region'        => $row['region'],
            'delivery_notes'=> $row['delivery_notes'],
            'is_default'    => (int) $row['is_default'],
        ];
    }
    $stmt->close();
    echo json_encode($addresses);
    exit();
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = trim($input['action'] ?? 'create');

    if ($action === 'set_default') {
        $addrId = (int) ($input['id'] ?? 0);
        if (!$addrId) {
            http_response_code(400);
            echo json_encode(['error' => 'Address ID required']);
            exit();
        }
        // Verify ownership
        $chk = $conn->prepare("SELECT id FROM user_addresses WHERE id = ? AND user_id = ? LIMIT 1");
        $chk->bind_param('ii', $addrId, $userId);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) {
            $chk->close();
            http_response_code(403);
            echo json_encode(['error' => 'Address not found']);
            exit();
        }
        $chk->close();

        $conn->begin_transaction();
        $clear = $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
        $clear->bind_param('i', $userId);
        $clear->execute();
        $clear->close();

        $set = $conn->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $set->bind_param('ii', $addrId, $userId);
        $set->execute();
        $set->close();
        $conn->commit();

        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === 'create' || $action === 'update') {
        $addrId    = (int) ($input['id'] ?? 0);
        $label     = trim($input['label']          ?? 'Home');
        $line1     = trim($input['address_line1']  ?? '');
        $line2     = trim($input['address_line2']  ?? '');
        $city      = trim($input['city']           ?? '');
        $province  = trim($input['province']       ?? '');
        $zip       = trim($input['zip_code']       ?? '');
        $region    = trim($input['region']         ?? '');
        $notes     = trim($input['delivery_notes'] ?? '');
        $isDefault = (int) ($input['is_default']   ?? 0);

        // Validation
        $errors = [];
        if (strlen($line1) < 3)           $errors[] = 'Address line 1 is required';
        if (strlen($city) < 2)            $errors[] = 'City is required';
        if (strlen($province) < 2)        $errors[] = 'Province is required';
        if (!preg_match('/^\d{4}$/', $zip)) $errors[] = 'ZIP code must be 4 digits';
        if (!$region)                     $errors[] = 'Region is required';

        if ($errors) {
            http_response_code(400);
            echo json_encode(['error' => implode('. ', $errors)]);
            exit();
        }

        if (!$label) $label = 'Home';

        if ($action === 'create') {
            $cap = $conn->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
            $cap->bind_param('i', $userId);
            $cap->execute();
            $cap->bind_result($count);
            $cap->fetch();
            $cap->close();
            if ($count >= 10) {
                http_response_code(400);
                echo json_encode(['error' => 'You can save up to 10 addresses only']);
                exit();
            }
        }

        $conn->begin_transaction();

        if ($isDefault) {
            $clear = $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
            $clear->bind_param('i', $userId);
            $clear->execute();
            $clear->close();
        }

        if ($action === 'create') {

            $cnt = $conn->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
            $cnt->bind_param('i', $userId);
            $cnt->execute();
            $cnt->bind_result($total);
            $cnt->fetch();
            $cnt->close();
            if ($total === 0) $isDefault = 1;

            $ins = $conn->prepare(
                "INSERT INTO user_addresses (user_id, label, address_line1, address_line2, city, province, zip_code, region, delivery_notes, is_default)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $ins->bind_param('issssssssi', $userId, $label, $line1, $line2, $city, $province, $zip, $region, $notes, $isDefault);
            $ok = $ins->execute();
            $newId = $conn->insert_id;
            $ins->close();

            if (!$ok) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create address']);
                exit();
            }
            $conn->commit();
            echo json_encode(['success' => true, 'id' => $newId]);
            exit();
        }

        if ($action === 'update') {
            if (!$addrId) {
                $conn->rollback();
                http_response_code(400);
                echo json_encode(['error' => 'Address ID required for update']);
                exit();
            }
            $upd = $conn->prepare(
                "UPDATE user_addresses
                 SET label=?, address_line1=?, address_line2=?, city=?, province=?, zip_code=?, region=?, delivery_notes=?, is_default=?
                 WHERE id=? AND user_id=?"
            );
            $upd->bind_param('ssssssssii', $label, $line1, $line2, $city, $province, $zip, $region, $notes, $isDefault, $addrId, $userId);
            $ok = $upd->execute();
            $upd->close();

            if (!$ok) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update address']);
                exit();
            }
            $conn->commit();
            echo json_encode(['success' => true]);
            exit();
        }
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
    exit();
}

if ($method === 'DELETE') {
    $input  = json_decode(file_get_contents('php://input'), true);
    $addrId = (int) ($input['id'] ?? 0);

    if (!$addrId) {
        http_response_code(400);
        echo json_encode(['error' => 'Address ID required']);
        exit();
    }

    // Check ownership + whether it's the default
    $chk = $conn->prepare("SELECT is_default FROM user_addresses WHERE id = ? AND user_id = ? LIMIT 1");
    $chk->bind_param('ii', $addrId, $userId);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) {
        $chk->close();
        http_response_code(403);
        echo json_encode(['error' => 'Address not found']);
        exit();
    }
    $chk->bind_result($wasDefault);
    $chk->fetch();
    $chk->close();

    $del = $conn->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
    $del->bind_param('ii', $addrId, $userId);
    $ok = $del->execute();
    $del->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete address']);
        exit();
    }

    if ($wasDefault) {
        $promote = $conn->prepare(
            "UPDATE user_addresses SET is_default = 1 WHERE user_id = ? ORDER BY created_at ASC LIMIT 1"
        );
        $promote->bind_param('i', $userId);
        $promote->execute();
        $promote->close();
    }

    echo json_encode(['success' => true]);
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>