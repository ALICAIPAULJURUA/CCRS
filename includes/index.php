<?php
// NO WHITESPACE BEFORE THIS OPENING TAG
require_once '../config/database.php';
require_once '../config/functions.php';

// Check authentication
if (!isLoggedIn()) {
    redirect('../auth/login.php', 'Please login to access the dashboard.', 'error');
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get dashboard stats based on role
$stats = [];

switch ($role) {
    case 'admin':
    case 'law_enforcement':
    case 'child_protection':
        $stats['total_cases'] = $db->query("SELECT COUNT(*) FROM reports")->fetchColumn();
        $stats['pending'] = $db->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
        $stats['investigating'] = $db->query("SELECT COUNT(*) FROM reports WHERE status = 'under_investigation'")->fetchColumn();
        $stats['resolved'] = $db->query("SELECT COUNT(*) FROM reports WHERE status = 'resolved'")->fetchColumn();
        
        $recent = $db->query("
            SELECT r.*, rc.category_name 
            FROM reports r
            LEFT JOIN report_categories rc ON r.category_id = rc.id
            ORDER BY r.submission_date DESC 
            LIMIT 10
        ")->fetchAll();
        break;
        
    case 'counselor':
        $stats['pending_requests'] = $db->prepare("SELECT COUNT(*) FROM counseling_sessions WHERE status = 'requested'");
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
        
    default:
        $recent = [];
}

// Include header and sidebar
include '../includes/dashboard-header.php';
include '../includes/dashboard-sidebar.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
        <p><?php echo date('l, F j, Y'); ?></p>
    </div>
    
    <!-- Statistics Cards -->
    <?php if (isset($stats['total_cases'])): ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['total_cases']); ?></div>
                <div class="stat-label">Total Cases</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-search"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['investigating']); ?></div>
                <div class="stat-label">Investigating</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['resolved']); ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($role == 'counselor'): ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['pending_requests']); ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Activity -->
    <?php if (!empty($recent)): ?>
    <div class="recent-section">
        <h2>Recent Activity</h2>
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
                        <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $item): ?>
                    <tr>
                        <?php if ($role == 'counselor'): ?>
                        <td><?php echo htmlspecialchars($item['tracking_id']); ?></td>
                        <td><span class="status-badge status-info"><?php echo ucfirst($item['status']); ?></span></td>
                        <td><?php echo date('M j, Y', strtotime($item['started_at'])); ?></td>
                        <td><a href="../counselor/messages.php?session=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">View</a></td>
                        <?php else: ?>
                        <td><?php echo htmlspecialchars($item['tracking_id']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['location']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($item['submission_date'])); ?></td>
                        <td><span class="status-badge status-<?php echo $item['status'] == 'pending' ? 'warning' : ($item['status'] == 'resolved' ? 'success' : 'info'); ?>"><?php echo ucwords(str_replace('_', ' ', $item['status'])); ?></span></td>
                        <td><a href="incident-details.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">View</a></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php include '../includes/dashboard-footer.php'; ?>