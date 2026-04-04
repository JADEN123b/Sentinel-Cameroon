<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$db = new Database();
$current_user_id = $_SESSION['user_id'];
$data = $_SERVER['REQUEST_METHOD'] === 'POST' ? json_decode(file_get_contents('php://input'), true) : $_GET;

if (!isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing action.']);
    exit;
}

// Security: Check if user is a member of the community
if (isset($data['community_id'])) {
    $is_member = $db->query("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?", [$data['community_id'], $current_user_id])->fetch();
    if (!$is_member) {
        echo json_encode(['success' => false, 'message' => 'Not a member.']);
        exit;
    }
}

switch ($data['action']) {
    case 'send':
        if (empty($data['message']) || empty($data['community_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing message content.']);
            exit;
        }

        $res = $db->query("
            INSERT INTO community_messages (community_id, user_id, message) 
            VALUES (?, ?, ?)
        ", [$data['community_id'], $current_user_id, $data['message']]);

        if ($res) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send message.']);
        }
        break;

    case 'pin':
        if (empty($data['message_id']) || empty($data['community_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing ID.']);
            exit;
        }
        // Only admin/mod can pin
        if ($is_member['role'] !== 'admin' && $is_member['role'] !== 'moderator') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }

        $db->beginTransaction();
        try {
            // Unpin others
            $db->query("UPDATE community_messages SET is_pinned = 0 WHERE community_id = ?", [$data['community_id']]);
            // Pin this one
            $db->query("UPDATE community_messages SET is_pinned = 1 WHERE id = ?", [$data['message_id']]);
            $db->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) { $db->rollback(); echo json_encode(['success' => false]); }
        break;

    case 'unpin':
        if (empty($data['community_id'])) exit;
        if ($is_member['role'] !== 'admin' && $is_member['role'] !== 'moderator') exit;
        $db->query("UPDATE community_messages SET is_pinned = 0 WHERE community_id = ?", [$data['community_id']]);
        echo json_encode(['success' => true]);
        break;

    case 'delete':
        if (empty($data['message_id'])) exit;
        $msg = $db->query("SELECT user_id FROM community_messages WHERE id = ?", [$data['message_id']])->fetch();
        if (!$msg) exit;
        
        // Author or Admin can delete
        if ($msg['user_id'] != $current_user_id && $is_member['role'] !== 'admin' && $is_member['role'] !== 'moderator') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }

        $db->query("UPDATE community_messages SET is_deleted = 1 WHERE id = ?", [$data['message_id']]);
        echo json_encode(['success' => true]);
        break;

    case 'fetch':
        if (empty($data['community_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing community ID for fetch.']);
            exit;
        }

        $last_id = isset($data['last_id']) ? (int)$data['last_id'] : 0;
        
        $messages = $db->query("
            SELECT m.*, u.full_name, u.profile_picture 
            FROM community_messages m
            JOIN users u ON m.user_id = u.id
            WHERE m.community_id = ? AND m.id > ? AND m.is_deleted = 0
            ORDER BY m.created_at ASC
            LIMIT 50
        ", [$data['community_id'], $last_id])->fetchAll();

        // Also fetch pinned message for the header
        $pinned = $db->query("
            SELECT m.*, u.full_name 
            FROM community_messages m
            JOIN users u ON m.user_id = u.id
            WHERE m.community_id = ? AND m.is_pinned = 1 AND m.is_deleted = 0
            LIMIT 1
        ", [$data['community_id']])->fetch();

        echo json_encode(['success' => true, 'messages' => $messages, 'pinned' => $pinned]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}
?>
