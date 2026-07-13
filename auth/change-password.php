<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php', 'Please login to change your password.', 'error');
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$error = '';
$success = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        // Verify current password
        $stmt = $db->prepare("SELECT password_hash FROM system_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($current_password, $user['password_hash'])) {
            // Update password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE system_users SET password_hash = ?, password_change_required = 0 WHERE id = ?");
            
            if ($stmt->execute([$new_hash, $user_id])) {
                $success = "Your password has been changed successfully!";
                logAudit($user_id, 'PASSWORD_CHANGED');
                
                // Redirect after 2 seconds
                header("refresh:2;url=../dashboard/index.php");
            } else {
                $error = "Error updating password. Please try again.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}

// Check if password change is required
$required = false;
$stmt = $db->prepare("SELECT password_change_required FROM system_users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$required = $user['password_change_required'] == 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - CCRS</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <style>
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .password-container {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .header i {
            font-size: 3rem;
            color: var(--secondary-color);
        }
        
        .header h1 {
            font-size: 1.5rem;
            margin: 0.5rem 0;
            color: var(--primary-color);
        }
        
        .header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
        }
        
        .password-requirements {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 1rem;
            font-size: 12px;
            color: #666;
        }
        
        .password-requirements ul {
            margin-left: 1.5rem;
            margin-top: 5px;
        }
        
        .btn-block {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
        }
        
        .skip-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .skip-link a {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .skip-link a:hover {
            text-decoration: underline;
        }
        
        .warning-banner {
            background: #fff3cd;
            border-left: 4px solid var(--warning-color);
            padding: 12px;
            margin-bottom: 1.5rem;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .warning-banner i {
            font-size: 1.2rem;
            color: #856404;
        }
        
        .warning-banner span {
            color: #856404;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="header">
            <i class="fas fa-key"></i>
            <h1>Change Password</h1>
            <p>Welcome, <?php echo htmlspecialchars($full_name); ?></p>
        </div>
        
        <?php if ($required): ?>
        <div class="warning-banner">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Security Notice: You are required to change your password before continuing.</span>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <div class="skip-link">
                <a href="../dashboard/index.php"><i class="fas fa-arrow-right"></i> Go to Dashboard</a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required autofocus>
                </div>
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <div class="password-requirements">
                    <strong>Password Requirements:</strong>
                    <ul>
                        <li>Minimum 6 characters</li>
                        <li>Use a mix of letters, numbers, and symbols</li>
                        <li>Avoid common or easily guessed passwords</li>
                    </ul>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save"></i> Change Password
                </button>
            </form>
            
            <?php if (!$required): ?>
            <div class="skip-link">
                <a href="../dashboard/index.php"><i class="fas fa-home"></i> Back to Dashboard</a>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>