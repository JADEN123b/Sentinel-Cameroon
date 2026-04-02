<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'database/config.php';

$action = $_GET['action'] ?? '';

try {
    $db = new Database();
    
    switch ($action) {
        case 'get_live_alerts':
            getLiveAlerts($db);
            break;
        case 'subscribe':
            subscribeToAlerts($db);
            break;
        case 'get_statistics':
            getAlertStatistics($db);
            break;
        case 'broadcast_alert':
            broadcastAlert($db);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getLiveAlerts($db) {
    $severity = $_GET['severity'] ?? 'all';
    $type = $_GET['type'] ?? 'all';
    $limit = $_GET['limit'] ?? 20;
    
    $where_conditions = [];
    $params = [];
    
    // Only show recent, active incidents
    $where_conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $where_conditions[] = "status IN ('reported', 'verified', 'investigating')";
    
    if ($severity !== 'all') {
        $where_conditions[] = "severity = ?";
        $params[] = $severity;
    }
    
    if ($type !== 'all') {
        $where_conditions[] = "incident_type = ?";
        $params[] = $type;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    $alerts = $db->query("
        SELECT i.*, u.full_name as reporter_name, u.profile_picture as reporter_picture,
               TIMESTAMPDIFF(MINUTE, i.created_at, NOW()) as minutes_ago
        FROM incidents i 
        LEFT JOIN users u ON i.user_id = u.id 
        $where_clause
        ORDER BY i.created_at DESC
        LIMIT ?
    ", array_merge($params, [$limit]))->fetchAll();
    
    // Format alerts for frontend
    $formatted_alerts = array_map(function($alert) {
        return [
            'id' => $alert['id'],
            'title' => $alert['title'],
            'description' => $alert['description'],
            'severity' => $alert['severity'],
            'type' => $alert['incident_type'],
            'location' => $alert['location_address'],
            'created_at' => $alert['created_at'],
            'minutes_ago' => $alert['minutes_ago'],
            'reporter' => [
                'name' => $alert['reporter_name'],
                'picture' => $alert['reporter_picture']
            ],
            'icon' => getAlertIcon($alert['incident_type']),
            'priority' => getAlertPriority($alert['severity'])
        ];
    }, $alerts);
    
    echo json_encode([
        'success' => true,
        'alerts' => $formatted_alerts,
        'timestamp' => date('Y-m-d H:i:s'),
        'total_count' => count($formatted_alerts)
    ]);
}

function getAlertStatistics($db) {
    $stats = [
        'critical_24h' => $db->query("
            SELECT COUNT(*) as count 
            FROM incidents 
            WHERE severity = 'critical' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND status IN ('reported', 'verified', 'investigating')
        ")->fetch()['count'],
        
        'high_24h' => $db->query("
            SELECT COUNT(*) as count 
            FROM incidents 
            WHERE severity = 'high' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND status IN ('reported', 'verified', 'investigating')
        ")->fetch()['count'],
        
        'total_today' => $db->query("
            SELECT COUNT(*) as count 
            FROM incidents 
            WHERE DATE(created_at) = CURDATE()
            AND status IN ('reported', 'verified', 'investigating')
        ")->fetch()['count'],
        
        'total_7days' => $db->query("
            SELECT COUNT(*) as count 
            FROM incidents 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND status IN ('reported', 'verified', 'investigating')
        ")->fetch()['count'],
        
        'by_severity' => $db->query("
            SELECT severity, COUNT(*) as count 
            FROM incidents 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND status IN ('reported', 'verified', 'investigating')
            GROUP BY severity
            ORDER BY 
                CASE severity 
                    WHEN 'critical' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'medium' THEN 3 
                    WHEN 'low' THEN 4 
                END
        ")->fetchAll(),
        
        'by_type' => $db->query("
            SELECT incident_type, COUNT(*) as count 
            FROM incidents 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND status IN ('reported', 'verified', 'investigating')
            GROUP BY incident_type
            ORDER BY count DESC
        ")->fetchAll()
    ];
    
    echo json_encode([
        'success' => true,
        'statistics' => $stats,
        'last_updated' => date('Y-m-d H:i:s')
    ]);
}

function subscribeToAlerts($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
        return;
    }
    
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'User not authenticated']);
        return;
    }
    
    // Validate required fields
    $required = ['email_alerts', 'sms_alerts', 'push_alerts', 'alert_radius', 'min_severity'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
            return;
        }
    }
    
    // Check if user already has alert preferences
    $existing = $db->query("
        SELECT id FROM alert_preferences 
        WHERE user_id = ?
    ", [$user_id])->fetch();
    
    if ($existing) {
        // Update existing preferences
        $stmt = $db->query("
            UPDATE alert_preferences 
            SET email_alerts = ?, sms_alerts = ?, push_alerts = ?, 
                alert_radius = ?, min_severity = ?, updated_at = NOW()
            WHERE user_id = ?
        ", [
            $data['email_alerts'],
            $data['sms_alerts'],
            $data['push_alerts'],
            $data['alert_radius'],
            $data['min_severity'],
            $user_id
        ]);
    } else {
        // Insert new preferences
        $stmt = $db->query("
            INSERT INTO alert_preferences 
            (user_id, email_alerts, sms_alerts, push_alerts, alert_radius, min_severity, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [
            $user_id,
            $data['email_alerts'],
            $data['sms_alerts'],
            $data['push_alerts'],
            $data['alert_radius'],
            $data['min_severity']
        ]);
    }
    
    if ($stmt) {
        echo json_encode([
            'success' => true,
            'message' => 'Alert preferences saved successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save preferences']);
    }
}

function broadcastAlert($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
        return;
    }
    
    // Validate required fields
    $required = ['title', 'message', 'severity', 'target_radius'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
            return;
        }
    }
    
    // Check if user has authority to broadcast
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'User not authenticated']);
        return;
    }
    
    $user = $db->query("SELECT role FROM users WHERE id = ?", [$user_id])->fetch();
    if (!$user || !in_array($user['role'], ['admin', 'authority'])) {
        echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
        return;
    }
    
    // Insert broadcast alert
    $stmt = $db->query("
        INSERT INTO broadcast_alerts 
        (user_id, title, message, severity, target_radius, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ", [
        $user_id,
        $data['title'],
        $data['message'],
        $data['severity'],
        $data['target_radius']
    ]);
    
    if ($stmt) {
        // In a real implementation, you would:
        // 1. Send push notifications to subscribed users
        // 2. Send SMS alerts to subscribed users
        // 3. Send email alerts to subscribed users
        // 4. Store delivery receipts
        
        echo json_encode([
            'success' => true,
            'message' => 'Broadcast alert sent successfully',
            'alert_id' => $db->lastInsertId()
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send broadcast']);
    }
}

function getAlertIcon($incident_type) {
    $icons = [
        'fire' => 'local_fire_department',
        'medical' => 'medical_services',
        'accident' => 'car_crash',
        'theft' => 'theft',
        'assault' => 'security',
        'other' => 'emergency'
    ];
    return $icons[$incident_type] ?? 'emergency';
}

function getAlertPriority($severity) {
    $priorities = [
        'critical' => 1,
        'high' => 2,
        'medium' => 3,
        'low' => 4
    ];
    return $priorities[$severity] ?? 4;
}
?>
