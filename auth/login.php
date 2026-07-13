<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('../dashboard/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $db = Database::getInstance()->getConnection();
        
        // Get user with role
        $stmt = $db->prepare("
            SELECT u.*, r.role_name 
            FROM system_users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.username = ? AND u.is_active = 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['logged_in'] = true;
            
            // Update last login
            $stmt = $db->prepare("UPDATE system_users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Log login
            logAudit($user['id'], 'LOGIN_SUCCESS');
            
            // Check if password change is required
            $check_stmt = $db->prepare("SELECT password_change_required FROM system_users WHERE id = ?");
            $check_stmt->execute([$user['id']]);
            $password_required = $check_stmt->fetchColumn();
            
            if ($password_required == 1) {
                redirect('../auth/change-password.php', 'You are required to change your password before continuing.', 'warning');
            } else {
                redirect('../dashboard/index.php', 'Welcome back, ' . $user['full_name'] . '!', 'success');
            }
        } else {
            $error = 'Invalid username or password';
            logAudit(null, 'LOGIN_FAILED', null, null, null, $username);
        }
    } else {
        $error = 'Please enter username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Staff Login - Community Crime Reporting System</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="apple-touch-icon" href="../assets/images/logo.png">
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body.login-page {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }
        
        .login-box {
            background-color: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .login-header .logo-img {
            max-width: 120px;
            height: auto;
            margin-bottom: 1rem;
        }
        
        .login-header h1 {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }
        
        .login-header p {
            font-size: 0.85rem;
            color: #666;
        }
        
        .login-form .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }
        
        .login-form .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--text-color);
        }
        
        .login-form .form-group i {
            position: absolute;
            left: 12px;
            top: 38px;
            color: #999;
            font-size: 1rem;
        }
        
        .login-form .form-group input {
            width: 100%;
            padding: 0.75rem 0.75rem 0.75rem 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .login-form .form-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }
        
        .login-form button {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            margin-top: 0.5rem;
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        .login-footer a {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            body.login-page {
                padding: 15px;
            }
            
            .login-box {
                padding: 1.5rem;
            }
            
            .login-header .logo-img {
                max-width: 100px;
            }
            
            .login-header h1 {
                font-size: 1.25rem;
            }
            
            .login-form .form-group i {
                top: 36px;
            }
            
            .login-form .form-group input {
                padding: 0.7rem 0.7rem 0.7rem 2.2rem;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <img src="../assets/images/logo.png" alt="CCRS Logo" class="logo-img">
                <h1>Community Crime Reporting System</h1>
                <p>Staff Portal Login</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
            <div class="alert alert-success">You have been logged out successfully.</div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <i class="fas fa-user"></i>
                    <input type="text" 
                           name="username" 
                           id="username" 
                           required 
                           autofocus
                           placeholder="Enter your username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           required
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="login-footer">
                <a href="../index.php"><i class="fas fa-home"></i> Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>