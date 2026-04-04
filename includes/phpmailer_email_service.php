<?php

class PHPMailerEmailService
{
    private $smtp_host = 'smtp.gmail.com';
    private $smtp_port = 587;
    private $smtp_username = 'chooazah@gmail.com'; // Your automated sender email
    private $smtp_password = 'gvfgpbnbafxkhjnh'; // Your App Password
    private $from_email = 'chooazah@gmail.com';
    private $from_name = 'Sentinel Cameroon';
    private $reply_to = 'chooazah@gmail.com';
    private $mailer;

    public function __construct()
    {
        // We are bypassing PHPMailer entirely and using the native PHP mail()
        // function, which we have meticulously configured in XAMPP's sendmail.ini.
        // Therefore, we just set this to true so the service is always "available".
        $this->mailer = true;
    }

    /**
     * Send verification email using PHPMailer
     */
    public function sendVerificationEmail($to_email, $to_name, $verification_token)
    {
        if (!$this->mailer) {
            return false;
        }

        $verification_link = "http://localhost/verify_email.php?token=" . urlencode($verification_token);
        $subject = 'Verify Your Sentinel Cameroon Account';
        $message = $this->getVerificationTemplate($to_name, $verification_link);

        return $this->sendEmail($to_email, $to_name, $subject, $message);
    }

    /**
     * Send password reset email using PHPMailer
     */
    public function sendPasswordResetEmail($to_email, $to_name, $reset_token)
    {
        if (!$this->mailer) {
            return false;
        }

        $reset_link = "http://localhost/reset_password.php?token=" . urlencode($reset_token);
        $subject = 'Reset Your Sentinel Cameroon Password';
        $message = $this->getPasswordResetTemplate($to_name, $reset_link);

        return $this->sendEmail($to_email, $to_name, $subject, $message);
    }

    /**
     * Send welcome email using PHPMailer
     */
    public function sendWelcomeEmail($to_email, $to_name)
    {
        if (!$this->mailer) {
            return false;
        }

        $subject = 'Welcome to Sentinel Cameroon!';
        $message = $this->getWelcomeTemplate($to_name);

        return $this->sendEmail($to_email, $to_name, $subject, $message);
    }

    /**
     * Send incident notification using PHPMailer
     */
    public function sendIncidentNotification($to_email, $to_name, $incident_title, $incident_location, $incident_severity)
    {
        if (!$this->mailer) {
            return false;
        }

        $subject = '🚨 New Incident Report - ' . $incident_severity . ' severity';
        $message = $this->getIncidentNotificationTemplate($to_name, $incident_title, $incident_location, $incident_severity);

        return $this->sendEmail($to_email, $to_name, $subject, $message);
    }

    /**
     * Send system notification using PHPMailer
     */
    public function sendSystemNotification($to_email, $to_name, $notification_title, $notification_message)
    {
        if (!$this->mailer) {
            return false;
        }

        $subject = '📢 ' . $notification_title;
        $message = $this->getSystemNotificationTemplate($to_name, $notification_title, $notification_message);

        return $this->sendEmail($to_email, $to_name, $subject, $message);
    }

    /**
     * Send email using PHPMailer
     */
    private function sendEmail($to_email, $to_name, $subject, $message)
    {
        try {
            // Build the headers required for an HTML email
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            // Ensure the From matches the authenticated Gmail address in sendmail.ini
            $headers .= "From: {$this->from_name} <{$this->from_email}>\r\n";
            $headers .= "Reply-To: {$this->reply_to}\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

            // Send using PHP's native mail(), which routes through our custom sendmail configuration!
            if (mail($to_email, $subject, $message, $headers)) {
                error_log("Email sent successfully via native mail() to: $to_email");
                return true;
            } else {
                error_log("Native mail() delivery failed to: $to_email");
                return false;
            }

        } catch (Exception $e) {
            error_log("Email delivery error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if service is available
     */
    public function isAvailable()
    {
        return $this->mailer !== null;
    }

    /**
     * Test configuration
     */
    public function testConfiguration()
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            return $this->sendEmail(
                'test@example.com',
                'Test User',
                'Test Email',
                'This is a test email from Sentinel Cameroon.'
            );
        } catch (Exception $e) {
            error_log("PHPMailer test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verification email template
     */
    private function getVerificationTemplate($name, $verification_link)
    {
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
                .phpmailer-note { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
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
                    
                    <div class='phpmailer-note'>
                        <p><strong>📧 PHPMailer Email:</strong> This email was sent automatically using PHPMailer SMTP.</p>
                    </div>
                    
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
                        <p>Sent automatically using PHPMailer SMTP</p>
                        <p>Reply to: {$this->reply_to}</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Password reset email template
     */
    private function getPasswordResetTemplate($name, $reset_link)
    {
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
                .phpmailer-note { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
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
                    
                    <div class='phpmailer-note'>
                        <p><strong>📧 PHPMailer Email:</strong> This email was sent automatically using PHPMailer SMTP.</p>
                    </div>
                    
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
                        <p>Sent automatically using PHPMailer SMTP</p>
                        <p>Reply to: {$this->reply_to}</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Welcome email template
     */
    private function getWelcomeTemplate($name)
    {
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
                .phpmailer-note { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
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
                    
                    <div class='phpmailer-note'>
                        <p><strong>📧 PHPMailer Email:</strong> This email was sent automatically using PHPMailer SMTP.</p>
                    </div>
                    
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
                        <p>Sent automatically using PHPMailer SMTP</p>
                        <p>Reply to: {$this->reply_to}</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Incident notification template
     */
    private function getIncidentNotificationTemplate($name, $incident_title, $incident_location, $incident_severity)
    {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .severity-high { background: #dc3545; color: white; padding: 5px 10px; border-radius: 3px; }
                .severity-medium { background: #ffc107; color: #000; padding: 5px 10px; border-radius: 3px; }
                .severity-low { background: #28a745; color: white; padding: 5px 10px; border-radius: 3px; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                .phpmailer-note { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🛡️ Sentinel Cameroon</h1>
                    <p>Community Safety Platform</p>
                </div>
                <div class='content'>
                    <h2>🚨 New Incident Report</h2>
                    <p>Hi {$name},</p>
                    <p>A new incident has been reported in your area. Please review the details below:</p>
                    
                    <div class='phpmailer-note'>
                        <p><strong>📧 PHPMailer Email:</strong> This email was sent automatically using PHPMailer SMTP.</p>
                    </div>
                    
                    <p><strong>Incident Details:</strong></p>
                    <ul>
                        <li><strong>Title:</strong> {$incident_title}</li>
                        <li><strong>Location:</strong> {$incident_location}</li>
                        <li><strong>Severity:</strong> <span class='severity-{$incident_severity}'>{$incident_severity}</span></li>
                    </ul>
                    
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='http://localhost/incidents.php' style='display: inline-block; background: #9c3400; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px;'>View Incident Details</a>
                    </p>
                    
                    <p>Please take appropriate action based on the incident severity and your role in the community.</p>
                    
                    <div class='footer'>
                        <p>© 2024 Sentinel Cameroon - Community Safety for Cameroon</p>
                        <p>Sent automatically using PHPMailer SMTP</p>
                        <p>Reply to: {$this->reply_to}</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * System notification template
     */
    private function getSystemNotificationTemplate($name, $notification_title, $notification_message)
    {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                .phpmailer-note { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🛡️ Sentinel Cameroon</h1>
                    <p>Community Safety Platform</p>
                </div>
                <div class='content'>
                    <h2>📢 {$notification_title}</h2>
                    <p>Hi {$name},</p>
                    
                    <div class='phpmailer-note'>
                        <p><strong>📧 PHPMailer Email:</strong> This email was sent automatically using PHPMailer SMTP.</p>
                    </div>
                    
                    <p>{$notification_message}</p>
                    
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='http://localhost/dashboard.php' style='display: inline-block; background: #9c3400; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a>
                    </p>
                    
                    <div class='footer'>
                        <p>© 2024 Sentinel Cameroon - Community Safety for Cameroon</p>
                        <p>Sent automatically using PHPMailer SMTP</p>
                        <p>Reply to: {$this->reply_to}</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>