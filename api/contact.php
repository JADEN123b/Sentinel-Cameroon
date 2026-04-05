<?php
header('Content-Type: application/json');
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'contact_partner') {
    $partner_id = $_POST['partner_id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($partner_id) || empty($name) || empty($email) || empty($message)) {
        $errors[] = 'All fields are required.';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (empty($errors)) {
        $db = new Database();
        
        // Get partner info
        $partner = $db->fetch("SELECT * FROM partners WHERE id = ?", [$partner_id]);
        
        if ($partner) {
            // Create contact record (you could create a separate table for this)
            $contact_data = [
                'partner_name' => $partner['name'],
                'contact_name' => $name,
                'contact_email' => $email,
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // For now, just return success (in real app, you'd email the partner)
            echo json_encode([
                'success' => true,
                'message' => 'Your message has been sent to ' . htmlspecialchars($partner['name']) . '. They will contact you soon.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Partner not found.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Validation errors: ' . implode(', ', $errors)
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
}
?>
