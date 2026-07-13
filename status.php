<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$tracking_id = $_GET['tracking_id'] ?? '';

if (!$tracking_id) {
    redirect('check-status.php', 'Please enter a tracking ID.', 'error');
}

$db = Database::getInstance()->getConnection();

// Get report details
$stmt = $db->prepare("
    SELECT r.*, rc.category_name,
           (SELECT COUNT(*) FROM evidence_files WHERE report_id = r.id) as evidence_count
    FROM reports r
    LEFT JOIN report_categories rc ON r.category_id = rc.id
    WHERE r.tracking_id = ?
");
$stmt->execute([$tracking_id]);
$report = $stmt->fetch();

if (!$report) {
    redirect('check-status.php', 'No report found with this tracking ID.', 'error');
}

// Get evidence files
$stmt = $db->prepare("SELECT * FROM evidence_files WHERE report_id = ?");
$stmt->execute([$report['id']]);
$evidence_files = $stmt->fetchAll();

// Get or create counseling session
$stmt = $db->prepare("
    SELECT * FROM counseling_sessions 
    WHERE report_id = ? AND status != 'cancelled'
");
$stmt->execute([$report['id']]);
$counseling = $stmt->fetch();

// Handle sending message from reporter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = sanitizeInput($_POST['message']);
    
    if (!empty($message) && $counseling && $counseling['status'] == 'active') {
        $stmt = $db->prepare("
            INSERT INTO counseling_messages (session_id, sender_type, message, sent_at)
            VALUES (?, 'survivor', ?, NOW())
        ");
        $stmt->execute([$counseling['id'], $message]);
        
        createNotification($counseling['counselor_id'], 'New Message', 'A survivor has sent you a message.', 'info', "../counselor/messages.php?session={$counseling['id']}");
        
        redirect("status.php?tracking_id=$tracking_id&msg_sent=1", 'Message sent successfully.', 'success');
    }
}

// Mark messages as read
if ($counseling) {
    $stmt = $db->prepare("
        UPDATE counseling_messages 
        SET is_read = 1 
        WHERE session_id = ? AND sender_type = 'counselor' AND is_read = 0
    ");
    $stmt->execute([$counseling['id']]);
}

// Get messages
$messages = [];
if ($counseling && $counseling['status'] == 'active') {
    $stmt = $db->prepare("
        SELECT * FROM counseling_messages 
        WHERE session_id = ?
        ORDER BY sent_at ASC
    ");
    $stmt->execute([$counseling['id']]);
    $messages = $stmt->fetchAll();
}

$status_colors = [
    'pending' => 'warning',
    'under_investigation' => 'info',
    'resolved' => 'success',
    'closed' => 'secondary'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Status - CCRS</title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="assets/images/logo.png">
    <style>
        .status-page { padding: 3rem 0; background-color: #f5f5f5; min-height: calc(100vh - 300px); }
        .status-container { max-width: 900px; margin: 0 auto; }
        .status-card { background: white; border-radius: 10px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .status-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; gap: 1rem; }
        .status-badge { padding: 0.5rem 1rem; border-radius: 20px; font-weight: 500; text-transform: capitalize; }
        .status-warning { background: #fff3e0; color: #f57c00; }
        .status-info { background: #e3f2fd; color: #1976d2; }
        .status-success { background: #e8f5e9; color: #388e3c; }
        .detail-row { display: flex; padding: 0.5rem 0; flex-wrap: wrap; }
        .detail-row strong { min-width: 120px; color: #666; }
        .description-text { background: var(--light-color); padding: 1rem; border-radius: 5px; margin-top: 0.5rem; line-height: 1.8; }
        
        /* Evidence Gallery */
        .evidence-section { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e0e0e0; }
        .evidence-title { font-size: 1rem; margin-bottom: 1rem; color: var(--primary-color); }
        .evidence-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; }
        .evidence-item { background: #f8f9fa; border-radius: 8px; padding: 10px; text-align: center; transition: transform 0.2s; }
        .evidence-item:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .evidence-preview { width: 100%; height: 100px; overflow: hidden; border-radius: 5px; margin-bottom: 8px; }
        .evidence-preview img, .evidence-preview video { width: 100%; height: 100%; object-fit: cover; }
        .evidence-preview i { font-size: 2rem; line-height: 100px; color: var(--primary-color); }
        .evidence-name { font-size: 10px; color: #666; word-break: break-all; margin-bottom: 5px; }
        .evidence-download { display: inline-flex; align-items: center; gap: 5px; background: var(--secondary-color); color: white; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 10px; transition: background 0.2s; }
        .evidence-download:hover { background: #2980b9; }
        
        /* Chat Styles */
        .chat-section { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 2rem; }
        .chat-header { background: var(--primary-color); color: white; padding: 1rem; font-weight: 500; }
        .chat-messages { height: 400px; overflow-y: auto; padding: 1.5rem; background: #f8f9fa; }
        .chat-message { margin-bottom: 1.5rem; display: flex; }
        .chat-message.survivor { justify-content: flex-end; }
        .chat-message.counselor { justify-content: flex-start; }
        .message-bubble { max-width: 70%; padding: 0.75rem 1rem; border-radius: 18px; position: relative; }
        .counselor .message-bubble { background: white; border: 1px solid #e0e0e0; border-bottom-left-radius: 5px; }
        .survivor .message-bubble { background: var(--secondary-color); color: white; border-bottom-right-radius: 5px; }
        .message-text { word-wrap: break-word; line-height: 1.4; }
        .message-time { font-size: 0.7rem; margin-top: 5px; opacity: 0.7; text-align: right; }
        .no-messages { text-align: center; padding: 3rem; color: #666; }
        .no-messages i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
        .chat-input { padding: 1rem; border-top: 1px solid #e0e0e0; background: white; }
        .chat-input form { display: flex; gap: 0.5rem; }
        .chat-input textarea { flex: 1; padding: 0.75rem; border: 1px solid #e0e0e0; border-radius: 5px; font-family: inherit; resize: vertical; }
        .chat-input textarea:focus { outline: none; border-color: var(--secondary-color); }
        .counseling-info { background: #e8f5e9; padding: 1rem; border-radius: 5px; margin-top: 1rem; text-align: center; }
        .counseling-pending { background: #fff3e0; }
        
        @media (max-width: 768px) {
            .status-header { flex-direction: column; text-align: center; }
            .detail-row { flex-direction: column; gap: 0.25rem; }
            .detail-row strong { min-width: auto; }
            .message-bubble { max-width: 85%; }
            .chat-messages { height: 300px; }
            .evidence-gallery { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="status-page">
        <div class="container">
            <div class="status-container">
                <?php echo displayFlash(); ?>
                
                <!-- Report Status Card -->
                <div class="status-card">
                    <div class="status-header">
                        <div>
                            <strong>Tracking ID:</strong> <?php echo htmlspecialchars($report['tracking_id']); ?>
                        </div>
                        <div class="status-badge status-<?php echo $status_colors[$report['status']]; ?>">
                            <?php echo str_replace('_', ' ', ucwords($report['status'])); ?>
                        </div>
                    </div>
                    
                    <div class="status-details">
                        <div class="detail-row">
                            <strong>Incident Type:</strong>
                            <span><?php echo htmlspecialchars($report['category_name'] ?? 'Not specified'); ?></span>
                        </div>
                        <div class="detail-row">
                            <strong>Date of Incident:</strong>
                            <span><?php echo date('F j, Y', strtotime($report['incident_date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <strong>Location:</strong>
                            <span><?php echo htmlspecialchars($report['location']); ?></span>
                        </div>
                        <div class="detail-row">
                            <strong>Description:</strong>
                            <p class="description-text"><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                        </div>
                        
                        <!-- Evidence Section -->
                        <?php if (!empty($evidence_files)): ?>
                        <div class="evidence-section">
                            <div class="evidence-title">
                                <i class="fas fa-paperclip"></i> Evidence Files (<?php echo count($evidence_files); ?>)
                            </div>
                            <div class="evidence-gallery">
                                <?php foreach ($evidence_files as $file): ?>
                                <div class="evidence-item">
                                    <div class="evidence-preview">
                                        <?php if ($file['file_type'] == 'image'): ?>
                                            <img src="<?php echo htmlspecialchars($file['file_path']); ?>" alt="Evidence">
                                        <?php elseif ($file['file_type'] == 'audio'): ?>
                                            <i class="fas fa-headphones"></i>
                                        <?php elseif ($file['file_type'] == 'document'): ?>
                                            <i class="fas fa-file-alt"></i>
                                        <?php else: ?>
                                            <i class="fas fa-file"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="evidence-name"><?php echo htmlspecialchars(substr($file['file_name'], 0, 15)) . '...'; ?></div>
                                    <a href="<?php echo htmlspecialchars($file['file_path']); ?>" class="evidence-download" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Counseling Section -->
                <?php if ($report['consent_counselor']): ?>
                    <?php if (!$counseling): ?>
                        <div class="counseling-info counseling-pending">
                            <i class="fas fa-clock"></i>
                            <strong>Counseling Request Pending</strong>
                            <p>A counselor will be assigned to your case soon. You'll be able to communicate with them here once the session starts.</p>
                        </div>
                    <?php elseif ($counseling['status'] == 'active'): ?>
                        <div class="chat-section">
                            <div class="chat-header">
                                <i class="fas fa-comments"></i> Anonymous Counseling Chat
                                <small style="float: right; opacity: 0.8;">Your identity is protected</small>
                            </div>
                            
                            <div class="chat-messages" id="chatMessages">
                                <?php if (count($messages) > 0): ?>
                                    <?php foreach ($messages as $msg): ?>
                                    <div class="chat-message <?php echo $msg['sender_type']; ?>">
                                        <div class="message-bubble">
                                            <div class="message-text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                            <div class="message-time"><?php echo date('g:i A', strtotime($msg['sent_at'])); ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-messages">
                                        <i class="fas fa-comment-dots"></i>
                                        <p>No messages yet. A counselor will reach out to you soon.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="chat-input">
                                <form method="POST" action="" id="messageForm">
                                    <textarea name="message" id="message" rows="2" placeholder="Type your message here... (Your identity remains anonymous)" required></textarea>
                                    <button type="submit" name="send_message" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Send
                                    </button>
                                </form>
                                <small style="display: block; margin-top: 0.5rem; text-align: center; color: #666;">
                                    <i class="fas fa-lock"></i> This conversation is confidential and anonymous.
                                </small>
                            </div>
                        </div>
                    <?php elseif ($counseling['status'] == 'completed'): ?>
                        <div class="counseling-info">
                            <i class="fas fa-check-circle"></i>
                            <strong>Counseling Session Completed</strong>
                            <p>Your counseling session has been completed. If you need further support, please submit a new report.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="status-actions" style="margin-top: 2rem; text-align: center;">
                    <a href="check-status.php" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Check Another Report
                    </a>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Home
                    </a>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Auto-scroll to bottom of messages
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            let lastMessageCount = <?php echo count($messages); ?>;
            setInterval(function() {
                fetch('get-messages.php?session=<?php echo $counseling['id'] ?? 0; ?>&last=' + lastMessageCount)
                    .then(response => response.json())
                    .then(data => {
                        if (data.count > lastMessageCount) {
                            location.reload();
                        }
                    })
                    .catch(console.error);
            }, 10000);
        }
        
        // Auto-resize textarea
        const textarea = document.getElementById('message');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            });
        }
    </script>
</body>
</html>