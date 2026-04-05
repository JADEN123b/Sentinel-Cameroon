<?php
require_once 'includes/auth.php';

// Get statistics
$db = new Database();
try {
    $total_incidents_result = $db->fetch("SELECT COUNT(*) as count FROM incidents");
    $active_incidents_result = $db->fetch("SELECT COUNT(*) as count FROM incidents WHERE status IN ('reported', 'verified', 'investigating')");
    $total_users_result = $db->fetch("SELECT COUNT(*) as count FROM users");
    $verified_partners_result = $db->fetch("SELECT COUNT(*) as count FROM partners WHERE is_verified = 1");

    $stats = [
        'total_incidents' => $total_incidents_result ? $total_incidents_result['count'] : 0,
        'active_incidents' => $active_incidents_result ? $active_incidents_result['count'] : 0,
        'total_users' => $total_users_result ? $total_users_result['count'] : 0,
        'verified_partners' => $verified_partners_result ? $verified_partners_result['count'] : 0
    ];
} catch (Throwable $e) {
    error_log("Landing page stats error: " . $e->getMessage());
    $stats = ['total_incidents' => 0, 'active_incidents' => 0, 'total_users' => 0, 'verified_partners' => 0];
}

// Get recent incidents
try {
    $recent_incidents = $db->fetchAll("
        SELECT i.*, u.full_name as reporter_name 
        FROM incidents i 
        LEFT JOIN users u ON i.user_id = u.id 
        ORDER BY i.created_at DESC 
        LIMIT 6
    ", []);
} catch (Throwable $e) {
    error_log("Landing page incidents error: " . $e->getMessage());
    $recent_incidents = [];
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Sentinel Cameroon | Authoritative Community Safety</title>
    <link rel="stylesheet" href="assets/css/authoritative.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;700;900&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
</head>

<body class="bg-surface font-body text-on-surface selection:bg-primary-fixed selection:text-on-primary-fixed">
    <!-- TopNavBar -->
    <!-- TopNavBar -->
    <nav class="navbar">
        <div class="container flex justify-between items-center">
            <div class="text-2xl font-black text-on-surface uppercase tracking-wider font-headline py-4">
                Sentinel Cameroon
            </div>
            
            <!-- Desktop Navigation -->
            <div class="navbar-nav desktop-only">
                <?php if (isLoggedIn()): ?>
                    <a class="nav-link" href="incidents.php">Reporting</a>
                    <a class="nav-link" href="map.php">Live Map</a>
                    <a class="nav-link" href="partners.php">Safety Partners</a>
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a class="nav-link" href="#" onclick="showLoginPrompt('Reporting')">Reporting</a>
                    <a class="nav-link" href="#" onclick="showLoginPrompt('Real-time Alerts')">Alerts</a>
                    <a class="nav-link" href="#" onclick="showLoginPrompt('Community Map')">Map</a>
                    <a class="nav-link" href="#" onclick="showLoginPrompt('Verification')">Verification</a>
                <?php endif; ?>
            </div>
            
            <div class="navbar-actions desktop-only">
                <?php if (isLoggedIn()): ?>
                    <a href="logout.php" class="btn btn-white">Sign Out</a>
                    <a href="report_incident.php" class="btn btn-primary">Report Incident</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-white">Sign In</a>
                    <a href="register.php" class="btn btn-primary">Join Platform</a>
                <?php endif; ?>
            </div>

            <!-- Mobile Hamburger Toggle -->
            <button id="mobileMenuToggle" class="mobile-only btn btn-white p-2">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>
        <div class="bg-slate-200/20 h-[1px]"></div>
    </nav>

    <!-- Mobile Navigation Drawer -->
    <div id="mobileDrawer" class="mobile-drawer-overlay" style="display: none;">
        <div class="mobile-drawer-content slide-in-right">
            <div class="drawer-header">
                <div class="text-xl font-black text-on-surface uppercase tracking-wider font-headline">
                    Sentinel
                </div>
                <button id="closeDrawer" class="btn btn-white p-2">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <div class="drawer-nav">
                <?php if (isLoggedIn()): ?>
                    <div class="nav-section-title">PLATFORM</div>
                    <a class="drawer-link" href="incidents.php">Reporting</a>
                    <a class="drawer-link" href="map.php">Live Map</a>
                    <a class="drawer-link" href="partners.php">Safety Partners</a>
                    <a class="drawer-link" href="dashboard.php">Dashboard</a>
                    <div class="nav-divider"></div>
                    <a class="drawer-link text-error" href="logout.php">Sign Out</a>
                <?php else: ?>
                    <div class="nav-section-title">NAVIGATION</div>
                    <a class="drawer-link" href="#" onclick="toggleAndPrompt('Reporting')">Reporting</a>
                    <a class="drawer-link" href="#" onclick="toggleAndPrompt('Real-time Alerts')">Alerts</a>
                    <a class="drawer-link" href="#" onclick="toggleAndPrompt('Community Map')">Map</a>
                    <a class="drawer-link" href="#" onclick="toggleAndPrompt('Verification')">Verification</a>
                    <div class="nav-divider"></div>
                    <a class="drawer-link font-bold text-primary" href="login.php">Sign In</a>
                    <a class="drawer-link font-bold text-secondary" href="register.php">Create Account</a>
                <?php endif; ?>
            </div>
            
            <div class="drawer-footer">
                <div class="p-4 bg-surface-container-low rounded-2xl flex items-center gap-3">
                    <div class="pulse-box">
                        <span class="pulse"></span>
                    </div>
                    <div class="text-xs font-bold uppercase tracking-widest text-slate-500">System Live</div>
                </div>
            </div>
        </div>
    </div>

    <main class="pt-20">
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-bg">
                <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuA4Ru5gzpj_UczHbSNhXeQXARmLKly8umVud3F19kaAxZCt-z-FAlRUsg4sVLpPtQgWjTK3kdoP727E0pZU_7yMPfSTdY72BuCWxbsabht_XR35zM9Nj9x0czDajxJ7tO_b65u0IRVWu_Kvk2-58YQxTL_X-nkw2qWk7XFDYLDVuQjgdiLj1vQCGI3409XG61gwH8ETmX9236B6PG9OMbEBEAS-LnMgZHPK6bsOfoK3h-ir_igl_cQkjokvln1DW1pz2n7oM90S0PA"
                    alt="Abstract aerial map of a modern African city with glowing network lines representing community connectivity and safety surveillance" />
            </div>
            <div class="container">
                <div class="hero-grid">
                    <div class="hero-content">
                        <div class="hero-badge">
                            <span class="pulse"></span>
                            <span
                                class="text-secondary font-bold text-sm tracking-widest uppercase font-headline">System
                                Status: Active</span>
                        </div>
                        <h1 class="hero-title">
                            Your Community, <br /><span class="highlight">Protected.</span>
                        </h1>
                        <p class="hero-description">
                            A unified platform for real-time incident reporting and alerts across Cameroon.
                            Authoritative data for a safer tomorrow.
                        </p>
                        <div class="hero-actions">
                            <a href="register.php" class="btn btn-primary btn-xl">Get Started</a>
                            <a href="map.php" class="btn btn-white btn-xl">View Live Map</a>
                        </div>
                    </div>
                    <div class="verification-card">
                        <div class="verification-card-bg"></div>
                        <div class="verification-card-content">
                            <div class="verification-card-header">
                                <h3 class="verification-card-title">Recent Verification</h3>
                                <span class="material-symbols-outlined text-tertiary float-rs">verified_user</span>
                            </div>
                            <div class="verification-steps">
                                <?php if (!empty($recent_incidents)): ?>
                                    <?php foreach (array_slice($recent_incidents, 0, 2) as $incident): ?>
                                        <div class="verification-item">
                                            <div class="verification-item-icon bg-error/10 text-error">
                                                <span class="material-symbols-outlined">warning</span>
                                            </div>
                                            <div class="verification-item-content">
                                                <p class="verification-item-title">
                                                    <?php echo htmlspecialchars($incident['title']); ?>
                                                </p>
                                                <p class="verification-item-location">
                                                    <?php echo htmlspecialchars($incident['location_address'] ?: 'Cameroon'); ?>
                                                </p>
                                                <div class="verification-badge bg-tertiary/10 text-tertiary">VERIFIED</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="verification-item">
                                        <div class="verification-item-icon bg-secondary/10 text-secondary">
                                            <span class="material-symbols-outlined">shield</span>
                                        </div>
                                        <div class="verification-item-content">
                                            <p class="verification-item-title">System Active</p>
                                            <p class="verification-item-location">Monitoring All Regions</p>
                                            <div class="verification-badge bg-secondary/10 text-secondary">ROUTINE</div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats-section">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-item">
                        <p class="stat-number"><?php echo number_format($stats['active_incidents']); ?></p>
                        <p class="stat-label">Active Reports Today</p>
                    </div>
                    <div class="stat-item">
                        <p class="stat-number">98%</p>
                        <p class="stat-label">Verification Rate</p>
                    </div>
                    <div class="stat-item">
                        <p class="stat-number"><?php echo number_format($stats['total_users']); ?>+</p>
                        <p class="stat-label">Safeguarding Communities</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Core Features -->
        <section class="features-section reveal">
            <div class="container">
                <div class="features-header">
                    <h2 class="text-4xl font-black font-headline mb-6 tracking-tight">Intelligent Safety Infrastructure
                    </h2>
                    <p class="text-lg text-slate-600">Advanced tools designed for rapid response and collective
                        security, built specifically for the Cameroonian landscape.</p>
                </div>
                <div class="features-grid">
                    <div class="feature-card group">
                        <div class="feature-icon primary">
                            <span class="material-symbols-outlined text-3xl">add_alert</span>
                        </div>
                        <h3 class="feature-title">Real-time Reporting</h3>
                        <p class="feature-description">Easily report incidents with precise location data and photo
                            evidence directly from the field.</p>
                    </div>
                    <div class="feature-card group">
                        <div class="feature-icon secondary">
                            <span class="material-symbols-outlined text-3xl">location_on</span>
                        </div>
                        <h3 class="feature-title">Localized Alerts</h3>
                        <p class="feature-description">Receive instant notifications relevant to your neighborhood,
                            ensuring you're never caught off guard.</p>
                    </div>
                    <div class="feature-card group">
                        <div class="feature-icon tertiary">
                            <span class="material-symbols-outlined text-3xl">fact_check</span>
                        </div>
                        <h3 class="feature-title">Verified Information</h3>
                        <p class="feature-description">Multi-tier verification involving trusted citizens and local
                            authorities to combat misinformation.</p>
                    </div>
                    <div class="feature-card group">
                        <div class="feature-icon dark">
                            <span class="material-symbols-outlined text-3xl">insights</span>
                        </div>
                        <h3 class="feature-title">Community Intelligence</h3>
                        <p class="feature-description">Access historical safety data and trend analysis to make informed
                            decisions about your environment.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Community Marketplace -->
        <section class="partners-section reveal">
            <div class="container">
                <div class="partners-header">
                    <div>
                        <h2 class="partners-title">Community Partners</h2>
                        <p class="partners-description">Connecting you with verified local expertise—from tactical
                            security professionals to legal aid and rapid maintenance services.</p>
                    </div>
                    <a href="login.php" class="partners-link">View Partner Directory</a>
                </div>
                <div class="partners-grid">
                    <div class="partner-card group">
                        <div class="partner-image">
                            <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuB63TD-CdG4bTXrWt756wN2y2VrLvlMhnGYrDzrFsePzBFQJarHSfzGHMNTeIizUBCTx9ocCIw-UcU2luccCDV8LHs9hRI7qbfBJCjv6WFq9iXwa1zzQ7AooeV1aMfeFC2NhaOssUeGjWbS65yuv9VxJKNcvU7UDvCO8WzcH4HaizSueh5qDIaPP3-sX8N5QNKUJj7mha8qaO2jhk1pYzQHzZoxB522l4okGlj5LEeYF26JTZYASOBDy1qg4Lv2CjpR7lrqN2j4upY"
                                alt="Professional security team members in high-visibility tactical gear monitoring digital safety systems in a modern command center" />
                        </div>
                        <div class="partner-content">
                            <div class="partner-header">
                                <h4 class="partner-title">Centurion Response</h4>
                                <span class="partner-badge platinum">PLATINUM PARTNER</span>
                            </div>
                            <p class="partner-description">Premium residential security and emergency escort services
                                across Yaoundé metropolitan areas.</p>
                            <div class="partner-category">
                                <span class="material-symbols-outlined text-secondary text-sm">shield</span>
                                <span class="text-xs font-bold text-slate-700">Security Sector</span>
                            </div>
                        </div>
                    </div>
                    <div class="partner-card group">
                        <div class="partner-image">
                            <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuC9Jlr26K9OmwW6m2-b8hdje8DhNTdXnfWXC7FeUZT6otrunaEOtJwxfNGhN-eJ6fFvRXE7yuoda4YWP85x3Q2ZuuntHYWaAZlPocuzIOg6H2_r32A8SyhkBH7h5hzmdXEtC_Q-sIpUYqVOlukPYtX9QPD9zLjNHwg3F39jWPtI0aAlng3WXCkCUI3BrSkcTnifnh30kKJ3BpX_5R4clQ7HQDiJKVV76aa9O4TKVlOP-3eyfoL_AGe1fS66qFcrr2V_U_JgLvVZ4AY"
                                alt="Modern law office library with leather bound books and a clean glass desk symbolizing professional legal aid and community justice" />
                        </div>
                        <div class="partner-content">
                            <div class="partner-header">
                                <h4 class="partner-title">Advocates for Peace</h4>
                                <span class="partner-badge verified">VERIFIED</span>
                            </div>
                            <p class="partner-description">Pro-bono legal counsel and mediation services for
                                community-dispute resolution and civil rights.</p>
                            <div class="partner-category">
                                <span class="material-symbols-outlined text-secondary text-sm">gavel</span>
                                <span class="text-xs font-bold text-slate-700">Legal Support</span>
                            </div>
                        </div>
                    </div>
                    <div class="partner-card group">
                        <div class="partner-image">
                            <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuA5S3CK_XSKdh-IS0nIShf8PeNweVeQwMPoD7g5saBYDokOB1iR0frSGwipn7KOaamyBT_0m28gPYHZUIA2JDGRdgS0w-X73pmOTMGPvwGWVEnUI17RL79ZMaj5scX0U0gqzZSw-ihe-1F-5okJxuE5h3rHs7D1n5-Bw4IlPNhWSwmZEXy1Piuv0_lkCw-n30RLtckH_vrSSHCvBM8lLGdVoUHYm31ysRefcoeNXA1tq9ZBoMEwG_mnK0BomBafhyON8Em6uQW4Jcg"
                                alt="Skilled technician in uniform working with precision tools on community infrastructure project under bright natural light" />
                        </div>
                        <div class="partner-content">
                            <div class="partner-header">
                                <h4 class="partner-title">Urban Repairs Co.</h4>
                                <span class="partner-badge verified">VERIFIED</span>
                            </div>
                            <p class="partner-description">Rapid response maintenance for community assets, lighting,
                                and safety infrastructure.</p>
                            <div class="partner-category">
                                <span class="material-symbols-outlined text-secondary text-sm">handyman</span>
                                <span class="text-xs font-bold text-slate-700">Infrastructure</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works & Authority -->
        <section class="protocol-section">
            <div class="container">
                <div class="protocol-grid">
                    <!-- Citizens -->
                    <div>
                        <h3 class="protocol-title">Citizen Protocol</h3>
                        <div class="protocol-steps">
                            <div class="protocol-step">
                                <div class="protocol-step-number">01</div>
                                <div>
                                    <h4 class="font-bold mb-2">Identify & Log</h4>
                                    <p class="text-slate-600">Open the app, select incident type, and capture live
                                        evidence with geo-tagging.</p>
                                </div>
                            </div>
                            <div class="protocol-step">
                                <div class="protocol-step-number">02</div>
                                <div>
                                    <h4 class="font-bold mb-2">Community Peer-Review</h4>
                                    <p class="text-slate-600">Trusted local nodes cross-verify the incident data for
                                        accuracy within minutes.</p>
                                </div>
                            </div>
                            <div class="protocol-step">
                                <div class="protocol-step-number">03</div>
                                <div>
                                    <h4 class="font-bold mb-2">Network Alert</h4>
                                    <p class="text-slate-600">Verified alerts are broadcasted to nearby residents and
                                        relevant authorities.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Authority Portal -->
                    <div class="authority-portal">
                        <div class="authority-portal-bg"></div>
                        <div class="authority-portal-content">
                            <span class="material-symbols-outlined authority-icon">admin_panel_settings</span>
                            <h3 class="authority-title">Authority Access</h3>
                            <p class="authority-description">Dedicated portal for law enforcement, emergency responders,
                                and government agencies to manage high-level responses and verified data streams.</p>
                            <ul class="authority-features">
                                <li>
                                    <span class="material-symbols-outlined" data-weight="fill">check_circle</span>
                                    Encrypted Command & Control Channel
                                </li>
                                <li>
                                    <span class="material-symbols-outlined" data-weight="fill">check_circle</span>
                                    Real-time Heatmapping & Analytics
                                </li>
                                <li>
                                    <span class="material-symbols-outlined" data-weight="fill">check_circle</span>
                                    Multi-Agency Coordination Tools
                                </li>
                            </ul>
                            <a href="admin.php" class="authority-btn">
                                Access Secure Portal
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="cta-card">
                <h2 class="cta-title">Security is a Collective Responsibility.</h2>
                <p class="cta-description">Join thousands of citizens in Cameroon who are proactively making their
                    neighborhoods safer through the power of verified community intelligence.</p>
                <div class="cta-actions">
                    <a href="register.php" class="btn btn-white btn-xl">Download App</a>
                    <button class="btn btn-outline-white btn-xl">Learn More</button>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-column">
                <div class="footer-brand">Sentinel Cameroon</div>
                <p class="footer-description">Authoritative community safety platform. Empowering citizens through
                    transparency and verified data.</p>
            </div>
            <div class="footer-column">
                <h5 class="footer-title">Platform</h5>
                <nav class="footer-links">
                    <?php if (isLoggedIn()): ?>
                        <a href="dashboard.php">User Dashboard</a>
                        <a href="incidents.php">Incident Reports</a>
                        <a href="map.php">Community Map</a>
                    <?php else: ?>
                        <a href="#" onclick="showLoginPrompt('Dashboard')">User Dashboard</a>
                        <a href="#" onclick="showLoginPrompt('Incidents')">Incident Reports</a>
                        <a href="#" onclick="showLoginPrompt('Map')">Community Map</a>
                    <?php endif; ?>
                </nav>
            </div>
            <div class="footer-column">
                <h5 class="footer-title">Resources</h5>
                <nav class="footer-links">
                    <a href="#">Citizen Guide</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="partners.php">Community Partners</a>
                    <?php else: ?>
                        <a href="#" onclick="showLoginPrompt('Partners')">Community Partners</a>
                    <?php endif; ?>
                    <a href="#">Contact Support</a>
                </nav>
            </div>
            <div class="footer-column">
                <h5 class="footer-title">Legal</h5>
                <nav class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </nav>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="footer-copyright"> &copy; <?php echo date('Y'); ?> Sentinel Cameroon. Authoritative Calm. All rights reserved.</p>
            <div class="footer-social">
                <a href="#"><span class="material-symbols-outlined text-xl">language</span></a>
                <a href="#"><span class="material-symbols-outlined text-xl">public</span></a>
                <a href="#"><span class="material-symbols-outlined text-xl">shield_person</span></a>
            </div>
        </div>
    </footer>

    <!-- Login Prompt Modal -->
    <div id="loginPromptModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="text-xl font-bold">🔐 Login Required</h3>
                <button onclick="closeLoginPrompt()" class="modal-close">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-6">
                    <div class="modal-icon">
                        <span class="material-symbols-outlined">lock</span>
                    </div>
                    <p class="text-lg font-bold mb-2">Please login to access <span id="featureName" class="text-primary"></span></p>
                    <p class="text-slate-600">Join our community safety platform to report incidents and stay informed.</p>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <a href="login.php" class="btn btn-primary justify-center">Sign In Now</a>
                    <a href="register.php" class="btn btn-white justify-center">Create New Account</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile Drawer Logic
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const closeDrawer = document.getElementById('closeDrawer');
        const mobileDrawer = document.getElementById('mobileDrawer');

        function toggleDrawer() {
            const isVisible = mobileDrawer.style.display === 'flex';
            mobileDrawer.style.display = isVisible ? 'none' : 'flex';
            document.body.style.overflow = isVisible ? 'auto' : 'hidden';
        }

        function toggleAndPrompt(feature) {
            toggleDrawer();
            showLoginPrompt(feature);
        }

        mobileMenuToggle.addEventListener('click', toggleDrawer);
        closeDrawer.addEventListener('click', toggleDrawer);

        // Click outside to close
        mobileDrawer.addEventListener('click', (e) => {
            if (e.target === mobileDrawer) toggleDrawer();
        });

        // Existing Modal Logic
        function showLoginPrompt(feature) {
            document.getElementById('featureName').textContent = feature;
            document.getElementById('loginPromptModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeLoginPrompt() {
            document.getElementById('loginPromptModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('loginPromptModal');
            if (event.target === modal) closeLoginPrompt();
        }
    </script>

    <style>
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            border-radius: 2rem;
            width: 90%;
            max-width: 480px;
            overflow: hidden;
            box-shadow: var(--shadow-2xl);
            animation: modalScale 0.3s ease-out;
        }
        @keyframes modalScale {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal-header {
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
        }
        .modal-close {
            background: none;
            border: none;
            cursor: pointer;
            color: #64748b;
        }
        .modal-body {
            padding: 2rem;
        }
        .modal-icon {
            width: 4rem;
            height: 4rem;
            background: rgba(156, 52, 0, 0.1);
            color: var(--primary);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .modal-icon span { font-size: 2rem; }
        .text-center { text-align: center; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .grid-cols-1 { display: grid; grid-template-columns: 1fr; }
        .gap-4 { gap: 1rem; }
        .justify-center { justify-content: center; }
    </style>
    <script src="assets/js/sentinel-animations.js"></script>
</body>

</html>