<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Check if user is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../auth/login.php', 'Access denied. Admin privileges required.', 'error');
}

$db = Database::getInstance()->getConnection();

// Get filters
$action = $_GET['action'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$sql = "SELECT al.*, u.username, u.full_name 
        FROM audit_logs al
        LEFT JOIN system_users u ON al.user_id = u.id
        WHERE 1=1";
$params = [];

if ($action) {
    $sql .= " AND al.action LIKE ?";
    $params[] = "%$action%";
}

if ($user_id) {
    $sql .= " AND al.user_id = ?";
    $params[] = $user_id;
}

if ($date_from) {
    $sql .= " AND DATE(al.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $sql .= " AND DATE(al.created_at) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY al.created_at DESC LIMIT 500";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique actions for filter
$actions = $db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

// Get users for filter
$users = $db->query("SELECT id, username FROM system_users ORDER BY username")->fetchAll();

include '../includes/dashboard-header.php';
include '../includes/dashboard-sidebar.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Audit Logs</h1>
        <p>View system activity and user actions</p>
    </div>
    
    <!-- Filters -->
    <div class="filters-bar">
        <form method="GET" action="" class="filter-form">
            <div class="filter-group">
                <label>Action:</label>
                <select name="action">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $a): ?>
                    <option value="<?php echo htmlspecialchars($a); ?>" <?php echo $action == $a ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($a); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>User:</label>
                <select name="user_id">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo $user_id == $u['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($u['username']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>From:</label>
                <input type="date" name="date_from" value="<?php echo $date_from; ?>">
            </div>
            
            <div class="filter-group">
                <label>To:</label>
                <input type="date" name="date_to" value="<?php echo $date_to; ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="audit-logs.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>
    
    <!-- Logs Table -->
    <div class="recent-section">
        <h2>Activity Logs (Last 500 entries)</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>IP Address</th>
                        <th>Changes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                        <td><?php echo $log['username'] ?? 'Anonymous'; ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo strpos($log['action'], 'LOGIN') !== false ? 'info' : 
                                    (strpos($log['action'], 'CREATE') !== false ? 'success' : 
                                    (strpos($log['action'], 'DELETE') !== false ? 'danger' : 'warning')); 
                            ?>">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($log['table_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($log['record_id'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                        <td>
                            <?php if ($log['old_value'] || $log['new_value']): ?>
                            <details>
                                <summary>View Changes</summary>
                                <small>Old: <?php echo htmlspecialchars(substr($log['old_value'] ?? '-', 0, 100)); ?></small><br>
                                <small>New: <?php echo htmlspecialchars(substr($log['new_value'] ?? '-', 0, 100)); ?></small>
                            </details>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No audit logs found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<style>
.filter-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 500;
}

.badge-info { background: #e3f2fd; color: #1976d2; }
.badge-success { background: #e8f5e9; color: #388e3c; }
.badge-danger { background: #ffebee; color: #d32f2f; }
.badge-warning { background: #fff3e0; color: #f57c00; }

details summary {
    cursor: pointer;
    color: var(--secondary-color);
    font-size: 12px;
}

details summary:hover {
    text-decoration: underline;
}
</style>

<?php include '../includes/dashboard-footer.php'; ?>