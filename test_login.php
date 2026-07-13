<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$username = 'admin';
$password = 'Admin@123';

echo "<h2>Testing Login System</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if user exists
    $stmt = $db->prepare("
        SELECT u.*, r.role_name 
        FROM system_users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.username = ? AND u.is_active = 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "User found!<br>";
        echo "Username: " . $user['username'] . "<br>";
        echo "Role: " . $user['role_name'] . "<br>";
        echo "Is Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";
        echo "Stored Hash: " . $user['password_hash'] . "<br><br>";
        
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            echo "✅ Password verification: SUCCESS!<br>";
            echo "Login would work correctly.<br>";
        } else {
            echo "❌ Password verification: FAILED!<br>";
            echo "The password hash doesn't match.<br>";
            
            // Create a new hash for comparison
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            echo "New hash for '$password': " . $new_hash . "<br>";
        }
    } else {
        echo "❌ User not found or inactive.<br>";
        echo "Checking all users:<br>";
        
        $all_users = $db->query("SELECT username, is_active FROM system_users")->fetchAll();
        if ($all_users) {
            foreach ($all_users as $u) {
                echo "- " . $u['username'] . " (Active: " . ($u['is_active'] ? 'Yes' : 'No') . ")<br>";
            }
        } else {
            echo "No users found in database.<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>