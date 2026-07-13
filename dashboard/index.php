<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Check authentication
if (!isLoggedIn()) {
    redirect('../auth/login.php', 'Please login to access the dashboard.', 'error');
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];

// Get dashboard stats based on role
$stats = [];
$recent = [];

switch ($role) {
    case 'admin':
        // Total cases
        $stats['total_cases'] = $db->query("SELECT COUNT(*) FROM reports")->fetchColumn();
        
        // Cases by status
        $stats['pending'] = $db->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
        $stats['investigating'] = $db->query("SELECT COUNT(*) FROM reports WHERE status = 'under_investigation'")->fetchColumn();
        $stats['resolved'] = $db->query("SELECT COUNT(*) FROM reports WHERE status = 'resolved'")->fetchColumn();
        
        // Recent cases
        $recent = $db->query("
            SELECT r.*, rc.category_name,
                   assigned_user.full_name as assigned_to_name
            FROM reports r
            LEFT JOIN report_categories rc ON r.category_id = rc.id
            LEFT JOIN system_users assigned_user ON r.assigned_to = assigned_user.id
            ORDER BY r.submission_date DESC 
            LIMIT 10
        ")->fetchAll();
        
        // Cases assigned to current user
        $stats['assigned_to_me'] = $db->prepare("
            SELECT COUNT(*) FROM reports WHERE assigned_to = ?
        ");
        $stats['assigned_to_me']->execute([$user_id]);
        $stats['assigned_to_me'] = $stats['assigned_to_me']->fetchColumn();
        break;
        
    case 'law_enforcement':
    case 'child_protection':
        // Total cases in system
        $stats['total_cases'] = $db->query("SELECT COUNT(*) FROM reports")->fetchColumn();
        
        // Cases by status
        $stats['pending'] = $db->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
        $stats['investigating'] = $db->query("SELECT COUNT(*) FROM reports WHERE status = 'under_investigation'")->fetchColumn();
        $stats['resolved'] = $db->query("SELECT COUNT(*) FROM reports WHERE status = 'resolved'")->fetchColumn();
        
        // Cases assigned to current officer
        $assigned_stmt = $db->prepare("
            SELECT COUNT(*) FROM reports WHERE assigned_to = ?
        ");
        $assigned_stmt->execute([$user_id]);
        $stats['assigned_to_me'] = $assigned_stmt->fetchColumn();
        
        // Get assigned cases list for this officer
        $recent = $db->prepare("
            SELECT r.*, rc.category_name,
                   assigned_user.full_name as assigned_to_name
            FROM reports r
            LEFT JOIN report_categories rc ON r.category_id = rc.id
            LEFT JOIN system_users assigned_user ON r.assigned_to = assigned_user.id
            WHERE r.assigned_to = ?
            ORDER BY r.submission_date DESC 
            LIMIT 10
        ");
        $recent->execute([$user_id]);
        $recent = $recent->fetchAll();
        break;
        
    case 'counselor':
        $stats['pending_requests'] = $db->prepare("
            SELECT COUNT(*) FROM counseling_sessions WHERE status = 'requested'
        ");
        $stats['pending_requests']->execute();
        $stats['pending_requests'] = $stats['pending_requests']->fetchColumn();
        
        $recent = $db->prepare("
            SELECT cs.*, r.tracking_id 
            FROM counseling_sessions cs
            JOIN reports r ON cs.report_id = r.id
            WHERE cs.counselor_id = ? AND cs.status = 'active'
            ORDER BY cs.started_at DESC
            LIMIT 10
        ");
        $recent->execute([$user_id]);
        $recent = $recent->fetchAll();
        break;
        
    case 'ngo':
    case 'lc1':
        $stats['total_reports'] = $db->query("SELECT COUNT(*) FROM reports")->fetchColumn();
        $stats['by_category'] = $db->query("
            SELECT rc.category_name, COUNT(r.id) as count
            FROM report_categories rc
            LEFT JOIN reports r ON rc.id = r.category_id
            GROUP BY rc.id, rc.category_name
        ")->fetchAll();
        break;
}

include '../includes/dashboard-header.php';
include '../includes/dashboard-sidebar.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Welcome, <?php echo htmlspecialchars($full_name); ?></h1>
        <p><?php echo date('l, F j, Y'); ?></p>
    </div>
    
    <!-- Statistics Cards -->
    <?php if (in_array($role, ['admin', 'law_enforcement', 'child_protection'])): ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['total_cases']); ?></div>
                <div class="stat-label">Total Cases</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-search"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['investigating']); ?></div>
                <div class="stat-label">Investigating</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['resolved']); ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['assigned_to_me']); ?></div>
                <div class="stat-label">Assigned to Me</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($role == 'counselor'): ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['pending_requests']); ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (in_array($role, ['ngo', 'lc1'])): ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['total_reports']); ?></div>
                <div class="stat-label">Total Reports</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Activity / Assigned Cases -->
    <?php if (!empty($recent)): ?>
    <div class="recent-section">
        <h2>
            <?php if (in_array($role, ['law_enforcement', 'child_protection'])): ?>
                <i class="fas fa-briefcase"></i> My Assigned Cases
            <?php else: ?>
                <i class="fas fa-clock"></i> Recent Activity
            <?php endif; ?>
        </h2>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <?php if ($role == 'counselor'): ?>
                        <th>Case ID</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th>Action</th>
                        <?php else: ?>
                        <th>Tracking ID</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $item): ?>
                    <tr>
                        <?php if ($role == 'counselor'): ?>
                        <td data-label="Case ID"><?php echo htmlspecialchars($item['tracking_id']); ?></td>
                        <td data-label="Status">
                            <span class="status-badge status-info"><?php echo ucfirst($item['status']); ?></span>
                        </td>
                        <td data-label="Started"><?php echo date('M j, Y', strtotime($item['started_at'])); ?></td>
                        <td data-label="Action">
                            <a href="../counselor/messages.php?session=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-comment"></i> View
                            </a>
                        </td>
                        <?php else: ?>
                        <td data-label="Tracking ID">
                            <strong><?php echo htmlspecialchars($item['tracking_id']); ?></strong>
                        </td>
                        <td data-label="Category"><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td data-label="Location"><?php echo htmlspecialchars($item['location']); ?></td>
                        <td data-label="Date"><?php echo date('M j, Y', strtotime($item['submission_date'])); ?></td>
                        <td data-label="Status">
                            <span class="status-badge status-<?php 
                                echo $item['status'] == 'pending' ? 'warning' : 
                                    ($item['status'] == 'resolved' ? 'success' : 'info'); 
                            ?>">
                                <?php echo ucwords(str_replace('_', ' ', $item['status'])); ?>
                            </span>
                        </td>
                        <td data-label="Assigned To">
                            <?php if (!empty($item['assigned_to_name'])): ?>
                                <span class="assigned-badge">
                                    <i class="fas fa-user-check"></i> <?php echo htmlspecialchars($item['assigned_to_name']); ?>
                                </span>
                            <?php else: ?>
                                <span class="unassigned-badge">
                                    <i class="fas fa-user-clock"></i> Unassigned
                                </span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Action">
                            <a href="incident-details.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div class="action-buttons">
            <?php if (in_array($role, ['admin', 'law_enforcement', 'child_protection'])): ?>
            <a href="incidents.php?status=pending" class="action-btn">
                <i class="fas fa-clock"></i>
                View Pending Cases
            </a>
            <a href="incidents.php?assigned=me" class="action-btn">
                <i class="fas fa-user-check"></i>
                My Assigned Cases
            </a>
            <a href="analytics.php" class="action-btn">
                <i class="fas fa-chart-bar"></i>
                View Analytics
            </a>
            <?php endif; ?>
            
            <?php if ($role == 'counselor'): ?>
            <a href="../counselor/sessions.php" class="action-btn">
                <i class="fas fa-comments"></i>
                View All Sessions
            </a>
            <?php endif; ?>
            
            <?php if ($role == 'admin'): ?>
            <a href="../admin/users.php" class="action-btn">
                <i class="fas fa-users"></i>
                Manage Users
            </a>
            <a href="../admin/slides.php" class="action-btn">
                <i class="fas fa-images"></i>
                Manage Slides
            </a>
            <?php endif; ?>
            
            <?php if (in_array($role, ['ngo', 'lc1'])): ?>
            <a href="export.php" class="action-btn">
                <i class="fas fa-download"></i>
                Export Reports
            </a>
            <?php endif; ?>
            
            <a href="../auth/change-password.php" class="action-btn">
                <i class="fas fa-key"></i>
                Change Password
            </a>
        </div>
    </div>
</main>

<style>
.assigned-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #e8f5e9;
    color: #388e3c;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.unassigned-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #fff3e0;
    color: #f57c00;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

@media (max-width: 600px) {
    .data-table td:before {
        content: attr(data-label) ": ";
        font-weight: 600;
        display: inline-block;
        width: 120px;
    }
    
    .data-table td {
        display: flex;
        flex-wrap: wrap;
        padding: 8px;
    }
}
</style>

<?php include '../includes/dashboard-footer.php'; ?>