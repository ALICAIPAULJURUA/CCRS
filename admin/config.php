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

// Handle configuration updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'config_') === 0) {
            $config_key = substr($key, 7);
            $config_value = sanitizeInput($value);
            
            $stmt = $db->prepare("
                UPDATE system_config 
                SET config_value = ?, updated_by = ?, updated_at = NOW()
                WHERE config_key = ?
            ");
            if ($stmt->execute([$config_value, $_SESSION['user_id'], $config_key])) {
                $message = "Configuration updated successfully!";
                logAudit($_SESSION['user_id'], 'CONFIG_UPDATED', 'system_config', null, null, $config_key);
            } else {
                $error = "Error updating configuration.";
            }
        }
    }
}

// Get all configuration settings
$configs = $db->query("SELECT * FROM system_config ORDER BY config_key")->fetchAll();

include '../includes/dashboard-header.php';
include '../includes/dashboard-sidebar.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>System Configuration</h1>
        <p>Manage system settings and preferences</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" class="config-form">
        <div class="recent-section">
            <h2>General Settings</h2>
            
            <?php foreach ($configs as $config): ?>
            <div class="form-group">
                <label for="config_<?php echo $config['config_key']; ?>">
                    <?php echo ucwords(str_replace('_', ' ', $config['config_key'])); ?>
                </label>
                
                <?php if (strpos($config['config_key'], 'email') !== false): ?>
                <input type="email" 
                       name="config_<?php echo $config['config_key']; ?>" 
                       id="config_<?php echo $config['config_key']; ?>"
                       value="<?php echo htmlspecialchars($config['config_value']); ?>"
                       class="form-control">
                       
                <?php elseif (strpos($config['config_key'], 'phone') !== false): ?>
                <input type="tel" 
                       name="config_<?php echo $config['config_key']; ?>" 
                       id="config_<?php echo $config['config_key']; ?>"
                       value="<?php echo htmlspecialchars($config['config_value']); ?>"
                       class="form-control">
                       
                <?php elseif (strpos($config['config_key'], 'max_file_size') !== false): ?>
                <input type="number" 
                       name="config_<?php echo $config['config_key']; ?>" 
                       id="config_<?php echo $config['config_key']; ?>"
                       value="<?php echo htmlspecialchars($config['config_value']); ?>"
                       class="form-control">
                <small>Size in bytes (1MB = 104857600 bytes)</small>
                
                <?php elseif (strpos($config['config_key'], 'session_timeout') !== false): ?>
                <input type="number" 
                       name="config_<?php echo $config['config_key']; ?>" 
                       id="config_<?php echo $config['config_key']; ?>"
                       value="<?php echo htmlspecialchars($config['config_value']); ?>"
                       class="form-control">
                <small>Timeout in seconds</small>
                
                <?php else: ?>
                <input type="text" 
                       name="config_<?php echo $config['config_key']; ?>" 
                       id="config_<?php echo $config['config_key']; ?>"
                       value="<?php echo htmlspecialchars($config['config_value']); ?>"
                       class="form-control">
                <?php endif; ?>
                
                <?php if ($config['description']): ?>
                <small class="form-hint"><?php echo htmlspecialchars($config['description']); ?></small>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="recent-section">
            <h2>System Information</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <strong>PHP Version:</strong>
                    <span><?php echo phpversion(); ?></span>
                </div>
                <div class="info-item">
                    <strong>MySQL Version:</strong>
                    <span><?php echo $db->getAttribute(PDO::ATTR_SERVER_VERSION); ?></span>
                </div>
                <div class="info-item">
                    <strong>Server Software:</strong>
                    <span><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                </div>
                <div class="info-item">
                    <strong>Upload Max Size:</strong>
                    <span><?php echo ini_get('upload_max_filesize'); ?></span>
                </div>
                <div class="info-item">
                    <strong>Post Max Size:</strong>
                    <span><?php echo ini_get('post_max_size'); ?></span>
                </div>
                <div class="info-item">
                    <strong>Memory Limit:</strong>
                    <span><?php echo ini_get('memory_limit'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="recent-section">
            <h2>Database Statistics</h2>
            
            <?php
            // Get table sizes
            $tables = $db->query("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
            ")->fetchAll();
            ?>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Size (MB)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $table): ?>
                        <tr>
                            <td><?php echo $table['table_name']; ?></td>
                            <td><?php echo $table['size_mb']; ?> MB</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Configuration</button>
            <button type="button" onclick="confirmReset()" class="btn btn-danger">Reset to Defaults</button>
        </div>
    </form>
</main>

<script>
function confirmReset() {
    if (confirm('WARNING: This will reset all configuration to default values. Are you sure?')) {
        window.location.href = 'config-reset.php';
    }
}
</script>

<style>
.config-form {
    max-width: 100%;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.info-item {
    padding: 12px;
    background: #f8f9fa;
    border-radius: 5px;
    display: flex;
    justify-content: space-between;
}

.info-item strong {
    color: var(--primary-color);
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}
</style>

<?php include '../includes/dashboard-footer.php'; ?>