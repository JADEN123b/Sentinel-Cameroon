<?php
require_once 'includes/header.php';
require_once 'database/config.php';

// Get recent incidents for dashboard
$db = new Database();
$incidents = $db->query("
    SELECT i.*, u.full_name as reporter_name 
    FROM incidents i 
    LEFT JOIN users u ON i.user_id = u.id 
    ORDER BY i.created_at DESC 
    LIMIT 10
")->fetchAll();

// Get statistics
$stats = [
    'total_incidents' => $db->query("SELECT COUNT(*) as count FROM incidents")->fetch()['count'],
    'active_incidents' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE status IN ('reported', 'verified', 'investigating')")->fetch()['count'],
    'resolved_incidents' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE status = 'resolved'")->fetch()['count'],
    'verified_users' => $db->query("SELECT COUNT(*) as count FROM users WHERE is_verified = 1")->fetch()['count']
];
?>

<div class="grid grid-cols-4 gap-6 mb-8">
    <div class="card">
        <h3>Total Incidents</h3>
        <p class="text-3xl font-bold text-primary"><?php echo $stats['total_incidents']; ?></p>
    </div>
    <div class="card">
        <h3>Active Cases</h3>
        <p class="text-3xl font-bold text-warning"><?php echo $stats['active_incidents']; ?></p>
    </div>
    <div class="card">
        <h3>Resolved</h3>
        <p class="text-3xl font-bold text-success"><?php echo $stats['resolved_incidents']; ?></p>
    </div>
    <div class="card">
        <h3>Verified Users</h3>
        <p class="text-3xl font-bold text-secondary"><?php echo $stats['verified_users']; ?></p>
    </div>
</div>

<div class="grid grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header">
            <h2>Recent Incidents</h2>
            <a href="incidents.php" class="btn btn-outline">View All</a>
        </div>
        
        <?php if (empty($incidents)): ?>
            <p class="text-center text-gray-600 py-8">No incidents reported yet.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($incidents as $incident): ?>
                    <div class="border border-surface-container-low rounded p-4">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-bold"><?php echo htmlspecialchars($incident['title']); ?></h4>
                            <span class="text-xs bg-<?php echo $incident['severity'] === 'critical' ? 'error' : ($incident['severity'] === 'high' ? 'warning' : 'info'); ?> px-2 py-1 rounded">
                                <?php echo ucfirst($incident['severity']); ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars(substr($incident['description'], 0, 150)); ?>...</p>
                        <div class="flex justify-between items-center text-xs text-gray-500">
                            <span>By: <?php echo htmlspecialchars($incident['reporter_name']); ?></span>
                            <span><?php echo date('M j, Y', strtotime($incident['created_at'])); ?></span>
                        </div>
                        <a href="incident_detail.php?id=<?php echo $incident['id']; ?>" class="btn btn-primary text-sm">View Details</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2>Quick Actions</h2>
        </div>
        <div class="space-y-4">
            <a href="report_incident.php" class="btn btn-secondary w-full">
                <span class="material-symbols-outlined">add_alert</span>
                Report New Incident
            </a>
            <a href="map.php" class="btn btn-outline w-full">
                <span class="material-symbols-outlined">map</span>
                View Live Map
            </a>
            <a href="partners.php" class="btn btn-outline w-full">
                <span class="material-symbols-outlined">handshake</span>
                Find Partners
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
