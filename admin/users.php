<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Check if user is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../auth/login.php', 'Access denied. Admin privileges required.', 'error');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // CREATE USER
    if ($_POST['action'] === 'create') {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $full_name = sanitizeInput($_POST['full_name']);
        $role_id = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
        $password = generateRandomPassword(8);
        
        $role_stmt = $db->prepare("SELECT role_name FROM roles WHERE id = ?");
        $role_stmt->execute([$role_id]);
        $role = $role_stmt->fetch();
        
        if ($username && $email && $full_name && $role_id && $password) {
            $check = $db->prepare("SELECT id FROM system_users WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);
            if ($check->fetch()) {
                $error = "Username or email already exists.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $db->prepare("
                        INSERT INTO system_users (username, password_hash, email, full_name, role_id, is_active, password_change_required)
                        VALUES (?, ?, ?, ?, ?, 1, 1)
                    ");
                    $stmt->execute([$username, $hash, $email, $full_name, $role_id]);
                    $user_id = $db->lastInsertId();
                    
                    $email_sent = sendWelcomeEmail($email, $username, $password, $full_name, $role['role_name']);
                    
                    if ($email_sent) {
                        $message = "User created successfully! Login credentials have been sent to {$email}.";
                    } else {
                        $creds_display = '
                        <div style="background: #fff3cd; border: 1px solid #ffeeba; padding: 15px; margin-top: 10px; border-radius: 5px;">
                            <strong><i class="fas fa-exclamation-triangle"></i> Email could not be sent.</strong><br>
                            Please provide these credentials manually:<br><br>
                            <strong>Username:</strong> ' . $username . '<br>
                            <strong>Password:</strong> ' . $password . '<br>
                        </div>';
                        $message = "User created successfully! " . $creds_display;
                    }
                    
                    logAudit($_SESSION['user_id'], 'USER_CREATED', 'system_users', $user_id);
                } catch (Exception $e) {
                    $error = "Error creating user: " . $e->getMessage();
                }
            }
        } else {
            $error = "Please fill in all required fields.";
        }
    } 
    
    // TOGGLE USER STATUS
    elseif ($_POST['action'] === 'toggle_status') {
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $current_status = $_POST['current_status'];
        $new_status = $current_status == '1' ? '0' : '1';
        
        $stmt = $db->prepare("UPDATE system_users SET is_active = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $user_id])) {
            $message = "User status updated successfully!";
            logAudit($_SESSION['user_id'], 'USER_STATUS_TOGGLED', 'system_users', $user_id);
        } else {
            $error = "Error updating user status.";
        }
    } 
    
    // RESET USER PASSWORD
    elseif ($_POST['action'] === 'reset_password') {
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $new_password = generateRandomPassword(8);
        
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE system_users SET password_hash = ?, password_change_required = 1 WHERE id = ?");
        
        $user_stmt = $db->prepare("SELECT email, username, full_name FROM system_users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch();
        
        if ($stmt->execute([$hash, $user_id])) {
            $email_sent = sendPasswordResetEmail($user['email'], $user['username'], $new_password);
            
            if ($email_sent) {
                $message = "Password reset successfully! New credentials sent to {$user['email']}.";
            } else {
                $message = "Password reset but email could not be sent. New password: {$new_password}";
            }
            logAudit($_SESSION['user_id'], 'PASSWORD_RESET', 'system_users', $user_id);
        } else {
            $error = "Error resetting password.";
        }
    } 
    
    // DELETE USER - FIXED WITH PROPER REDIRECT
    elseif ($_POST['action'] === 'delete_user') {
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        
        // Prevent admin from deleting themselves
        if ($user_id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } else {
            // Check if user exists
            $check = $db->prepare("SELECT id, username, email, full_name FROM system_users WHERE id = ?");
            $check->execute([$user_id]);
            $user = $check->fetch();
            
            if (!$user) {
                $error = "User not found.";
            } else {
                try {
                    // Start transaction
                    $db->beginTransaction();
                    
                    // 1. Delete from password_resets
                    $stmt = $db->prepare("DELETE FROM password_resets WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 2. Delete notifications
                    $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 3. Delete audit_logs
                    $stmt = $db->prepare("DELETE FROM audit_logs WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 4. Update case_notes to set user_id to NULL
                    $stmt = $db->prepare("UPDATE case_notes SET user_id = NULL WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 5. Update reports to set assigned_to to NULL
                    $stmt = $db->prepare("UPDATE reports SET assigned_to = NULL WHERE assigned_to = ?");
                    $stmt->execute([$user_id]);
                    
                    // 6. Update status_history to set changed_by to NULL
                    $stmt = $db->prepare("UPDATE status_history SET changed_by = NULL WHERE changed_by = ?");
                    $stmt->execute([$user_id]);
                    
                    // 7. Update counseling_sessions to set counselor_id to NULL
                    $stmt = $db->prepare("UPDATE counseling_sessions SET counselor_id = NULL WHERE counselor_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 8. Finally, delete the user
                    $stmt = $db->prepare("DELETE FROM system_users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Commit transaction
                    $db->commit();
                    
                    logAudit($_SESSION['user_id'], 'USER_DELETED', 'system_users', $user_id, null, $user['username']);
                    
                    // For AJAX requests, send JSON response
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
                        exit;
                    } else {
                        $message = "User '" . htmlspecialchars($user['username']) . "' has been deleted successfully.";
                        // Force redirect to refresh the page
                        echo "<script>window.location.href = 'users.php';</script>";
                        exit;
                    }
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = "Error deleting user: " . $e->getMessage();
                    error_log("Delete user error: " . $e->getMessage());
                }
            }
        }
    }
}

// Get all users with roles
$users = $db->query("
    SELECT u.*, r.role_name 
    FROM system_users u
    JOIN roles r ON u.role_id = r.id
    ORDER BY u.created_at DESC
")->fetchAll();

// Get roles for dropdown
$roles = $db->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();

include '../includes/dashboard-header.php';
include '../includes/dashboard-sidebar.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>User Management</h1>
        <p>Create, edit, and manage system users. Credentials are sent automatically via email.</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Create User Form -->
    <div class="recent-section">
        <h2>Create New User</h2>
        <form method="POST" action="" class="user-form">
            <input type="hidden" name="action" value="create">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                    <small>Credentials will be sent to this email</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role_id" required>
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $role['role_name'])); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Note:</strong> A secure password will be auto-generated and sent to the user's email address. 
                The user will be required to change their password upon first login.
            </div>
            
            <button type="submit" class="btn btn-primary">Create User & Send Email</button>
        </form>
    </div>
    
    <!-- Users List -->
    <div class="recent-section">
        <h2>System Users (<?php echo count($users); ?>)</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Password Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr id="user-row-<?php echo $user['id']; ?>">
                        <td data-label="ID"><?php echo $user['id']; ?></td>
                        <td data-label="Username"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td data-label="Full Name"><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td data-label="Role"><?php echo ucwords(str_replace('_', ' ', $user['role_name'])); ?></td>
                        <td data-label="Status">
                            <span class="status-badge <?php echo $user['is_active'] ? 'status-success' : 'status-warning'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td data-label="Password">
                            <?php if ($user['password_change_required']): ?>
                                <span class="status-badge status-warning">Change Required</span>
                            <?php else: ?>
                                <span class="status-badge status-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Last Login"><?php echo $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?></td>
                        <td data-label="Actions">
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="current_status" value="<?php echo $user['is_active']; ?>">
                                <button type="submit" class="btn btn-sm <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                    <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            
                            <form method="POST" style="display: inline-block;" 
                                  onsubmit="return confirm('Reset password for <?php echo htmlspecialchars($user['username']); ?>? New credentials will be sent to their email.')">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary">
                                    Reset Password
                                </button>
                            </form>
                            
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <button type="button" class="btn btn-sm btn-danger delete-user-btn" 
                                    data-user-id="<?php echo $user['id']; ?>" 
                                    data-user-name="<?php echo htmlspecialchars($user['username']); ?>">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            <?php endif; ?>
                         </td
                      </tr>
                    <?php endforeach; ?>
                </tbody>
              </table>
        </div>
    </div>
</main>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Delete User</h2>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="warning-message">
                <p><strong>Warning!</strong> You are about to permanently delete this user account.</p>
                <p class="user-details" id="deleteUserInfo"></p>
                <div class="warning-box">
                    <i class="fas fa-info-circle"></i>
                    <span>This action cannot be undone. All data associated with this user will be permanently removed from the system.</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="btn-confirm" id="confirmDeleteBtn">
                <i class="fas fa-trash"></i> Yes, Delete User
            </button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Deleting user...</p>
    </div>
</div>

<style>
/* Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    z-index: 10000;
    backdrop-filter: blur(4px);
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-container {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    background: linear-gradient(135deg, #c0392b, #e74c3c);
    color: white;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
}

.modal-icon {
    background: rgba(255, 255, 255, 0.2);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-icon i {
    font-size: 24px;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 600;
    flex: 1;
}

.modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.modal-body {
    padding: 24px;
}

.warning-message p {
    margin-bottom: 16px;
    font-size: 1rem;
    color: #333;
}

.user-details {
    background: #f8f9fa;
    padding: 12px 15px;
    border-radius: 8px;
    font-weight: 500;
    color: #2c3e50;
    border-left: 3px solid #e74c3c;
}

.warning-box {
    background: #fff3cd;
    border-radius: 8px;
    padding: 15px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
    margin-top: 15px;
}

.warning-box i {
    color: #856404;
    font-size: 18px;
}

.warning-box span {
    color: #856404;
    font-size: 0.85rem;
    line-height: 1.4;
}

.modal-footer {
    padding: 16px 24px;
    background: #f8f9fa;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    border-top: 1px solid #e0e0e0;
}

.btn-cancel {
    background: #6c757d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-cancel:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.btn-confirm {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-confirm:hover {
    background: #c0392b;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 10001;
    backdrop-filter: blur(5px);
    display: flex;
    justify-content: center;
    align-items: center;
}

.loading-spinner {
    background: white;
    padding: 30px 40px;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.loading-spinner i {
    font-size: 48px;
    color: var(--secondary-color);
    margin-bottom: 15px;
}

.loading-spinner p {
    margin: 0;
    font-size: 1rem;
    color: #333;
}

/* Toast Message */
.toast-message {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #27ae60;
    color: white;
    padding: 14px 24px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 10002;
    animation: slideInRight 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.toast-message.error {
    background: #e74c3c;
}

.toast-message i {
    font-size: 20px;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Form Styles */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 15px;
}

.user-form {
    margin-bottom: 20px;
}

.btn-warning {
    background: #f39c12;
    color: white;
}

.btn-warning:hover {
    background: #e67e22;
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-container {
        width: 95%;
        margin: 20px;
    }
    
    .modal-footer {
        flex-direction: column;
    }
    
    .btn-cancel, .btn-confirm {
        width: 100%;
        justify-content: center;
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

<script>
let userIdToDelete = null;
let usernameToDelete = null;

// Attach event listeners to all delete buttons
document.addEventListener('DOMContentLoaded', function() {
    // Get all delete buttons
    const deleteButtons = document.querySelectorAll('.delete-user-btn');
    
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            showDeleteModal(userId, userName);
        });
    });
    
    // Confirm delete button
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', confirmDelete);
    }
    
    // Close modal on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeDeleteModal();
        }
    });
    
    // Close modal when clicking outside
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeDeleteModal();
            }
        });
    }
});

function showDeleteModal(userId, username) {
    userIdToDelete = userId;
    usernameToDelete = username;
    
    const modal = document.getElementById('deleteModal');
    const userInfo = document.getElementById('deleteUserInfo');
    userInfo.innerHTML = `<strong>User:</strong> ${username} (ID: ${userId})`;
    
    modal.style.display = 'flex';
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'none';
}

function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

function confirmDelete() {
    if (!userIdToDelete) {
        showToast('No user selected for deletion.', true);
        return;
    }
    
    showLoading();
    closeDeleteModal();
    
    // Create form data for POST request
    const formData = new FormData();
    formData.append('action', 'delete_user');
    formData.append('user_id', userIdToDelete);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showToast('User ' + usernameToDelete + ' deleted successfully!', false);
            // Reload the page after 1 second
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.message || 'An error occurred. Please try again.', true);
        }
    })
    .catch(error => {
        hideLoading();
        showToast('An error occurred. Please try again.', true);
        console.error('Error:', error);
    });
}

function showToast(message, isError = false) {
    // Remove any existing toasts
    const existingToasts = document.querySelectorAll('.toast-message');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `toast-message ${isError ? 'error' : ''}`;
    toast.innerHTML = `
        <i class="fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100px)';
        setTimeout(() => {
            if (toast.parentNode) toast.remove();
        }, 300);
    }, 3000);
}
</script>

<?php include '../includes/dashboard-footer.php'; ?>