<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../report/report-form.php');
}

// Rate limiting
if (!checkRateLimit($_SERVER['REMOTE_ADDR'])) {
    redirect('../report/report-form.php', 'Too many submissions from your location. Please try again later.', 'error');
}

// Ensure upload directory exists
$upload_dir = __DIR__ . '/../uploads/evidence/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();
    
    // Generate tracking ID
    $tracking_id = generateTrackingID();
    
    // Validate and sanitize inputs
    $category_id = filter_input(INPUT_POST, 'category', FILTER_VALIDATE_INT);
    $incident_date = sanitizeInput($_POST['incident_date'] ?? '');
    $incident_time = sanitizeInput($_POST['incident_time'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $consent_counselor = isset($_POST['consent_counselor']) ? 1 : 0;
    
    // Optional contact info
    $contact_name = sanitizeInput($_POST['contact_name'] ?? '');
    $contact_phone = sanitizeInput($_POST['contact_phone'] ?? '');
    $contact_email = sanitizeInput($_POST['contact_email'] ?? '');
    $contact_preference = isset($_POST['contact_preference']) ? 1 : 0;
    
    // Validate required fields
    if (!$category_id || !$incident_date || !$location || !$description) {
        throw new Exception('Please fill in all required fields.');
    }
    
    // Insert report
    $stmt = $db->prepare("
        INSERT INTO reports (
            tracking_id, category_id, description, location, 
            incident_date, incident_time, consent_counselor, 
            contact_name, contact_phone, contact_email, contact_preference,
            ip_address, user_agent, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $tracking_id,
        $category_id,
        $description,
        $location,
        $incident_date,
        $incident_time,
        $consent_counselor,
        $contact_name,
        $contact_phone,
        $contact_email,
        $contact_preference,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
    
    $report_id = $db->lastInsertId();
    
    // Handle file uploads
    $uploaded_files = [];
    if (isset($_FILES['evidence']) && !empty($_FILES['evidence']['name'][0])) {
        // Define allowed types
        $allowed_images = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowed_audio = ['mp3', 'wav', 'ogg', 'm4a'];
        $allowed_video = ['mp4', 'webm', 'avi', 'mov'];
        $allowed_docs = ['pdf', 'doc', 'docx', 'txt'];
        $all_allowed = array_merge($allowed_images, $allowed_audio, $allowed_video, $allowed_docs);
        
        foreach ($_FILES['evidence']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['evidence']['error'][$key] === UPLOAD_ERR_OK) {
                $original_name = $_FILES['evidence']['name'][$key];
                $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                $file_size = $_FILES['evidence']['size'][$key];
                $file_type_raw = $_FILES['evidence']['type'][$key];
                
                // Validate file extension
                if (!in_array($file_extension, $all_allowed)) {
                    continue; // Skip invalid files
                }
                
                // Determine file type category
                if (in_array($file_extension, $allowed_images)) {
                    $file_type = 'image';
                } elseif (in_array($file_extension, $allowed_audio)) {
                    $file_type = 'audio';
                } elseif (in_array($file_extension, $allowed_video)) {
                    $file_type = 'video';
                } else {
                    $file_type = 'document';
                }
                
                // Generate unique filename
                $new_filename = uniqid() . '_' . $report_id . '.' . $file_extension;
                $target_file = $upload_dir . $new_filename;
                
                // Move uploaded file
                if (move_uploaded_file($tmp_name, $target_file)) {
                    // Set proper permissions
                    chmod($target_file, 0644);
                    
                    // Insert into database
                    $stmt = $db->prepare("
                        INSERT INTO evidence_files 
                        (report_id, file_name, file_path, file_type, file_size, mime_type) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $report_id,
                        $original_name,
                        'uploads/evidence/' . $new_filename,
                        $file_type,
                        $file_size,
                        $file_type_raw
                    ]);
                    
                    $uploaded_files[] = $original_name;
                }
            }
        }
    }
    
    // Create counseling session if requested
    if ($consent_counselor) {
        $stmt = $db->prepare("
            INSERT INTO counseling_sessions 
            (report_id, survivor_identifier, status) 
            VALUES (?, ?, 'requested')
        ");
        $survivor_id = 'SURV-' . substr($tracking_id, -8);
        $stmt->execute([$report_id, $survivor_id]);
    }
    
    // Log the submission
    logAudit(null, 'REPORT_SUBMITTED', 'reports', $report_id);
    
    $db->commit();
    
    // Store tracking ID in session for confirmation page
    $_SESSION['last_tracking_id'] = $tracking_id;
    $_SESSION['uploaded_files_count'] = count($uploaded_files);
    
    $message = 'Your report has been submitted successfully!';
    if (count($uploaded_files) > 0) {
        $message .= ' ' . count($uploaded_files) . ' file(s) uploaded.';
    }
    
    redirect('confirmation.php', $message, 'success');
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    error_log("Report submission error: " . $e->getMessage());
    redirect('../report/report-form.php', 'An error occurred: ' . $e->getMessage(), 'error');
}
?>