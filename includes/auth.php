<?php
/**
 * Sentinel Cameroon - Authentication & Security Guard
 * This file handles session management, page protection, and CSRF security.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database/config.php';

/**
 * Log any system action for Super Admin oversight
 */
if (!function_exists('logSystemActivity')) {
    function logSystemActivity($actionText, $type = 'system') {
        $db = new Database();
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $db->query("INSERT INTO activity_logs (user_id, action_text, action_type) VALUES (?, ?, ?)", [$userId, $actionText, $type]);
    }
}

/**
 * Check if the current user is authenticated
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

/**
 * Get detailed information about the currently logged-in user
 */
if (!function_exists('getCurrentUser')) {
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
}

/**
 * Get the current user's ID
 */
if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId() {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
}

/**
 * Get the current user's role (user, authority, admin)
 */
if (!function_exists('getUserRole')) {
    function getUserRole() {
        if (isLoggedIn()) {
            if (isset($_SESSION['role'])) {
                return $_SESSION['role'];
            }
            $user = getCurrentUser();
            return $user ? $user['role'] : 'guest';
        }
        return 'guest';
    }
}

/**
 * CSRF Protection Functions
 */

if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('csrfInput')) {
    function csrfInput() {
        $token = generateCsrfToken();
        echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

/**
 * Page Protection Logic
 */

if (!function_exists('canAccessPage')) {
    function canAccessPage($page) {
        if (!isLoggedIn()) {
            return in_array($page, ['login.php', 'register.php', 'authority_register.php', 'index.php', 'forgot_password.php', 'reset_password.php', 'verify_email.php']);
        }
        
        $role = getUserRole();
        $public_protected = ['dashboard.php', 'profile.php', 'incidents.php', 'map.php', 'alerts.php', 'partners.php', 'logout.php', 'report_incident.php'];
        
        if ($role === 'admin') return true;
        if ($role === 'authority') return in_array($page, array_merge($public_protected, ['admin.php']));
        return in_array($page, $public_protected);
    }
}

if (!function_exists('protectPage')) {
    function protectPage() {
        $current = basename($_SERVER['PHP_SELF']);
        if (!canAccessPage($current)) {
            if (!isLoggedIn()) {
                $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
                header('Location: login.php');
                exit;
            } else {
                header('Location: dashboard.php?error=unauthorized');
                exit;
            }
        }
    }
}

if (!function_exists('autoProtect')) {
    function autoProtect() {
        protectPage();
    }
}

/**
 * Header Security
 */
if (!function_exists('setSecurityHeaders')) {
    function setSecurityHeaders() {
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
    }
}
