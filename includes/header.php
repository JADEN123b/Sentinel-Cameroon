<?php
/**
 * Sentinel Cameroon - Visual Header Template
 * This file contains only the visual structure (sidebar, head, and layout).
 * auth.php must be included before this file at the page entry point.
 */
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentinel Cameroon | Local Safety Network</title>

    <!-- 🛡️ Unified Design System -->
    <link rel="stylesheet" href="assets/css/resilient-sentinel.css">
    <link rel="stylesheet" href="assets/css/authoritative.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Public+Sans:wght@700;800;900&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">

    <style>
        /* Modern Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .sidebar-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* FORCE MOBILE DRAWER OVERRIDES TO BYPASS CACHE */
        @media (max-width: 1024px) {
            .sidebar {
                position: fixed !important;
                left: 0;
                top: 0;
                bottom: 0;
                width: 280px;
                z-index: 4000;
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: flex !important;
                flex-direction: column;
                overflow-y: auto !important;
                box-shadow: 10px 0 30px rgba(0, 0, 0, 0.3);
            }

            .sidebar.active {
                transform: translateX(0) !important;
            }

            .drawer-overlay {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.4);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                z-index: 3800;
                display: none;
            }

            .drawer-overlay.active {
                display: block !important;
            }

            .mobile-hamburger {
                display: flex !important;
            }

            /* =========================================
               GLOBAL MOBILE VIEWPORT LOCK-DOWN
               ========================================= */
            .no-scroll {
                overflow: hidden !important;
                height: 100vh !important;
            }

            html,
            body,
            .app-shell,
            .fluid-container {
                overflow-x: hidden !important;
                max-width: 100vw !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            .main-content {
                padding: 0.75rem !important;
                overflow-x: hidden !important;
                max-width: 100vw !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            .rs-card {
                padding: 1.25rem !important;
                /* Force overrides all inline desktop paddings */
                max-width: 100% !important;
                box-sizing: border-box !important;
                overflow-x: hidden !important;
            }

            h1 {
                font-size: 1.75rem !important;
            }

            h2 {
                font-size: 1.35rem !important;
            }
        }
    </style>
</head>

<body style="background-color: var(--rs-bg); -webkit-font-smoothing: antialiased;">
    <div id="drawerOverlay" class="drawer-overlay" onclick="toggleSidebar()"></div>
    <div class="app-shell">
        <?php if (isLoggedIn()): ?>
            <aside class="sidebar">
                <div class="sidebar-header">
                    <div class="sidebar-brand">
                        <span class="material-symbols-outlined sidebar-brand-icon">shield</span>
                        <h3 style="color: white; margin: 0; font-size: 1.25rem;">Sentinel</h3>
                    </div>
                </div>

                <div
                    style="padding: 1.5rem; margin: 0 0.75rem 1rem; background: rgba(255,255,255,0.03); border-radius: 12px; display: flex; align-items: center; gap: 12px;">
                    <?php
                    $user = getCurrentUser();
                    if ($user && !empty($user['profile_picture'])): ?>
                        <div class="sidebar-user-avatar">
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php else: ?>
                        <div class="sidebar-user-avatar">
                            <span class="material-symbols-outlined"
                                style="color: rgba(255,255,255,0.5); font-size: 1.25rem;">person</span>
                        </div>
                    <?php endif; ?>
                    <div style="overflow: hidden;">
                        <div
                            style="font-size: 0.85rem; font-weight: 700; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </div>
                        <div
                            style="font-size: 0.65rem; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 1px;">
                            <?php echo getUserRole() === 'authority_pending' ? 'Pending' : ucfirst(getUserRole()); ?>
                            Account
                        </div>
                    </div>
                </div>

                <nav class="sidebar-menu">
                    <?php if (getUserRole() === 'admin' || getUserRole() === 'authority' || getUserRole() === 'authority_pending'): ?>
                        <a href="admin.php"
                            class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : ''; ?>">
                            <span class="material-symbols-outlined">dashboard</span>
                            Main Dashboard
                        </a>
                    <?php else: ?>
                        <a href="dashboard.php"
                            class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                            <span class="material-symbols-outlined">home</span>
                            My Overview
                        </a>
                    <?php endif; ?>

                    <a href="incidents.php"
                        class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'incidents.php' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">description</span>
                        Incident Reports
                    </a>

                    <a href="map-functional.php"
                        class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'map-functional.php' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">map</span>
                        Live Map
                    </a>

                    <a href="communities.php"
                        class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'communities.php' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">groups</span>
                        Communities
                    </a>

                    <a href="market.php"
                        class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'market.php' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">shopping_bag</span>
                        Marketplace
                    </a>

                    <a href="partners.php"
                        class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'partners.php' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">group</span>
                        Our Partners
                    </a>

                    <div style="margin: 1.5rem; border-top: 1px solid rgba(255,255,255,0.05);"></div>

                    <a href="profile.php"
                        class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">manage_accounts</span>
                        My Profile
                    </a>

                    <a href="logout.php" class="sidebar-link" style="margin-top: auto; color: #f87171;">
                        <span class="material-symbols-outlined">logout</span>
                        Log Out
                    </a>
                </nav>
            </aside>
        <?php endif; ?>

        <main class="main-content">
            <div class="fluid-container">
                <?php if (isLoggedIn()): ?>
                    <!-- Mobile Top Bar -->
                    <style>
                        /* Custom Premium Hamburger Bars */
                        .mobile-hamburger {
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            gap: 5px;
                            background: white;
                            border: 1px solid #e2e8f0;
                            width: 44px;
                            height: 44px;
                            border-radius: 12px;
                            cursor: pointer;
                            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                            transition: all 0.2s ease;
                            padding: 0;
                            outline: none;
                        }

                        .mobile-hamburger:active {
                            transform: scale(0.92);
                        }

                        .hamburger-bar {
                            display: block;
                            width: 20px;
                            height: 2.5px;
                            background-color: var(--rs-primary);
                            border-radius: 4px;
                            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        }

                        .mobile-hamburger:hover .hamburger-bar {
                            background-color: var(--rs-secondary);
                        }
                    </style>
                    <div class="mobile-only"
                        style="align-items: center; justify-content: space-between; margin-bottom: 2rem;">
                        <button class="mobile-hamburger" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
                            <span class="hamburger-bar"></span>
                            <span class="hamburger-bar"></span>
                            <span class="hamburger-bar"></span>
                        </button>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="material-symbols-outlined"
                                style="color: var(--rs-secondary); font-size: 1.5rem;">shield</span>
                            <span style="font-weight: 900; font-size: 1.1rem;">Sentinel</span>
                        </div>
                    </div>
                <?php endif; ?>