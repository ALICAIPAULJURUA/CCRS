<?php
// Start output buffering to prevent whitespace issues
if (!ob_get_level()) {
    ob_start();
}

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include email configuration
require_once __DIR__ . '/email_config.php';

// Database connection function (fallback for when Database class isn't available)
function getDbConnection() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            $host = DB_HOST;
            $dbname = DB_NAME;
            $username = DB_USER;
            $password = DB_PASS;
            
            $connection = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    return $connection;
}

// Generate unique tracking ID
function generateTrackingID() {
    $prefix = defined('TRACKING_ID_PREFIX') ? TRACKING_ID_PREFIX : 'CCRS';
    $date = date('Ymd');
    $random = strtoupper(substr(uniqid(), -6));
    return $prefix . '-' . $date . '-' . $random;
}

// Sanitize input
function sanitizeInput($data) {
    if ($data === null) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// File upload handling
function uploadFile($file, $report_id) {
    $target_dir = defined('UPLOAD_DIR') ? UPLOAD_DIR : __DIR__ . '/../uploads/evidence/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_types = explode(',', getConfig('allowed_file_types'));
    
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    $max_size = defined('MAX_FILE_SIZE') ? MAX_FILE_SIZE : 10485760;
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $new_filename = uniqid() . '_' . $report_id . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        if (in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
            removeImageMetadata($target_file);
        }
        
        return [
            'success' => true,
            'filename' => $new_filename,
            'path' => 'uploads/evidence/' . $new_filename,
            'type' => getFileType($file_extension)
        ];
    }
    
    return ['success' => false, 'message' => 'Upload failed'];
}

// Get file type category
function getFileType($extension) {
    $image_types = ['jpg', 'jpeg', 'png', 'gif'];
    $audio_types = ['mp3', 'wav', 'ogg'];
    $doc_types = ['pdf', 'doc', 'docx'];
    
    if (in_array($extension, $image_types)) return 'image';
    if (in_array($extension, $audio_types)) return 'audio';
    if (in_array($extension, $doc_types)) return 'document';
    return 'other';
}

// Remove image metadata
function removeImageMetadata($filepath) {
    return true;
}

// Log audit action
function logAudit($user_id, $action, $table = null, $record_id = null, $old_value = null, $new_value = null) {
    try {
        $db = getDbConnection();
        if (!$db) {
            error_log("Could not connect to database for audit log");
            return false;
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        if ($old_value && strlen($old_value) > 1000) $old_value = substr($old_value, 0, 1000);
        if ($new_value && strlen($new_value) > 1000) $new_value = substr($new_value, 0, 1000);
        
        $stmt = $db->prepare("
            INSERT INTO audit_logs 
            (user_id, action, table_name, record_id, old_value, new_value, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([$user_id, $action, $table, $record_id, $old_value, $new_value, $ip, $ua]);
        
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
}

// Get system config
function getConfig($key) {
    try {
        $db = getDbConnection();
        if (!$db) return null;
        
        $stmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['config_value'] : null;
    } catch (Exception $e) {
        error_log("Config error: " . $e->getMessage());
        return null;
    }
}

// Create notification
function createNotification($user_id, $title, $message, $type = 'info', $link = null) {
    try {
        $db = getDbConnection();
        if (!$db) return false;
        
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, link, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$user_id, $title, $message, $type, $link]);
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Check user role
function hasRole($role_name) {
    if (!isLoggedIn()) return false;
    return isset($_SESSION['role']) && $_SESSION['role'] === $role_name;
}

// Redirect with message
function redirect($url, $message = null, $type = 'info') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: " . $url);
    exit();
}

// Display flash message
function displayFlash() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'] ?? 'info';
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        $alert_class = $type === 'error' ? 'alert-error' : ($type === 'success' ? 'alert-success' : 'alert-info');
        return "<div class='alert {$alert_class}'>{$message}</div>";
    }
    return '';
}

// Rate limiting for report submissions
function checkRateLimit($ip) {
    try {
        $db = getDbConnection();
        if (!$db) return true;
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM reports 
            WHERE ip_address = ? AND submission_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$ip]);
        $result = $stmt->fetch();
        return $result['count'] < 5;
    } catch (Exception $e) {
        error_log("Rate limit error: " . $e->getMessage());
        return true;
    }
}

// Generate random password
function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

// ============================================
// EMAIL FUNCTIONS
// ============================================

/**
 * Send welcome email with login credentials
 */
function sendWelcomeEmail($email, $username, $password, $full_name, $role) {
    $subject = "Welcome to CCRS - Your Account Credentials";
    
    $message = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 20px; background: #f9f9f9; }
            .credentials { background: #fff; padding: 15px; border-left: 4px solid #3498db; margin: 15px 0; border-radius: 5px; }
            .footer { text-align: center; padding: 15px; font-size: 12px; color: #777; }
            .warning { color: #e74c3c; font-size: 12px; }
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
                
                <p><strong>Important Security Instructions:</strong></p>
                <ul>
                    <li>This is your temporary password. Please change it after your first login.</li>
                    <li>Never share your password with anyone.</li>
                    <li>If you suspect unauthorized access, contact the system administrator immediately.</li>
                    <li>Always log out after completing your work, especially on shared computers.</li>
                </ul>
                
                <p class="warning"><strong>Note:</strong> This email contains sensitive information. Please keep it secure.</p>
                
                <p>Thank you for your service to the community.</p>
                
                <p><strong>CCRS Team</strong><br>Muni University, Arua City, Uganda</p>
            </div>
            <div class="footer">
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; ' . date('Y') . ' Community Crime Reporting System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Try PHPMailer first
    $email_sent = sendPHPMailerEmail($email, $subject, $message, $full_name);
    
    if (!$email_sent) {
        // Save to file as backup
        $saved_file = saveEmailToFileBackup($email, $subject, $message, $full_name, $username, $password, $role);
        
        // Store credentials in session to display to admin
        $_SESSION['temp_credentials'] = [
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'full_name' => $full_name,
            'saved_file' => $saved_file
        ];
        return false;
    }
    
    return true;
}

/**
 * Send password reset email (for admin-initiated resets)
 * This sends the new temporary password directly to the user
 */
function sendPasswordResetEmail($email, $username, $new_password) {
    $subject = "Your Password Has Been Reset - CCRS";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 20px; background: #f9f9f9; }
            .credentials { background: #fff; padding: 15px; border-left: 4px solid #3498db; margin: 15px 0; border-radius: 5px; }
            .footer { text-align: center; padding: 15px; font-size: 12px; color: #777; }
            .warning { color: #e74c3c; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Password Reset Confirmation</h2>
                <p>Community Crime Reporting System</p>
            </div>
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($username) . "</strong>,</p>
                <p>An administrator has reset your password for the CCRS system.</p>
                
                <div class='credentials'>
                    <h3>Your New Login Credentials:</h3>
                    <p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>
                    <p><strong>Temporary Password:</strong> " . htmlspecialchars($new_password) . "</p>
                </div>
                
                <p><strong>Login URL:</strong> <a href='" . SITE_URL . "/auth/login.php'>" . SITE_URL . "/auth/login.php</a></p>
                
                <p><strong>Important:</strong> You will be required to change this password after your first login.</p>
                
                <p class='warning'><strong>Security Notice:</strong> For security reasons, please change your password immediately after logging in.</p>
                
                <p>If you did not request this password reset, please contact the system administrator immediately.</p>
                
                <p>Thank you,<br><strong>CCRS Team</strong><br>Muni University, Arua City, Uganda</p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " Community Crime Reporting System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
    
    return sendPHPMailerEmail($email, $subject, $message, $username);
}
?>