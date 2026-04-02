<?php
session_start();
require_once 'database/config.php';
require_once 'includes/auth.php';

// Auto-protect current page
autoProtect();

$user = getCurrentUser();
$user_role = getUserRole();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentinel Cameroon - Community Safety Platform</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/profile-fix.css">
    <link rel="stylesheet" href="assets/css/modern-framework.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Public+Sans:wght@600;700;800;900&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">
    
    <style>
        /* Modern Layout Fixes */
        .desktop-navbar {
            background: var(--surface);
            border-bottom: 1px solid var(--surface-container);
            padding: 0.875rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        @media (max-width: 768px) {
            .desktop-navbar, .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0 !important;
                padding-bottom: 80px; /* Space for bottom nav */
            }
        }
    </style>
</head>

<body class="<?php echo isLoggedIn() ? 'has-bottom-nav' : ''; ?>">

    <?php if (isLoggedIn()): ?>

        <!-- ── Mobile Top Bar ── -->
        <header class="mobile-topbar">
            <span class="mobile-topbar-title">Sentinel Cameroon</span>
            <div class="mobile-topbar-actions">
                <a href="alerts.php" class="mobile-icon-btn" title="Alerts">
                    <span class="material-symbols-outlined">notifications</span>
                </a>
                <a href="profile.php" class="mobile-avatar" title="Profile">
                    <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
                </a>
            </div>
        </header>

        <!-- ── Desktop Navbar ── -->
        <nav class="desktop-navbar">
            <div class="container flex justify-between items-center">
                <div class="flex items-center gap-6">
                    <a href="dashboard.php" class="navbar-brand">Sentinel Cameroon</a>
                    <nav class="navbar-nav">
                        <a href="dashboard.php"
                            class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                        <a href="incidents.php"
                            class="nav-link <?php echo $current_page == 'incidents.php' ? 'active' : ''; ?>">Incidents</a>
                        <a href="map.php"
                            class="nav-link <?php echo $current_page == 'map.php' ? 'active' : ''; ?>">Map</a>
                        <a href="alerts.php"
                            class="nav-link <?php echo $current_page == 'alerts.php' ? 'active' : ''; ?>">Alerts</a>
                        <a href="partners.php"
                            class="nav-link <?php echo $current_page == 'partners.php' ? 'active' : ''; ?>">Partners</a>
                        <?php if ($user_role === 'authority' || $user_role === 'admin'): ?>
                            <a href="admin.php"
                                class="nav-link <?php echo $current_page == 'admin.php' ? 'active' : ''; ?>">Admin Panel</a>
                        <?php endif; ?>
                    </nav>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600">Welcome,
                        <?php echo htmlspecialchars($user['full_name'] ?? ''); ?></span>
                    <a href="profile.php" class="nav-link">Profile</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                </div>
            </div>
        </nav>

        <!-- ── Desktop Sidebar ── -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <h3 style="font-size:1.125rem;font-weight:700;color:var(--primary);">Sentinel Cameroon</h3>
                <p class="text-xs text-gray-500 mt-1"><?php echo ucfirst($user_role); ?> Portal</p>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"
                    class="sidebar-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="material-symbols-outlined">dashboard</span>
                    Overview
                </a>
                <a href="incidents.php"
                    class="sidebar-link <?php echo $current_page == 'incidents.php' ? 'active' : ''; ?>">
                    <span class="material-symbols-outlined">emergency</span>
                    Incidents
                </a>
                <a href="report_incident.php"
                    class="sidebar-link <?php echo $current_page == 'report_incident.php' ? 'active' : ''; ?>">
                    <span class="material-symbols-outlined">add_alert</span>
                    Report Incident
                </a>
                <a href="map.php"
                    class="sidebar-link <?php echo $current_page == 'map.php' ? 'active' : ''; ?>">
                    <span class="material-symbols-outlined">map</span>
                    Live Map
                </a>
                <a href="alerts.php"
                    class="sidebar-link <?php echo $current_page == 'alerts.php' ? 'active' : ''; ?>">
                    <span class="material-symbols-outlined">notifications_active</span>
                    Alerts
                </a>
                <a href="partners.php"
                    class="sidebar-link <?php echo $current_page == 'partners.php' ? 'active' : ''; ?>">
                    <span class="material-symbols-outlined">handshake</span>
                    Partners
                </a>
                <a href="profile.php"
                    class="sidebar-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <span class="material-symbols-outlined">person</span>
                    Profile
                </a>
                <?php if ($user_role === 'authority' || $user_role === 'admin'): ?>
                    <a href="admin.php"
                        class="sidebar-link <?php echo $current_page == 'admin.php' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">admin_panel_settings</span>
                        Admin Panel
                    </a>
                <?php endif; ?>
                <a href="logout.php" class="sidebar-link" style="margin-top:auto;color:var(--error);">
                    <span class="material-symbols-outlined">logout</span>
                    Sign Out
                </a>
            </nav>
        </aside>

        <!-- ── Mobile Bottom Navigation ── -->
        <nav class="bottom-nav" aria-label="Main navigation">
            <a href="dashboard.php"
                class="bottom-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">dashboard</span>
                <span>Home</span>
            </a>
            <a href="incidents.php"
                class="bottom-nav-item <?php echo $current_page == 'incidents.php' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">emergency</span>
                <span>Incidents</span>
            </a>
            <!-- Center FAB: Report -->
            <a href="report_incident.php" class="bottom-nav-fab" aria-label="Report an incident">
                <div class="fab-circle">
                    <span class="material-symbols-outlined">add_alert</span>
                </div>
                <span class="fab-label">Report</span>
            </a>
            <a href="map.php" class="bottom-nav-item <?php echo $current_page == 'map.php' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">map</span>
                <span>Map</span>
            </a>
            <a href="alerts.php"
                class="bottom-nav-item <?php echo $current_page == 'alerts.php' ? 'active' : ''; ?>">
                <span class="material-symbols-outlined">notifications</span>
                <span>Alerts</span>
            </a>
        </nav>

    <?php else: ?>
        <!-- Public nav (unauthenticated) -->
        <nav class="desktop-navbar">
            <div class="container flex justify-between items-center">
                <a href="index.php" class="navbar-brand">Sentinel Cameroon</a>
                <nav class="navbar-nav">
                    <a href="login.php" class="nav-link">Login</a>
                    <a href="register.php" class="nav-link">Register</a>
                    <a href="authority_register.php" class="nav-link">Authority Registration</a>
                </nav>
            </div>
        </nav>
    <?php endif; ?>

    <main class="main-content">
        <div class="container">