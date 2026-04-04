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

// Check for POST action (via FormData or JSON)
$action = $_POST['action'] ?? null;

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Missing action.']);
    exit;
}

switch ($action) {
    case 'create_item':
        $title = $_POST['title'] ?? '';
        $price = $_POST['price'] ?? 0;
        $category = $_POST['category'] ?? 'Other';
        $description = $_POST['description'] ?? '';
        
        if (empty($title) || empty($price) || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }

        // Handle Image Upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/market/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $filename = uniqid('item_', true) . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $target = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_path = 'uploads/market/' . $filename;
            }
        }

        $res = $db->query("
            INSERT INTO marketplace_items (seller_id, title, description, price, category, image_path) 
            VALUES (?, ?, ?, ?, ?, ?)
        ", [$current_user_id, $title, $description, $price, $category, $image_path]);

        if ($res) {
            logSystemActivity("Listed a new marketplace item: $title", "market");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        break;

    case 'mark_sold':
        $item_id = $_POST['item_id'] ?? 0;
        $res = $db->query("
            UPDATE marketplace_items SET status = 'sold' 
            WHERE id = ? AND seller_id = ?
        ", [$item_id, $current_user_id]);
        
        echo json_encode(['success' => $res]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}
?>
