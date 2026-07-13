<?php
require_once '../config/database.php';
require_once '../config/functions.php';

$tracking_id = $_SESSION['last_tracking_id'] ?? '';
if (!$tracking_id) {
    redirect('../report/report-form.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Confirmed - CCRS</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="apple-touch-icon" href="../assets/images/logo.png">
    <style>
        .confirmation-page {
            padding: 3rem 0;
            min-height: calc(100vh - 300px);
            background-color: #f5f5f5;
        }
        
        .confirmation-box {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .success-icon {
            font-size: 5rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }
        
        .tracking-info {
            background-color: var(--light-color);
            padding: 2rem;
            border-radius: 5px;
            margin: 2rem 0;
        }
        
        .tracking-id-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 1rem 0;
            flex-wrap: wrap;
        }
        
        .tracking-id {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            padding: 0.8rem 1.2rem;
            background-color: white;
            border-radius: 5px;
            letter-spacing: 2px;
            font-family: monospace;
            border: 1px solid #ddd;
        }
        
        .copy-btn {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 0.8rem 1.2rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .copy-btn:hover {
            background: #2980b9;
            transform: scale(1.02);
        }
        
        .copy-btn.copied {
            background: var(--success-color);
        }
        
        .tracking-note {
            color: var(--warning-color);
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        .next-steps {
            text-align: left;
            margin: 2rem 0;
            padding: 1.5rem;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .next-steps ol {
            margin-left: 1.5rem;
            margin-top: 0.5rem;
        }
        
        .next-steps li {
            margin-bottom: 0.5rem;
        }
        
        .confirmation-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .safety-reminder {
            margin-top: 2rem;
            padding: 1rem;
            background-color: #fff3cd;
            border-radius: 5px;
            color: #856404;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .confirmation-box {
                padding: 1.5rem;
                margin: 0 15px;
            }
            
            .tracking-id {
                font-size: 1rem;
                padding: 0.6rem 1rem;
            }
            
            .tracking-id-container {
                flex-direction: column;
            }
            
            .copy-btn {
                width: 100%;
                justify-content: center;
            }
            
            .confirmation-actions {
                flex-direction: column;
            }
            
            .confirmation-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="confirmation-page">
        <div class="container">
            <div class="confirmation-box">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h1>Report Submitted Successfully</h1>
                
                <p class="confirmation-message">
                    Thank you for your report. Your courage helps make our community safer.
                </p>
                
                <div class="tracking-info">
                    <h2>Your Tracking ID</h2>
                    <div class="tracking-id-container">
                        <div class="tracking-id" id="trackingId"><?php echo htmlspecialchars($tracking_id); ?></div>
                        <button class="copy-btn" id="copyTrackingBtn" onclick="copyTrackingId()">
                            <i class="fas fa-copy"></i> Copy Tracking ID
                        </button>
                    </div>
                    <p class="tracking-note">
                        <i class="fas fa-exclamation-triangle"></i>
                        Save this ID! It's the only way to check your report status.
                    </p>
                </div>
                
                <div class="next-steps">
                    <h3>What happens next?</h3>
                    <ol>
                        <li>Authorities will review your report</li>
                        <li>If assigned, an investigator will look into the case</li>
                        <li>You can check status anytime using your tracking ID</li>
                        <li>If you requested counseling, a counselor will reach out anonymously</li>
                    </ol>
                </div>
                
                <div class="confirmation-actions">
                    <a href="../check-status.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Check Report Status
                    </a>
                    <a href="../index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Return Home
                    </a>
                </div>
                
                <div class="safety-reminder">
                    <i class="fas fa-shield-alt"></i>
                    <p>Remember to clear your browser history for privacy.</p>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        function copyTrackingId() {
            const trackingId = document.getElementById('trackingId').innerText;
            const copyBtn = document.getElementById('copyTrackingBtn');
            
            // Modern approach
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(trackingId).then(function() {
                    showCopySuccess(copyBtn);
                }).catch(function() {
                    fallbackCopy(trackingId, copyBtn);
                });
            } else {
                fallbackCopy(trackingId, copyBtn);
            }
        }
        
        function fallbackCopy(text, btn) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showCopySuccess(btn);
        }
        
        function showCopySuccess(btn) {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            btn.classList.add('copied');
            
            setTimeout(function() {
                btn.innerHTML = originalHtml;
                btn.classList.remove('copied');
            }, 3000);
        }
    </script>
</body>
</html>