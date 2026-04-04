<?php

class SendGridEmailService {
    private $api_key;
    private $from_email;
    private $from_name;
    private $available = false;
    
    public function __construct() {
        // Configure these with your SendGrid settings
        $this->api_key = 'YOUR_SENDGRID_API_KEY'; // Replace with your API key
        $this->from_email = 'noreply@sentinelcameroon.cm';
        $this->from_name = 'Sentinel Cameroon';
        
        // Check if cURL is available (required for HTTP requests)
        if (function_exists('curl_init')) {
            $this->available = true;
        } else {
            error_log("SendGrid service requires cURL extension");
        }
        
        // Check if API key is configured
        if ($this->api_key === 'YOUR_SENDGRID_API_KEY') {
            error_log("SendGrid API key not configured. Please update includes/sendgrid_email_service.php");
            $this->available = false;
        } else {
            $this->available = true;
        }
    }
    
    /**
     * Send verification email using SendGrid API
     */
    public function sendVerificationEmail($to_email, $to_name, $verification_token) {
        if (!$this->available) {
            return false;
        }
        
        $verification_link = "http://localhost/verify_email.php?token=" . urlencode($verification_token);
        
        $subject = 'Verify Your Sentinel Cameroon Account';
        $html_content = $this->getVerificationTemplate($to_name, $verification_link);
        
        return $this->sendEmail($to_email, $to_name, $subject, $html_content, 'verification');
    }
    
    /**
     * Send password reset email using SendGrid API
     */
    public function sendPasswordResetEmail($to_email, $to_name, $reset_token) {
        if (!$this->available) {
            return false;
        }
        
        $reset_link = "http://localhost/reset_password.php?token=" . urlencode($reset_token);
        
        $subject = 'Reset Your Sentinel Cameroon Password';
        $html_content = $this->getPasswordResetTemplate($to_name, $reset_link);
        
        return $this->sendEmail($to_email, $to_name, $subject, $html_content, 'password_reset');
    }
    
    /**
     * Send welcome email using SendGrid API
     */
    public function sendWelcomeEmail($to_email, $to_name) {
        if (!$this->available) {
            return false;
        }
        
        $subject = 'Welcome to Sentinel Cameroon!';
        $html_content = $this->getWelcomeTemplate($to_name);
        
        return $this->sendEmail($to_email, $to_name, $subject, $html_content, 'welcome');
    }
    
    /**
     * Send email using SendGrid API
     */
    private function sendEmail($to_email, $to_name, $subject, $html_content, $email_type) {
        $url = 'https://api.sendgrid.com/v3/mail/send';
        
        $data = [
            'personalizations' => [
                [
                    'to' => [
                        [
                            'email' => $to_email,
                            'name' => $to_name
                        ]
                    ],
                    'subject' => $subject
                ]
            ],
            'from' => [
                'email' => $this->from_email,
                'name' => $this->from_name
            ],
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $html_content
                ]
            ],
            'custom_args' => [
                'email_type' => $email_type
            ]
        ];
        
        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("SendGrid API Error: " . $error);
            return false;
        }
        
        if ($http_code === 202) {
            error_log("SendGrid email sent successfully to: $to_email");
            return true;
        } else {
            error_log("SendGrid API Error - HTTP Code: $http_code, Response: $response");
            return false;
        }
    }
    
    /**
     * Check if service is available
     */
    public function isAvailable() {
        return $this->available && !empty($this->api_key) && $this->api_key !== 'YOUR_SENDGRID_API_KEY';
    }
    
    /**
     * Test configuration
     */
    public function testConfiguration() {
        if (!$this->isAvailable()) {
            return false;
        }
        
        // Test API connectivity
        $url = 'https://api.sendgrid.com/v3/user/account';
        $headers = ['Authorization: Bearer ' . $this->api_key];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code === 200;
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
}
?>
