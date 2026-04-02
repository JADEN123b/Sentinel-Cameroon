<?php
/**
 * Sentinel Cameroon - Mailer Utility
 * Handles sending emails (using PHP's native mail() function)
 */

// Your site's base URL — adjust this if needed
define('SITE_URL', 'http://localhost/stitch/stitch');
define('SITE_NAME', 'Sentinel Cameroon');
define('MAIL_FROM', 'chooazah@gmail.com');
define('MAIL_FROM_NAME', 'Sentinel Cameroon');

/**
 * Send an email verification link to a newly registered user.
 *
 * @param string $to_email  Recipient email address
 * @param string $to_name   Recipient full name
 * @param string $token     The verification token stored in the DB
 * @return bool             True on success, false on failure
 */
function sendVerificationEmail($to_email, $to_name, $token) {
    $verify_url = SITE_URL . '/verify.php?token=' . urlencode($token);

    $subject = 'Verify your ' . SITE_NAME . ' account';

    $body = '
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Inter, Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
    .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .header { background: linear-gradient(135deg, #1a73e8, #0d47a1); padding: 36px 40px; text-align: center; }
    .header h1 { color: #ffffff; margin: 0; font-size: 26px; letter-spacing: -0.5px; }
    .header p { color: rgba(255,255,255,0.8); margin: 6px 0 0; font-size: 14px; }
    .body { padding: 40px; }
    .body p { color: #444; font-size: 15px; line-height: 1.7; margin: 0 0 16px; }
    .btn { display: inline-block; margin: 24px 0; padding: 14px 36px; background: #1a73e8; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 600; letter-spacing: 0.3px; }
    .link-fallback { font-size: 13px; color: #888; word-break: break-all; }
    .footer { background: #f9f9f9; padding: 20px 40px; text-align: center; font-size: 12px; color: #aaa; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>' . SITE_NAME . '</h1>
      <p>Community Safety Platform</p>
    </div>
    <div class="body">
      <p>Hi <strong>' . htmlspecialchars($to_name) . '</strong>,</p>
      <p>Thanks for registering with ' . SITE_NAME . '! Please verify your email address to activate your account and start using the platform.</p>
      <p style="text-align:center;">
        <a href="' . $verify_url . '" class="btn">Verify My Email</a>
      </p>
      <p>This link will expire in <strong>24 hours</strong>. If you did not register, you can safely ignore this email.</p>
      <p class="link-fallback">If the button does not work, copy and paste this link into your browser:<br>' . $verify_url . '</p>
    </div>
    <div class="footer">
      &copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.
    </div>
  </div>
</body>
</html>';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $result = mail($to_email, $subject, $body, $headers);

    if (!$result) {
        error_log("[Sentinel Mailer] Failed to send verification email to: {$to_email}. Verify URL: {$verify_url}");
    }

    return $result;
}

/**
 * Send a password reset link to the user.
 *
 * @param string $to_email  Recipient email address
 * @param string $to_name   Recipient full name
 * @param string $token     The reset token stored in the DB
 * @return bool             True on success, false on failure
 */
function sendPasswordResetEmail($to_email, $to_name, $token) {
    $reset_url = SITE_URL . '/reset_password.php?token=' . urlencode($token);

    $subject = 'Reset your ' . SITE_NAME . ' password';

    $body = '
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Inter, Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
    .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .header { background: linear-gradient(135deg, #dc2626, #991b1b); padding: 36px 40px; text-align: center; }
    .header h1 { color: #ffffff; margin: 0; font-size: 26px; letter-spacing: -0.5px; }
    .header p { color: rgba(255,255,255,0.8); margin: 6px 0 0; font-size: 14px; }
    .body { padding: 40px; }
    .body p { color: #444; font-size: 15px; line-height: 1.7; margin: 0 0 16px; }
    .btn { display: inline-block; margin: 24px 0; padding: 14px 36px; background: #dc2626; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 600; letter-spacing: 0.3px; }
    .link-fallback { font-size: 13px; color: #888; word-break: break-all; }
    .warning { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #9a3412; margin: 16px 0; }
    .footer { background: #f9f9f9; padding: 20px 40px; text-align: center; font-size: 12px; color: #aaa; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>' . SITE_NAME . '</h1>
      <p>Password Reset Request</p>
    </div>
    <div class="body">
      <p>Hi <strong>' . htmlspecialchars($to_name) . '</strong>,</p>
      <p>We received a request to reset your password. Click the button below to choose a new one.</p>
      <p style="text-align:center;">
        <a href="' . $reset_url . '" class="btn">Reset My Password</a>
      </p>
      <div class="warning">
        ⚠️ This link expires in <strong>1 hour</strong>. If you did not request a password reset, you can safely ignore this email — your password will not change.
      </div>
      <p class="link-fallback">If the button does not work, copy and paste this link into your browser:<br>' . $reset_url . '</p>
    </div>
    <div class="footer">
      &copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.
    </div>
  </div>
</body>
</html>';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $result = mail($to_email, $subject, $body, $headers);

    if (!$result) {
        error_log("[Sentinel Mailer] Failed to send reset email to: {$to_email}. Reset URL: {$reset_url}");
    }

    return $result;
}

/**
 * Send a welcome email to a newly registered user.
 *
 * @param string $to_email  Recipient email address
 * @param string $to_name   Recipient full name
 * @return bool             True on success, false on failure
 */
function sendWelcomeEmail($to_email, $to_name) {
    $dashboard_url = SITE_URL . '/dashboard.php';
    $report_url    = SITE_URL . '/report_incident.php';

    $subject = 'Welcome to ' . SITE_NAME . ' 🎉';

    $first_name = explode(' ', trim($to_name))[0];

    $body = '
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Inter, Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
    .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .header { background: linear-gradient(135deg, #1a73e8, #0d47a1); padding: 40px; text-align: center; }
    .header h1 { color: #ffffff; margin: 0 0 6px; font-size: 28px; letter-spacing: -0.5px; }
    .header p { color: rgba(255,255,255,0.85); margin: 0; font-size: 15px; }
    .body { padding: 40px; }
    .body p { color: #444; font-size: 15px; line-height: 1.75; margin: 0 0 16px; }
    .feature-grid { display: table; width: 100%; border-collapse: separate; border-spacing: 12px; margin: 24px 0; }
    .feature { display: table-cell; background: #f0f7ff; border-radius: 10px; padding: 18px; text-align: center; width: 33%; }
    .feature .icon { font-size: 28px; margin-bottom: 8px; }
    .feature .label { font-size: 13px; font-weight: 600; color: #1a73e8; }
    .btn { display: inline-block; margin: 8px 6px; padding: 13px 30px; background: #1a73e8; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 600; }
    .btn-outline { background: transparent; color: #1a73e8; border: 2px solid #1a73e8; }
    .cta { text-align: center; padding: 8px 0 24px; }
    .divider { border: none; border-top: 1px solid #f0f0f0; margin: 24px 0; }
    .footer { background: #f9f9f9; padding: 20px 40px; text-align: center; font-size: 12px; color: #aaa; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>Welcome to ' . SITE_NAME . '!</h1>
      <p>Community Safety Platform &mdash; Cameroon</p>
    </div>
    <div class="body">
      <p>Hi <strong>' . htmlspecialchars($first_name) . '</strong> 👋,</p>
      <p>Your account is all set up. You are now part of a growing community helping to make Cameroon safer. Here is what you can do on the platform:</p>

      <div class="feature-grid">
        <div class="feature">
          <div class="icon">📍</div>
          <div class="label">Report Incidents</div>
        </div>
        <div class="feature">
          <div class="icon">🔔</div>
          <div class="label">Receive Alerts</div>
        </div>
        <div class="feature">
          <div class="icon">🗺️</div>
          <div class="label">Live Safety Map</div>
        </div>
      </div>

      <hr class="divider">

      <p>Ready to get started? Head to your dashboard or report your first incident.</p>

      <div class="cta">
        <a href="' . $dashboard_url . '" class="btn">Go to Dashboard</a>
        <a href="' . $report_url . '" class="btn btn-outline">Report an Incident</a>
      </div>

      <p style="font-size:13px;color:#888;">If you did not create this account, please ignore this email or contact our support team.</p>
    </div>
    <div class="footer">
      &copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.
    </div>
  </div>
</body>
</html>';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $result = mail($to_email, $subject, $body, $headers);

    if (!$result) {
        error_log("[Sentinel Mailer] Failed to send welcome email to: {$to_email}");
    }

    return $result;
}
?>
