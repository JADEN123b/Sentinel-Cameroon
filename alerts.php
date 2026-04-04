<?php
require_once 'includes/header.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = new Database();

// Fetch all active alerts, ordered so that critical/emergency alerts stay on top
$alerts = $db->query("
    SELECT a.*, u.full_name as author_name 
    FROM alerts a
    LEFT JOIN users u ON a.created_by = u.id
    WHERE a.is_active = 1 
      AND (a.expires_at IS NULL OR a.expires_at > NOW())
    ORDER BY 
        CASE a.priority 
            WHEN 'critical' THEN 1 
            WHEN 'high' THEN 2 
            WHEN 'medium' THEN 3 
            ELSE 4 
        END ASC, 
        a.created_at DESC
")->fetchAll();

?>

<style>
.alerts-container {
    max-width: 900px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.alerts-header {
    margin-bottom: 2rem;
    text-align: center;
}

.alert-card {
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
    background: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    transition: transform 0.2s;
    border-left: 6px solid #e2e8f0;
}

.alert-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}

.alert-critical {
    border-left-color: #ef4444;
    background: #fef2f2;
}
.alert-warning {
    border-left-color: #f59e0b;
    background: #fffbeb;
}
.alert-info {
    border-left-color: #3b82f6;
    background: #eff6ff;
}

.alert-badge {
    position: absolute;
    top: 1rem;
    right: 1.5rem;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.badge-critical { background: #fee2e2; color: #dc2626; }
.badge-warning { background: #fef3c7; color: #d97706; }
.badge-info { background: #dbeafe; color: #2563eb; }

.alert-title {
    font-size: 1.4rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.75rem;
    padding-right: 100px; /* Make room for badge */
}

.alert-message {
    color: #475569;
    font-size: 1.05rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.alert-meta {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    font-size: 0.85rem;
    color: #64748b;
    border-top: 1px solid rgba(0,0,0,0.05);
    padding-top: 1rem;
}

.alert-meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@keyframes flash {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.icon-pulse {
    animation: flash 2s infinite;
}
</style>

<div class="alerts-container">
    <div class="alerts-header">
        <h1 class="text-4xl font-extrabold text-gray-900 mb-3 flex items-center justify-center gap-3">
            <span class="material-symbols-outlined text-red-500" style="font-size: 2.5rem;">campaign</span>
            Safety Alerts
        </h1>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">Official safety announcements, weather warnings, and critical community alerts issued by verified Sentinel Authorities.</p>
    </div>

    <?php if (empty($alerts)): ?>
        <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <span class="material-symbols-outlined" style="font-size: 4rem; color: #10b981; margin-bottom: 1rem;">verified_user</span>
            <h3 style="font-size: 1.5rem; font-weight: 700; color: #334155; margin-bottom: 0.5rem;">All Clear!</h3>
            <p style="color: #64748b; font-size: 1.1rem;">There are currently no active safety alerts in your region. Enjoy your day securely!</p>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach($alerts as $alert): ?>
                <?php 
                    $priority = $alert['priority'];
                    $cardClass = 'alert-info';
                    $badgeClass = 'badge-info';
                    $icon = 'info';
                    $iconColor = '#3b82f6';
                    
                    if ($priority === 'critical') {
                        $cardClass = 'alert-critical';
                        $badgeClass = 'badge-critical';
                        $icon = 'warning';
                        $iconColor = '#ef4444';
                    } elseif ($priority === 'high') {
                        $cardClass = 'alert-warning';
                        $badgeClass = 'badge-warning';
                        $icon = 'error';
                        $iconColor = '#f59e0b';
                    }
                ?>
                
                <div class="alert-card <?php echo $cardClass; ?>">
                    <div class="alert-badge <?php echo $badgeClass; ?>">
                        <?php echo $priority === 'critical' ? 'EMERGENCY' : strtoupper($alert['alert_type']); ?>
                    </div>
                    
                    <h2 class="alert-title flex items-start gap-2">
                        <span class="material-symbols-outlined <?php echo $priority === 'critical' ? 'icon-pulse' : ''; ?>" style="color: <?php echo $iconColor; ?>; margin-top: 2px;">
                            <?php echo $icon; ?>
                        </span>
                        <?php echo htmlspecialchars($alert['title']); ?>
                    </h2>
                    
                    <p class="alert-message">
                        <?php echo nl2br(htmlspecialchars($alert['message'])); ?>
                    </p>
                    
                    <div class="alert-meta">
                        <div class="alert-meta-item">
                            <span class="material-symbols-outlined" style="font-size: 1.1rem;">shield</span>
                            Broadcasted by verified Authority (<?php echo htmlspecialchars($alert['author_name'] ?? 'SysAdmin'); ?>)
                        </div>
                        <div class="alert-meta-item">
                            <span class="material-symbols-outlined" style="font-size: 1.1rem;">schedule</span>
                            <?php echo date('F j, Y g:i A', strtotime($alert['created_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
