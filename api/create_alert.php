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

// Verify that the acting user is an authority or admin
$actor = $db->fetch("SELECT role FROM users WHERE id = ?", [$_SESSION['user_id']]);
if (!$actor || ($actor['role'] !== 'authority' && $actor['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Only verified authorities can broadcast alerts.']);
    exit;
}

$title = $input['title'] ?? '';
$message = $input['message'] ?? '';
$type = $input['type'] ?? 'info';
$duration_hours = (int)($input['duration'] ?? 24);

if (empty($title) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Title and message are required.']);
    exit;
}

// Map the HTML form types to DB enum priorities
$priority_map = [
    'info' => 'low',
    'warning' => 'high',
    'emergency' => 'critical'
];
$priority = $priority_map[$type] ?? 'low';

try {
    // Calculate expiration
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$duration_hours} hours"));

    $db->query("
        INSERT INTO alerts (title, message, alert_type, priority, target_audience, is_active, expires_at, created_by, created_at)
        VALUES (?, ?, ?, ?, 'all', 1, ?, ?, NOW())
    ", [$title, $message, $type, $priority, $expires_at, $_SESSION['user_id']]);

    
    // Auto-fire an email notification if it's an emergency alert?
    // We already have a strong mailer, but we'll stick to UI for now to avoid spamming the whole DB.

    // Super Admin Oversight: Log the new alert
    logSystemActivity("New Safety Alert broadcasted: $title ($type)", "alert");

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
