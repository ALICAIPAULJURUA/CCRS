<?php
// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define constants if not defined
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost/ccrs-system1');
}

// Simple database connection function
function getDbConnection() {
    try {
        $host = 'localhost';
        $dbname = 'crime_reporting_system'; // Your database name
        $username = 'root';
        $password = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Logout DB error: " . $e->getMessage());
        return null;
    }
}

// Simple audit log function for logout
function logLogout($user_id) {
    if (!$user_id) return;
    
    try {
        $db = getDbConnection();
        if (!$db) return;
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action, ip_address, user_agent, created_at) 
            VALUES (?, 'LOGOUT', ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $ip, $ua]);
    } catch (Exception $e) {
        error_log("Logout log error: " . $e->getMessage());
    }
}

// Log the logout if user was logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    logLogout($_SESSION['user_id']);
}

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php?logout=success');
exit();
?>