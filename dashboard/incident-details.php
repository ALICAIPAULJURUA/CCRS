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

// Get incident ID
$incident_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$incident_id) {
    redirect('incidents.php', 'Invalid incident ID.', 'error');
}

// Get incident details
$stmt = $db->prepare("
    SELECT r.*, rc.category_name, 
           assigned_user.full_name as assigned_to_name
    FROM reports r
    LEFT JOIN report_categories rc ON r.category_id = rc.id
    LEFT JOIN system_users assigned_user ON r.assigned_to = assigned_user.id
    WHERE r.id = ?
");
$stmt->execute([$incident_id]);
$incident = $stmt->fetch();

if (!$incident) {
    redirect('incidents.php', 'Incident not found.', 'error');
}

// Get evidence files
$stmt = $db->prepare("SELECT * FROM evidence_files WHERE report_id = ? ORDER BY id DESC");
$stmt->execute([$incident_id]);
$evidence_files = $stmt->fetchAll();

// Get case notes
$stmt = $db->prepare("
    SELECT cn.*, u.full_name as author_name
    FROM case_notes cn
    LEFT JOIN system_users u ON cn.user_id = u.id
    WHERE cn.report_id = ? AND cn.is_private = 0
    ORDER BY cn.created_at DESC
");
$stmt->execute([$incident_id]);
$notes = $stmt->fetchAll();

// Handle adding case note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $note = sanitizeInput($_POST['note']);
    $is_private = isset($_POST['is_private']) ? 1 : 0;
    
    if (!empty($note)) {
        $stmt = $db->prepare("
            INSERT INTO case_notes (report_id, user_id, note, is_private, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$incident_id, $user_id, $note, $is_private]);
        redirect("incident-details.php?id=$incident_id", 'Note added successfully.', 'success');
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = sanitizeInput($_POST['status']);
    $old_status = $incident['status'];
    
    $stmt = $db->prepare("UPDATE reports SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $incident_id]);
    
    $stmt = $db->prepare("
        INSERT INTO status_history (report_id, old_status, new_status, changed_by, changed_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$incident_id, $old_status, $new_status, $user_id]);
    
    redirect("incident-details.php?id=$incident_id", 'Status updated successfully.', 'success');
}

// Handle assignment - FIXED
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_case'])) {
    $assigned_to = filter_input(INPUT_POST, 'assigned_to', FILTER_VALIDATE_INT);
    
    $stmt = $db->prepare("UPDATE reports SET assigned_to = ?, assigned_date = NOW() WHERE id = ?");
    if ($stmt->execute([$assigned_to, $incident_id])) {
        // Get the assigned officer's name for the log
        $officer_stmt = $db->prepare("SELECT full_name FROM system_users WHERE id = ?");
        $officer_stmt->execute([$assigned_to]);
        $officer = $officer_stmt->fetch();
        
        logAudit($user_id, 'CASE_ASSIGNED', 'reports', $incident_id, null, $officer['full_name'] ?? 'Unknown');
        redirect("incident-details.php?id=$incident_id", 'Case assigned successfully.', 'success');
    } else {
        $error = "Error assigning case.";
    }
}

// Get users for assignment dropdown - FIXED: Shows ALL active law enforcement and child protection officers
$users = $db->query("
    SELECT u.id, u.full_name, r.role_name 
    FROM system_users u
    JOIN roles r ON u.role_id = r.id
    WHERE r.role_name IN ('law_enforcement', 'child_protection') AND u.is_active = 1
    ORDER BY u.full_name ASC
")->fetchAll();

$status_colors = [
    'pending' => 'warning',
    'under_investigation' => 'info',
    'resolved' => 'success',
    'closed' => 'secondary'
];

include '../includes/dashboard-header.php';
include '../includes/dashboard-sidebar.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Incident Details</h1>
        <p>Tracking ID: <?php echo htmlspecialchars($incident['tracking_id']); ?></p>
    </div>
    
    <?php echo displayFlash(); ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="incident-details-grid">
        <!-- Main Incident Info -->
        <div class="detail-card">
            <div class="card-header">
                <h2><i class="fas fa-info-circle"></i> Incident Information</h2>
                <span class="status-badge status-<?php echo $status_colors[$incident['status']]; ?>">
                    <?php echo ucwords(str_replace('_', ' ', $incident['status'])); ?>
                </span>
            </div>
            
            <div class="detail-row">
                <strong>Category:</strong>
                <span><?php echo htmlspecialchars($incident['category_name'] ?? 'Not specified'); ?></span>
            </div>
            <div class="detail-row">
                <strong>Date of Incident:</strong>
                <span><?php echo date('F j, Y', strtotime($incident['incident_date'])); ?></span>
            </div>
            <?php if (!empty($incident['incident_time'])): ?>
            <div class="detail-row">
                <strong>Time:</strong>
                <span><?php echo date('g:i A', strtotime($incident['incident_time'])); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <strong>Location:</strong>
                <span><?php echo htmlspecialchars($incident['location']); ?></span>
            </div>
            <div class="detail-row">
                <strong>Submitted:</strong>
                <span><?php echo date('F j, Y g:i A', strtotime($incident['submission_date'])); ?></span>
            </div>
            <div class="detail-row">
                <strong>Assigned To:</strong>
                <span>
                    <?php 
                    if (!empty($incident['assigned_to_name'])) {
                        echo '<span class="assigned-badge"><i class="fas fa-user-check"></i> ' . htmlspecialchars($incident['assigned_to_name']) . '</span>';
                    } else {
                        echo '<span class="unassigned-badge"><i class="fas fa-user-clock"></i> Not Assigned</span>';
                    }
                    ?>
                </span>
            </div>
            <div class="detail-row full-width">
                <strong>Description:</strong>
                <div class="description-box">
                    <?php echo nl2br(htmlspecialchars($incident['description'])); ?>
                </div>
            </div>
        </div>
        
        <!-- Evidence Section -->
        <div class="detail-card">
            <div class="card-header">
                <h2><i class="fas fa-paperclip"></i> Evidence Files</h2>
                <span><?php echo count($evidence_files); ?> file(s)</span>
            </div>
            
            <?php if (count($evidence_files) > 0): ?>
                <div class="evidence-list">
                    <?php foreach ($evidence_files as $file): 
                        $file_path = '../' . $file['file_path'];
                    ?>
                    <div class="evidence-item">
                        <div class="evidence-icon">
                            <?php if ($file['file_type'] == 'image'): ?>
                                <i class="fas fa-image"></i>
                            <?php elseif ($file['file_type'] == 'audio'): ?>
                                <i class="fas fa-headphones"></i>
                            <?php elseif ($file['file_type'] == 'video'): ?>
                                <i class="fas fa-video"></i>
                            <?php else: ?>
                                <i class="fas fa-file-alt"></i>
                            <?php endif; ?>
                        </div>
                        <div class="evidence-details">
                            <div class="evidence-name"><?php echo htmlspecialchars($file['file_name']); ?></div>
                            <div class="evidence-meta">
                                Type: <?php echo ucfirst($file['file_type']); ?> | 
                                Size: <?php echo round($file['file_size'] / 1024, 2); ?> KB
                            </div>
                            <?php if ($file['file_type'] == 'image'): ?>
                                <div class="evidence-preview">
                                    <img src="<?php echo $file_path; ?>" alt="Preview" onclick="openImageModal('<?php echo $file_path; ?>')">
                                </div>
                            <?php elseif ($file['file_type'] == 'audio'): ?>
                                <div class="evidence-preview">
                                    <audio controls class="audio-player">
                                        <source src="<?php echo $file_path; ?>">
                                    </audio>
                                </div>
                            <?php elseif ($file['file_type'] == 'video'): ?>
                                <div class="evidence-preview">
                                    <video controls class="video-player" width="100%">
                                        <source src="<?php echo $file_path; ?>">
                                    </video>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="evidence-actions">
                            <a href="<?php echo $file_path; ?>" class="btn-download" download="<?php echo urlencode($file['file_name']); ?>">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-evidence">
                    <i class="fas fa-folder-open"></i>
                    <p>No evidence files uploaded for this case.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Case Notes -->
        <div class="detail-card">
            <div class="card-header">
                <h2><i class="fas fa-sticky-note"></i> Case Notes</h2>
            </div>
            
            <form method="POST" action="" class="add-note-form">
                <textarea name="note" rows="3" placeholder="Add a case note..." required></textarea>
                <div class="note-options">
                    <label>
                        <input type="checkbox" name="is_private" value="1"> Private note
                    </label>
                    <button type="submit" name="add_note" class="btn btn-sm btn-primary">Add Note</button>
                </div>
            </form>
            
            <?php if (count($notes) > 0): ?>
                <div class="notes-list">
                    <?php foreach ($notes as $note): ?>
                    <div class="note-item">
                        <div class="note-header">
                            <strong><?php echo htmlspecialchars($note['author_name'] ?? 'System'); ?></strong>
                            <small><?php echo date('F j, Y g:i A', strtotime($note['created_at'])); ?></small>
                        </div>
                        <div class="note-content">
                            <?php echo nl2br(htmlspecialchars($note['note'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-notes">No case notes yet. Add the first note above.</p>
            <?php endif; ?>
        </div>
        
        <!-- Actions Panel -->
        <div class="detail-card">
            <div class="card-header">
                <h2><i class="fas fa-cogs"></i> Actions</h2>
            </div>
            
            <div class="actions-panel">
                <!-- Update Status -->
                <form method="POST" action="" class="action-form">
                    <h3>Update Status</h3>
                    <select name="status">
                        <option value="pending" <?php echo $incident['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="under_investigation" <?php echo $incident['status'] == 'under_investigation' ? 'selected' : ''; ?>>Under Investigation</option>
                        <option value="resolved" <?php echo $incident['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $incident['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                    <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update Status</button>
                </form>
                
                <!-- Assign Case - FIXED: Shows all law enforcement officers -->
                <?php if (in_array($role, ['admin', 'law_enforcement', 'child_protection'])): ?>
                <form method="POST" action="" class="action-form" id="assignForm">
                    <h3>Assign To</h3>
                    <select name="assigned_to" id="assigned_to">
                        <option value="">-- Select Officer --</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $incident['assigned_to'] == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['full_name']) . ' (' . ucwords(str_replace('_', ' ', $user['role_name'])) . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="assign_case" class="btn btn-sm btn-primary">Assign Case</button>
                </form>
                <?php endif; ?>
                
                <!-- Back Button -->
                <a href="incidents.php" class="btn btn-secondary btn-block">
                    <i class="fas fa-arrow-left"></i> Back to Incidents
                </a>
            </div>
        </div>
    </div>
</main>

<!-- Image Modal -->
<div id="imageModal" class="modal">
    <span class="modal-close" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<style>
.incident-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 20px;
}

.detail-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
}

.card-header h2 {
    font-size: 1.2rem;
    margin: 0;
    color: var(--primary-color);
}

.detail-row {
    display: flex;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.detail-row strong {
    width: 140px;
    color: #666;
}

.detail-row.full-width {
    flex-direction: column;
}

.detail-row.full-width strong {
    margin-bottom: 10px;
}

.description-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    line-height: 1.6;
}

.assigned-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #e8f5e9;
    color: #388e3c;
    padding: 4px 12px;
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
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

/* Evidence List */
.evidence-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.evidence-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.evidence-icon {
    width: 50px;
    height: 50px;
    background: #e3f2fd;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.evidence-icon i {
    font-size: 24px;
    color: var(--secondary-color);
}

.evidence-details {
    flex: 1;
}

.evidence-name {
    font-weight: 500;
    margin-bottom: 5px;
    word-break: break-all;
}

.evidence-meta {
    font-size: 11px;
    color: #999;
    margin-bottom: 8px;
}

.evidence-preview {
    margin-top: 8px;
}

.evidence-preview img {
    max-width: 150px;
    max-height: 100px;
    border-radius: 5px;
    cursor: pointer;
}

.audio-player, .video-player {
    max-width: 300px;
}

.evidence-actions {
    display: flex;
    gap: 10px;
}

.btn-download {
    background: var(--secondary-color);
    color: white;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 12px;
    transition: background 0.2s;
}

.btn-download:hover {
    background: #2980b9;
}

.no-evidence {
    text-align: center;
    padding: 40px;
    color: #999;
}

.no-evidence i {
    font-size: 48px;
    margin-bottom: 15px;
}

/* Notes */
.add-note-form textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-family: inherit;
    resize: vertical;
    margin-bottom: 10px;
}

.note-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.notes-list {
    margin-top: 20px;
    max-height: 400px;
    overflow-y: auto;
}

.note-item {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 10px;
    border-left: 3px solid var(--secondary-color);
}

.note-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 12px;
    color: #666;
}

.actions-panel {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.action-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.action-form select {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.btn-block {
    width: 100%;
    text-align: center;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
}

.modal-content {
    max-width: 90%;
    max-height: 90%;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.modal-close {
    position: absolute;
    top: 15px;
    right: 35px;
    color: white;
    font-size: 40px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .incident-details-grid {
        grid-template-columns: 1fr;
    }
    
    .evidence-item {
        flex-direction: column;
        text-align: center;
    }
    
    .evidence-icon {
        width: 40px;
        height: 40px;
    }
    
    .evidence-actions {
        width: 100%;
    }
    
    .btn-download {
        width: 100%;
        text-align: center;
    }
    
    .audio-player, .video-player {
        max-width: 100%;
    }
}
</style>

<script>
function openImageModal(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    modal.style.display = 'block';
    modalImg.src = imageSrc;
}

function closeModal() {
    const modal = document.getElementById('imageModal');
    modal.style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('imageModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include '../includes/dashboard-footer.php'; ?>