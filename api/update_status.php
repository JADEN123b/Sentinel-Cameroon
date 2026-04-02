<?php
header('Content-Type: application/json');
session_start();
require_once '../database/config.php';
require_once '../includes/auth.php';

// Set global security headers
setSecurityHeaders();

// Check if user is logged in and has authority role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'authority') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Authority access required.'
    ]);
    exit;
}

// CSRF Check for API
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($csrf_token)) {
    echo json_encode([
        'success' => false,
        'message' => 'CSRF token validation failed.'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['incident_id']) && isset($data['status'])) {
    $incident_id = $data['incident_id'];
    $new_status = $data['status'];
    
    // Validate status
    $valid_statuses = ['reported', 'verified', 'investigating', 'resolved'];
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid status.'
        ]);
        exit;
    }
    
    $db = new Database();
    
    // Check if incident exists
    $incident = $db->query("SELECT id FROM incidents WHERE id = ?", [$incident_id])->fetch();
    
    if ($incident) {
        // Update incident status
        $stmt = $db->query("UPDATE incidents SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$new_status, $incident_id]);
        
        if ($stmt) {
            echo json_encode([
                'success' => true,
                'message' => 'Incident status updated successfully.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update status.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Incident not found.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
}
?>
