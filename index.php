<?php
require_once 'includes/public_header.php';

// Get recent incidents for homepage
$db = new Database();
$recent_incidents_stmt = $db->query("
    SELECT i.*, u.full_name as reporter_name 
    FROM incidents i 
    LEFT JOIN users u ON i.user_id = u.id 
    ORDER BY i.created_at DESC 
    LIMIT 6
");
$recent_incidents = $recent_incidents_stmt ? $recent_incidents_stmt->fetchAll() : [];

// Get statistics
try {
    $total_incidents_stmt = $db->query("SELECT COUNT(*) as count FROM incidents");
    $active_incidents_stmt = $db->query("SELECT COUNT(*) as count FROM incidents WHERE status IN ('reported', 'verified', 'investigating')");
    $total_users_stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $verified_partners_stmt = $db->query("SELECT COUNT(*) as count FROM partners WHERE is_verified = 1");

    $stats = [
        'total_incidents' => $total_incidents_stmt ? $total_incidents_stmt->fetch()['count'] : 0,
        'active_incidents' => $active_incidents_stmt ? $active_incidents_stmt->fetch()['count'] : 0,
        'total_users' => $total_users_stmt ? $total_users_stmt->fetch()['count'] : 0,
        'verified_partners' => $verified_partners_stmt ? $verified_partners_stmt->fetch()['count'] : 0
    ];
} catch (Exception $e) {
    error_log("Failed to fetch landing page statistics: " . $e->getMessage());
    $stats = ['total_incidents' => 0, 'active_incidents' => 0, 'total_users' => 0, 'verified_partners' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentinel Cameroon - Community Safety Platform</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Public+Sans:wght@600;700;800;900&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container flex justify-between items-center">
            <a href="index.php" class="navbar-brand">Sentinel Cameroon</a>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <span class="material-symbols-outlined">menu</span>
            </button>

            <div class="navbar-nav" id="mainNav">
                <a href="#features" class="nav-link">Features</a>
                <a href="#how-it-works" class="nav-link">How It Works</a>
                <?php if (isLoggedIn()): ?>
                    <a href="incidents.php" class="nav-link">Incidents</a>
                    <a href="partners.php" class="nav-link">Partners</a>
                    <a href="dashboard.php" class="btn btn-primary btn-sm">Dashboard</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="#" onclick="showLoginPrompt('View Incidents')" class="nav-link">Incidents</a>
                    <a href="#" onclick="showLoginPrompt('Partners')" class="nav-link">Partners</a>
                    <a href="login.php" class="nav-link">Login</a>
                    <a href="register.php" class="btn btn-primary btn-sm">Join Now</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-primary to-primary-dark text-white py-20">
        <div class="container text-center">
            <h1 class="text-5xl font-bold mb-6">Community Safety for Cameroon</h1>
            <p class="text-xl mb-8 max-w-3xl mx-auto leading-relaxed">
                Report incidents, track safety alerts, and connect with verified partners in your community.
                Together, we can make Cameroon safer for everyone.
            </p>
            <div class="flex gap-4 justify-center">
                <?php if (isLoggedIn()): ?>
                    <a href="report_incident.php" class="btn btn-secondary text-lg">
                        <span class="material-symbols-outlined">add_alert</span>
                        Report Incident
                    </a>
                    <a href="map.php"
                        class="btn btn-outline text-lg border-white text-white hover:bg-white hover:text-primary">
                        <span class="material-symbols-outlined">map</span>
                        View Live Map
                    </a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-secondary text-lg">
                        <span class="material-symbols-outlined">person_add</span>
                        Join Community
                    </a>
                    <a href="#" onclick="showLoginPrompt('View Incidents')"
                        class="btn btn-outline text-lg border-white text-white hover:bg-white hover:text-primary">
                        <span class="material-symbols-outlined">visibility</span>
                        View Incidents
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-16 bg-surface">
        <div class="container">
            <h2 class="text-3xl font-bold text-center mb-12">Platform Impact</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <div class="bg-surface rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="text-4xl font-bold text-primary mb-2">
                        <?php echo number_format($stats['total_incidents']); ?></div>
                    <div class="text-gray-600">Incidents Reported</div>
                    <div class="text-xs text-gray-500 mt-2">Since launch</div>
                </div>
                <div class="bg-surface rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="text-4xl font-bold text-warning mb-2">
                        <?php echo number_format($stats['active_incidents']); ?></div>
                    <div class="text-gray-600">Active Cases</div>
                    <div class="text-xs text-gray-500 mt-2">Requiring attention</div>
                </div>
                <div class="bg-surface rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="text-4xl font-bold text-success mb-2">
                        <?php echo number_format($stats['total_users']); ?></div>
                    <div class="text-gray-600">Community Members</div>
                    <div class="text-xs text-gray-500 mt-2">Registered users</div>
                </div>
                <div class="bg-surface rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
                    <div class="text-4xl font-bold text-secondary mb-2">
                        <?php echo number_format($stats['verified_partners']); ?></div>
                    <div class="text-gray-600">Verified Partners</div>
                    <div class="text-xs text-gray-500 mt-2">Trusted organizations</div>
                </div>
            </div>

            <!-- Recent Activity Feed -->
            <div class="mt-12">
                <h3 class="text-xl font-bold text-center mb-6">Recent Activity</h3>
                <div class="bg-surface-container-low rounded-lg p-6">
                    <?php if (empty($recent_incidents)): ?>
                        <p class="text-center text-gray-600 py-8">No recent incidents reported.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach (array_slice($recent_incidents, 0, 3) as $incident): ?>
                                <div class="flex items-start gap-3 p-3 bg-surface rounded-lg">
                                    <div
                                        class="w-2 h-2 bg-<?php echo $incident['severity'] === 'critical' ? 'error' : ($incident['severity'] === 'high' ? 'warning' : 'info'); ?> rounded-full">
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-sm"><?php echo htmlspecialchars($incident['title']); ?></h4>
                                        <p class="text-xs text-gray-500">
                                            <?php echo date('M j, g:i A', strtotime($incident['created_at'])); ?></p>
                                        <p class="text-sm text-gray-600">
                                            <?php echo htmlspecialchars(substr($incident['description'], 0, 100)); ?>...</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                    </div>
                        <div class="text-center mt-4">
                            <?php if (isLoggedIn()): ?>
                                <a href="incidents.php" class="btn btn-primary">View All Incidents</a>
                            <?php else: ?>
                                <a href="#" onclick="showLoginPrompt('View All Incidents')" class="btn btn-primary">View All
                                    Incidents</a>
                            <?php endif; ?>
                        </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Recent Incidents -->
    <section class="py-16">
        <div class="container">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold">Recent Incidents</h2>
                <?php if (isLoggedIn()): ?>
                    <a href="incidents.php" class="btn btn-primary">View All Incidents</a>
                <?php else: ?>
                    <a href="#" onclick="showLoginPrompt('View All Incidents')" class="btn btn-primary">View All
                        Incidents</a>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($recent_incidents as $incident): ?>
                    <div class="card">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="font-bold"><?php echo htmlspecialchars($incident['title']); ?></h3>
                            <span
                                class="bg-<?php echo $incident['severity'] === 'critical' ? 'error' : ($incident['severity'] === 'high' ? 'warning' : 'info'); ?> text-white px-2 py-1 rounded text-xs">
                                <?php echo ucfirst($incident['severity']); ?>
                            </span>
                        </div>
                        <p class="text-gray-700 mb-3">
                            <?php echo htmlspecialchars(substr($incident['description'], 0, 150)); ?>...</p>
                        <div class="flex justify-between items-center text-sm text-gray-600">
                            <span>By:
                                <?php echo $incident['is_anonymous'] ? 'Anonymous' : htmlspecialchars($incident['reporter_name']); ?></span>
                            <span><?php echo date('M j, Y', strtotime($incident['created_at'])); ?></span>
                        </div>
                        <?php if (isLoggedIn()): ?>
                            <a href="incident_detail.php?id=<?php echo $incident['id']; ?>" class="btn btn-outline text-sm">
                                View Details
                            </a>
                        <?php else: ?>
                            <a href="#" onclick="showLoginPrompt('Incident Details')" class="btn btn-outline text-sm">
                                View Details
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-16 bg-surface-container-low">
        <div class="container">
            <h2 class="text-3xl font-bold text-center mb-12">How Sentinel Cameroon Works</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary rounded-full mx-auto mb-4 flex items-center justify-center">
                        <span class="material-symbols-outlined text-white text-3xl">report_problem</span>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Report Incidents</h3>
                    <p class="text-gray-600 mb-4">Quickly report safety incidents with details, photos, and location
                        data for faster response.</p>
                    <ul class="text-left text-sm text-gray-600 space-y-2">
                        <li>• Multiple incident types (theft, assault, accident, fire, medical)</li>
                        <li>• Photo and video evidence upload</li>
                        <li>• GPS location tracking</li>
                        <li>• Anonymous reporting option</li>
                        <li>• Real-time status updates</li>
                    </ul>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-warning rounded-full mx-auto mb-4 flex items-center justify-center">
                        <span class="material-symbols-outlined text-white text-3xl">notifications_active</span>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Real-time Alerts</h3>
                    <p class="text-gray-600 mb-4">Get instant notifications about incidents in your area and stay
                        informed.</p>
                    <ul class="text-left text-sm text-gray-600 space-y-2">
                        <li>• Location-based push notifications</li>
                        <li>• Incident severity levels</li>
                        <li>• SMS and email alerts</li>
                        <li>• Custom alert preferences</li>
                        <li>• Emergency broadcast system</li>
                    </ul>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-success rounded-full mx-auto mb-4 flex items-center justify-center">
                        <span class="material-symbols-outlined text-white text-3xl">handshake</span>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Verified Partners</h3>
                    <p class="text-gray-600 mb-4">Connect with trusted local authorities, businesses, and community
                        organizations.</p>
                    <ul class="text-left text-sm text-gray-600 space-y-2">
                        <li>• Verified government agencies</li>
                        <li>• Medical facilities and hospitals</li>
                        <li>• Local businesses and services</li>
                        <li>• Community organizations</li>
                        <li>• Direct messaging system</li>
                        <li>• Rating and review system</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-surface-container border-t border-surface-container-low mt-16">
        <div class="container py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h4 class="font-bold mb-4 text-primary">Sentinel Cameroon</h4>
                    <p class="text-sm text-gray-600 mb-4">Leveraging community intelligence to build a safer nation for all citizens.</p>
                    <div class="flex gap-3">
                        <a href="#" class="text-primary hover:opacity-80"><span class="material-symbols-outlined">facebook</span></a>
                        <a href="#" class="text-primary hover:opacity-80"><span class="material-symbols-outlined">share</span></a>
                        <a href="#" class="text-primary hover:opacity-80"><span class="material-symbols-outlined">alternate_email</span></a>
                    </div>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Platform</h4>
                    <ul class="space-y-2">
                        <?php if (isLoggedIn()): ?>
                            <li><a href="dashboard.php" class="text-sm text-gray-600 hover:text-primary">User Dashboard</a></li>
                            <li><a href="incidents.php" class="text-sm text-gray-600 hover:text-primary">Incident Reports</a></li>
                            <li><a href="map.php" class="text-sm text-gray-600 hover:text-primary">Community Map</a></li>
                            <li><a href="partners.php" class="text-sm text-gray-600 hover:text-primary">Safety Partners</a></li>
                        <?php else: ?>
                            <li><a href="#" onclick="showLoginPrompt('User Dashboard')" class="text-sm text-gray-600 hover:text-primary">User Dashboard</a></li>
                            <li><a href="#" onclick="showLoginPrompt('Incident Reports')" class="text-sm text-gray-600 hover:text-primary">Incident Reports</a></li>
                            <li><a href="#" onclick="showLoginPrompt('Community Map')" class="text-sm text-gray-600 hover:text-primary">Community Map</a></li>
                            <li><a href="#" onclick="showLoginPrompt('Safety Partners')" class="text-sm text-gray-600 hover:text-primary">Safety Partners</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Company</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-sm text-gray-600 hover:text-primary">About Us</a></li>
                        <li><a href="#" class="text-sm text-gray-600 hover:text-primary">Contact Support</a></li>
                        <li><a href="#" class="text-sm text-gray-600 hover:text-primary">Help Center</a></li>
                        <li><a href="#" class="text-sm text-gray-600 hover:text-primary">Official Blog</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Legal</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-sm text-gray-600 hover:text-primary">Privacy Policy</a></li>
                        <li><a href="#" class="text-sm text-gray-600 hover:text-primary">Terms of Service</a></li>
                        <li><a href="#" class="text-sm text-gray-600 hover:text-primary">Data Security</a></li>
                        <li><a href="#" class="text-sm text-gray-600 hover:text-primary">Emergency Protocol</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-surface-container-low mt-12 pt-8 text-center">
                <p class="text-sm text-gray-500">&copy; <?php echo date('Y'); ?> Sentinel Cameroon. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Login Prompt Modal -->
    <div id="loginPromptModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="text-xl font-bold text-neutral-900">🔐 Login Required</h3>
                <button onclick="closeLoginPrompt()" class="modal-close">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-primary-600 text-2xl">lock</span>
                    </div>
                    <p class="text-lg font-medium text-neutral-900 mb-2">Please login to access <span id="featureName"
                            class="text-primary-600"></span></p>
                    <p class="text-neutral-600">Join our community safety platform to report incidents and stay
                        informed.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <a href="authority_register.php" class="btn btn-primary">
                        <span class="material-symbols-outlined">add_business</span>
                        Register as Agency
                    </a>
                    <a href="login.php" class="btn btn-primary">
                        <span class="material-symbols-outlined">login</span>
                        Login
                    </a>
                    <a href="register.php" class="btn btn-secondary">
                        <span class="material-symbols-outlined">person_add</span>
                        Register
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const nav = document.getElementById('mainNav');
            nav.classList.toggle('active');
        }

        function showLoginPrompt(feature) {
            document.getElementById('featureName').textContent = feature;
            document.getElementById('loginPromptModal').style.display = 'flex';
        }

        function closeLoginPrompt() {
            document.getElementById('loginPromptModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('loginPromptModal');
            if (event.target === modal) {
                closeLoginPrompt();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeLoginPrompt();
            }
        });
    </script>

    <style>
        .mobile-menu-toggle {
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            display: none;
            color: var(--on-surface);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .navbar-nav {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                padding: 20px;
                border-bottom: 1px solid var(--surface-container);
                gap: 15px;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            }

            .navbar-nav.active {
                display: flex;
            }

            .navbar-nav .btn {
                width: 100%;
                justify-content: center;
            }
        }

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
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
            padding: 4px;
            border-radius: 6px;
            transition: color 0.15s;
        }

        .modal-close:hover {
            color: #374151;
        }

        .modal-body {
            padding: 24px;
        }
    </style>
</body>

</html>