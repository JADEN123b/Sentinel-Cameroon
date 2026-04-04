<?php
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Map logic has been consolidated into map-functional.php
header('Location: map-functional.php');
exit;
?>

// Convert to JSON for map markers
$markers = [];
foreach ($incidents as $incident) {
    $markers[] = [
        'lat' => (float) $incident['latitude'],
        'lng' => (float) $incident['longitude'],
        'title' => htmlspecialchars($incident['title']),
        'description' => htmlspecialchars(substr($incident['description'], 0, 200)),
        'severity' => $incident['severity'],
        'status' => $incident['status'],
        'type' => $incident['incident_type'],
        'id' => $incident['id']
    ];
}
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Live Incident Map</h1>
    <div class="flex gap-4">
        <select id="severityFilter" class="form-input w-auto">
            <option value="all">All Severities</option>
            <option value="critical">Critical</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <?php
// Redirect to the functional map
header('Location: map-functional.php');
exit;
?>
        </select>
        
        <select id="typeFilter" class="form-input w-auto">
            <option value="all">All Types</option>
            <option value="theft">Theft</option>
            <option value="assault">Assault</option>
            <option value="accident">Accident</option>
            <option value="fire">Fire</option>
            <option value="medical">Medical</option>
            <option value="other">Other</option>
        </select>
        
        <button onclick="refreshMap()" class="btn btn-outline">
            <span class="material-symbols-outlined">refresh</span>
            Refresh
        </button>
    </div>
</div>

<div class="grid grid-cols-3 gap-6">
    <!-- Map Container -->
    <div class="col-span-2">
        <div class="card h-96">
            <div id="map" class="w-full h-full rounded-lg"></div>
        </div>
    </div>
    
    <!-- Incident List -->
    <div class="col-span-1">
        <div class="card">
            <div class="card-header">
                <h3>Recent Incidents</h3>
                <span class="text-sm text-gray-600"><?php echo count($incidents); ?> total</span>
            </div>
            
            <div class="space-y-3 max-h-80 overflow-y-auto">
                <?php if (empty($incidents)): ?>
                    <p class="text-center text-gray-600 py-8">No incidents on map.</p>
                <?php else: ?>
                    <?php foreach ($incidents as $incident): ?>
                        <div class="border border-surface-container-low rounded p-3 cursor-pointer hover:bg-surface-container-high" onclick="focusIncident(<?php echo $incident['id']; ?>)">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-bold text-sm"><?php echo htmlspecialchars($incident['title']); ?></h4>
                                <span class="text-xs bg-<?php echo $incident['severity'] === 'critical' ? 'error' : ($incident['severity'] === 'high' ? 'warning' : 'info'); ?> px-2 py-1 rounded">
                                    <?php echo ucfirst($incident['severity']); ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-600"><?php echo date('M j, g:i A', strtotime($incident['created_at'])); ?></p>
                            <div class="text-xs text-gray-500">
                                Type: <?php echo ucfirst($incident['incident_type']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Map Script -->
<script>
    let map;
    let markers = [];
    let incidentData = <?php echo json_encode($markers); ?>;
    
    // Initialize map (using OpenStreetMap as free alternative)
    function initMap() {
        map = L.map('map').setView([3.8480, 11.5021], 11);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: ' OpenStreetMap contributors'
        }).addTo(map);
        
        // Add markers
        incidentData.forEach(incident => {
            const color = getSeverityColor(incident.severity);
            const marker = L.circleMarker([incident.lat, incident.lng], {
                radius: 8,
                fillColor: color,
                color: '#fff',
                weight: 2,
                opacity: 0.8
            }).addTo(map);
            
            marker.bindPopup(`
                <div class="p-2">
                    <h4 class="font-bold">${incident.title}</h4>
                    <p class="text-sm">${incident.description}</p>
                    <div class="text-xs mt-1">
                        <strong>Type:</strong> ${incident.type}<br>
                        <strong>Severity:</strong> ${incident.severity}<br>
                        <strong>Status:</strong> ${incident.status}<br>
                        <a href="incident_detail.php?id=${incident.id}" class="text-primary">View Details</a>
                    </div>
                </div>
            `);
            
            markers.push(marker);
        });
    }
    
    function getSeverityColor(severity) {
        switch(severity) {
            case 'critical': return '#dc3545';
            case 'high': return '#fd7e14';
            case 'medium': return '#ffc107';
            case 'low': return '#28a745';
            default: return '#6c757d';
        }
    }
    
    function focusIncident(id) {
        const incident = incidentData.find(i => i.id === id);
        if (incident) {
            map.setView([incident.lat, incident.lng], 15);
        }
    }
    
    function refreshMap() {
        window.location.reload();
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
            setTimeout(initMap, 100);
        };
        document.head.appendChild(script);
    };
</script>

<?php require_once 'includes/footer.php'; ?>
