<?php
// NO WHITESPACE BEFORE THIS OPENING TAG
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>
<div class="dashboard-sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/logo.png" alt="CCRS Logo" class="sidebar-logo">
        <h3>CCRS Portal</h3>
        <p>Welcome, <?php echo htmlspecialchars($full_name); ?></p>
    </div>
    
    <div class="sidebar-nav-wrapper">
        <nav class="sidebar-nav">
            <ul>
                <!-- Dashboard Home -->
                <li class="<?php echo ($current_page == 'index.php' && $current_dir == 'dashboard') ? 'active' : ''; ?>">
                    <a href="../dashboard/index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <!-- Reports Section -->
                <?php if (in_array($role, ['admin', 'law_enforcement', 'child_protection'])): ?>
                <li class="menu-header">REPORTS</li>
                <li class="<?php echo ($current_page == 'incidents.php' && !isset($_GET['status'])) ? 'active' : ''; ?>">
                    <a href="../dashboard/incidents.php">
                        <i class="fas fa-file-alt"></i>
                        <span>All Incidents</span>
                    </a>
                </li>
                <li>
                    <a href="../dashboard/incidents.php?status=pending">
                        <i class="fas fa-clock"></i>
                        <span>Pending Cases</span>
                    </a>
                </li>
                <li>
                    <a href="../dashboard/incidents.php?assigned=me">
                        <i class="fas fa-user-check"></i>
                        <span>Assigned to Me</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Analytics Section -->
                <?php if (in_array($role, ['admin', 'law_enforcement', 'child_protection', 'ngo', 'lc1'])): ?>
                <li class="menu-header">ANALYTICS</li>
                <li class="<?php echo ($current_page == 'analytics.php') ? 'active' : ''; ?>">
                    <a href="../dashboard/analytics.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistics</span>
                    </a>
                </li>
                <li>
                    <a href="../dashboard/export.php">
                        <i class="fas fa-download"></i>
                        <span>Export Reports</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Counseling Section -->
                <?php if ($role == 'counselor'): ?>
                <li class="menu-header">COUNSELING</li>
                <li class="<?php echo ($current_page == 'sessions.php') ? 'active' : ''; ?>">
                    <a href="../counselor/sessions.php">
                        <i class="fas fa-comments"></i>
                        <span>My Sessions</span>
                    </a>
                </li>
                <li>
                    <a href="../counselor/messages.php">
                        <i class="fas fa-envelope"></i>
                        <span>Messages</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Administration Section -->
                <?php if ($role == 'admin'): ?>
                <li class="menu-header">ADMINISTRATION</li>
                <li class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
                    <a href="../admin/users.php">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                <li class="<?php echo ($current_page == 'slides.php') ? 'active' : ''; ?>">
                    <a href="../admin/slides.php">
                        <i class="fas fa-images"></i>
                        <span>Hero Slides</span>
                    </a>
                </li>
                <li class="<?php echo ($current_page == 'audit-logs.php') ? 'active' : ''; ?>">
                    <a href="../admin/audit-logs.php">
                        <i class="fas fa-history"></i>
                        <span>Audit Logs</span>
                    </a>
                </li>
                <li class="<?php echo ($current_page == 'config.php') ? 'active' : ''; ?>">
                    <a href="../admin/config.php">
                        <i class="fas fa-cog"></i>
                        <span>System Settings</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    
    <div class="sidebar-footer">
    <a href="../auth/change-password.php" class="change-password-btn">
        <i class="fas fa-key"></i>
        <span>Change Password</span>
    </a>
    <a href="../auth/logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
    </a>
</div>
</div>

<style>
.sidebar-logo {
    max-width: 80px;
    height: auto;
    margin-bottom: 10px;
    display: block;
    margin-left: auto;
    margin-right: auto;
}

.sidebar-header h3 {
    font-size: 1rem;
    margin-bottom: 5px;
    text-align: center;
}

.sidebar-header p {
    font-size: 0.8rem;
    text-align: center;
}

.change-password-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
    text-decoration: none;
    padding: 10px;
    border-radius: 5px;
    transition: background 0.3s;
    margin-bottom: 10px;
    background: rgba(255,255,255,0.1);
}

.change-password-btn:hover {
    background: rgba(255,255,255,0.2);
}
</style>