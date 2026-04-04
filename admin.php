<?php
require_once 'includes/auth.php';

// Check if user has authority access
$role = getUserRole();
if ($role !== 'authority' && $role !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once 'includes/header.php';

$db = new Database();

// --- 1. STATISTICS ---
$stats = [
    'total_reports' => $db->query("SELECT COUNT(*) as count FROM incidents")->fetch()['count'],
    'pending' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE status = 'reported'")->fetch()['count'],
    'active' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE status = 'investigating'")->fetch()['count'],
    'resolved_today' => $db->query("SELECT COUNT(*) as count FROM incidents WHERE status = 'resolved' AND DATE(updated_at) = CURDATE()")->fetch()['count'],
    'total_users' => $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
    'unverified' => $db->query("SELECT COUNT(*) as count FROM users WHERE is_verified = 0")->fetch()['count'],
    'total_authorities' => $db->query("SELECT COUNT(*) as count FROM users WHERE role IN ('authority', 'admin')")->fetch()['count']
];

// --- 2. RECENT ACTIVITY ---
$activity_logs = $db->query("
    SELECT l.*, u.full_name, u.email 
    FROM activity_logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 30
")->fetchAll();

// --- 3. INCIDENTS ---
$pending_incidents = $db->query("
    SELECT i.*, u.full_name as reporter_name 
    FROM incidents i 
    LEFT JOIN users u ON i.user_id = u.id 
    WHERE i.status IN ('reported', 'investigating')
    ORDER BY i.created_at DESC 
    LIMIT 20
")->fetchAll();

// --- 4. AUTHORITY APPLICATIONS ---
$pending_authorities = [];
if ($role === 'admin') {
    $pending_authorities = $db->query("
        SELECT a.*, u.email, u.full_name as user_name 
        FROM authority_applications a
        JOIN users u ON a.user_id = u.id
        WHERE a.status = 'pending'
        ORDER BY a.created_at ASC
    ")->fetchAll();
}

// --- 5. USERS ---
$all_users = [];
if ($role === 'admin') {
    $all_users = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 100")->fetchAll();
}

// --- 6. PARTNERS ---
$all_partners = $db->query("SELECT * FROM partners ORDER BY is_sponsored DESC, name ASC")->fetchAll();

// --- 7. REVENUE ---
$revenue_stats = [
    'total_marketplace' => $db->query("SELECT SUM(amount_fcfa) as total FROM marketplace_payments WHERE status = 'confirmed'")->fetch()['total'] ?? 0,
    'mrr' => $db->query("SELECT SUM(monthly_fee) as total FROM partners WHERE is_sponsored = 1")->fetch()['total'] ?? 0,
    'authority_annual' => $db->query("SELECT COUNT(*) * 100000 as total FROM users WHERE role = 'authority' AND subscription_tier = 'premium'")->fetch()['total'] ?? 0,
    'pending_marketplace' => $db->query("SELECT COUNT(*) as count FROM marketplace_payments WHERE status = 'pending'")->fetch()['count']
];

$all_authorities = $db->query("SELECT * FROM users WHERE role = 'authority' ORDER BY subscription_tier DESC, full_name ASC")->fetchAll();

$pending_payments = $db->query("
    SELECT p.*, l.title as listing_title, u.full_name as seller_name
    FROM marketplace_payments p
    JOIN marketplace_listings l ON p.listing_id = l.id
    JOIN users u ON p.user_id = u.id
    WHERE p.status = 'pending'
    ORDER BY p.created_at ASC
")->fetchAll();
?>

<style>
    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .admin-tab-btn {
        padding: 1rem 1.5rem;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        color: #64748b;
        font-weight: 700;
        cursor: pointer;
        transition: var(--rs-transition);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9rem;
    }

    .admin-tab-btn:hover {
        color: var(--rs-primary);
        background: #f8fafc;
    }

    .admin-tab-btn.active {
        color: var(--rs-primary);
        border-bottom-color: var(--rs-secondary);
    }

    .styled-table {
        width: 100%;
        border-collapse: collapse;
    }

    .styled-table th {
        text-align: left;
        padding: 1rem;
        background: #f8fafc;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
    }

    .styled-table td {
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem;
    }

    .activity-badge {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--rs-success);
        display: inline-block;
        box-shadow: 0 0 8px var(--rs-success);
    }
</style>


<!-- 🏢 Main Header -->
    <div class="rs-card" style="margin-bottom: 2rem; border-radius: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-size: 2.25rem; margin-bottom: 5px;">Admin Dashboard</h1>
                <p style="color: #64748b; font-weight: 600;">Managing Sentinel system as <span
                        style="color: var(--rs-primary);"><?php echo strtoupper($role); ?></span></p>
            </div>
            <div style="text-align: right;">
                <div
                    style="display: flex; align-items: center; gap: 8px; color: var(--rs-success); font-weight: 800; font-size: 0.8rem;">
                    <span class="activity-badge"></span>
                    SYSTEM ONLINE
                </div>
            </div>
        </div>
    </div>

    <!-- 🧭 Tab Navigation -->
    <div class="rs-card" style="padding: 0; margin-bottom: 2rem; border-radius: 16px; overflow: hidden;">
        <div style="display: flex; border-bottom: 1px solid #e2e8f0; overflow-x: auto;">
            <button class="admin-tab-btn active" onclick="showTab('overview')"><span
                    class="material-symbols-outlined">analytics</span> Overview</button>
            <button class="admin-tab-btn" onclick="showTab('activity')"><span
                    class="material-symbols-outlined">list_alt</span> Activity Log</button>
            <button class="admin-tab-btn" onclick="showTab('reports')"><span
                    class="material-symbols-outlined">description</span> Manage Reports</button>
            <?php if ($role === 'admin'): ?>
                <button class="admin-tab-btn" onclick="showTab('users')"><span
                        class="material-symbols-outlined">group</span> User Registry</button>
                <button class="admin-tab-btn" onclick="showTab('verify')">
                    <span class="material-symbols-outlined">verified_user</span>
                    Applications <?php if (count($pending_authorities) > 0): ?><span
                            style="background: var(--rs-error); color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.6rem; display: flex; align-items: center; justify-content: center;"><?php echo count($pending_authorities); ?></span><?php endif; ?>
                </button>
            <?php endif; ?>
            <button class="admin-tab-btn" onclick="showTab('partners')"><span
                    class="material-symbols-outlined">handshake</span> Partners</button>
            <button class="admin-tab-btn" onclick="showTab('revenue')" style="color: var(--rs-success);"><span
                    class="material-symbols-outlined">payments</span> Revenue</button>
            <button class="admin-tab-btn" onclick="showTab('alerts')" style="color: var(--rs-error);"><span
                    class="material-symbols-outlined">emergency_share</span> Send Alerts</button>
        </div>

        <div style="padding: 2rem;">
            <!-- OVERVIEW TAB -->
            <div id="overview" class="tab-content active">
                <div class="rs-grid rs-grid-stats" style="margin-bottom: 2.5rem;">
                    <div class="rs-card" style="background: #f8fafc;">
                        <div class="text-label">All Incident Reports</div>
                        <div class="text-value"><?php echo $stats['total_reports']; ?></div>
                    </div>
                    <div class="rs-card" style="background: #f8fafc;">
                        <div class="text-label">Currently Active</div>
                        <div class="text-value" style="color: var(--rs-warning);">
                            <?php echo $stats['active'] + $stats['pending']; ?></div>
                    </div>
                    <div class="rs-card" style="background: #f8fafc;">
                        <div class="text-label">Resolved Today</div>
                        <div class="text-value" style="color: var(--rs-success);">
                            <?php echo $stats['resolved_today']; ?></div>
                    </div>
                    <div class="rs-card" style="background: #f8fafc;">
                        <div class="text-label">Total Platform Users</div>
                        <div class="text-value" style="color: var(--rs-accent);"><?php echo $stats['total_users']; ?>
                        </div>
                    </div>
                </div>

                <div class="rs-grid rs-grid-main">
                    <div class="rs-card">
                        <h3 style="margin-bottom: 1.5rem;">Recent Platform Events</h3>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php foreach (array_slice($activity_logs, 0, 10) as $log): ?>
                                <div
                                    style="display: flex; align-items: center; gap: 15px; font-size: 0.85rem; padding: 0.75rem; border-bottom: 1px solid #f1f5f9;">
                                    <span
                                        style="color: #94a3b8; font-family: monospace;"><?php echo date('H:i', strtotime($log['created_at'])); ?></span>
                                    <span
                                        style="color: #475569; font-weight: 500;"><?php echo htmlspecialchars($log['action_text']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="rs-card" style="background: var(--rs-primary); color: white; border: none;">
                        <h3 style="color: white; margin-bottom: 1rem;">Admin Note</h3>
                        <p style="font-size: 0.9rem; line-height: 1.6; opacity: 0.8; margin-bottom: 2rem;">Ensure all
                            emergency reports are verified before assigning resources. Misinformation can cause
                            network-wide delays.</p>
                        <div
                            style="padding: 1.25rem; background: rgba(255,255,255,0.05); border-radius: 12px; border-left: 4px solid var(--rs-secondary);">
                            <div
                                style="font-size: 0.65rem; text-transform: uppercase; font-weight: 800; margin-bottom: 5px;">
                                Security Mode</div>
                            <div style="font-weight: 900; color: white;">FULL CONTROL</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ACTIVITY LOG TAB -->
            <div id="activity" class="tab-content">
                <h3 style="margin-bottom: 1.5rem;">Detailed Audit Logs</h3>
                <div class="rs-card" style="padding: 0; overflow-x: auto;">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Category</th>
                                <th>Action Performed</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activity_logs as $log): ?>
                                <tr>
                                    <td style="color: #94a3b8;">
                                        <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                                    <td><span
                                            style="font-size: 0.65rem; font-weight: 800; padding: 4px 8px; border-radius: 4px; background: #f1f5f9; color: #64748b;"><?php echo strtoupper($log['action_type']); ?></span>
                                    </td>
                                    <td style="font-weight: 600; color: var(--rs-primary);">
                                        <?php echo htmlspecialchars($log['action_text']); ?></td>
                                    <td style="color: #64748b; font-size: 0.8rem;">
                                        <?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- REPORTS TAB -->
            <div id="reports" class="tab-content">
                <h3 style="margin-bottom: 1.5rem;">Manage Incident Reports</h3>
                <div class="rs-card" style="padding: 0; overflow-x: auto;">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Incident</th>
                                <th>Reporter</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_incidents as $incident): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 700;"><?php echo htmlspecialchars($incident['title']); ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #94a3b8;">
                                            <?php echo date('M d, H:i', strtotime($incident['created_at'])); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($incident['reporter_name']); ?></td>
                                    <td>
                                        <span
                                            style="color: <?php echo $incident['severity'] === 'critical' ? 'var(--rs-error)' : '#d97706'; ?>; font-weight: 800; font-size: 0.7rem;">
                                            <?php echo strtoupper($incident['severity']); ?>
                                        </span>
                                    </td>
                                    <td><span
                                            style="font-weight: 700; color: #64748b; font-size: 0.8rem;"><?php echo strtoupper($incident['status']); ?></span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <a href="incident_detail.php?id=<?php echo $incident['id']; ?>"
                                                class="btn-rs btn-rs-outline" style="padding: 6px;"><span
                                                    class="material-symbols-outlined"
                                                    style="font-size: 1rem;">visibility</span></a>
                                            <?php if ($incident['status'] === 'reported'): ?>
                                                <button onclick="updateStatus(<?php echo $incident['id']; ?>, 'verified')"
                                                    class="btn-rs"
                                                    style="padding: 6px; background: #dcfce7; color: #15803d;"><span
                                                        class="material-symbols-outlined"
                                                        style="font-size: 1rem;">check</span></button>
                                            <?php endif; ?>
                                            <?php if ($incident['status'] === 'investigating'): ?>
                                                <button onclick="updateStatus(<?php echo $incident['id']; ?>, 'resolved')"
                                                    class="btn-rs"
                                                    style="padding: 6px; background: #dbeafe; color: #1d4ed8;"><span
                                                        class="material-symbols-outlined"
                                                        style="font-size: 1rem;">done_all</span></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- USERS TAB (Admin Only) -->
            <?php if ($role === 'admin'): ?>
                <div id="users" class="tab-content">
                    <h3 style="margin-bottom: 1.5rem;">User Registry</h3>
                    <div class="rs-card" style="padding: 0; overflow-x: auto;">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Email Address</th>
                                    <th>System Role</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_users as $u): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <?php if ($u['profile_picture']): ?>
                                                    <img src="<?php echo htmlspecialchars($u['profile_picture']); ?>"
                                                        style="width: 32px; height: 32px; border-radius: 8px;">
                                                <?php else: ?>
                                                    <div
                                                        style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #cbd5e1;">
                                                        <span class="material-symbols-outlined"
                                                            style="font-size: 1.25rem;">person</span></div>
                                                <?php endif; ?>
                                                <span
                                                    style="font-weight: 700;"><?php echo htmlspecialchars($u['full_name']); ?></span>
                                                <?php if ($u['is_verified']): ?><span class="material-symbols-outlined"
                                                        style="color: var(--rs-success); font-size: 1rem;">verified</span><?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><span
                                                style="font-size: 0.7rem; font-weight: 800; background: #f1f5f9; padding: 4px 8px; border-radius: 4px;"><?php echo strtoupper($u['role']); ?></span>
                                        </td>
                                        <td style="color: #94a3b8; font-size: 0.8rem;">
                                            <?php echo date('M Y', strtotime($u['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- VERIFY TAB (Admin Only) -->
                <div id="verify" class="tab-content">
                    <h3 style="margin-bottom: 1.5rem;">Pending Official Applications</h3>
                    <?php if (empty($pending_authorities)): ?>
                        <p
                            style="padding: 3rem; text-align: center; color: #94a3b8; background: #f8fafc; border-radius: 12px; border: 2px dashed #e2e8f0;">
                            No pending applications for authorities.</p>
                    <?php else: ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($pending_authorities as $auth): ?>
                                <div class="rs-card">
                                    <h4 style="font-size: 1.25rem; margin-bottom: 5px;">
                                        <?php echo htmlspecialchars($auth['organization_name']); ?></h4>
                                    <p
                                        style="color: var(--rs-secondary); font-size: 0.7rem; font-weight: 800; text-transform: uppercase; margin-bottom: 1.5rem; letter-spacing: 1px;">
                                        <?php echo htmlspecialchars($auth['organization_type']); ?></p>

                                    <div
                                        style="display: flex; flex-direction: column; gap: 8px; font-size: 0.85rem; color: #64748b; margin-bottom: 2rem;">
                                        <div style="display: flex; align-items: center; gap: 8px;"><span
                                                class="material-symbols-outlined" style="font-size: 1.1rem;">person</span>
                                            <?php echo htmlspecialchars($auth['contact_person']); ?>
                                            (<?php echo htmlspecialchars($auth['position']); ?>)</div>
                                        <div style="display: flex; align-items: center; gap: 8px;"><span
                                                class="material-symbols-outlined" style="font-size: 1.1rem;">badge</span> ID:
                                            <?php echo htmlspecialchars($auth['government_id']); ?></div>
                                        <div style="display: flex; align-items: center; gap: 8px;"><span
                                                class="material-symbols-outlined" style="font-size: 1.1rem;">call</span>
                                            <?php echo htmlspecialchars($auth['phone']); ?></div>
                                    </div>

                                    <div style="display: flex; gap: 10px;">
                                        <button onclick="approveAuth(<?php echo $auth['user_id']; ?>)" class="btn-rs btn-rs-primary"
                                            style="flex: 1; justify-content: center;">Approve</button>
                                        <button onclick="rejectAuth(<?php echo $auth['user_id']; ?>)" class="btn-rs"
                                            style="flex: 1; justify-content: center; background: #f1f5f9;">Dismiss</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- PARTNERS TAB -->
            <div id="partners" class="tab-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0;">Emergency Ecosystem Partners</h3>
                    <button class="btn-rs btn-rs-primary" onclick="showTab('revenue')"><span class="material-symbols-outlined">military_tech</span> Manage Sponsorships</button>
                </div>
                <div class="rs-card" style="padding: 0; overflow-x: auto;">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Organization</th>
                                <th>Category</th>
                                <th>Sponsorship</th>
                                <th>Contact Axis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_partners as $p): ?>
                                <tr>
                                    <td style="font-weight: 700; color: var(--rs-primary);">
                                        <?php echo htmlspecialchars($p['name']); ?></td>
                                    <td><span
                                             style="font-size: 0.7rem; font-weight: 800; background: #f1f5f9; padding: 4px 8px; border-radius: 4px;"><?php echo strtoupper($p['partner_type']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($p['is_sponsored']): ?>
                                            <span style="color: #ea580c; font-weight: 900; font-size: 0.7rem;">★
                                                <?php echo strtoupper($p['sponsor_tier'] ?? 'SPONSORED'); ?></span>
                                        <?php else: ?>
                                            <span style="color: #cbd5e1; font-weight: 600; font-size: 0.7rem;">GENERAL</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.8rem; color: #64748b; font-weight: 600;">
                                        <?php echo htmlspecialchars($p['contact_phone']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- REVENUE TAB -->
            <div id="revenue" class="tab-content">
                <h3 style="margin-bottom: 1.5rem;">Revenue & Monetization Management</h3>
                
                <div class="rs-grid rs-grid-stats" style="margin-bottom: 2.5rem;">
                    <div class="rs-card" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                        <div class="text-label" style="color: #166534;">Marketplace Sales</div>
                        <div class="text-value" style="color: #15803d;"><?php echo number_format($revenue_stats['total_marketplace']); ?> <span style="font-size: 0.8rem;">FCFA</span></div>
                    </div>
                    <div class="rs-card" style="background: #fffbeb; border: 1px solid #fef3c7;">
                        <div class="text-label" style="color: #92400e;">Active MRR (Partners)</div>
                        <div class="text-value" style="color: #b45309;"><?php echo number_format($revenue_stats['mrr']); ?> <span style="font-size: 0.8rem;">FCFA/mo</span></div>
                    </div>
                    <div class="rs-card" style="background: #eff6ff; border: 1px solid #dbeafe;">
                        <div class="text-label" style="color: #1a56db;">Authority Subscriptions</div>
                        <div class="text-value" style="color: #1e40af;"><?php echo number_format($revenue_stats['authority_annual']); ?> <span style="font-size: 0.8rem;">FCFA/yr</span></div>
                    </div>
                    <div class="rs-card" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                        <div class="text-label">Pending Payments</div>
                        <div class="text-value" style="color: var(--rs-primary);"><?php echo $revenue_stats['pending_marketplace']; ?></div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <!-- Pending Marketplace Payments -->
                    <div class="rs-card" style="padding: 0;">
                        <div style="padding: 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                            <h4 style="margin: 0;">Marketplace Approvals</h4>
                            <span class="badge" style="background: var(--rs-secondary); color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 800;"><?php echo count($pending_payments); ?> NEW</span>
                        </div>
                        <?php if (empty($pending_payments)): ?>
                            <p style="padding: 2rem; text-align: center; color: #94a3b8; font-size: 0.9rem;">No pending marketplace payments.</p>
                        <?php else: ?>
                            <div style="max-height: 400px; overflow-y: auto;">
                                <table class="styled-table">
                                    <thead>
                                        <tr>
                                            <th>Seller / Listing</th>
                                            <th>Reference</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_payments as $pay): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight: 700;"><?php echo htmlspecialchars($pay['seller_name']); ?></div>
                                                    <div style="font-size: 0.75rem; color: #64748b;"><?php echo htmlspecialchars($pay['listing_title']); ?></div>
                                                </td>
                                                <td>
                                                    <div style="font-family: monospace; font-size: 0.8rem;"><?php echo htmlspecialchars($pay['payment_reference'] ?? 'No Ref'); ?></div>
                                                    <div style="font-size: 0.7rem; color: #94a3b8;"><?php echo htmlspecialchars($pay['payment_phone']); ?></div>
                                                </td>
                                                <td>
                                                    <div style="display: flex; gap: 5px;">
                                                        <button onclick="manageRevenue('confirm_payment', {payment_id: <?php echo $pay['id']; ?>, listing_id: <?php echo $pay['listing_id']; ?>})" class="btn-rs" style="padding: 5px; background: #dcfce7; color: #15803d; border-radius: 6px;"><span class="material-symbols-outlined" style="font-size: 1rem;">check</span></button>
                                                        <button onclick="manageRevenue('reject_payment', {payment_id: <?php echo $pay['id']; ?>, listing_id: <?php echo $pay['listing_id']; ?>})" class="btn-rs" style="padding: 5px; background: #fee2e2; color: #b91c1c; border-radius: 6px;"><span class="material-symbols-outlined" style="font-size: 1rem;">close</span></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Partner Sponsorship Management -->
                    <div class="rs-card" style="padding: 0;">
                        <div style="padding: 1.5rem; border-bottom: 1px solid #f1f5f9;">
                            <h4 style="margin: 0;">Partner Sponsorships</h4>
                        </div>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="styled-table">
                                <thead>
                                    <tr>
                                        <th>Partner</th>
                                        <th>Current Tier</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_partners as $p): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 700;"><?php echo htmlspecialchars($p['name']); ?></div>
                                                <div style="font-size: 0.75rem; color: #64748b;"><?php echo $p['is_sponsored'] ? 'Expires: ' . date('M d, Y', strtotime($p['sponsor_expires_at'])) : 'Unsponsored'; ?></div>
                                            </td>
                                            <td>
                                                <span style="font-size: 0.7rem; font-weight: 800; padding: 4px 8px; border-radius: 4px; background: <?php echo $p['sponsor_tier'] === 'gold' ? '#fef3c7' : ($p['sponsor_tier'] === 'silver' ? '#f1f5f9' : '#f8fafc'); ?>; color: <?php echo $p['sponsor_tier'] === 'gold' ? '#b45309' : ($p['sponsor_tier'] === 'silver' ? '#475569' : '#94a3b8'); ?>; border: 1px solid <?php echo $p['sponsor_tier'] === 'gold' ? '#fcd34d' : ($p['sponsor_tier'] === 'silver' ? '#cbd5e1' : '#e2e8f0'); ?>;">
                                                    <?php echo strtoupper($p['sponsor_tier'] ?? 'LISTED'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button onclick="openSponsorModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['name'])); ?>')" class="btn-rs btn-rs-outline" style="padding: 6px; font-size: 0.75rem;">Modify</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 2rem;">
                    <!-- Authority Subscription Management -->
                    <div class="rs-card" style="padding: 0;">
                        <div style="padding: 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                            <h4 style="margin: 0;">Official Subscriptions (NGOs/Security Firms)</h4>
                            <span class="badge" style="background: #eff6ff; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 800;">100,000 FCFA / YR</span>
                        </div>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="styled-table">
                                <thead>
                                    <tr>
                                        <th>Official</th>
                                        <th>Tier</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_authorities as $auth): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 700;"><?php echo htmlspecialchars($auth['full_name']); ?></div>
                                                <div style="font-size: 0.7rem; color: #94a3b8;"><?php echo htmlspecialchars($auth['email']); ?></div>
                                            </td>
                                            <td>
                                                <span style="font-size: 0.7rem; font-weight: 800; padding: 4px 8px; border-radius: 6px; background: <?php echo $auth['subscription_tier'] === 'premium' ? '#dbeafe' : '#f1f5f9'; ?>; color: <?php echo $auth['subscription_tier'] === 'premium' ? '#1e40af' : '#64748b'; ?>;">
                                                    <?php echo strtoupper($auth['subscription_tier'] ?? 'BASIC'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-size: 0.65rem; font-weight: 900; color: <?php echo $auth['subscription_status'] === 'active' ? '#16a34a' : ($auth['subscription_status'] === 'expired' ? '#dc2626' : '#94a3b8'); ?>;">
                                                    <?php echo strtoupper($auth['subscription_status'] ?? 'NONE'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button onclick="openSubscriptionModal(<?php echo $auth['id']; ?>, '<?php echo htmlspecialchars(addslashes($auth['full_name'])); ?>', '<?php echo $auth['subscription_tier']; ?>')" class="btn-rs btn-rs-outline" style="padding: 6px; font-size: 0.75rem;">Modify</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Authority Subscription Modal -->
            <div id="subModal" class="modal-overlay" style="display:none;">
                <div class="modal-box" style="max-width: 400px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="margin: 0;">Plan: <span id="subName"></span></h3>
                        <button onclick="document.getElementById('subModal').style.display='none'" style="background: none; border: none; cursor: pointer;"><span class="material-symbols-outlined">close</span></button>
                    </div>
                    <form id="subForm" onsubmit="event.preventDefault(); submitSubscription();">
                        <input type="hidden" id="subUserId">
                        <div style="margin-bottom: 1.25rem;">
                            <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Subscription Tier</label>
                            <select id="subTier" class="form-field" style="width: 100%;">
                                <option value="basic">Basic (Free)</option>
                                <option value="premium">Premium NGO (100,000 FCFA/YR)</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Expiry Date</label>
                            <input type="date" id="subExpiry" class="form-field" style="width: 100%;" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                        </div>
                        <button type="submit" class="btn-rs btn-rs-primary" style="width: 100%; justify-content: center; padding: 1rem;">Update Subscription</button>
                    </form>
                </div>

            <!-- Sponsor Modal -->
            <div id="sponsorModal" class="modal-overlay" style="display:none;">
                <div class="modal-box" style="max-width: 450px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="margin: 0;">Sponsorship: <span id="sponsorPartnerName"></span></h3>
                        <button onclick="document.getElementById('sponsorModal').style.display='none'" style="background: none; border: none; cursor: pointer;"><span class="material-symbols-outlined">close</span></button>
                    </div>
                    <form id="sponsorForm" onsubmit="event.preventDefault(); submitSponsorship();">
                        <input type="hidden" id="sponsorPartnerId">
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Select Tier</label>
                            <select id="sponsorTier" class="form-field" style="width: 100%;">
                                <option value="listed">Listed (Free)</option>
                                <option value="silver">Silver (25,000 FCFA)</option>
                                <option value="gold">Gold (50,000 FCFA)</option>
                            </select>
                        </div>
                        <div id="premiumFields" style="display: none;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Total Fee (FCFA)</label>
                                    <input type="number" id="sponsorAmount" class="form-field" style="width: 100%;" placeholder="e.g. 50000">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Duration (Months)</label>
                                    <input type="number" id="sponsorMonths" class="form-field" style="width: 100%;" value="1">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Payment Phone</label>
                                    <input type="text" id="sponsorPhone" class="form-field" style="width: 100%;" placeholder="+237...">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Reference</label>
                                    <input type="text" id="sponsorRef" class="form-field" style="width: 100%;" placeholder="TXN...">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn-rs btn-rs-primary" style="width: 100%; justify-content: center; padding: 1rem;">Update Sponsorship</button>
                    </form>
                </div>
            </div>
 </div>

            <!-- ALERTS TAB -->
            <div id="alerts" class="tab-content">
                <div style="max-width: 700px; margin: 0 auto; padding: 2rem 0;">
                    <div class="rs-card" style="border-top: 6px solid var(--rs-error);">
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 2rem;">
                            <div
                                style="background: #fee2e2; color: var(--rs-error); padding: 12px; border-radius: 12px;">
                                <span class="material-symbols-outlined" style="font-size: 2rem;">campaign</span></div>
                            <h2 style="font-size: 1.75rem;">Global Alert Broadcast</h2>
                        </div>
                        <p style="color: #64748b; margin-bottom: 2.5rem; font-weight: 500;">Warning: Alerts sent here
                            will be broadcast across the entire Sentinel network immediately.</p>

                        <form id="alertForm" class="rs-grid" style="gap: 1.5rem;">
                            <div>
                                <label
                                    style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Alert
                                    Heading</label>
                                <input type="text" id="alertTitle"
                                    style="width: 100%; padding: 1rem; border-radius: 10px; border: 1px solid #e2e8f0; font-weight: 700;"
                                    placeholder="e.g. Traffic Reroute in Douala Center" required>
                            </div>
                            <div>
                                <label
                                    style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Message
                                    Content</label>
                                <textarea id="alertMessage"
                                    style="width: 100%; padding: 1rem; border-radius: 10px; border: 1px solid #e2e8f0; font-weight: 500; min-height: 120px;"
                                    placeholder="Details of the situation..." required></textarea>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div>
                                    <label
                                        style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Priority
                                        Level</label>
                                    <select id="alertType"
                                        style="width: 100%; padding: 1rem; border-radius: 10px; border: 1px solid #e2e8f0; font-weight: 700;">
                                        <option value="emergency">Critical (Red)</option>
                                        <option value="warning">Warning (Yellow)</option>
                                        <option value="info">Info (Blue)</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Expires
                                        in</label>
                                    <select id="alertDuration"
                                        style="width: 100%; padding: 1rem; border-radius: 10px; border: 1px solid #e2e8f0; font-weight: 700;">
                                        <option value="4">4 Hours</option>
                                        <option value="12">12 Hours</option>
                                        <option value="24">24 Hours</option>
                                        <option value="48">48 Hours</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn-rs btn-rs-primary"
                                style="background: var(--rs-error); padding: 1.25rem; font-size: 1.1rem; justify-content: center; margin-top: 1rem; border-radius: 12px;">
                                SEND EMERGENCY ALERT
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.admin-tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');

        // Find triggering button
        const buttons = document.querySelectorAll('.admin-tab-btn');
        buttons.forEach(btn => {
            if (btn.innerText.toLowerCase().includes(tabId.replace('activity', 'log').replace('reports', 'manage').replace('verify', 'app').trim())) {
                btn.classList.add('active');
            }
        });
    }

    function manageRevenue(action, params) {
        if (!confirm('Confirm revenue action: ' + action + '?')) return;
        fetch('api/manage_revenue.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action, ...params })
        })
        .then(r => r.json())
        .then(d => d.success ? location.reload() : alert('Error: ' + d.message));
    }

    function openSponsorModal(id, name) {
        document.getElementById('sponsorPartnerId').value = id;
        document.getElementById('sponsorPartnerName').textContent = name;
        document.getElementById('sponsorModal').style.display = 'flex';
    }

    document.getElementById('sponsorTier').addEventListener('change', function() {
        document.getElementById('premiumFields').style.display = this.value !== 'listed' ? 'block' : 'none';
        if (this.value === 'gold') document.getElementById('sponsorAmount').value = 50000;
        else if (this.value === 'silver') document.getElementById('sponsorAmount').value = 25000;
        else document.getElementById('sponsorAmount').value = 0;
    });

    function submitSponsorship() {
        const params = {
            partner_id: document.getElementById('sponsorPartnerId').value,
            tier: document.getElementById('sponsorTier').value,
            amount: document.getElementById('sponsorAmount').value,
            months: document.getElementById('sponsorMonths').value,
            payment_phone: document.getElementById('sponsorPhone').value,
            payment_reference: document.getElementById('sponsorRef').value
        };
        manageRevenue('set_partner_tier', params);
    }

    function openSubscriptionModal(id, name, tier) {
        document.getElementById('subUserId').value = id;
        document.getElementById('subName').textContent = name;
        document.getElementById('subTier').value = tier || 'basic';
        document.getElementById('subModal').style.display = 'flex';
    }

    function submitSubscription() {
        const params = {
            user_id: document.getElementById('subUserId').value,
            tier: document.getElementById('subTier').value,
            expiry: document.getElementById('subExpiry').value
        };
        fetch('api/manage_revenue.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'set_authority_subscription', ...params })
        })
        .then(r => r.json())
        .then(d => d.success ? location.reload() : alert('Error: ' + d.message));
    }

    function updateStatus(id, newStatus) {
        if (!confirm('Update incident to ' + newStatus + '?')) return;
        fetch('api/update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ incident_id: id, status: newStatus })
        })
            .then(r => r.json())
            .then(d => d.success ? location.reload() : alert('Error: ' + d.message));
    }

    function approveAuth(id) {
        if (!confirm('Authorize this official organization?')) return;
        processAuth(id, 'approved');
    }

    function rejectAuth(id) {
        if (!confirm('Reject this application?')) return;
        processAuth(id, 'rejected');
    }

    function processAuth(id, status) {
        fetch('api/verify_authority.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: id, status: status })
        })
            .then(r => r.json())
            .then(d => d.success ? location.reload() : alert('Error: ' + d.message));
    }

    document.getElementById('alertForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const b = this.querySelector('button');
        b.innerText = 'BROADCASTING...';
        b.disabled = true;

        fetch('api/create_alert.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title: document.getElementById('alertTitle').value,
                message: document.getElementById('alertMessage').value,
                type: document.getElementById('alertType').value,
                duration: document.getElementById('alertDuration').value
            })
        })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    alert('Success: Alert sent to all nodes.');
                    this.reset();
                } else alert('Error: ' + d.message);
            })
            .finally(() => {
                b.innerText = 'SEND EMERGENCY ALERT';
                b.disabled = false;
            });
    });
    // Sync Bottom Nav with showTab
    const originalShowTab = window.showTab;
    window.showTab = function(tabId) {
        if (originalShowTab) originalShowTab(tabId);
        
        // Update Bottom Nav Active States
        document.querySelectorAll('.mobile-app-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('onclick').includes(tabId)) {
                link.classList.add('active');
            }
        });
    };
</script>

<?php require_once 'includes/footer.php'; ?>