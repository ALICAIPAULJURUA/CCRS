<?php
// PHPMailer configuration and autoloader

// Path to PHPMailer library
$phpmailer_path = __DIR__ . '/../libs/phpmailer/';

// Check if PHPMailer files exist and load them
if (file_exists($phpmailer_path . 'PHPMailer.php')) {
    require_once $phpmailer_path . 'PHPMailer.php';
    require_once $phpmailer_path . 'SMTP.php';
    require_once $phpmailer_path . 'Exception.php';
} else {
    error_log("PHPMailer not found at: " . $phpmailer_path);
}

/**
 * Send email using PHPMailer
 */
function sendPHPMailerEmail($to, $subject, $message, $to_name = '') {
    // Check if PHPMailer classes are available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer classes not loaded - using fallback");
        return sendMailFallback($to, $subject, $message);
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->SMTPDebug = 0;                      // Disable debug output
        $mail->isSMTP();                           
        $mail->Host       = SMTP_HOST;             
        $mail->SMTPAuth   = true;                  
        $mail->Username   = SMTP_USER;             
        $mail->Password   = SMTP_PASS;             
        $mail->SMTPSecure = SMTP_SECURE;           
        $mail->Port       = SMTP_PORT;             
        $mail->CharSet    = 'UTF-8';
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to, $to_name ?: $to);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        error_log("Email sent successfully to: " . $to);
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return sendMailFallback($to, $subject, $message);
    }
}

/**
 * Fallback email function using PHP mail()
 */
function sendMailFallback($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">" . "\r\n";
    $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
    
    $result = @mail($to, $subject, $message, $headers);
    
    if ($result) {
        error_log("Fallback mail sent successfully to: " . $to);
    } else {
        error_log("Fallback mail failed to: " . $to);
    }
    
    return $result;
}

/**
 * Save email to file for backup (when email sending fails)
 */
function saveEmailToFileBackup($to, $subject, $message, $full_name, $username, $password, $role) {
    $email_dir = __DIR__ . '/../emails/';
    if (!file_exists($email_dir)) {
        mkdir($email_dir, 0755, true);
    }
    
    $filename = $email_dir . 'user_created_' . date('Y-m-d_H-i-s') . '.html';
    
    $full_content = '<!DOCTYPE html>
    <html>
    <head>
        <title>New User Credentials - CCRS</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; }
            .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 20px; }
            .credentials { background: #f0f0f0; padding: 15px; border-left: 4px solid #3498db; margin: 15px 0; }
            .footer { text-align: center; padding: 15px; font-size: 12px; color: #777; border-top: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Welcome to CCRS</h2>
                <p>Community Crime Reporting System</p>
            </div>
            <div class="content">
                <p>Dear <strong>' . htmlspecialchars($full_name) . '</strong>,</p>
                <p>Your account has been created in the Community Crime Reporting System.</p>
                
                <div class="credentials">
                    <h3>Your Login Credentials:</h3>
                    <p><strong>Username:</strong> ' . htmlspecialchars($username) . '</p>
                    <p><strong>Temporary Password:</strong> ' . htmlspecialchars($password) . '</p>
                    <p><strong>Role:</strong> ' . ucwords(str_replace('_', ' ', $role)) . '</p>
                </div>
                
                <p><strong>Login URL:</strong> <a href="' . SITE_URL . '/auth/login.php">' . SITE_URL . '/auth/login.php</a></p>
                
                <p><strong>Important:</strong> Please change your password after first login.</p>
                
                <p>Thank you,<br><strong>CCRS Team</strong><br>Muni University, Arua City, Uganda</p>
            </div>
            <div class="footer">
                <p>This is an automated message. Please do not reply.</p>
                <p>&copy; ' . date('Y') . ' Community Crime Reporting System</p>
            </div>
        </div>
    </body>
    </html>';
    
    file_put_contents($filename, $full_content);
    error_log("Email saved to file: " . $filename);
    return $filename;
}
?>