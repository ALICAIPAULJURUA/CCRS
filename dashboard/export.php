<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Check authentication
if (!isLoggedIn()) {
    redirect('../auth/login.php', 'Please login to access this page.', 'error');
}

$db = Database::getInstance()->getConnection();
$role = $_SESSION['role'];

// Check if user has export permission
if (!in_array($role, ['admin', 'law_enforcement', 'child_protection', 'ngo', 'lc1'])) {
    redirect('index.php', 'You do not have permission to export data.', 'error');
}

$message = '';
$error = '';

// Handle export request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    $format = $_POST['format'] ?? 'csv';
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    $category = $_POST['category'] ?? '';
    $status = $_POST['status'] ?? '';
    
    // Build query
    $sql = "
        SELECT 
            r.tracking_id,
            rc.category_name,
            r.description,
            r.location,
            r.incident_date,
            r.submission_date,
            r.status,
            r.consent_counselor,
            (SELECT COUNT(*) FROM evidence_files WHERE report_id = r.id) as evidence_count
        FROM reports r
        LEFT JOIN report_categories rc ON r.category_id = rc.id
        WHERE 1=1
    ";
    $params = [];
    
    if ($date_from) {
        $sql .= " AND DATE(r.submission_date) >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $sql .= " AND DATE(r.submission_date) <= ?";
        $params[] = $date_to;
    }
    
    if ($category) {
        $sql .= " AND r.category_id = ?";
        $params[] = $category;
    }
    
    if ($status) {
        $sql .= " AND r.status = ?";
        $params[] = $status;
    }
    
    // For non-admin users, anonymize location data
    if (!in_array($role, ['admin', 'law_enforcement', 'child_protection'])) {
        $sql = str_replace('r.location', "'[REDACTED]' as location", $sql);
    }
    
    $sql .= " ORDER BY r.submission_date DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    if (count($data) > 0) {
        if ($format === 'csv') {
            exportCSV($data);
        } elseif ($format === 'excel') {
            exportExcel($data);
        } elseif ($format === 'pdf') {
            exportPDF($data);
        }
    } else {
        $error = "No data found for the selected criteria.";
    }
}

function exportCSV($data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="ccrs_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit();
}

function exportExcel($data) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="ccrs_export_' . date('Y-m-d') . '.xls"');
    
    echo '<table border="1">';
    
    // Headers
    if (!empty($data)) {
        echo '<tr>';
        foreach (array_keys($data[0]) as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
        
        // Data
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $value) {
                echo '<td>' . htmlspecialchars($value) . '</td>';
            }
            echo '</tr>';
        }
    }
    
    echo '</table>';
    exit();
}

function exportPDF($data) {
    // Note: For PDF export, you would need a library like TCPDF or FPDF
    // For now, redirect to CSV as fallback
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="ccrs_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit();
}

// Get categories for filter
$categories = $db->query("SELECT id, category_name FROM report_categories")->fetchAll();

include '../includes/dashboard-header.php';
include '../includes/dashboard-sidebar.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Export Reports</h1>
        <p>Export report data in various formats</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="recent-section">
        <h2>Export Options</h2>
        
        <form method="POST" action="" class="export-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Date Range (From)</label>
                    <input type="date" name="date_from" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Date Range (To)</label>
                    <input type="date" name="date_to" class="form-control">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="under_investigation">Under Investigation</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Export Format</label>
                    <select name="format" class="form-control" required>
                        <option value="csv">CSV (Comma Separated Values)</option>
                        <option value="excel">Excel (XLS)</option>
                        <option value="pdf">PDF (via CSV fallback)</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="export" class="btn btn-primary">
                    <i class="fas fa-download"></i> Export Data
                </button>
            </div>
        </form>
    </div>
    
    <div class="recent-section">
        <h2>Export Guidelines</h2>
        <div class="guidelines">
            <h3>Data Privacy Notice</h3>
            <p>When exporting data, please ensure:</p>
            <ul>
                <li>Exports are only used for official purposes</li>
                <li>Data is stored securely and not shared inappropriately</li>
                <li>Personal information is protected according to data protection guidelines</li>
                <li>Exported files are deleted when no longer needed</li>
            </ul>
            
            <h3>Available Data</h3>
            <p>The export includes the following information:</p>
            <ul>
                <li>Tracking ID</li>
                <li>Incident Category</li>
                <li>Description</li>
                <li>Location (anonymized for non-law enforcement roles)</li>
                <li>Incident Date</li>
                <li>Submission Date</li>
                <li>Case Status</li>
                <li>Counseling Consent Status</li>
                <li>Number of Evidence Files</li>
            </ul>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Note:</strong> For security reasons, evidence files are not included in exports. Contact the system administrator if you need access to specific evidence.
            </div>
        </div>
    </div>
</main>

<style>
.export-form {
    max-width: 100%;
}

.guidelines {
    line-height: 1.6;
}

.guidelines h3 {
    margin-top: 20px;
    margin-bottom: 10px;
    font-size: 1.1rem;
    color: var(--primary-color);
}

.guidelines ul {
    margin-left: 20px;
    margin-bottom: 15px;
}

.guidelines li {
    margin-bottom: 5px;
}
</style>

<?php include '../includes/dashboard-footer.php'; ?>