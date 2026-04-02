<?php
require_once 'includes/header.php';
require_once 'database/config.php';

// Get incident ID from URL
$incident_id = $_GET['id'] ?? 0;

if (!$incident_id || !is_numeric($incident_id)) {
    header('Location: incidents.php');
    exit;
}

$db = new Database();

// Get incident details
$incident = $db->query("
    SELECT i.*, u.full_name as reporter_name, u.email as reporter_email 
    FROM incidents i 
    LEFT JOIN users u ON i.user_id = u.id 
    WHERE i.id = ?
", [$incident_id])->fetch();

if (!$incident) {
    echo '<div class="container py-8"><div class="alert alert-error">Incident not found.</div></div>';
    require_once 'includes/footer.php';
    exit;
}

// Get incident attachments
$attachments = $db->query("
    SELECT * FROM incident_attachments 
    WHERE incident_id = ? 
    ORDER BY created_at ASC
", [$incident_id])->fetchAll();
?>

<div class="max-w-4xl mx-auto">
    <!-- Back Navigation -->
    <div class="mb-6">
        <a href="incidents.php" class="btn btn-outline">
            <span class="material-symbols-outlined">arrow_back</span>
            Back to Incidents
        </a>
    </div>
    
    <!-- Incident Header -->
    <div class="card mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($incident['title']); ?></h1>
                <div class="flex items-center gap-3">
                    <span class="bg-<?php echo $incident['severity'] === 'critical' ? 'error' : ($incident['severity'] === 'high' ? 'warning' : 'info'); ?> text-white px-3 py-1 rounded-full text-sm font-medium">
                        <?php echo ucfirst($incident['severity']); ?> Severity
                    </span>
                    <span class="bg-<?php echo $incident['status'] === 'resolved' ? 'success' : 'warning'; ?> text-white px-3 py-1 rounded-full text-sm font-medium">
                        <?php echo ucfirst($incident['status']); ?>
                    </span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500">Reported: <?php echo date('F j, Y g:i A', strtotime($incident['created_at'])); ?></div>
                <?php if ($incident['updated_at'] !== $incident['created_at']): ?>
                    <div class="text-sm text-gray-500">Updated: <?php echo date('F j, Y g:i A', strtotime($incident['updated_at'])); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="font-bold mb-2">Incident Details</h3>
                <div class="space-y-2">
                    <div>
                        <span class="text-gray-500">Type:</span>
                        <span class="font-medium ml-2"><?php echo ucfirst($incident['incident_type']); ?></span>
                    </div>
                    <?php if ($incident['location_address']): ?>
                        <div>
                            <span class="text-gray-500">Location:</span>
                            <span class="font-medium ml-2"><?php echo htmlspecialchars($incident['location_address']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($incident['latitude'] && $incident['longitude']): ?>
                        <div>
                            <span class="text-gray-500">Coordinates:</span>
                            <span class="font-medium ml-2"><?php echo $incident['latitude']; ?>, <?php echo $incident['longitude']; ?></span>
                        </div>
                    <?php endif; ?>
                    <div>
                        <span class="text-gray-500">Reported by:</span>
                        <span class="font-medium ml-2"><?php echo $incident['is_anonymous'] ? 'Anonymous' : htmlspecialchars($incident['reporter_name']); ?></span>
                    </div>
                </div>
            </div>
            
            <div>
                <h3 class="font-bold mb-2">Description</h3>
                <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($incident['description'])); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Attachments -->
    <?php if (!empty($attachments)): ?>
        <div class="card mb-6">
            <h3 class="font-bold mb-4">Attachments</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($attachments as $attachment): ?>
                    <div class="border border-surface-container-low rounded-lg p-3 text-center">
                        <div class="mb-2">
                            <?php if ($attachment['file_type'] === 'image'): ?>
                                <img src="<?php echo htmlspecialchars($attachment['file_path']); ?>" alt="Attachment" class="w-full h-32 object-cover rounded">
                            <?php elseif ($attachment['file_type'] === 'video'): ?>
                                <div class="w-full h-32 bg-gray-200 rounded flex items-center justify-center">
                                    <span class="material-symbols-outlined text-3xl text-gray-500">videocam</span>
                                </div>
                            <?php else: ?>
                                <div class="w-full h-32 bg-gray-200 rounded flex items-center justify-center">
                                    <span class="material-symbols-outlined text-3xl text-gray-500">description</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-gray-500">
                            <?php echo date('M j, Y', strtotime($attachment['created_at'])); ?>
                        </div>
                        <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" target="_blank" class="btn btn-outline text-sm">
                            <span class="material-symbols-outlined">download</span>
                            View
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Map for Location -->
    <?php if ($incident['latitude'] && $incident['longitude']): ?>
        <div class="card">
            <h3 class="font-bold mb-4">Location</h3>
            <div id="incidentMap" class="w-full h-64 rounded-lg"></div>
        </div>
    <?php endif; ?>
    
    <!-- Actions -->
    <div class="flex gap-4">
        <?php if (getUserRole() === 'authority'): ?>
            <button onclick="updateStatus(<?php echo $incident_id; ?>, 'verified')" class="btn btn-primary">
                <span class="material-symbols-outlined">verified</span>
                Mark as Verified
            </button>
            <button onclick="updateStatus(<?php echo $incident_id; ?>, 'investigating')" class="btn btn-secondary">
                <span class="material-symbols-outlined">search</span>
                Start Investigation
            </button>
            <button onclick="updateStatus(<?php echo $incident_id; ?>, 'resolved')" class="btn btn-success">
                <span class="material-symbols-outlined">check_circle</span>
                Mark Resolved
            </button>
        <?php endif; ?>
        
        <a href="incidents.php" class="btn btn-outline">Back to List</a>
    </div>
</div>

<!-- Map Script for Incident Location -->
<?php if ($incident['latitude'] && $incident['longitude']): ?>
<script>
    let incidentMap;
    
    function initIncidentMap() {
        incidentMap = L.map('incidentMap').setView([<?php echo $incident['latitude']; ?>, <?php echo $incident['longitude']; ?>], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(incidentMap);
        
        // Add marker for incident location
        const marker = L.circleMarker([<?php echo $incident['latitude']; ?>, <?php echo $incident['longitude']; ?>], {
            radius: 12,
            fillColor: '#dc3545',
            color: '#fff',
            weight: 3,
            opacity: 0.8
        }).addTo(incidentMap);
        
        marker.bindPopup('Incident Location: <?php echo htmlspecialchars($incident['location_address'] ?: 'Unknown'); ?>');
    }
    
    // Load map when page loads
    window.onload = function() {
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = function() {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(link);
            setTimeout(initIncidentMap, 100);
        };
        document.head.appendChild(script);
    };
</script>
<?php endif; ?>

<!-- Status Update Script -->
<?php if (getUserRole() === 'authority'): ?>
<script>
    function updateStatus(incidentId, newStatus) {
        if (confirm('Are you sure you want to update the incident status to ' + newStatus + '?')) {
            fetch('api/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?php echo generateCsrfToken(); ?>'
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
    }
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
