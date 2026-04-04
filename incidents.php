<?php
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';

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
    SELECT i.*, u.full_name as reporter_name, u.profile_picture as reporter_picture 
    FROM incidents i 
    LEFT JOIN users u ON i.user_id = u.id 
    $where_clause
    ORDER BY i.created_at DESC
", $params)->fetchAll();
?>

<div class="animate-rs">
    
    <!-- Header -->
    <div class="rs-card" style="margin-bottom: 2rem; border-left: 6px solid var(--rs-primary);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-size: 2.25rem; margin-bottom: 5px;">Incident Reports</h1>
                <p style="color: #64748b; font-weight: 600;">View and track safety reports from the community.</p>
            </div>
            <a href="report_incident_enhanced.php" class="btn-rs btn-rs-primary" style="padding: 1rem 2rem;">
                <span class="material-symbols-outlined">add_circle</span>
                New Report
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="rs-card" style="margin-bottom: 2rem; background: #f8fafc;">
        <form method="GET" style="display: flex; gap: 1.5rem; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Filter by Status</label>
                <select name="status" onchange="this.form.submit()" style="width: 100%; padding: 0.85rem; border-radius: 10px; border: 1px solid #e2e8f0; font-weight: 700; color: var(--rs-primary);">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="reported" <?php echo $status === 'reported' ? 'selected' : ''; ?>>Newly Reported</option>
                    <option value="verified" <?php echo $status === 'verified' ? 'selected' : ''; ?>>Verified</option>
                    <option value="investigating" <?php echo $status === 'investigating' ? 'selected' : ''; ?>>Being Investigated</option>
                    <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved / Closed</option>
                </select>
            </div>
            
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Filter by Type</label>
                <select name="type" onchange="this.form.submit()" style="width: 100%; padding: 0.85rem; border-radius: 10px; border: 1px solid #e2e8f0; font-weight: 700; color: var(--rs-primary);">
                    <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="theft" <?php echo $type === 'theft' ? 'selected' : ''; ?>>Theft / Robbery</option>
                    <option value="assault" <?php echo $type === 'assault' ? 'selected' : ''; ?>>Violence / Assault</option>
                    <option value="accident" <?php echo $type === 'accident' ? 'selected' : ''; ?>>Road Accident</option>
                    <option value="fire" <?php echo $type === 'fire' ? 'selected' : ''; ?>>Fire / Smoke</option>
                    <option value="medical" <?php echo $type === 'medical' ? 'selected' : ''; ?>>Medical Help</option>
                    <option value="other" <?php echo $type === 'other' ? 'selected' : ''; ?>>Other Concerns</option>
                </select>
            </div>

            <?php if ($status !== 'all' || $type !== 'all'): ?>
                <a href="incidents.php" class="btn-rs" style="color: var(--rs-error); background: transparent; padding: 0.85rem;">
                    <span class="material-symbols-outlined">filter_alt_off</span> Reset Filters
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Reports Grid -->
    <style>
        .adaptive-incidents-page-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 2rem;
        }
        @media (max-width: 600px) {
            .adaptive-incidents-page-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            .rs-card.adaptive-incidents-card {
                padding: 0.85rem !important;
            }
            .adaptive-incidents-card h3 {
                font-size: 0.95rem !important;
                margin-bottom: 0.5rem !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .adaptive-incidents-card .tag-label {
                font-size: 0.55rem !important;
                padding: 2px 6px !important;
            }
            .adaptive-incidents-card .date-label {
                font-size: 0.6rem !important;
            }
            .adaptive-incidents-card .severity-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.35rem;
                margin-bottom: 0.75rem !important;
            }
            .adaptive-incidents-card p {
                font-size: 0.75rem !important;
                line-height: 1.4 !important;
                -webkit-line-clamp: 3;
                display: -webkit-box;
                -webkit-box-orient: vertical;
                overflow: hidden;
                margin-bottom: 1rem !important;
            }
            .adaptive-incidents-card .location-box {
                font-size: 0.7rem !important;
                padding: 8px !important;
                margin-bottom: 1rem !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                display: block;
            }
            .adaptive-incidents-card .footer-box {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 0.75rem;
                padding-top: 0.75rem !important;
            }
            .adaptive-incidents-card .reporter-box {
                margin-bottom: 0;
            }
            .adaptive-incidents-card .btn-rs {
                width: 100%;
                text-align: center;
                justify-content: center;
                font-size: 0.75rem !important;
                padding: 0.4rem !important;
            }
            .adaptive-incidents-card .reporter-name {
                font-size: 0.75rem !important;
                max-width: 90px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                display: inline-block;
            }
        }
    </style>

    <?php if (empty($incidents)): ?>
        <div class="rs-card" style="text-align: center; padding: 6rem 2rem; border-style: dashed; background: transparent;">
            <span class="material-symbols-outlined" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 1rem;">inbox</span>
            <h3 style="color: #64748b;">No matching reports found</h3>
            <p style="color: #94a3b8;">Try changing your filters or check back later.</p>
        </div>
    <?php else: ?>
        <div class="adaptive-incidents-page-grid">
            <?php foreach ($incidents as $incident): ?>
                <div class="rs-card hover adaptive-incidents-card" style="display: flex; flex-direction: column; border-top: 4px solid <?php echo $incident['severity'] === 'critical' ? 'var(--rs-error)' : ($incident['severity'] === 'high' ? 'var(--rs-warning)' : 'var(--rs-primary)'); ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                        <span class="tag-label" style="background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">
                            <?php echo htmlspecialchars($incident['incident_type'] ?: 'General'); ?>
                        </span>
                        <div class="date-label" style="text-align: right; color: #94a3b8; font-size: 0.75rem; font-weight: 600;">
                            <?php echo date('M d, Y • g:i A', strtotime($incident['created_at'])); ?>
                        </div>
                    </div>
                    
                    <h3 style="font-size: 1.4rem; margin-bottom: 1rem;"><?php echo htmlspecialchars($incident['title']); ?></h3>
                    
                    <div class="severity-row" style="display: flex; gap: 8px; margin-bottom: 1.5rem;">
                        <span class="tag-label" style="background: <?php echo $incident['severity'] === 'critical' ? '#fee2e2' : ($incident['severity'] === 'high' ? '#fef3c7' : '#f1f5f9'); ?>; color: <?php echo $incident['severity'] === 'critical' ? 'var(--rs-error)' : ($incident['severity'] === 'high' ? '#d97706' : '#64748b'); ?>; padding: 4px 10px; border-radius: 6px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase;">
                            <?php echo $incident['severity']; ?> Priority
                        </span>
                        <span class="tag-label" style="background: <?php echo $incident['status'] === 'resolved' ? '#dcfce7' : '#f1f5f9'; ?>; color: <?php echo $incident['status'] === 'resolved' ? 'var(--rs-success)' : '#64748b'; ?>; padding: 4px 10px; border-radius: 6px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase;">
                            Status: <?php echo $incident['status']; ?>
                        </span>
                    </div>

                    <p style="color: #475569; font-size: 0.95rem; line-height: 1.6; margin-bottom: 2rem; flex: 1;">
                        <?php echo htmlspecialchars(substr($incident['description'], 0, 180)) . (strlen($incident['description']) > 180 ? '...' : ''); ?>
                    </p>
                    
                    <?php if ($incident['location_address']): ?>
                        <div class="location-box" style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #64748b; font-weight: 600; margin-bottom: 1.5rem; background: #f8fafc; padding: 12px; border-radius: 10px;">
                            <span class="material-symbols-outlined" style="font-size: 1.1rem; color: var(--rs-accent);">pin_drop</span>
                            <?php echo htmlspecialchars($incident['location_address']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="footer-box" style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 1.25rem;">
                        <div class="reporter-box" style="display: flex; align-items: center; gap: 10px;">
                            <?php if (!empty($incident['reporter_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($incident['reporter_picture']); ?>" style="width: 36px; height: 36px; border-radius: 8px; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 36px; height: 36px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #cbd5e1;">
                                    <span class="material-symbols-outlined" style="font-size: 1.25rem;">person</span>
                                </div>
                            <?php endif; ?>
                            <div class="reporter-name" style="font-size: 0.85rem; font-weight: 700; color: #475569;">
                                <?php echo htmlspecialchars($incident['reporter_name']); ?>
                            </div>
                        </div>
                        <a href="incident_detail.php?id=<?php echo $incident['id']; ?>" class="btn-rs btn-rs-primary" style="padding: 0.6rem 1.25rem; font-size: 0.75rem;">
                            View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
