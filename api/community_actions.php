<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login again.']);
    exit;
}

$db = new Database();
$current_user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing action parameter.']);
    exit;
}

switch ($data['action']) {
    case 'create':
        if (empty($data['name']) || empty($data['description'])) {
            echo json_encode(['success' => false, 'message' => 'Name and description are required.']);
            exit;
        }

        $db->beginTransaction();
        try {
            // 1. Create Community
            $db->query("
                INSERT INTO communities (name, description, creator_id) 
                VALUES (?, ?, ?)
            ", [$data['name'], $data['description'], $current_user_id]);
            
            $community_id = $db->lastInsertId();

            // 2. Add creator as admin member
            $db->query("
                INSERT INTO community_members (community_id, user_id, role) 
                VALUES (?, ?, 'admin')
            ", [$community_id, $current_user_id]);

            $db->commit();
            logSystemActivity("Created a new community: " . $data['name'], "community");
            echo json_encode(['success' => true, 'message' => 'Community created!']);
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;

    case 'join':
        if (!isset($data['community_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing community ID.']);
            exit;
        }

        try {
            $db->query("
                INSERT INTO community_members (community_id, user_id) 
                VALUES (?, ?)
            ", [$data['community_id'], $current_user_id]);
            
            logSystemActivity("Joined community ID: " . $data['community_id'], "community");
            echo json_encode(['success' => true, 'message' => 'Joined successfully!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'You are already a member or error occurred.']);
        }
        break;

    case 'leave':
        if (!isset($data['community_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing community ID.']);
            exit;
        }

        $db->query("
            DELETE FROM community_members 
            WHERE community_id = ? AND user_id = ?
        ", [$data['community_id'], $current_user_id]);

        logSystemActivity("Left community ID: " . $data['community_id'], "community");
        echo json_encode(['success' => true, 'message' => 'Left community.']);
        break;

    case 'update':
        $cid = $_POST['community_id'] ?? null;
        if (!$cid) exit;
        
        // Admin check
        $role = $db->query("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?", [$cid, $current_user_id])->fetch();
        if (!$role || $role['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }

        $name = $_POST['name'] ?? '';
        $desc = $_POST['description'] ?? '';
        
        // Image
        $img = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $dir = '../uploads/communities/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $fn = uniqid('comm_', true) . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dir.$fn)) {
                $img = 'uploads/communities/'.$fn;
            }
        }

        $sql = "UPDATE communities SET name = ?, description = ?" . ($img ? ", image_path = ?" : "") . " WHERE id = ?";
        $p = [$name, $desc];
        if ($img) $p[] = $img;
        $p[] = $cid;

        if ($db->query($sql, $p)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}
?>
