<?php
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Redirect officials to their specific dashboard
$role = getUserRole();
if ($role === 'admin' || $role === 'authority' || $role === 'authority_pending') {
    header('Location: admin.php');
    exit;
}

require_once 'includes/header.php';

$db = new Database();

// Get recent reports
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

$current_user = getCurrentUser();
$hour = (int)date('H');
$greeting = ($hour < 12) ? "Good morning" : (($hour < 17) ? "Good afternoon" : "Good evening");
?>

    
    <!-- 🌊 Hero Wave Greeting -->
    <div class="rs-card reveal" style="background: linear-gradient(135deg, var(--rs-primary) 0%, #020617 100%); color: white; padding: 3rem 2rem; margin-bottom: 2rem; border: none; border-radius: 20px; position: relative; overflow: hidden;">
        <div style="position: relative; z-index: 2;">
            <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem; color: white; font-weight: 900; letter-spacing: -0.02em;">
                <?php echo $greeting; ?>, <span style="color: var(--rs-secondary);"><?php echo htmlspecialchars(explode(' ', $current_user['full_name'])[0]); ?></span>
            </h1>
            <p style="font-size: 1.1rem; opacity: 0.8; font-weight: 500;">Your safety perimeter is active and being monitored.</p>
        </div>
        <!-- Decorative subtle glow -->
        <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: var(--rs-secondary); filter: blur(100px); opacity: 0.2;"></div>
    </div>

    <!-- 📊 Authoritative Stats Grid -->
    <div class="rs-grid rs-grid-stats mb-8">
        <div class="rs-card reveal">
            <div class="text-label" style="display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="font-size: 1rem;">analytics</span>
                Total Incidents
            </div>
            <div class="text-value"><?php echo number_format($stats['total_incidents']); ?></div>
            <div style="height: 4px; width: 100%; background: var(--rs-bg); margin-top: 10px; border-radius: 2px;">
                <div style="height: 100%; width: 100%; background: var(--rs-primary); border-radius: 2px;"></div>
            </div>
        </div>
        <div class="rs-card reveal">
            <div class="text-label" style="display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="font-size: 1rem; color: var(--rs-warning);">warning</span>
                Active Cases
            </div>
            <div class="text-value" style="color: var(--rs-warning);"><?php echo number_format($stats['active_incidents']); ?></div>
            <div style="height: 4px; width: 100%; background: var(--rs-bg); margin-top: 10px; border-radius: 2px;">
                <div style="height: 100%; width: 40%; background: var(--rs-warning); border-radius: 2px;"></div>
            </div>
        </div>
        <div class="rs-card reveal">
            <div class="text-label" style="display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="font-size: 1rem; color: var(--rs-success);">check_circle</span>
                Resolved
            </div>
            <div class="text-value" style="color: var(--rs-success);"><?php echo number_format($stats['resolved_incidents']); ?></div>
            <div style="height: 4px; width: 100%; background: var(--rs-bg); margin-top: 10px; border-radius: 2px;">
                <div style="height: 100%; width: 85%; background: var(--rs-success); border-radius: 2px;"></div>
            </div>
        </div>
        <div class="rs-card reveal">
            <div class="text-label" style="display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="font-size: 1rem; color: var(--rs-accent);">verified</span>
                Verified Users
            </div>
            <div class="text-value" style="color: var(--rs-accent);"><?php echo number_format($stats['verified_users']); ?></div>
            <div style="height: 4px; width: 100%; background: var(--rs-bg); margin-top: 10px; border-radius: 2px;">
                <div style="height: 100%; width: 60%; background: var(--rs-accent); border-radius: 2px;"></div>
            </div>
        </div>
    </div>

    <!-- 📰 Unified Intelligence Feed & Actions -->
    <div class="rs-grid rs-grid-main">
        
        <!-- Left Column: Recent Intelligence -->
        <div class="rs-card reveal" style="padding: 0;">
            <div style="padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--rs-bg);">
                <h2 style="font-size: 1.3rem; font-weight: 800; display: flex; align-items: center; gap: 12px;">
                    <span class="material-symbols-outlined" style="color: var(--rs-secondary);">rss_feed</span>
                    Recent Intelligence
                </h2>
                <a href="incidents.php" class="btn-rs" style="font-size: 0.85rem; color: var(--rs-secondary); font-weight: 700;">View All</a>
            </div>

            <style>
                .adaptive-incident-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                    gap: 1.25rem;
                }
                .adaptive-card {
                    padding: 1.25rem;
                    background: #fff;
                    border: 1px solid var(--rs-border);
                    transition: transform 0.2s ease;
                    border-radius: 12px;
                    display: flex;
                    flex-direction: column;
                }
                .adaptive-card h4 {
                    font-size: 1.05rem;
                    margin: 0;
                    font-weight: 800;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    max-width: 60%;
                }
                .adaptive-card p {
                    font-size: 0.9rem;
                    color: #475569;
                    margin-bottom: auto;
                    line-height: 1.5;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                }
                .adaptive-card-footer {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-top: 1px solid var(--rs-bg);
                    padding-top: 0.75rem;
                    font-size: 0.75rem;
                    margin-top: 1rem;
                }
                
                @media (max-width: 600px) {
                    .adaptive-incident-grid {
                        grid-template-columns: repeat(2, 1fr);
                        gap: 0.75rem;
                    }
                    .adaptive-card {
                        padding: 0.85rem;
                    }
                    .adaptive-card h4 {
                        font-size: 0.85rem;
                    }
                    .adaptive-card p {
                        font-size: 0.75rem;
                        -webkit-line-clamp: 3;
                    }
                    .severity-badge {
                        font-size: 0.55rem !important;
                        padding: 2px 6px !important;
                    }
                    .adaptive-card-footer {
                        flex-direction: column;
                        align-items: stretch;
                        gap: 0.75rem;
                        padding-top: 0.6rem;
                    }
                    .meta-info {
                        flex-direction: column !important;
                        align-items: flex-start !important;
                        gap: 2px !important;
                    }
                    .explore-btn {
                        width: 100%;
                        text-align: center;
                        justify-content: center;
                        font-size: 0.75rem !important;
                        padding: 0.35rem 0 !important;
                    }
                    .recent-intel-container { padding: 1rem !important; }
                }
            </style>

            <div class="recent-intel-container" style="padding: 1.5rem 2rem;">
                <?php if (empty($incidents)): ?>
                    <div style="text-align: center; padding: 4rem 1rem; opacity: 0.5;">
                        <span class="material-symbols-outlined" style="font-size: 3rem; margin-bottom: 1rem;">inventory_2</span>
                        <p>No recent reports available for your sector.</p>
                    </div>
                <?php else: ?>
                    <div class="adaptive-incident-grid">
                        <?php foreach ($incidents as $incident): ?>
                            <div class="rs-card reveal adaptive-card">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem; gap: 4px;">
                                    <h4><?php echo htmlspecialchars($incident['title']); ?></h4>
                                    <span class="severity-badge" style="font-size: 0.65rem; font-weight: 900; text-transform: uppercase; padding: 4px 10px; border-radius: 6px; background: <?php echo $incident['severity'] === 'critical' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(245, 158, 11, 0.1)'; ?>; color: <?php echo $incident['severity'] === 'critical' ? '#ef4444' : '#d97706'; ?>;">
                                        <?php echo htmlspecialchars($incident['severity']); ?>
                                    </span>
                                </div>
                                <p>
                                    <?php echo htmlspecialchars(substr($incident['description'], 0, 100)); ?>...
                                </p>
                                <div class="adaptive-card-footer">
                                    <div class="meta-info" style="color: #94a3b8; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                                        <div style="display: flex; align-items: center; gap: 4px;">
                                            <span class="material-symbols-outlined" style="font-size: 0.9rem;">person</span>
                                            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 80px;">By <?php echo htmlspecialchars(explode(' ', $incident['reporter_name'] ?? 'System Node')[0]); ?></span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 4px;">
                                            <span style="opacity: 0.5; display: none;" class="desktop-dot">•</span>
                                            <span style="font-size: 0.65rem;"><?php echo date('M j', strtotime($incident['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <a href="incident_detail.php?id=<?php echo $incident['id']; ?>" class="btn-rs explore-btn" style="background: var(--rs-secondary); color: white; padding: 0.4rem 1rem; border-radius: 8px;">Explore</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Quick Response & Utilities -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- Quick Actions -->
            <div class="rs-card reveal" style="border-top: 4px solid var(--rs-error); height: fit-content;">
                <h3 style="margin-bottom: 1.5rem; font-size: 1.2rem; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                    <span class="material-symbols-outlined text-error">bolt</span>
                    Quick Dispatch
                </h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <a href="report_incident_enhanced.php" class="btn-rs btn-rs-primary float-rs" style="background: var(--rs-error); padding: 1.25rem; justify-content: center; font-size: 1.05rem; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(186, 26, 26, 0.3);">
                        <span class="material-symbols-outlined">add_alert</span>
                        Report Incident
                    </a>
                    <a href="map-functional.php" class="btn-rs btn-rs-outline" style="padding: 1rem; justify-content: center; border-radius: 12px; font-weight: 700; color: #475569;">
                        <span class="material-symbols-outlined">map</span>
                        Live Safety Map
                    </a>
                    <a href="partners.php" class="btn-rs btn-rs-outline" style="padding: 1rem; justify-content: center; border-radius: 12px; font-weight: 700; color: #475569;">
                        <span class="material-symbols-outlined">handshake</span>
                        Verified Partners
                    </a>
                </div>
            </div>

            <!-- Protocol Status -->
            <div class="rs-card reveal" style="background: #f8fafc; border: 1px dashed var(--rs-border);">
                <h4 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 1rem; font-weight: 800;">Protocol Status</h4>
                <div style="display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 0.9rem;">
                    <div style="width: 10px; height: 10px; background: var(--rs-success); border-radius: 50%; box-shadow: 0 0 10px var(--rs-success);"></div>
                    System Secure & Live
                </div>
                <p style="font-size: 0.75rem; color: #64748b; margin-top: 10px;">End-to-end encryption active for all incident reports.</p>
            </div>

        </div>

    </div>


<?php require_once 'includes/footer.php'; ?>
