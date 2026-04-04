<?php

class DirectLinkService {
    private $session;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->session = &$_SESSION;
    }
    
    /**
     * Store verification link in session
     */
    public function sendVerificationEmail($to_email, $to_name, $verification_token) {
        $verification_link = "http://localhost/verify_email.php?token=" . urlencode($verification_token);
        
        // Store in session for display
        $this->session['verification_link'] = $verification_link;
        $this->session['verification_email'] = $to_email;
        $this->session['verification_name'] = $to_name;
        
        error_log("Verification link stored for: $to_email");
        return true;
    }
    
    /**
     * Store password reset link in session
     */
    public function sendPasswordResetEmail($to_email, $to_name, $reset_token) {
        $reset_link = "http://localhost/reset_password.php?token=" . urlencode($reset_token);
        
        // Store in session for display
        $this->session['reset_link'] = $reset_link;
        $this->session['reset_email'] = $to_email;
        $this->session['reset_name'] = $to_name;
        
        error_log("Password reset link stored for: $to_email");
        return true;
    }
    
    /**
     * Send welcome email (store in session)
     */
    public function sendWelcomeEmail($to_email, $to_name) {
        $this->session['welcome_email'] = $to_email;
        $this->session['welcome_name'] = $to_name;
        
        error_log("Welcome message stored for: $to_email");
        return true;
    }
    
    /**
     * Get verification link from session
     */
    public function getVerificationLink() {
        return $this->session['verification_link'] ?? null;
    }
    
    /**
     * Get verification details from session
     */
    public function getVerificationDetails() {
        return [
            'link' => $this->session['verification_link'] ?? null,
            'email' => $this->session['verification_email'] ?? null,
            'name' => $this->session['verification_name'] ?? null
        ];
    }
    
    /**
     * Get reset link from session
     */
    public function getResetLink() {
        return $this->session['reset_link'] ?? null;
    }
    
    /**
     * Get reset details from session
     */
    public function getResetDetails() {
        return [
            'link' => $this->session['reset_link'] ?? null,
            'email' => $this->session['reset_email'] ?? null,
            'name' => $this->session['reset_name'] ?? null
        ];
    }
    
    /**
     * Get welcome details from session
     */
    public function getWelcomeDetails() {
        return [
            'email' => $this->session['welcome_email'] ?? null,
            'name' => $this->session['welcome_name'] ?? null
        ];
    }
    
    /**
     * Clear verification details from session
     */
    public function clearVerificationDetails() {
        unset($this->session['verification_link']);
        unset($this->session['verification_email']);
        unset($this->session['verification_name']);
    }
    
    /**
     * Clear reset details from session
     */
    public function clearResetDetails() {
        unset($this->session['reset_link']);
        unset($this->session['reset_email']);
        unset($this->session['reset_name']);
    }
    
    /**
     * Clear welcome details from session
     */
    public function clearWelcomeDetails() {
        unset($this->session['welcome_email']);
        unset($this->session['welcome_name']);
    }
    
    /**
     * Check if service is available
     */
    public function isAvailable() {
        return true; // Always available - uses session storage
    }
    
    /**
     * Test configuration
     */
    public function testConfiguration() {
        return true; // Always passes - uses session storage
    }
    
    /**
     * Generate verification HTML for display
     */
    public function generateVerificationHTML($name, $verification_link) {
        return "
        <div style='max-width: 600px; margin: 20px auto; padding: 20px; border: 2px solid #9c3400; border-radius: 10px; background: #fff9f9;'>
            <div style='background: #9c3400; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px;'>
                <h1 style='margin: 0;'>🛡️ Sentinel Cameroon</h1>
                <p style='margin: 5px 0 0 0;'>Community Safety Platform</p>
            </div>
            
            <h2>📧 Email Verification</h2>
            <p>Hi <strong>$name</strong>,</p>
            <p>Thank you for registering with Sentinel Cameroon. Your verification email has been generated.</p>
            
            <div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p><strong>📋 Action Required:</strong> Click the link below to verify your email address:</p>
                <p style='word-break: break-all;'><a href='$verification_link' style='color: #9c3400; font-weight: bold;'>$verification_link</a></p>
            </div>
            
            <p><strong>Important:</strong> This verification link will expire in 24 hours.</p>
            
            <p>If you didn't create an account with Sentinel Cameroon, please ignore this message.</p>
            
            <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px;'>
                <p>© 2024 Sentinel Cameroon - Community Safety for Cameroon</p>
                <p>Email system is using direct link display (mail server not configured)</p>
            </div>
        </div>";
    }
    
    /**
     * Generate password reset HTML for display
     */
    public function generatePasswordResetHTML($name, $reset_link) {
        return "
        <div style='max-width: 600px; margin: 20px auto; padding: 20px; border: 2px solid #dc3545; border-radius: 10px; background: #fff9f9;'>
            <div style='background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px;'>
                <h1 style='margin: 0;'>🛡️ Sentinel Cameroon</h1>
                <p style='margin: 5px 0 0 0;'>Community Safety Platform</p>
            </div>
            
            <h2>🔐 Password Reset</h2>
            <p>Hi <strong>$name</strong>,</p>
            <p>We received a request to reset your password for your Sentinel Cameroon account.</p>
            
            <div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p><strong>⚠️ Security Notice:</strong> If you didn't request this password reset, please ignore this message. Your account will remain secure.</p>
            </div>
            
            <div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p><strong>📋 Action Required:</strong> Click the link below to reset your password:</p>
                <p style='word-break: break-all;'><a href='$reset_link' style='color: #dc3545; font-weight: bold;'>$reset_link</a></p>
            </div>
            
            <p><strong>Important:</strong> This reset link will expire in 1 hour for security reasons.</p>
            
            <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px;'>
                <p>© 2024 Sentinel Cameroon - Community Safety for Cameroon</p>
                <p>Email system is using direct link display (mail server not configured)</p>
            </div>
        </div>";
    }
}
?>
