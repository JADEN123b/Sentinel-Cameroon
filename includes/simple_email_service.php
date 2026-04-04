<?php

class SimpleEmailService {
    private $from_email;
    private $from_name;
    
    public function __construct() {
        // Gmail SMTP Configuration
        $this->from_email = 'noreply@sentinelcameroon.cm';
        $this->from_name = 'Sentinel Cameroon';
    }
    
    /**
     * Send verification email using PHP's built-in mail() function
     */
    public function sendVerificationEmail($to_email, $to_name, $verification_token) {
        $verification_link = "http://localhost/verify_email.php?token=" . urlencode($verification_token);
        
        $subject = 'Verify Your Sentinel Cameroon Account';
        $message = $this->getVerificationTemplate($to_name, $verification_link);
        
        $headers = $this->getHeaders();
        
        return mail($to_email, $subject, $message, $headers);
    }
    
    /**
     * Send password reset email using PHP's built-in mail() function
     */
    public function sendPasswordResetEmail($to_email, $to_name, $reset_token) {
        $reset_link = "http://localhost/reset_password.php?token=" . urlencode($reset_token);
        
        $subject = 'Reset Your Sentinel Cameroon Password';
        $message = $this->getPasswordResetTemplate($to_name, $reset_link);
        
        $headers = $this->getHeaders();
        
        return mail($to_email, $subject, $message, $headers);
    }
    
    /**
     * Send welcome email using PHP's built-in mail() function
     */
    public function sendWelcomeEmail($to_email, $to_name) {
        $subject = 'Welcome to Sentinel Cameroon!';
        $message = $this->getWelcomeTemplate($to_name);
        
        $headers = $this->getHeaders();
        
        return mail($to_email, $subject, $message, $headers);
    }
    
    /**
     * Send incident notification email
     */
    public function sendIncidentNotification($to_email, $to_name, $incident_title, $incident_location, $incident_severity) {
        $subject = '🚨 New Incident Report - ' . $incident_severity . ' severity';
        $message = $this->getIncidentNotificationTemplate($to_name, $incident_title, $incident_location, $incident_severity);
        
        $headers = $this->getHeaders();
        
        return mail($to_email, $subject, $message, $headers);
    }
    
    /**
     * Send email using Gmail SMTP
     */
    private function sendEmail($to_email, $subject, $message) {
        // Gmail SMTP Configuration
        $smtp_host = 'smtp.gmail.com';
        $smtp_port = 587;
        $smtp_username = 'your-email@gmail.com'; // Replace with your Gmail
        $smtp_password = 'your-app-password'; // Replace with your App Password
        $from_email = $this->from_email;
        $from_name = $this->from_name;
        
        try {
            // Create SMTP connection
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ]);
            
            $socket = fsockopen("ssl://$smtp_host", $smtp_port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
            
            if (!$socket) {
                error_log("SMTP connection failed: $errstr");
                return false;
            }
            
            // Read SMTP greeting
            $greeting = fgets($socket, 1024);
            if (strpos($greeting, '220') === false) {
                fclose($socket);
                return false;
            }
            
            // Say hello
            fputs($socket, "EHLO localhost\r\n");
            fgets($socket, 1024);
            
            // Start TLS encryption
            fputs($socket, "STARTTLS\r\n");
            fgets($socket, 1024);
            
            // Authenticate
            fputs($socket, "AUTH LOGIN\r\n");
            fgets($socket, 1024);
            fputs($socket, base64_encode("$smtp_username\r\n$smtp_password") . "\r\n");
            fgets($socket, 1024);
            
            // Set sender
            fputs($socket, "MAIL FROM:<$from_email>\r\n");
            fgets($socket, 1024);
            
            // Set recipient
            fputs($socket, "RCPT TO:<$to_email>\r\n");
            fgets($socket, 1024);
            
            // Start data
            fputs($socket, "DATA\r\n");
            fgets($socket, 1024);
            
            // Send email headers
            $headers = "From: $from_name <$from_email>\r\n";
            $headers .= "Reply-To: $from_email\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            fputs($socket, "Subject: $subject\r\n");
            fputs($socket, $headers);
            fputs($socket, "\r\n");
            fputs($socket, $message);
            fputs($socket, "\r\n.\r\n");
            
            // Read response
            $response = fgets($socket, 1024);
            
            fclose($socket);
            
            if (strpos($response, '250') !== false) {
                error_log("Email sent successfully to: $to_email");
                return true;
            } else {
                error_log("Email sending failed: $response");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
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
                    
                    <p>Click the button below to verify your email:</p>
                    <center>
                        <a href='{$verification_link}' class='button'>Verify Email Address</a>
                    </center>
                    
                    <p>Or copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; background: #eee; padding: 10px; border-radius: 5px;'>{$verification_link}</p>
                    
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
                    
                    <p>Click the button below to reset your password:</p>
                    <center>
                        <a href='{$reset_link}' class='button'>Reset Password</a>
                    </center>
                    
                    <p>Or copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; background: #eee; padding: 10px; border-radius: 5px;'>{$reset_link}</p>
                    
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
    
    /**
     * Incident notification template
     */
    private function getIncidentNotificationTemplate($recipient_name, $incident_title, $incident_location, $incident_severity) {
        $severity_colors = [
            'low' => '#28a745',
            'medium' => '#ffc107', 
            'high' => '#fd7e14',
            'critical' => '#dc3545'
        ];
        
        $color = $severity_colors[$incident_severity] ?? '#6c757d';
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #9c3400; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .incident-info { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid {$color}; }
                .severity { background: {$color}; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
                .button { display: inline-block; background: #9c3400; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚨 New Incident Report</h1>
                    <p>Sentinel Cameroon Alert</p>
                </div>
                <div class='content'>
                    <h2>New Incident Reported</h2>
                    <p>Hi {$recipient_name},</p>
                    <p>A new incident has been reported in your area that requires your attention:</p>
                    
                    <div class='incident-info'>
                        <h3>{$incident_title}</h3>
                        <p><strong>Location:</strong> {$incident_location}</p>
                        <p><strong>Severity:</strong> <span class='severity'>" . ucfirst($incident_severity) . "</span></p>
                    </div>
                    
                    <p>Please log in to your dashboard to review and take appropriate action.</p>
                    <center>
                        <a href='http://localhost/dashboard.php' class='button'>View Dashboard</a>
                    </center>
                    
                    <div class='footer'>
                        <p>© 2024 Sentinel Cameroon - Community Safety for Cameroon</p>
                        <p>This is an automated alert, please do not reply.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Test if email service is available (always true for simple mail)
     */
    public function isAvailable() {
        return true;
    }
    
    /**
     * Test email configuration
     */
    public function testConfiguration() {
        // Test if mail function is available
        if (function_exists('mail')) {
            return true;
        }
        return false;
    }
}
?>
