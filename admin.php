<?php
require_once 'database/config.php';
require_once 'includes/auth.php';

// Check if user has authority access before sending any HTML
if (getUserRole() !== 'authority' && getUserRole() !== 'admin') {
    header('Location: dashboard.php?error=unauthorized');
    exit;
}

require_once 'includes/header.php';

$db = new Database();

// Get statistics for admin dashboard
$stats = [
    'total_incidents' => $db->query("SELECT COUNT(*) as count FROM incidents")->fetch()['count'],
    'pending_verification' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE status = 'reported'")->fetch()['count'],
    'investigating' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE status = 'investigating'")->fetch()['count'],
    'resolved_today' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE status = 'resolved' AND DATE(updated_at) = CURDATE()")->fetch()['count'],
    'total_users' => $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
    'unverified_users' => $db->query("SELECT COUNT(*) as count FROM users WHERE is_verified = 0")->fetch()['count']
];

// Get recent incidents needing attention
$pending_incidents = $db->query("
    SELECT i.*, u.full_name as reporter_name 
    FROM incidents i 
    LEFT JOIN users u ON i.user_id = u.id 
    WHERE i.status IN ('reported', 'investigating')
    ORDER BY i.created_at DESC 
    LIMIT 10
")->fetchAll();
?>

<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Authority Dashboard</h1>
        <div class="flex gap-4">
            <span class="text-sm text-gray-600">Authority Panel</span>
            <a href="dashboard.php" class="btn btn-outline">Switch to User View</a>
        </div>
    </div>
    
    <!-- Statistics Overview -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
        <div class="card text-center">
            <div class="text-3xl font-bold text-primary mb-2"><?php echo number_format($stats['total_incidents']); ?></div>
            <div class="text-sm text-gray-600">Total Incidents</div>
        </div>
        <div class="card text-center">
            <div class="text-3xl font-bold text-warning mb-2"><?php echo number_format($stats['pending_verification']); ?></div>
            <div class="text-sm text-gray-600">Pending Verification</div>
        </div>
        <div class="card text-center">
            <div class="text-3xl font-bold text-secondary mb-2"><?php echo number_format($stats['investigating']); ?></div>
            <div class="text-sm text-gray-600">Under Investigation</div>
        </div>
        <div class="card text-center">
            <div class="text-3xl font-bold text-success mb-2"><?php echo number_format($stats['resolved_today']); ?></div>
            <div class="text-sm text-gray-600">Resolved Today</div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Pending Incidents -->
        <div class="md:col-span-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-warning">pending_actions</span>
                        Incidents Requiring Attention
                    </h2>
                    <span class="text-sm text-gray-600"><?php echo count($pending_incidents); ?> incidents</span>
                </div>
                
                <?php if (empty($pending_incidents)): ?>
                    <p class="text-center text-gray-600 py-8">No incidents pending attention.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($pending_incidents as $incident): ?>
                            <div class="border border-surface-container-low rounded-lg p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-bold"><?php echo htmlspecialchars($incident['title']); ?></h3>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="bg-<?php echo $incident['severity'] === 'critical' ? 'error' : ($incident['severity'] === 'high' ? 'warning' : 'info'); ?> text-white px-2 py-1 rounded text-xs">
                                                <?php echo ucfirst($incident['severity']); ?>
                                            </span>
                                            <span class="bg-<?php echo $incident['status'] === 'reported' ? 'warning' : 'secondary'; ?> text-white px-2 py-1 rounded text-xs">
                                                <?php echo ucfirst($incident['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Reported: <?php echo date('M j, g:i A', strtotime($incident['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <a href="incident_detail.php?id=<?php echo $incident['id']; ?>" class="btn btn-outline text-sm">
                                        <span class="material-symbols-outlined">visibility</span>
                                        View Details
                                    </a>
                                    <?php if ($incident['status'] === 'reported'): ?>
                                        <button onclick="verifyIncident(<?php echo $incident['id']; ?>)" class="btn btn-primary text-sm">
                                            <span class="material-symbols-outlined">verified</span>
                                            Verify
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($incident['status'] === 'investigating'): ?>
                                        <button onclick="resolveIncident(<?php echo $incident['id']; ?>)" class="btn btn-success text-sm">
                                            <span class="material-symbols-outlined">check_circle</span>
                                            Mark Resolved
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- User Management -->
        <div class="md:col-span-1">
            <div class="card">
                <div class="card-header">
                    <h2 class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">people</span>
                        User Management
                    </h2>
                </div>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="bg-surface-container-low rounded-lg p-4">
                            <div class="text-2xl font-bold text-primary mb-2"><?php echo number_format($stats['total_users']); ?></div>
                            <div class="text-sm text-gray-600">Total Users</div>
                        </div>
                        <div class="bg-surface-container-low rounded-lg p-4">
                            <div class="text-2xl font-bold text-warning mb-2"><?php echo number_format($stats['unverified_users']); ?></div>
                            <div class="text-sm text-gray-600">Unverified</div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <h3 class="font-bold mb-3">Quick Actions</h3>
                        <div class="space-y-2">
                            <a href="register.php" class="btn btn-outline w-full">
                                <span class="material-symbols-outlined">person_add</span>
                                Create Authority Account
                            </a>
                            <a href="partners.php" class="btn btn-outline w-full">
                                <span class="material-symbols-outlined">add_business</span>
                                Manage Partners
                            </a>
                            <a href="incidents.php" class="btn btn-outline w-full">
                                <span class="material-symbols-outlined">list</span>
                                View All Incidents
                            </a>
                            <a href="map.php" class="btn btn-outline w-full">
                                <span class="material-symbols-outlined">map</span>
                                Live Map View
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Authority Actions Script -->
<script>
    function verifyIncident(incidentId) {
        if (confirm('Are you sure you want to verify this incident?')) {
            updateIncidentStatus(incidentId, 'verified');
        }
    }
    
    function resolveIncident(incidentId) {
        if (confirm('Are you sure this incident has been resolved?')) {
            updateIncidentStatus(incidentId, 'resolved');
        }
    }
    
    function updateIncidentStatus(incidentId, newStatus) {
        fetch('api/update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                incident_id: incidentId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Status updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error updating status. Please try again.');
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>
