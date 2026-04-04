<?php

class GmailEmailService {
    private $smtp_host = 'smtp.gmail.com';
    private $smtp_port = 587;
    private $smtp_username = 'your-email@gmail.com'; // Replace with your Gmail
    private $smtp_password = 'your-app-password'; // Replace with your App Password
    private $from_email = 'noreply@sentinelcameroon.cm';
    private $from_name = 'Sentinel Cameroon';
    
    public function __construct() {
        // Check if required functions are available
        if (!function_exists('fsockopen') || !function_exists('base64_encode')) {
            error_log("GmailEmailService requires fsockopen and base64_encode functions");
            return;
        }
    }
    
    /**
     * Send verification email using Gmail SMTP
     */
    public function sendVerificationEmail($to_email, $to_name, $verification_token) {
        $verification_link = "http://localhost/verify_email.php?token=" . urlencode($verification_token);
        $subject = 'Verify Your Sentinel Cameroon Account';
        $message = $this->getVerificationTemplate($to_name, $verification_link);
        
        return $this->sendEmail($to_email, $subject, $message);
    }
    
    /**
     * Send password reset email using Gmail SMTP
     */
    public function sendPasswordResetEmail($to_email, $to_name, $reset_token) {
        $reset_link = "http://localhost/reset_password.php?token=" . urlencode($reset_token);
        $subject = 'Reset Your Sentinel Cameroon Password';
        $message = $this->getPasswordResetTemplate($to_name, $reset_link);
        
        return $this->sendEmail($to_email, $subject, $message);
    }
    
    /**
     * Send welcome email using Gmail SMTP
     */
    public function sendWelcomeEmail($to_email, $to_name) {
        $subject = 'Welcome to Sentinel Cameroon!';
        $message = $this->getWelcomeTemplate($to_name);
        
        return $this->sendEmail($to_email, $subject, $message);
    }
    
    /**
     * Send email using Gmail SMTP
     */
    private function sendEmail($to_email, $subject, $message) {
        try {
            // Create SMTP connection
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ]);
            
            $socket = fsockopen("ssl://{$this->smtp_host}", $this->smtp_port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
            
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
            fputs($socket, base64_encode("{$this->smtp_username}\r\n{$this->smtp_password}") . "\r\n");
            fgets($socket, 1024);
            
            // Set sender
            fputs($socket, "MAIL FROM:<{$this->from_email}>\r\n");
            fgets($socket, 1024);
            
            // Set recipient
            fputs($socket, "RCPT TO:<$to_email>\r\n");
            fgets($socket, 1024);
            
            // Start data
            fputs($socket, "DATA\r\n");
            fgets($socket, 1024);
            
            // Send email headers
            $headers = "From: {$this->from_name} <{$this->from_email}>\r\n";
            $headers .= "Reply-To: {$this->from_email}\r\n";
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
     * Check if service is available
     */
    public function isAvailable() {
        return function_exists('fsockopen') && function_exists('base64_encode');
    }
    
    /**
     * Test configuration
     */
    public function testConfiguration() {
        return $this->isAvailable();
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
                        <p><strong>⚠️ Security Notice:</strong> If you didn't request this password reset, please ignore this email. Your account will remain secure.</p>
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
}
?>
