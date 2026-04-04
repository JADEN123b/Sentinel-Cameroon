<?php
require_once 'email_config.php';

class EmailService {
    private $mailer;
    private $available = false;
    
    public function __construct() {
        // Check if PHPMailer is available
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            error_log("PHPMailer not found. Install with: composer require phpmailer/phpmailer");
            $this->available = false;
            return;
        }
        
        try {
            // Initialize PHPMailer
            $this->mailer = new PHPMailer\PHPMailer\PHPMailer();
            
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = EmailConfig::SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = EmailConfig::SMTP_USERNAME;
            $this->mailer->Password = EmailConfig::SMTP_PASSWORD;
            $this->mailer->SMTPSecure = EmailConfig::SMTP_ENCRYPTION;
            $this->mailer->Port = EmailConfig::SMTP_PORT;
            
            // Recipients
            $this->mailer->setFrom(EmailConfig::FROM_EMAIL, EmailConfig::FROM_NAME);
            $this->mailer->addReplyTo(EmailConfig::REPLY_TO, 'Sentinel Cameroon Support');
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
            $this->available = true;
            
        } catch (Exception $e) {
            error_log("Email service initialization failed: " . $e->getMessage());
            $this->available = false;
        }
    }
    
    /**
     * Send verification email
     */
    public function sendVerificationEmail($to_email, $to_name, $verification_token) {
        if (!$this->available) {
            error_log("Email service not available - verification email not sent to: $to_email");
            return false;
        }
        
        try {
            $verification_link = "http://localhost/verify_email.php?token=" . urlencode($verification_token);
            
            $this->mailer->addAddress($to_email, $to_name);
            $this->mailer->Subject = 'Verify Your Sentinel Cameroon Account';
            $this->mailer->Body = EmailConfig::getVerificationTemplate($to_name, $verification_link);
            
            $result = $this->mailer->send();
            $this->clearAddresses();
            
            error_log("Verification email sent to: $to_email");
            return $result;
            
        } catch (Exception $e) {
            error_log("Failed to send verification email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($to_email, $to_name, $reset_token) {
        if (!$this->available) {
            error_log("Email service not available - password reset email not sent to: $to_email");
            return false;
        }
        
        try {
            $reset_link = "http://localhost/reset_password.php?token=" . urlencode($reset_token);
            
            $this->mailer->addAddress($to_email, $to_name);
            $this->mailer->Subject = 'Reset Your Sentinel Cameroon Password';
            $this->mailer->Body = EmailConfig::getPasswordResetTemplate($to_name, $reset_link);
            
            $result = $this->mailer->send();
            $this->clearAddresses();
            
            error_log("Password reset email sent to: $to_email");
            return $result;
            
        } catch (Exception $e) {
            error_log("Failed to send password reset email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send incident notification email
     */
    public function sendIncidentNotification($to_email, $to_name, $incident_title, $incident_location, $incident_severity) {
        if (!$this->available) {
            error_log("Email service not available - incident notification not sent to: $to_email");
            return false;
        }
        
        try {
            $this->mailer->addAddress($to_email, $to_name);
            $this->mailer->Subject = '🚨 New Incident Report - ' . $incident_severity . ' severity';
            $this->mailer->Body = EmailConfig::getIncidentNotificationTemplate($to_name, $incident_title, $incident_location, $incident_severity);
            
            $result = $this->mailer->send();
            $this->clearAddresses();
            
            error_log("Incident notification sent to: $to_email");
            return $result;
            
        } catch (Exception $e) {
            error_log("Failed to send incident notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send welcome email after verification
     */
    public function sendWelcomeEmail($to_email, $to_name) {
        if (!$this->available) {
            error_log("Email service not available - welcome email not sent to: $to_email");
            return false;
        }
        
        try {
            $this->mailer->addAddress($to_email, $to_name);
            $this->mailer->Subject = 'Welcome to Sentinel Cameroon!';
            $this->mailer->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <div style='background: #9c3400; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                        <h1>🛡️ Welcome to Sentinel Cameroon!</h1>
                        <p>Your account is now active</p>
                    </div>
                    <div style='background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px;'>
                        <h2>Hi {$to_name},</h2>
                        <p>Congratulations! Your Sentinel Cameroon account has been successfully verified and is now active.</p>
                        
                        <h3>What you can do now:</h3>
                        <ul>
                            <li>📊 Report incidents in your community</li>
                            <li>🗺️ View incidents on the interactive map</li>
                            <li>🚨 Receive safety alerts and notifications</li>
                            <li>🤝 Connect with verified community partners</li>
                        </ul>
                        
                        <p style='text-align: center; margin: 30px 0;'>
                            <a href='http://localhost/dashboard.php' style='display: inline-block; background: #9c3400; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a>
                        </p>
                        
                        <p>Thank you for joining our community safety platform. Together, we can make Cameroon safer for everyone!</p>
                        
                        <div style='text-align: center; color: #666; font-size: 12px; margin-top: 30px;'>
                            <p>© 2024 Sentinel Cameroon - Community Safety for Cameroon</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>";
            
            $result = $this->mailer->send();
            $this->clearAddresses();
            
            error_log("Welcome email sent to: $to_email");
            return $result;
            
        } catch (Exception $e) {
            error_log("Failed to send welcome email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear all recipient addresses
     */
    private function clearAddresses() {
        $this->mailer->clearAddresses();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();
        $this->mailer->clearReplyTos();
    }
    
    /**
     * Test email configuration
     */
    public function testConfiguration() {
        if (!$this->available) {
            return false;
        }
        
        try {
            $this->mailer->SMTPDebug = 2; // Enable verbose debug output
            $this->mailer->Debugoutput = 'error_log';
            
            // Try to connect to SMTP
            if ($this->mailer->getSMTPInstance()->connect()) {
                $this->mailer->getSMTPInstance()->disconnect();
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Email configuration test failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if email service is available
     */
    public function isAvailable() {
        return $this->available;
    }
}
?>
