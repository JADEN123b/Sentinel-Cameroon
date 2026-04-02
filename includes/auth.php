<?php
/**
 * Sentinel Cameroon - Authentication Guard
 * Protects pages from unauthorized access
 */

// Require this file at the top of any protected page

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user info
function getCurrentUser() {
    if (isLoggedIn()) {
        try {
            $db = new Database();
            $stmt = $db->query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
            return $stmt ? $stmt->fetch() : null;
        } catch (Exception $e) {
            error_log("getCurrentUser failed: " . $e->getMessage());
            return null;
        }
    }
    return null;
}

// Get user role
function getUserRole() {
    $user = getCurrentUser();
    return $user ? $user['role'] : 'guest';
}

// Redirect to login if not authenticated
function requireAuth() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
}

// Redirect if user doesn't have required role
function requireRole($required_role) {
    requireAuth();
    
    $user_role = getUserRole();
    
    // Admin can access everything
    if ($user_role === 'admin') {
        return;
    }
    
    // Check specific role requirements
    if ($required_role === 'authority' && $user_role !== 'authority') {
        header('Location: dashboard.php?error=unauthorized');
        exit;
    }
}

// Check if user can access specific page
function canAccessPage($page) {
    if (!isLoggedIn()) {
        return in_array($page, ['login.php', 'register.php', 'authority_register.php', 'index.php']);
    }
    
    $user_role = getUserRole();
    
    // Pages accessible to all logged-in users
    $public_pages = [
        'dashboard.php',
        'incidents.php', 
        'alerts.php',
        'map-functional.php',
        'map.php',
        'partners.php',
        'profile.php',
        'report_incident.php',
        'report_incident_enhanced.php',
        'incident_detail.php',
        'logout.php'
    ];
    
    // Pages requiring authority role
    $authority_pages = [
        'admin.php'
    ];
    
    // Pages requiring admin role
    $admin_pages = [
        // Add admin-only pages here if needed
    ];
    
    if (in_array($page, $public_pages)) {
        return true;
    }
    
    if (in_array($page, $authority_pages) && $user_role === 'authority') {
        return true;
    }
    
    if (in_array($page, $admin_pages) && $user_role === 'admin') {
        return true;
    }
    
    return false;
}

// Automatically protect current page
function protectPage() {
    $current_page = basename($_SERVER['PHP_SELF']);
    
    if (!canAccessPage($current_page)) {
        if (!isLoggedIn()) {
            // Redirect to login with return URL
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: login.php');
            exit;
        } else {
            // User is logged in but doesn't have permission
            header('Location: dashboard.php?error=unauthorized');
            exit;
        }
    }
}

// Get redirect URL after login
function getRedirectUrl() {
    $redirect_url = $_SESSION['redirect_url'] ?? 'dashboard.php';
    unset($_SESSION['redirect_url']);
    return $redirect_url;
}

// Check if session is valid (prevent session hijacking)
function validateSession() {
    if (isLoggedIn()) {
        $user = getCurrentUser();
        if (!$user) {
            // User doesn't exist in database, clear session
            session_destroy();
            header('Location: login.php?error=session_expired');
            exit;
        }
        
        // Optional: Check if session IP matches current IP
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            session_destroy();
            header('Location: login.php?error=session_invalid');
            exit;
        }
    }
}

// Store session security info
function secureSession() {
    if (isLoggedIn()) {
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['last_activity'] = time();
    }
}

// Check for session timeout
function checkSessionTimeout($timeout_minutes = 30) {
    if (isLoggedIn()) {
        $last_activity = $_SESSION['last_activity'] ?? 0;
        $timeout = $timeout_minutes * 60;
        
        if (time() - $last_activity > $timeout) {
            session_destroy();
            header('Location: login.php?error=session_timeout');
            exit;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
    }
}

// Auto-protect page (call this at the top of protected pages)
function autoProtect() {
    validateSession();
    checkSessionTimeout();
    protectPage();
}

/**
 * Security Hardening Functions
 */

// Generate a cryptographically secure CSRF token
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify a CSRF token from a form submission
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Set standard security headers to protect against common attacks
function setSecurityHeaders() {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

// Helper to output a hidden CSRF input field
function csrfInput() {
    $token = generateCsrfToken();
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
}
?>
