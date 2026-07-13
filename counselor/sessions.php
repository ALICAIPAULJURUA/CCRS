<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Check if user is counselor
if (!isLoggedIn() || $_SESSION['role'] !== 'counselor') {
    redirect('../auth/login.php', 'Access denied. Counselor privileges required.', 'error');
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Handle session status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $session_id = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);
    
    if ($_POST['action'] === 'start_session') {
        $stmt = $db->prepare("
            UPDATE counseling_sessions 
            SET counselor_id = ?, status = 'active', started_at = NOW()
            WHERE id = ? AND status = 'requested'
        ");
        if ($stmt->execute([$user_id, $session_id])) {
            createNotification($user_id, 'Session Started', 'You have started a counseling session.', 'success');
            redirect("messages.php?session=$session_id", 'Session started successfully.', 'success');
        }
    } elseif ($_POST['action'] === 'complete_session') {
        $stmt = $db->prepare("
            UPDATE counseling_sessions 
            SET status = 'completed', completed_at = NOW()
            WHERE id = ? AND counselor_id = ?
        ");
        if ($stmt->execute([$session_id, $user_id])) {
            createNotification($user_id, 'Session Completed', 'Counseling session marked as completed.', 'info');
            redirect("sessions.php", 'Session completed successfully.', 'success');
        }
    } elseif ($_POST['action'] === 'cancel_session') {
        $stmt = $db->prepare("
            UPDATE counseling_sessions 
            SET status = 'cancelled'
            WHERE id = ? AND counselor_id = ?
        ");
        if ($stmt->execute([$session_id, $user_id])) {
            createNotification($user_id, 'Session Cancelled', 'Counseling session has been cancelled.', 'warning');
            redirect("sessions.php", 'Session cancelled.', 'info');
        }
    }
}

// Get pending requests
$pending = $db->prepare("
    SELECT cs.*, r.tracking_id, r.description
    FROM counseling_sessions cs
    JOIN reports r ON cs.report_id = r.id
    WHERE cs.status = 'requested'
    ORDER BY cs.requested_at ASC
");
$pending->execute();
$pending_sessions = $pending->fetchAll();

// Get active sessions
$active = $db->prepare("
    SELECT cs.*, r.tracking_id, r.description,
           (SELECT COUNT(*) FROM counseling_messages 
            WHERE session_id = cs.id AND is_read = 0 AND sender_type = 'survivor') as unread_count
    FROM counseling_sessions cs
    JOIN reports r ON cs.report_id = r.id
    WHERE cs.counselor_id = ? AND cs.status = 'active'
    ORDER BY cs.started_at DESC
");
$active->execute([$user_id]);
$active_sessions = $active->fetchAll();

// Get completed sessions
$completed = $db->prepare("
    SELECT cs.*, r.tracking_id, r.description
    FROM counseling_sessions cs
    JOIN reports r ON cs.report_id = r.id
    WHERE cs.counselor_id = ? AND cs.status IN ('completed', 'cancelled')
    ORDER BY cs.completed_at DESC
    LIMIT 20
");
$completed->execute([$user_id]);
$completed_sessions = $completed->fetchAll();

include '../includes/dashboard-header.php';
include '../includes/dashboard-sidebar.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Counseling Sessions</h1>
        <p>Manage your counseling sessions and support survivors</p>
    </div>
    
    <!-- Pending Requests -->
    <?php if (count($pending_sessions) > 0): ?>
    <div class="recent-section">
        <h2><i class="fas fa-clock"></i> Pending Requests</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Requested</th>
                        <th>Tracking ID</th>
                        <th>Case Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_sessions as $session): ?>
                    <tr>
                        <td><?php echo date('M j, Y g:i A', strtotime($session['requested_at'])); ?></td>
                        <td><?php echo htmlspecialchars($session['tracking_id']); ?></td>
                        <td><?php echo htmlspecialchars(substr($session['description'], 0, 100)) . '...'; ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="start_session">
                                <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-play"></i> Start Session
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Active Sessions -->
    <div class="recent-section">
        <h2><i class="fas fa-comments"></i> Active Sessions</h2>
        <?php if (count($active_sessions) > 0): ?>
        <div class="sessions-grid">
            <?php foreach ($active_sessions as $session): ?>
            <div class="session-card">
                <div class="session-header">
                    <div class="session-id">
                        <i class="fas fa-user-secret"></i>
                        <strong><?php echo htmlspecialchars($session['survivor_identifier']); ?></strong>
                    </div>
                    <div class="session-status active">
                        <i class="fas fa-circle"></i> Active
                    </div>
                </div>
                <div class="session-details">
                    <p><strong>Case:</strong> <?php echo htmlspecialchars(substr($session['tracking_id'], 0, 20)); ?></p>
                    <p><strong>Started:</strong> <?php echo date('M j, Y g:i A', strtotime($session['started_at'])); ?></p>
                    <?php if ($session['unread_count'] > 0): ?>
                    <div class="unread-badge">
                        <i class="fas fa-envelope"></i> <?php echo $session['unread_count']; ?> unread message(s)
                    </div>
                    <?php endif; ?>
                </div>
                <div class="session-actions">
                    <a href="messages.php?session=<?php echo $session['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-comment"></i> Messages
                    </a>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="complete_session">
                        <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Mark this session as completed?')">
                            <i class="fas fa-check"></i> Complete
                        </button>
                    </form>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="cancel_session">
                        <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this session?')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center">No active sessions at the moment.</p>
        <?php endif; ?>
    </div>
    
    <!-- Completed Sessions -->
    <?php if (count($completed_sessions) > 0): ?>
    <div class="recent-section">
        <h2><i class="fas fa-history"></i> Completed Sessions</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Survivor</th>
                        <th>Started</th>
                        <th>Completed</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completed_sessions as $session): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($session['survivor_identifier']); ?></td>
                        <td><?php echo $session['started_at'] ? date('M j, Y', strtotime($session['started_at'])) : '-'; ?></td>
                        <td><?php echo $session['completed_at'] ? date('M j, Y', strtotime($session['completed_at'])) : '-'; ?></td>
                        <td>
                            <span class="status-badge <?php echo $session['status'] == 'completed' ? 'status-success' : 'status-warning'; ?>">
                                <?php echo ucfirst($session['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="messages.php?session=<?php echo $session['id']; ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>

<style>
.sessions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.session-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid #e0e0e0;
    transition: transform 0.2s, box-shadow 0.2s;
}

.session-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.session-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e0e0e0;
}

.session-id {
    font-size: 1rem;
}

.session-status {
    font-size: 0.85rem;
    padding: 3px 8px;
    border-radius: 20px;
}

.session-status.active {
    background: #e8f5e9;
    color: #388e3c;
}

.unread-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 0.85rem;
    margin-top: 10px;
    display: inline-block;
}

.session-actions {
    margin-top: 1rem;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-success {
    background: #27ae60;
    color: white;
}

.btn-success:hover {
    background: #229954;
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

.text-center {
    text-align: center;
    padding: 2rem;
    color: #666;
}
</style>

<?php include '../includes/dashboard-footer.php'; ?>