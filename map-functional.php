<?php
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';

$db = new Database();

$incidents = $db->fetchAll("
    SELECT id, title, description, incident_type, severity, status, latitude, longitude, created_at
    FROM incidents
    WHERE latitude IS NOT NULL AND longitude IS NOT NULL
    ORDER BY created_at DESC
    LIMIT 100
");

$markers_json = json_encode($incidents);
?>

<style>
    #map { width: 100%; height: 560px; border-radius: 16px; z-index: 1; }
    .map-filter-bar {
        display: flex; gap: 0.75rem; flex-wrap: wrap;
        background: white; padding: 1rem 1.5rem;
        border-radius: 14px; margin-bottom: 1.5rem;
        border: 1px solid var(--rs-border);
        box-shadow: var(--rs-shadow);
        align-items: center;
    }
    .map-filter-bar select, .map-filter-bar button {
        padding: 0.5rem 1rem; border-radius: 8px;
        border: 1.5px solid var(--rs-border);
        font-size: 0.85rem; font-weight: 600;
        background: var(--rs-bg); cursor: pointer;
        transition: all 0.2s;
    }
    .map-filter-bar button { background: var(--rs-primary); color: white; border-color: var(--rs-primary); }
    .map-filter-bar button:hover { opacity: 0.85; }
    .incident-list-item {
        padding: 1rem; border-radius: 12px;
        border: 1.5px solid var(--rs-border);
        cursor: pointer; transition: all 0.2s;
        background: white; margin-bottom: 0.75rem;
    }
    .incident-list-item:hover { border-color: var(--rs-primary); transform: translateX(3px); }
    .severity-dot {
        width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
    }
</style>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 1.75rem; font-weight: 900; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 10px;">
        <span class="material-symbols-outlined" style="color: var(--rs-secondary);">map</span>
        Live Safety Map
    </h1>
    <p style="color: #64748b; font-size: 0.9rem;">Real-time incident heatmap across Cameroon</p>
</div>

<div class="map-filter-bar">
    <span style="font-weight: 800; font-size: 0.85rem; color: #475569; display: flex; align-items: center; gap: 6px;">
        <span class="material-symbols-outlined" style="font-size: 1rem;">filter_list</span> Filters:
    </span>
    <select id="severityFilter" onchange="applyFilters()">
        <option value="all">All Severities</option>
        <option value="critical">Critical</option>
        <option value="high">High</option>
        <option value="medium">Medium</option>
        <option value="low">Low</option>
    </select>
    <select id="typeFilter" onchange="applyFilters()">
        <option value="all">All Types</option>
        <option value="theft">Theft</option>
        <option value="assault">Assault</option>
        <option value="accident">Accident</option>
        <option value="fire">Fire</option>
        <option value="medical">Medical</option>
        <option value="other">Other</option>
    </select>
    <button onclick="resetFilters()">
        <span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle;">refresh</span> Reset
    </button>
    <div style="margin-left: auto; font-size: 0.8rem; color: #94a3b8; font-weight: 600;">
        <span id="markerCount"><?php echo count($incidents); ?></span> incidents shown
    </div>
</div>

<div class="rs-grid" style="grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start;">

    <!-- Map -->
    <div class="rs-card reveal" style="padding: 1rem;">
        <div id="map"></div>
    </div>

    <!-- Incident List Panel -->
    <div>
        <div class="rs-card reveal" style="padding: 0; overflow: hidden;">
            <div style="padding: 1.25rem 1.5rem; background: var(--rs-primary); color: white; display: flex; align-items: center; gap: 10px;">
                <span class="material-symbols-outlined">rss_feed</span>
                <h3 style="margin: 0; font-size: 1rem; font-weight: 800;">Incident Feed</h3>
            </div>
            <div id="incidentList" style="max-height: 500px; overflow-y: auto; padding: 1rem;">
                <?php if (empty($incidents)): ?>
                    <div style="text-align: center; padding: 3rem 1rem; opacity: 0.5;">
                        <span class="material-symbols-outlined" style="font-size: 2.5rem;">location_off</span>
                        <p style="margin-top: 0.75rem; font-weight: 600;">No geo-tagged incidents yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($incidents as $i): ?>
                        <div class="incident-list-item" onclick="focusIncident(<?php echo $i['id']; ?>)">
                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                <div class="severity-dot" style="margin-top: 5px; background: <?php
                                    echo $i['severity'] === 'critical' ? '#ef4444' :
                                        ($i['severity'] === 'high' ? '#f97316' :
                                        ($i['severity'] === 'medium' ? '#eab308' : '#22c55e'));
                                ?>;"></div>
                                <div>
                                    <div style="font-weight: 800; font-size: 0.9rem; margin-bottom: 2px;"><?php echo htmlspecialchars($i['title']); ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600; text-transform: uppercase;">
                                        <?php echo ucfirst($i['incident_type']); ?> • <?php echo date('M j, g:i A', strtotime($i['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Legend -->
        <div class="rs-card reveal" style="margin-top: 1rem; padding: 1.25rem;">
            <h4 style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 1rem; font-weight: 800;">Legend</h4>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ([['critical','#ef4444'],['high','#f97316'],['medium','#eab308'],['low','#22c55e']] as [$sev, $col]): ?>
                <div style="display: flex; align-items: center; gap: 10px; font-size: 0.85rem; font-weight: 600;">
                    <div style="width: 14px; height: 14px; border-radius: 50%; background: <?php echo $col; ?>; border: 2px solid white; box-shadow: 0 0 0 2px <?php echo $col; ?>44;"></div>
                    <?php echo ucfirst($sev); ?> Severity
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const incidentData = <?php echo $markers_json; ?>;
    let map, allMarkers = [];

    function getSeverityColor(s) {
        return s === 'critical' ? '#ef4444' : s === 'high' ? '#f97316' : s === 'medium' ? '#eab308' : '#22c55e';
    }

    function initMap() {
        map = L.map('map').setView([4.0511, 9.7679], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        incidentData.forEach(incident => {
            if (!incident.latitude || !incident.longitude) return;
            const color = getSeverityColor(incident.severity);
            const marker = L.circleMarker([incident.latitude, incident.longitude], {
                radius: incident.severity === 'critical' ? 12 : 9,
                fillColor: color, color: '#fff', weight: 2,
                opacity: 1, fillOpacity: 0.85
            }).addTo(map);

            marker.bindPopup(`
                <div style="min-width:200px; font-family:'Inter',sans-serif;">
                    <div style="font-weight:800; font-size:0.95rem; margin-bottom:6px;">${incident.title}</div>
                    <div style="font-size:0.75rem; background:${color}22; color:${color}; font-weight:700; padding:2px 8px; border-radius:6px; display:inline-block; margin-bottom:8px; text-transform:uppercase;">${incident.severity}</div>
                    <p style="font-size:0.8rem; color:#475569; margin-bottom:8px;">${incident.description ? incident.description.substring(0, 100) + '...' : ''}</p>
                    <a href="incident_detail.php?id=${incident.id}" style="background:#020617; color:white; padding:6px 12px; border-radius:8px; text-decoration:none; font-size:0.78rem; font-weight:700; display:inline-block;">View Details →</a>
                </div>
            `);
            allMarkers.push({ marker, data: incident });
        });
    }

    function applyFilters() {
        const sev = document.getElementById('severityFilter').value;
        const type = document.getElementById('typeFilter').value;
        let count = 0;
        allMarkers.forEach(({ marker, data }) => {
            const show = (sev === 'all' || data.severity === sev) && (type === 'all' || data.incident_type === type);
            show ? map.addLayer(marker) : map.removeLayer(marker);
            if (show) count++;
        });
        document.getElementById('markerCount').textContent = count;
    }

    function resetFilters() {
        document.getElementById('severityFilter').value = 'all';
        document.getElementById('typeFilter').value = 'all';
        applyFilters();
    }

    function focusIncident(id) {
        const found = allMarkers.find(m => m.data.id == id);
        if (found) {
            map.setView([found.data.latitude, found.data.longitude], 14);
            found.marker.openPopup();
        }
    }

    window.addEventListener('load', initMap);
</script>

<?php require_once 'includes/footer.php'; ?>
