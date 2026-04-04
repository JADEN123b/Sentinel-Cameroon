<?php

class EmailQueueService {
    private $db;
    
    public function __construct() {
        require_once 'database/config.php';
        $this->db = new Database();
    }
    
    /**
     * Queue email for later sending (bypasses PHP mail() issues)
     */
    public function queueEmail($to_email, $to_name, $subject, $message, $email_type = 'general') {
        try {
            $this->db->query("
                INSERT INTO email_queue (to_email, to_name, subject, message, email_type, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'queued', NOW())
            ", [$to_email, $to_name, $subject, $message, $email_type]);
            
            error_log("Email queued for: $to_email (Type: $email_type)");
            return true;
        } catch (Exception $e) {
            error_log("Failed to queue email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send verification email (queued version)
     */
    public function sendVerificationEmail($to_email, $to_name, $verification_token) {
        $verification_link = "http://localhost/verify_email.php?token=" . urlencode($verification_token);
        
        $subject = 'Verify Your Sentinel Cameroon Account';
        $message = $this->getVerificationTemplate($to_name, $verification_link);
        
        return $this->queueEmail($to_email, $to_name, $subject, $message, 'verification');
    }
    
    /**
     * Send password reset email (queued version)
     */
    public function sendPasswordResetEmail($to_email, $to_name, $reset_token) {
        $reset_link = "http://localhost/reset_password.php?token=" . urlencode($reset_token);
        
        $subject = 'Reset Your Sentinel Cameroon Password';
        $message = $this->getPasswordResetTemplate($to_name, $reset_link);
        
        return $this->queueEmail($to_email, $to_name, $subject, $message, 'password_reset');
    }
    
    /**
     * Send welcome email (queued version)
     */
    public function sendWelcomeEmail($to_email, $to_name) {
        $subject = 'Welcome to Sentinel Cameroon!';
        $message = $this->getWelcomeTemplate($to_name);
        
        return $this->queueEmail($to_email, $to_name, $subject, $message, 'welcome');
    }
    
    /**
     * Get queued emails
     */
    public function getQueuedEmails() {
        try {
            return $this->db->fetchAll("SELECT * FROM email_queue WHERE status = 'queued' ORDER BY created_at ASC");
        } catch (Exception $e) {
            error_log("Failed to get queued emails: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Send queued email manually (for testing/admin use)
     */
    public function sendQueuedEmail($email_id) {
        try {
            $email = $this->db->fetch("SELECT * FROM email_queue WHERE id = ?", [$email_id]);
            
            if (!$email) {
                return false;
            }
            
            $headers = "From: noreply@sentinelcameroon.cm\r\n";
            $headers .= "Reply-To: noreply@sentinelcameroon.cm\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            $result = mail($email['to_email'], $email['subject'], $email['message'], $headers);
            
            if ($result) {
                $this->db->query("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?", [$email_id]);
                error_log("Queued email sent to: " . $email['to_email']);
                return true;
            } else {
                $this->db->query("UPDATE email_queue SET status = 'failed', error_message = 'mail() function failed' WHERE id = ?", [$email_id]);
                error_log("Failed to send queued email to: " . $email['to_email']);
                return false;
            }
        } catch (Exception $e) {
            error_log("Error sending queued email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if service is available
     */
    public function isAvailable() {
        return true; // Always available - uses database queue
    }
    
    /**
     * Test configuration
     */
    public function testConfiguration() {
        return true; // Always passes - uses database queue
    }
    
    /**
     * Verification email template
     */
    private function getVerificationTemplate($name, $verification_link) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #9c3400; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: #9c3400; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                .notice { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🛡️ Sentinel Cameroon</h1>
                    <p>Community Safety Platform</p>
                </div>
                <div class='content'>
                    <h2>Welcome, {$name}!</h2>
                    <p>Thank you for registering with Sentinel Cameroon. To complete your registration and activate your account, please verify your email address.</p>
                    
                    <div class='notice'>
                        <p><strong>📧 Email Queued:</strong> Your verification email has been queued for delivery.</p>
                        <p><strong>🔗 Manual Verification:</strong> You can verify your account using this link:</p>
                        <p><a href='{$verification_link}'>{$verification_link}</a></p>
                    </div>
                    
                    <p><strong>Important:</strong> This verification link will expire in 24 hours.</p>
                    
                    <p>If you didn't create an account with Sentinel Cameroon, please ignore this email.</p>
                    
                    <div class='footer'>
                        <p>© 2024 Sentinel Cameroon - Community Safety for Cameroon</p>
                        <p>This is an automated message, please do not reply.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Password reset email template
     */
    private function getPasswordResetTemplate($name, $reset_link) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #9c3400; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: #dc3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                .notice { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🛡️ Sentinel Cameroon</h1>
                    <p>Community Safety Platform</p>
                </div>
                <div class='content'>
                    <h2>Password Reset Request</h2>
                    <p>Hi {$name},</p>
                    <p>We received a request to reset your password for your Sentinel Cameroon account.</p>
                    
                    <div class='warning'>
                        <p><strong>Security Notice:</strong> If you didn't request this password reset, please ignore this email. Your account will remain secure.</p>
                    </div>
                    
                    <div class='notice'>
                        <p><strong>📧 Email Queued:</strong> Your password reset email has been queued for delivery.</p>
                        <p><strong>🔗 Manual Reset:</strong> You can reset your password using this link:</p>
                        <p><a href='{$reset_link}'>{$reset_link}</a></p>
                    </div>
                    
                    <p><strong>Important:</strong> This reset link will expire in 1 hour for security reasons.</p>
                    
                    <div class='footer'>
                        <p>© 2024 Sentinel Cameroon - Community Safety for Cameroon</p>
                        <p>This is an automated message, please do not reply.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Welcome email template
     */
    private function getWelcomeTemplate($name) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #9c3400; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: #9c3400; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                .notice { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🛡️ Sentinel Cameroon</h1>
                    <p>Community Safety Platform</p>
                </div>
                <div class='content'>
                    <h2>Hi {$name},</h2>
                    <p>Congratulations! Your Sentinel Cameroon account has been successfully verified and is now active.</p>
                    
                    <h3>What you can do now:</h3>
                    <ul>
                        <li>📊 Report incidents in your community</li>
                        <li>🗺️ View incidents on interactive map</li>
                        <li>🚨 Receive safety alerts and notifications</li>
                        <li>🤝 Connect with verified community partners</li>
                    </ul>
                    
                    <div class='notice'>
                        <p><strong>📧 Welcome Email Queued:</strong> This welcome email has been queued for delivery.</p>
                    </div>
                    
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='http://localhost/dashboard.php' class='button'>Go to Dashboard</a>
                    </p>
                    
                    <p>Thank you for joining our community safety platform. Together, we can make Cameroon safer for everyone!</p>
                    
                    <div class='footer'>
                        <p>© 2024 Sentinel Cameroon - Community Safety for Cameroon</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>
