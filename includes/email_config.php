<?php
// Email Configuration for Sentinel Cameroon
class EmailConfig {
    // Email settings (configure these based on your email service)
    const SMTP_HOST = 'smtp.gmail.com';  // Or your SMTP server
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'your-email@gmail.com';  // Your email
    const SMTP_PASSWORD = 'your-app-password';     // Your app password
    const SMTP_ENCRYPTION = 'tls';
    
    const FROM_EMAIL = 'noreply@sentinelcameroon.cm';
    const FROM_NAME = 'Sentinel Cameroon';
    const REPLY_TO = 'support@sentinelcameroon.cm';
    
    // Email templates
    public static function getVerificationTemplate($name, $verification_link) {
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
    
    public static function getPasswordResetTemplate($name, $reset_link) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #9c3400; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: #dc3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
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
    
    public static function getIncidentNotificationTemplate($recipient_name, $incident_title, $incident_location, $incident_severity) {
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
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                .button { display: inline-block; background: #9c3400; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
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
}
?>
