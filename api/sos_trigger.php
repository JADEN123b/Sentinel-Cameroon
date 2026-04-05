<?php
require_once '../includes/auth.php';
require_once '../database/config.php';
require_once '../includes/phpmailer_email_service.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$lat  = isset($data['latitude'])  ? floatval($data['latitude'])  : null;
$lng  = isset($data['longitude']) ? floatval($data['longitude']) : null;
$address = 'Location tracking restricted by user device.';

$user = getCurrentUser();
$db   = new Database();

try {
    $db->beginTransaction();

    $title       = "[SOS BEACON] " . $user['full_name'] . " is in distress!";
    $description = "A Global SOS panic button was triggered by " . $user['full_name'] . " (" . ($user['phone'] ?? 'no phone') . "). Immediate dispatch necessary.";

    // Inject Live Navigation Link if Coordinates Exist
    if ($lat && $lng) {
        $maps_url    = "https://www.google.com/maps?q={$lat},{$lng}";
        $description .= "\n\nHQ COORDINATES SECURED: " . $maps_url;
        $address     = "GPS: {$lat}, {$lng} (See Description for Map Link)";
    }

    // 1. Create the critical incident
    $db->query("
        INSERT INTO incidents (user_id, title, description, incident_type, severity, status, latitude, longitude, location_address, created_at)
        VALUES (?, ?, ?, 'other', 'critical', 'reported', ?, ?, ?, NOW())
    ", [$user['id'], $title, $description, $lat, $lng, $address]);

    $incident_id = $db->lastInsertId();

    // 2. Log activity
    $db->query("INSERT INTO activity_logs (user_id, action_text, action_type) VALUES (?, ?, 'incident')",
        [$user['id'], "Triggered GLOBAL SOS BEACON."]);

    // 3. Dispatch escalation emails to all Admins & Authorities
    $emailService = new PHPMailerEmailService();
    $authorities = $db->fetchAll("SELECT email, full_name FROM users WHERE role IN ('admin', 'authority') AND email IS NOT NULL", []);

    foreach ($authorities as $auth) {
        $emailService->sendIncidentNotification(
            $auth['email'],
            $auth['full_name'],
            $title,
            $address,
            'CRITICAL - IMMEDIATE DISPATCH'
        );
    }

    $db->commit();
    echo json_encode(['success' => true, 'incident_id' => $incident_id]);

} catch (Exception $e) {
    $db->rollback();
    error_log("[SOS ERROR] " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to broadcast SOS.']);
}
?>
