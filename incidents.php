<?php
require_once 'includes/header.php';
require_once 'database/config.php';

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$type = $_GET['type'] ?? 'all';

// Build query
$where_conditions = [];
$params = [];

if ($status !== 'all') {
    $where_conditions[] = "i.status = ?";
    $params[] = $status;
}

if ($type !== 'all') {
    $where_conditions[] = "i.incident_type = ?";
    $params[] = $type;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get incidents
$db = new Database();
$incidents = $db->query("
    SELECT i.*, u.full_name as reporter_name 
    FROM incidents i 
    LEFT JOIN users u ON i.user_id = u.id 
    $where_clause
    ORDER BY i.created_at DESC
", $params)->fetchAll();
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Incidents</h1>
    <div class="flex gap-4">
        <form method="GET" class="flex gap-4">
            <select name="status" onchange="this.form.submit()" class="form-input w-auto">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="reported" <?php echo $status === 'reported' ? 'selected' : ''; ?>>Reported</option>
                <option value="verified" <?php echo $status === 'verified' ? 'selected' : ''; ?>>Verified</option>
                <option value="investigating" <?php echo $status === 'investigating' ? 'selected' : ''; ?>>Investigating</option>
                <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
            </select>
            
            <select name="type" onchange="this.form.submit()" class="form-input w-auto">
                <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Types</option>
                <option value="theft" <?php echo $type === 'theft' ? 'selected' : ''; ?>>Theft</option>
                <option value="assault" <?php echo $type === 'assault' ? 'selected' : ''; ?>>Assault</option>
                <option value="accident" <?php echo $type === 'accident' ? 'selected' : ''; ?>>Accident</option>
                <option value="fire" <?php echo $type === 'fire' ? 'selected' : ''; ?>>Fire</option>
                <option value="medical" <?php echo $type === 'medical' ? 'selected' : ''; ?>>Medical</option>
                <option value="other" <?php echo $type === 'other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </form>
        
        <a href="report_incident.php" class="btn btn-primary">
            <span class="material-symbols-outlined">add_alert</span>
            Report New Incident
        </a>
    </div>
</div>

<?php if (empty($incidents)): ?>
    <div class="card text-center py-12">
        <p class="text-gray-600">No incidents found matching your criteria.</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 gap-6">
        <?php foreach ($incidents as $incident): ?>
            <div class="card">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-bold text-lg"><?php echo htmlspecialchars($incident['title']); ?></h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs bg-<?php echo $incident['severity'] === 'critical' ? 'error' : ($incident['severity'] === 'high' ? 'warning' : 'info'); ?> px-2 py-1 rounded">
                                <?php echo ucfirst($incident['severity']); ?>
                            </span>
                            <span class="text-xs bg-<?php echo $incident['status'] === 'resolved' ? 'success' : 'warning'; ?> px-2 py-1 rounded">
                                <?php echo ucfirst($incident['status']); ?>
                            </span>
                        </div>
                    </div>
                    <span class="text-sm text-gray-500">
                        <?php echo date('M j, Y g:i A', strtotime($incident['created_at'])); ?>
                    </span>
                </div>
                
                <p class="text-gray-700 mb-4"><?php echo htmlspecialchars($incident['description']); ?></p>
                
                <?php if ($incident['location_address']): ?>
                    <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
                        <span class="material-symbols-outlined">location_on</span>
                        <?php echo htmlspecialchars($incident['location_address']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        Reported by: <?php echo htmlspecialchars($incident['reporter_name']); ?>
                    </div>
                    <a href="incident_detail.php?id=<?php echo $incident['id']; ?>" class="btn btn-outline text-sm">
                        View Details
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
