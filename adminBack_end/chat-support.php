<?php
header('Content-Type: application/json');

if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'admin');
require_once __DIR__ . '/../session_bootstrap.php';
require_once __DIR__ . '/../config.php';

// Check if admin is authenticated
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'get_all';

try {
    // Ensure table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'chat_support_messages'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        echo json_encode([
            'success' => true,
            'messages' => [],
            'total' => 0
        ]);
        exit;
    }

    if ($action === 'get_all') {
        // Get all unread chat support messages (grouped by email)
        $stmt = $conn->prepare('
            SELECT 
                guest_email,
                guest_name,
                COUNT(*) as message_count,
                MAX(created_at) as last_message_time,
                GROUP_CONCAT(message SEPARATOR "\n---\n" ORDER BY created_at DESC LIMIT 1) as latest_message
            FROM chat_support_messages
            WHERE sender_type = "user"
            GROUP BY guest_email, guest_name
            ORDER BY last_message_time DESC
        ');
        
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $messages = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'messages' => $messages,
                'total' => count($messages)
            ]);
            exit;
        }
    }

    if ($action === 'get_conversation') {
        $email = isset($_GET['email']) ? trim($_GET['email']) : '';
        
        if (!$email) {
            echo json_encode(['success' => false, 'error' => 'Email required']);
            exit;
        }

        $stmt = $conn->prepare('
            SELECT * FROM chat_support_messages 
            WHERE guest_email = ?
            ORDER BY created_at ASC
        ');
        
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $messages = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'messages' => $messages,
                'total' => count($messages)
            ]);
            exit;
        }
    }

    if ($action === 'send_reply') {
        // Admin sends a reply message
        $input = json_decode(file_get_contents('php://input'), true);
        $guestEmail = trim($input['guest_email'] ?? '');
        $replyMessage = trim($input['message'] ?? '');
        
        if (!$guestEmail || !$replyMessage) {
            echo json_encode(['success' => false, 'error' => 'Invalid input']);
            exit;
        }

        $stmt = $conn->prepare('INSERT INTO chat_support_messages (guest_email, guest_name, message, sender_type) VALUES (?, ?, ?, ?)');
        
        if ($stmt) {
            $adminName = 'LookGood Support';
            $senderType = 'admin';
            $stmt->bind_param('ssss', $guestEmail, $adminName, $replyMessage, $senderType);
            $stmt->execute();
            $messageId = $conn->insert_id;
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'id' => $messageId,
                'message' => 'Reply sent successfully'
            ]);
            exit;
        }
    }

    if ($action === 'mark_read') {
        $email = isset($_GET['email']) ? trim($_GET['email']) : '';
        
        if (!$email) {
            echo json_encode(['success' => false, 'error' => 'Email required']);
            exit;
        }

        $stmt = $conn->prepare('UPDATE chat_support_messages SET is_read = 1 WHERE guest_email = ? AND sender_type = ?');
        
        if ($stmt) {
            $senderType = 'user';
            $stmt->bind_param('ss', $email, $senderType);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode(['success' => true]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'error' => 'Invalid action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
