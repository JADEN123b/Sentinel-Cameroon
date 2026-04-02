<?php
session_start();
require_once 'database/config.php';
require_once 'includes/auth.php';

// Set global security headers
setSecurityHeaders();

// Don't auto-protect public pages
// Just load the functions without protection
?>
