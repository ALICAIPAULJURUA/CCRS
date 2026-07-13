<?php
require_once '../config/database.php';
require_once '../config/functions.php';

$page_title = "Report an Incident";
$page_description = "Fill out the form below to report an incident. All information is kept confidential and anonymous. Your identity will remain protected.";

// Get categories from database
$db = Database::getInstance()->getConnection();
$categories = $db->query("SELECT * FROM report_categories WHERE is_active = 1 ORDER BY category_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report a Case - Community Crime Reporting System | Muni University</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="apple-touch-icon" href="../assets/images/logo.png">
    <style>
        .report-page {
            padding: 0 0 3rem 0;
            background-color: #f5f5f5;
            min-height: calc(100vh - 300px);
        }
        
        .report-form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .safety-tips {
            max-width: 800px;
            margin: 2rem auto 0;
            background: #fff3cd;
            border-radius: 10px;
            padding: 1.5rem;
            border-left: 4px solid var(--warning-color);
        }
        
        .safety-tips h3 {
            margin-bottom: 0.5rem;
            color: #856404;
        }
        
        .safety-tips ul {
            margin-left: 1.5rem;
        }
        
        .safety-tips li {
            margin-bottom: 0.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .file-list {
            margin-top: 10px;
        }
        
        .file-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 5px;
            margin-right: 10px;
            margin-bottom: 5px;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="report-page">
        <!-- Page Header Banner -->
        <div class="page-header-banner">
            <div class="container">
                <h1><?php echo $page_title; ?></h1>
                <p><?php echo $page_description; ?></p>
            </div>
        </div>
        
        <div class="container">
            <?php echo displayFlash(); ?>
            
            <div class="report-form-container">
                <form action="submit-report.php" method="POST" enctype="multipart/form-data" id="reportForm">
                    <div class="form-section">
                        <h2>Incident Details</h2>
                        
                        <div class="form-group">
                            <label for="category">Incident Type *</label>
                            <select name="category" id="category" required>
                                <option value="">-- Select Incident Type --</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="incident_date">Date of Incident *</label>
                                <input type="date" name="incident_date" id="incident_date" max="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="incident_time">Time of Incident (approximate)</label>
                                <input type="time" name="incident_time" id="incident_time">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Location *</label>
                            <input type="text" name="location" id="location" placeholder="e.g. Winners Hostel, Ocholin, Muni University, Arua City, Uganday" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description of Incident *</label>
                            <textarea name="description" id="description" rows="6" placeholder="Please provide as much detail as possible..." required></textarea>
                            <small class="form-hint">Include what happened, who was involved, and any other relevant details.</small>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2>Optional Contact Information</h2>
                        <p class="section-note">This information is optional and will be kept confidential. It helps us follow up if needed.</p>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_name">Your Name (Optional)</label>
                                <input type="text" name="contact_name" id="contact_name" placeholder="Enter your name">
                                <small class="form-hint">This will only be used for follow-up purposes</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_phone">Phone Number (Optional)</label>
                                <input type="tel" name="contact_phone" id="contact_phone" placeholder="e.g., 07XX XXX XXX">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_email">Email Address (Optional)</label>
                            <input type="email" name="contact_email" id="contact_email" placeholder="Enter your email address">
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="contact_preference" value="1">
                                I prefer to be contacted for follow-up
                            </label>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Privacy Note:</strong> Your contact information will only be used by authorized officers to follow up on your report.
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2>Evidence (Optional)</h2>
                        <p class="section-note">You can upload photos, audio recordings, videos, or documents as evidence. Maximum file size: 10MB per file</p>
                        
                        <div class="form-group">
                            <label for="evidence">Upload Files</label>
                            <input type="file" name="evidence[]" id="evidence" multiple 
                                   accept=".jpg,.jpeg,.png,.gif,.webp,.mp3,.wav,.ogg,.m4a,.mp4,.webm,.avi,.mov,.pdf,.doc,.docx,.txt">
                            <div id="fileList" class="file-list"></div>
                            <small class="form-hint">
                                Accepted formats: Images (JPG, PNG, GIF, WEBP), Audio (MP3, WAV, OGG), 
                                Video (MP4, WEBM), Documents (PDF, DOC, DOCX, TXT)
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2>Support Options</h2>
                        
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="consent_counselor" value="1">
                                I would like to be contacted by a counselor (anonymously)
                            </label>
                            <small class="form-hint">Check this if you want professional counseling support. Your identity remains anonymous.</small>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2>Confirmation</h2>
                        
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="confirm_truth" required>
                                I confirm that the information provided is true to the best of my knowledge *
                            </label>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="confirm_anonymous" required>
                                I understand that my report is anonymous and I will not be identified *
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            <i class="fas fa-paper-plane"></i> Submit Report
                        </button>
                        <button type="reset" class="btn btn-secondary">Reset Form</button>
                    </div>
                </form>
            </div>
            
            <div class="safety-tips">
                <h3><i class="fas fa-shield-alt"></i> Safety Tips</h3>
                <ul>
                    <li>Use a private or incognito browser window for extra privacy</li>
                    <li>Clear your browser history after submitting</li>
                    <li>Save your tracking ID in a safe place - it's the only way to check your report</li>
                    <li>If you're in danger, contact emergency services immediately</li>
                </ul>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // File upload preview
        document.getElementById('evidence').addEventListener('change', function(e) {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';
            
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <i class="fas fa-${getFileIcon(file.type)}"></i>
                    <span>${file.name.substring(0, 30)}${file.name.length > 30 ? '...' : ''}</span>
                    <small>(${formatFileSize(file.size)})</small>
                `;
                fileList.appendChild(fileItem);
            }
        });
        
        function getFileIcon(mimeType) {
            if (mimeType.startsWith('image/')) return 'image';
            if (mimeType.startsWith('audio/')) return 'music';
            if (mimeType.startsWith('video/')) return 'video';
            return 'file';
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Form submission spinner
        document.getElementById('reportForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        });
    </script>
</body>
</html>