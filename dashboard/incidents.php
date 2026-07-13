<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Check authentication
if (!isLoggedIn()) {
    redirect('../auth/login.php', 'Please login to access the dashboard.', 'error');
}

$db = Database::getInstance()->getConnection();
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Get filter parameters
$status = $_GET['status'] ?? '';
$assigned = $_GET['assigned'] ?? '';

// Build query
$sql = "SELECT r.*, rc.category_name, 
        assigned_user.full_name as assigned_to_name
        FROM reports r
        LEFT JOIN report_categories rc ON r.category_id = rc.id
        LEFT JOIN system_users assigned_user ON r.assigned_to = assigned_user.id
        WHERE 1=1";
$params = [];

if ($status && in_array($status, ['pending', 'under_investigation', 'resolved', 'closed'])) {
    $sql .= " AND r.status = ?";
    $params[] = $status;
}

// Show assigned to current user for law enforcement/child protection
if ($assigned == 'me' && in_array($role, ['admin', 'law_enforcement', 'child_protection'])) {
    $sql .= " AND r.assigned_to = ?";
    $params[] = $user_id;
}

$sql .= " ORDER BY r.submission_date DESC LIMIT 50";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$incidents = $stmt->fetchAll();

include '../includes/dashboard-header.php';
include '../includes/dashboard-sidebar.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Incident Management</h1>
        <p>View and manage reported incidents</p>
    </div>
    
    <!-- Filters -->
    <div class="filters-bar">
        <div class="filter-group">
            <label>Filter by Status:</label>
            <select onchange="window.location.href='?status='+this.value">
                <option value="">All</option>
                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="under_investigation" <?php echo $status == 'under_investigation' ? 'selected' : ''; ?>>Under Investigation</option>
                <option value="resolved" <?php echo $status == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                <option value="closed" <?php echo $status == 'closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>
        
        <?php if (in_array($role, ['admin', 'law_enforcement', 'child_protection'])): ?>
        <div class="filter-group">
            <label>Assigned to:</label>
            <select onchange="window.location.href='?assigned='+this.value">
                <option value="">Anyone</option>
                <option value="me" <?php echo $assigned == 'me' ? 'selected' : ''; ?>>Me</option>
            </select>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Incidents Table -->
    <div class="incidents-table-container">
        <?php if (count($incidents) > 0): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tracking ID</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incidents as $incident): ?>
                    <tr>
                        <td data-label="Tracking ID">
                            <strong><?php echo htmlspecialchars($incident['tracking_id']); ?></strong>
                        </td>
                        <td data-label="Category"><?php echo htmlspecialchars($incident['category_name'] ?? 'N/A'); ?></td>
                        <td data-label="Location"><?php echo htmlspecialchars($incident['location']); ?></td>
                        <td data-label="Date"><?php echo date('M j, Y', strtotime($incident['submission_date'])); ?></td>
                        <td data-label="Status">
                            <span class="status-badge status-<?php 
                                echo $incident['status'] == 'pending' ? 'warning' : 
                                    ($incident['status'] == 'resolved' ? 'success' : 'info'); 
                            ?>">
                                <?php echo ucwords(str_replace('_', ' ', $incident['status'])); ?>
                            </span>
                        </td>
                        <td data-label="Assigned To">
                            <?php if (!empty($incident['assigned_to_name'])): ?>
                                <span class="assigned-badge">
                                    <i class="fas fa-user-check"></i> <?php echo htmlspecialchars($incident['assigned_to_name']); ?>
                                </span>
                            <?php else: ?>
                                <span class="unassigned-badge">
                                    <i class="fas fa-user-clock"></i> Unassigned
                                </span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Actions">
                            <a href="incident-details.php?id=<?php echo $incident['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </td>
                     </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No incidents found matching your criteria.
        </div>
        <?php endif; ?>
    </div>
</main>

<style>
.filters-bar {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-group label {
    font-weight: 500;
    color: #666;
    font-size: 0.9rem;
}

.filter-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    background: white;
    cursor: pointer;
}

.incidents-table-container {
    background: white;
    border-radius: 10px;
    padding: 20px;
    overflow-x: auto;
}

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

@media (max-width: 768px) {
    .filters-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .filter-group select {
        width: 100%;
    }
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