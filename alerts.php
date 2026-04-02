<?php
require_once 'includes/header.php';

// Get filter parameters
$severity = $_GET['severity'] ?? 'all';
$type = $_GET['type'] ?? 'all';
$location = $_GET['location'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($severity !== 'all') {
    $where_conditions[] = "severity = ?";
    $params[] = $severity;
}

if ($type !== 'all') {
    $where_conditions[] = "incident_type = ?";
    $params[] = $type;
}

if ($location) {
    $where_conditions[] = "location_address LIKE ?";
    $params[] = "%$location%";
}

// Only show recent, active incidents for alerts
$where_conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$where_conditions[] = "status IN ('reported', 'verified', 'investigating')";

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get alerts (recent incidents)
$db = new Database();
$alerts = $db->query("
    SELECT i.*, u.full_name as reporter_name, u.profile_picture as reporter_picture 
    FROM incidents i 
    LEFT JOIN users u ON i.user_id = u.id 
    $where_clause
    ORDER BY i.created_at DESC
    LIMIT 50
", $params)->fetchAll();

// Get alert statistics
$stats = [
    'total' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status IN ('reported', 'verified', 'investigating')")->fetch()['count'],
    'critical' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE severity = 'critical' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND status IN ('reported', 'verified', 'investigating')")->fetch()['count'],
    'high' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE severity = 'high' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND status IN ('reported', 'verified', 'investigating')")->fetch()['count'],
    'today' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE DATE(created_at) = CURDATE() AND status IN ('reported', 'verified', 'investigating')")->fetch()['count']
];
?>

<div class="main-content">
    <div class="container py-8">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-neutral-900 mb-2 flex items-center gap-3">
                    <span class="material-symbols-outlined text-4xl text-error-600">notifications_active</span>
                    Safety Alerts
                </h1>
                <p class="text-neutral-600">Real-time safety alerts and incident notifications for your community</p>
            </div>
            <div class="flex gap-3">
                <button onclick="subscribeToAlerts()" class="btn btn-primary">
                    <span class="material-symbols-outlined">notifications</span>
                    Subscribe to Alerts
                </button>
                <a href="report_incident.php" class="btn btn-secondary">
                    <span class="material-symbols-outlined">add_alert</span>
                    Report Incident
                </a>
            </div>
        </div>

        <!-- Alert Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card border-l-4 border-l-error-500">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-neutral-600 mb-1">Critical Alerts</p>
                            <p class="text-3xl font-bold text-error-600"><?php echo $stats['critical']; ?></p>
                            <p class="text-xs text-neutral-500">Last 24 hours</p>
                        </div>
                        <div class="w-12 h-12 bg-error-100 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-error-600 text-xl">emergency</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-l-4 border-l-warning-500">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-neutral-600 mb-1">High Priority</p>
                            <p class="text-3xl font-bold text-warning-600"><?php echo $stats['high']; ?></p>
                            <p class="text-xs text-neutral-500">Last 24 hours</p>
                        </div>
                        <div class="w-12 h-12 bg-warning-100 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-warning-600 text-xl">warning</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-l-4 border-l-primary-500">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-neutral-600 mb-1">Today's Total</p>
                            <p class="text-3xl font-bold text-primary-600"><?php echo $stats['today']; ?></p>
                            <p class="text-xs text-neutral-500">All severities</p>
                        </div>
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary-600 text-xl">today</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-l-4 border-l-success-500">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-neutral-600 mb-1">Active Alerts</p>
                            <p class="text-3xl font-bold text-success-600"><?php echo $stats['total']; ?></p>
                            <p class="text-xs text-neutral-500">Last 7 days</p>
                        </div>
                        <div class="w-12 h-12 bg-success-100 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-success-600 text-xl">notifications</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Filters -->
        <div class="card mb-8">
            <div class="card-body">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="form-group">
                        <label for="severity" class="form-label">Severity</label>
                        <select id="severity" name="severity" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $severity === 'all' ? 'selected' : ''; ?>>All Severities</option>
                            <option value="critical" <?php echo $severity === 'critical' ? 'selected' : ''; ?>>🚨 Critical Only</option>
                            <option value="high" <?php echo $severity === 'high' ? 'selected' : ''; ?>>⚠️ High Only</option>
                            <option value="medium" <?php echo $severity === 'medium' ? 'selected' : ''; ?>>📋 Medium Only</option>
                            <option value="low" <?php echo $severity === 'low' ? 'selected' : ''; ?>>ℹ️ Low Only</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="type" class="form-label">Incident Type</label>
                        <select id="type" name="type" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="theft" <?php echo $type === 'theft' ? 'selected' : ''; ?>>Theft</option>
                            <option value="assault" <?php echo $type === 'assault' ? 'selected' : ''; ?>>Assault</option>
                            <option value="accident" <?php echo $type === 'accident' ? 'selected' : ''; ?>>Accident</option>
                            <option value="fire" <?php echo $type === 'fire' ? 'selected' : ''; ?>>Fire</option>
                            <option value="medical" <?php echo $type === 'medical' ? 'selected' : ''; ?>>Medical</option>
                            <option value="other" <?php echo $type === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" id="location" name="location" class="form-input" 
                               placeholder="Search by location..." value="<?php echo htmlspecialchars($location); ?>">
                    </div>
                    
                    <div class="form-group flex items-end">
                        <button type="submit" class="btn btn-primary w-full">
                            <span class="material-symbols-outlined">search</span>
                            Filter Alerts
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Live Alerts Feed -->
        <div class="card">
            <div class="card-header">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-neutral-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-error-600">live_tv</span>
                        Live Alerts Feed
                        <span class="badge badge-error">LIVE</span>
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="loading loading-sm"></span>
                        <span class="text-sm text-neutral-600">Auto-refreshing every 30 seconds</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($alerts)): ?>
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-success-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="material-symbols-outlined text-success-600 text-3xl">check_circle</span>
                        </div>
                        <h3 class="text-xl font-bold text-neutral-900 mb-2">No Active Alerts</h3>
                        <p class="text-neutral-600 mb-4">Great! No safety alerts in your area right now.</p>
                        <button onclick="location.reload()" class="btn btn-outline">
                            <span class="material-symbols-outlined">refresh</span>
                            Check Again
                        </button>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($alerts as $alert): ?>
                            <div class="alert-item <?php echo 'severity-' . $alert['severity']; ?>">
                                <div class="alert-header">
                                    <div class="flex items-center gap-3">
                                        <div class="alert-icon">
                                            <span class="material-symbols-outlined">
                                                <?php 
                                                echo $alert['incident_type'] === 'fire' ? 'local_fire_department' : 
                                                     ($alert['incident_type'] === 'medical' ? 'medical_services' : 
                                                     ($alert['incident_type'] === 'accident' ? 'car_crash' : 
                                                     ($alert['incident_type'] === 'theft' ? 'theft' : 
                                                     ($alert['incident_type'] === 'assault' ? 'security' : 'emergency')))); 
                                                ?>
                                            </span>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="alert-title"><?php echo htmlspecialchars($alert['title']); ?></h3>
                                            <div class="alert-meta">
                                                <span class="alert-type"><?php echo ucfirst($alert['incident_type']); ?></span>
                                                <span class="alert-time">
                                                    <span class="material-symbols-outlined text-sm">schedule</span>
                                                    <?php echo getTimeAgo($alert['created_at']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="alert-actions">
                                            <span class="severity-badge severity-<?php echo $alert['severity']; ?>">
                                                <?php echo ucfirst($alert['severity']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert-content">
                                    <p class="alert-description"><?php echo htmlspecialchars($alert['description']); ?></p>
                                    
                                    <?php if ($alert['location_address']): ?>
                                        <div class="alert-location">
                                            <span class="material-symbols-outlined text-sm">location_on</span>
                                            <?php echo htmlspecialchars($alert['location_address']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="alert-footer">
                                        <div class="alert-reporter">
                                            <?php if (!empty($alert['reporter_picture'])): ?>
                                                <img src="<?php echo htmlspecialchars($alert['reporter_picture']); ?>" 
                                                     alt="<?php echo htmlspecialchars($alert['reporter_name']); ?>" 
                                                     class="w-6 h-6 rounded-full">
                                            <?php else: ?>
                                                <div class="w-6 h-6 bg-neutral-300 rounded-full flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-xs">person</span>
                                                </div>
                                            <?php endif; ?>
                                            <span class="text-xs text-neutral-500">
                                                Reported by <?php echo htmlspecialchars($alert['reporter_name']); ?>
                                            </span>
                                        </div>
                                        <div class="alert-buttons">
                                            <a href="incident_detail.php?id=<?php echo $alert['id']; ?>" class="btn btn-sm btn-primary">
                                                <span class="material-symbols-outlined">visibility</span>
                                                View Details
                                            </a>
                                            <button onclick="shareAlert(<?php echo $alert['id']; ?>)" class="btn btn-sm btn-outline">
                                                <span class="material-symbols-outlined">share</span>
                                                Share
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Alert Subscription Modal -->
<div id="alertSubscriptionModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="text-xl font-bold text-neutral-900">Subscribe to Safety Alerts</h3>
            <button onclick="closeModal()" class="modal-close">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <p class="text-neutral-600 mb-6">Get real-time safety alerts delivered to your preferred channels</p>
            
            <form id="alertSubscriptionForm" class="space-y-4">
                <div class="form-group">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="email_alerts" checked class="form-checkbox">
                        <span>Email Alerts</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="sms_alerts" class="form-checkbox">
                        <span>SMS Alerts</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="push_alerts" checked class="form-checkbox">
                        <span>Push Notifications</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="alert_radius" class="form-label">Alert Radius</label>
                    <select id="alert_radius" name="alert_radius" class="form-select">
                        <option value="1">1 km radius</option>
                        <option value="5" selected>5 km radius</option>
                        <option value="10">10 km radius</option>
                        <option value="25">25 km radius</option>
                        <option value="50">50 km radius</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="alert_severity" class="form-label">Minimum Severity</label>
                    <select id="alert_severity" name="alert_severity" class="form-select">
                        <option value="low">All Incidents</option>
                        <option value="medium">Medium & Above</option>
                        <option value="high" selected>High & Above</option>
                        <option value="critical">Critical Only</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button onclick="closeModal()" class="btn btn-outline">Cancel</button>
            <button onclick="saveAlertPreferences()" class="btn btn-primary">Save Preferences</button>
        </div>
    </div>
</div>

<style>
/* Alert System Styles */
.alert-item {
    border: 1px solid var(--neutral-200);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
    margin-bottom: var(--space-4);
    transition: all var(--transition-fast);
    position: relative;
    overflow: hidden;
}

.alert-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
}

.alert-item.severity-critical::before {
    background: var(--error-500);
}

.alert-item.severity-high::before {
    background: var(--warning-500);
}

.alert-item.severity-medium::before {
    background: var(--primary-500);
}

.alert-item.severity-low::before {
    background: var(--success-500);
}

.alert-item.severity-critical {
    border-color: var(--error-200);
    background: var(--error-50);
}

.alert-item.severity-high {
    border-color: var(--warning-200);
    background: var(--warning-50);
}

.alert-item.severity-medium {
    border-color: var(--primary-200);
    background: var(--primary-50);
}

.alert-item.severity-low {
    border-color: var(--success-200);
    background: var(--success-50);
}

.alert-header {
    margin-bottom: var(--space-4);
}

.alert-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    box-shadow: var(--shadow-md);
}

.alert-icon .material-symbols-outlined {
    font-size: 24px;
}

.alert-item.severity-critical .alert-icon {
    color: var(--error-600);
}

.alert-item.severity-high .alert-icon {
    color: var(--warning-600);
}

.alert-item.severity-medium .alert-icon {
    color: var(--primary-600);
}

.alert-item.severity-low .alert-icon {
    color: var(--success-600);
}

.alert-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--neutral-900);
    margin: 0;
}

.alert-meta {
    display: flex;
    gap: var(--space-4);
    margin-top: var(--space-2);
}

.alert-type {
    font-size: 0.875rem;
    color: var(--neutral-600);
    font-weight: 500;
}

.alert-time {
    display: flex;
    align-items: center;
    gap: var(--space-1);
    font-size: 0.875rem;
    color: var(--neutral-500);
}

.severity-badge {
    padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.severity-badge.severity-critical {
    background: var(--error-100);
    color: var(--error-800);
}

.severity-badge.severity-high {
    background: var(--warning-100);
    color: var(--warning-800);
}

.severity-badge.severity-medium {
    background: var(--primary-100);
    color: var(--primary-800);
}

.severity-badge.severity-low {
    background: var(--success-100);
    color: var(--success-800);
}

.alert-content {
    margin-bottom: var(--space-4);
}

.alert-description {
    color: var(--neutral-700);
    margin-bottom: var(--space-3);
    line-height: 1.6;
}

.alert-location {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--neutral-600);
    font-size: 0.875rem;
    margin-bottom: var(--space-3);
}

.alert-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: var(--space-4);
    border-top: 1px solid var(--neutral-200);
}

.alert-reporter {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.alert-buttons {
    display: flex;
    gap: var(--space-2);
}

/* Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: var(--z-modal);
}

.modal-content {
    background: white;
    border-radius: var(--radius-xl);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow-2xl);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-6);
    border-bottom: 1px solid var(--neutral-200);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--neutral-400);
    padding: var(--space-2);
    border-radius: var(--radius-md);
    transition: color var(--transition-fast);
}

.modal-close:hover {
    color: var(--neutral-600);
}

.modal-body {
    padding: var(--space-6);
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-3);
    padding: var(--space-6);
    border-top: 1px solid var(--neutral-200);
}

.form-checkbox {
    width: 1.25rem;
    height: 1.25rem;
    border: 2px solid var(--neutral-300);
    border-radius: var(--radius-sm);
    cursor: pointer;
}

.loading-sm {
    width: 16px;
    height: 16px;
    border: 2px solid var(--neutral-200);
    border-top: 2px solid var(--primary-500);
    border-radius: var(--radius-full);
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
// Helper function to get time ago
function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
    return date.toLocaleDateString();
}

// Alert subscription functions
function subscribeToAlerts() {
    document.getElementById('alertSubscriptionModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('alertSubscriptionModal').style.display = 'none';
}

function saveAlertPreferences() {
    const form = document.getElementById('alertSubscriptionForm');
    const formData = new FormData(form);
    
    // Here you would send the data to your backend
    console.log('Saving alert preferences:', Object.fromEntries(formData));
    
    alert('Alert preferences saved successfully!');
    closeModal();
}

function shareAlert(alertId) {
    // Here you would implement sharing functionality
    if (navigator.share) {
        navigator.share({
            title: 'Safety Alert',
            text: 'Check out this safety alert',
            url: `incident_detail.php?id=${alertId}`
        });
    } else {
        // Fallback for browsers that don't support Web Share API
        const url = `incident_detail.php?id=${alertId}`;
        navigator.clipboard.writeText(url);
        alert('Alert link copied to clipboard!');
    }
}

// Auto-refresh alerts every 30 seconds
setInterval(() => {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 30000);

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('alertSubscriptionModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

<?php 
// Helper function for time ago (in case it's not defined elsewhere)
function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time / 60) . ' minutes ago';
    if ($time < 86400) return floor($time / 3600) . ' hours ago';
    if ($time < 604800) return floor($time / 86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}
?>

<?php require_once 'includes/footer.php'; ?>
