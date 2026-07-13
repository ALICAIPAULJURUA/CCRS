<?php
require_once 'config/database.php';

// Create password hash for 'Admin@123'
$password = 'Admin@123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "<br>";
echo "Hash: " . $hash . "<br><br>";

// Insert admin user
try {
    $db = Database::getInstance()->getConnection();
    
    // First, check if admin exists
    $check = $db->prepare("SELECT id FROM system_users WHERE username = ?");
    $check->execute(['admin']);
    $exists = $check->fetch();
    
    if ($exists) {
        // Update existing admin
        $stmt = $db->prepare("UPDATE system_users SET password_hash = ?, is_active = 1 WHERE username = 'admin'");
        $stmt->execute([$hash]);
        echo "Admin user updated successfully!<br>";
    } else {
        // Insert new admin
        $stmt = $db->prepare("
            INSERT INTO system_users (username, password_hash, email, full_name, role_id, is_active) 
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        
        // Get admin role ID
        $role = $db->prepare("SELECT id FROM roles WHERE role_name = 'admin'");
        $role->execute();
        $role_id = $role->fetchColumn();
        
        if (!$role_id) {
            // Insert admin role if it doesn't exist
            $db->exec("INSERT INTO roles (role_name, description) VALUES ('admin', 'System Administrator')");
            $role_id = $db->lastInsertId();
        }
        
        $stmt->execute(['admin', $hash, 'admin@ccrs.ug', 'System Administrator', $role_id]);
        echo "Admin user created successfully!<br>";
    }
    
    // Also create a test officer
    $officer_password = 'Officer@123';
    $officer_hash = password_hash($officer_password, PASSWORD_DEFAULT);
    
    $check = $db->prepare("SELECT id FROM system_users WHERE username = 'officer1'");
    $check->execute(['officer1']);
    $exists = $check->fetch();
    
    if (!$exists) {
        // Get law_enforcement role ID
        $role = $db->prepare("SELECT id FROM roles WHERE role_name = 'law_enforcement'");
        $role->execute();
        $role_id = $role->fetchColumn();
        
        if (!$role_id) {
            $db->exec("INSERT INTO roles (role_name, description) VALUES ('law_enforcement', 'Police Officer')");
            $role_id = $db->lastInsertId();
        }
        
        $stmt = $db->prepare("
            INSERT INTO system_users (username, password_hash, email, full_name, role_id, is_active) 
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute(['officer1', $officer_hash, 'officer1@ccrs.ug', 'Police Officer', $role_id]);
        echo "Officer user created successfully!<br>";
    }
    
    echo "<br>Login credentials:<br>";
    echo "Admin: username='admin', password='Admin@123'<br>";
    echo "Officer: username='officer1', password='Officer@123'<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>