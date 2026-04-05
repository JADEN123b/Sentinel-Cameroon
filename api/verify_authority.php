<?php
session_start();
require_once '../database/config.php';

header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = new Database();

// Verify that the acting user is an admin
$actor = $db->fetch("SELECT role FROM users WHERE id = ?", [$_SESSION['user_id']]);
if (!$actor || $actor['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Only admins can verify authorities.']);
    exit;
}

$target_user_id = $input['user_id'] ?? null;
$status = $input['status'] ?? null;

if (!$target_user_id || !in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $db->conn->beginTransaction();

    // Update the application status
    $db->query("UPDATE authority_applications SET status = ? WHERE user_id = ?", [$status, $target_user_id]);

    // If approved, upgrade the user's role to 'authority', otherwise keep them as 'user' (they lose pending)
    if ($status === 'approved') {
        $db->query("UPDATE users SET role = 'authority', is_verified = 1 WHERE id = ?", [$target_user_id]);
    } else {
        $db->query("UPDATE users SET role = 'user' WHERE id = ?", [$target_user_id]);
    }

    $db->conn->commit();

    // Super Admin Oversight: Log the authority verification result
    logSystemActivity("Authority application $status for user ID: $target_user_id", "security");

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
