<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Check if user is counselor
if (!isLoggedIn() || $_SESSION['role'] !== 'counselor') {
    redirect('../auth/login.php', 'Access denied. Counselor privileges required.', 'error');
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$session_id = filter_input(INPUT_GET, 'session', FILTER_VALIDATE_INT);

if (!$session_id) {
    redirect('sessions.php', 'Please select a session to view messages.', 'info');
}

// Verify session belongs to counselor
$stmt = $db->prepare("
    SELECT cs.*, r.tracking_id 
    FROM counseling_sessions cs
    JOIN reports r ON cs.report_id = r.id
    WHERE cs.id = ? AND cs.counselor_id = ?
");
$stmt->execute([$session_id, $user_id]);
$session = $stmt->fetch();

if (!$session) {
    redirect('sessions.php', 'Session not found or access denied.', 'error');
}

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = sanitizeInput($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $db->prepare("
            INSERT INTO counseling_messages (session_id, sender_type, message, sent_at)
            VALUES (?, 'counselor', ?, NOW())
        ");
        $stmt->execute([$session_id, $message]);
        
        // Create notification for survivor (if we had a way to notify them)
        // For now, we just log it
        redirect("messages.php?session=$session_id", 'Message sent successfully.', 'success');
    }
}

// Mark messages as read
$stmt = $db->prepare("
    UPDATE counseling_messages 
    SET is_read = 1 
    WHERE session_id = ? AND sender_type = 'survivor' AND is_read = 0
");
$stmt->execute([$session_id]);

// Get messages
$stmt = $db->prepare("
    SELECT * FROM counseling_messages 
    WHERE session_id = ?
    ORDER BY sent_at ASC
");
$stmt->execute([$session_id]);
$messages = $stmt->fetchAll();

include '../includes/dashboard-header.php';
include '../includes/dashboard-sidebar.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Counseling Conversation</h1>
        <p>Session with: <?php echo htmlspecialchars($session['survivor_identifier']); ?></p>
        <p><small>Case: <?php echo htmlspecialchars($session['tracking_id']); ?></small></p>
    </div>
    
    <div class="chat-container">
        <div class="chat-messages" id="chatMessages">
            <?php if (count($messages) > 0): ?>
                <?php foreach ($messages as $message): ?>
                <div class="message <?php echo $message['sender_type'] == 'counselor' ? 'message-out' : 'message-in'; ?>">
                    <div class="message-bubble">
                        <div class="message-text"><?php echo nl2br(htmlspecialchars($message['message'])); ?></div>
                        <div class="message-time">
                            <?php echo date('g:i A', strtotime($message['sent_at'])); ?>
                            <?php if ($message['sender_type'] == 'counselor'): ?>
                                <i class="fas fa-check<?php echo $message['is_read'] ? '-double' : ''; ?>"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-messages">
                    <i class="fas fa-comment-dots"></i>
                    <p>No messages yet. Start the conversation by sending a message below.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="chat-input-area">
            <form method="POST" action="" id="messageForm">
                <div class="input-group">
                    <textarea name="message" id="message" rows="3" placeholder="Type your message here... (Messages are anonymous and confidential)" required></textarea>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send
                    </button>
                </div>
            </form>
            <div class="chat-info">
                <small>
                    <i class="fas fa-lock"></i> This conversation is confidential and anonymous.
                    <i class="fas fa-clock"></i> Messages are saved for record keeping.
                </small>
            </div>
        </div>
    </div>
    
    <div class="session-actions-bottom">
        <a href="sessions.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Sessions
        </a>
        <?php if ($session['status'] == 'active'): ?>
        <form method="POST" action="sessions.php" style="display: inline;">
            <input type="hidden" name="action" value="complete_session">
            <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
            <button type="submit" class="btn btn-success" onclick="return confirm('Mark this session as completed?')">
                <i class="fas fa-check"></i> End Session
            </button>
        </form>
        <?php endif; ?>
    </div>
</main>

<style>
.chat-container {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.chat-messages {
    height: 500px;
    overflow-y: auto;
    padding: 20px;
    background: #f8f9fa;
}

.message {
    margin-bottom: 20px;
    display: flex;
}

.message-in {
    justify-content: flex-start;
}

.message-out {
    justify-content: flex-end;
}

.message-bubble {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 18px;
    position: relative;
}

.message-in .message-bubble {
    background: white;
    border: 1px solid #e0e0e0;
    border-bottom-left-radius: 5px;
}

.message-out .message-bubble {
    background: var(--secondary-color);
    color: white;
    border-bottom-right-radius: 5px;
}

.message-text {
    word-wrap: break-word;
    line-height: 1.4;
}

.message-time {
    font-size: 0.7rem;
    margin-top: 5px;
    opacity: 0.7;
    text-align: right;
}

.no-messages {
    text-align: center;
    padding: 50px;
    color: #666;
}

.no-messages i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.chat-input-area {
    padding: 20px;
    border-top: 1px solid #e0e0e0;
    background: white;
}

.input-group {
    display: flex;
    gap: 10px;
}

.input-group textarea {
    flex: 1;
    padding: 10px;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    font-family: inherit;
    resize: vertical;
}

.input-group textarea:focus {
    outline: none;
    border-color: var(--secondary-color);
}

.chat-info {
    margin-top: 10px;
    text-align: center;
    color: #666;
    font-size: 0.8rem;
}

.session-actions-bottom {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .message-bubble {
        max-width: 85%;
    }
    
    .input-group {
        flex-direction: column;
    }
    
    .input-group button {
        width: 100%;
    }
    
    .chat-messages {
        height: 400px;
    }
}
</style>

<script>
// Auto-scroll to bottom of messages
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Auto-resize textarea
const textarea = document.getElementById('message');
if (textarea) {
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 150) + 'px';
    });
}

// Auto-refresh messages every 10 seconds
let lastMessageCount = <?php echo count($messages); ?>;
setInterval(function() {
    fetch('get-messages.php?session=<?php echo $session_id; ?>&last=' + lastMessageCount)
        .then(response => response.json())
        .then(data => {
            if (data.count > lastMessageCount) {
                location.reload();
            }
        })
        .catch(console.error);
}, 10000);
</script>

<?php include '../includes/dashboard-footer.php'; ?>