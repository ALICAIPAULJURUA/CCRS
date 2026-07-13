<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$page_title = "Privacy Policy";
$page_description = "Learn how we protect your privacy and handle your data when using the Community Crime Reporting System.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Community Crime Reporting System | Muni University</title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="assets/images/logo.png">
    <style>
        .privacy-page {
            padding: 0 0 3rem 0;
            background-color: #f5f5f5;
            min-height: calc(100vh - 300px);
        }
        
        .privacy-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .privacy-section {
            margin-bottom: 2rem;
        }
        
        .privacy-section h2 {
            color: var(--primary-color);
            font-size: 1.3rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--light-color);
        }
        
        .privacy-section h3 {
            color: var(--dark-color);
            font-size: 1.1rem;
            margin: 1rem 0 0.5rem;
        }
        
        .privacy-section p {
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .privacy-section ul, .privacy-section ol {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .privacy-section li {
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }
        
        .highlight-box {
            background: var(--light-color);
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            border-left: 4px solid var(--primary-color);
        }
        
        .warning-box {
            background: #fff3cd;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            border-left: 4px solid var(--warning-color);
        }
        
        .contact-info {
            background: #e8f5e9;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .privacy-container {
                padding: 1.5rem;
                margin: 0 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="privacy-page">
        <!-- Page Header Banner -->
        <div class="page-header-banner">
            <div class="container">
                <h1><?php echo $page_title; ?></h1>
                <p><?php echo $page_description; ?></p>
            </div>
        </div>
        
        <div class="container">
            <div class="privacy-container">
                <div class="privacy-section">
                    <h2>1. Introduction</h2>
                    <p>The Community Crime Reporting System (CCRS) is committed to protecting your privacy and ensuring the confidentiality of your reports. This Privacy Policy explains how we collect, use, and protect your information when you use our platform.</p>
                    <p>We understand that reporting gender-based violence or child abuse requires courage, and we have designed our system to prioritize your anonymity and safety above all else.</p>
                    <div class="highlight-box">
                        <strong><i class="fas fa-check-circle"></i> Key Principle:</strong> You never need to provide your name, contact information, or any personally identifiable information to submit a report.
                    </div>
                </div>
                
                <div class="privacy-section">
                    <h2>2. Information We Collect</h2>
                    <p>When you use the CCRS platform, we collect only the minimum information necessary to process reports and provide support:</p>
                    
                    <h3>Information You Provide:</h3>
                    <ul>
                        <li><strong>Report Details:</strong> Incident description, location, date/time, and incident type</li>
                        <li><strong>Evidence Files:</strong> Photos, audio recordings, or documents you choose to upload</li>
                        <li><strong>Counseling Consent:</strong> Your preference regarding counselor contact</li>
                    </ul>
                    
                    <h3>Automatically Collected Information:</h3>
                    <ul>
                        <li><strong>IP Address:</strong> Stored for security and rate limiting, but not shared with investigators</li>
                        <li><strong>Browser Information:</strong> User agent string for compatibility purposes</li>
                        <li><strong>Timestamp:</strong> When the report was submitted</li>
                    </ul>
                    
                    <div class="warning-box">
                        <strong><i class="fas fa-info-circle"></i> What We DO NOT Collect:</strong>
                        <ul style="margin-top: 0.5rem;">
                            <li>Your name, email, or phone number (unless you voluntarily provide it)</li>
                            <li>Your physical location (GPS coordinates)</li>
                            <li>Device identifiers or tracking data</li>
                            <li>Cookies or browsing history</li>
                        </ul>
                    </div>
                </div>
                
                <div class="privacy-section">
                    <h2>3. Data Security</h2>
                    <p>We implement robust security measures to protect your information:</p>
                    <ul>
                        <li><strong>Encryption:</strong> All data transmission uses HTTPS encryption</li>
                        <li><strong>Secure Storage:</strong> Database is protected with strong passwords and access controls</li>
                        <li><strong>Access Control:</strong> Role-based access ensures only authorized personnel can view reports</li>
                        <li><strong>Audit Logs:</strong> All access to report data is logged and monitored</li>
                        <li><strong>Regular Backups:</strong> Data is backed up securely to prevent loss</li>
                    </ul>
                </div>
                
                <div class="privacy-section">
                    <h2>4. Your Rights</h2>
                    <p>You have the following rights regarding your information:</p>
                    <ul>
                        <li><strong>Right to Anonymity:</strong> The system is designed to protect your identity</li>
                        <li><strong>Right to Track:</strong> You can check report status using your tracking ID</li>
                        <li><strong>Right to Counseling:</strong> You can request anonymous counseling support</li>
                        <li><strong>Right to Withdraw:</strong> You can withdraw consent for counseling at any time</li>
                    </ul>
                </div>
                
                <div class="privacy-section">
                    <h2>5. Contact Us</h2>
                    <div class="contact-info">
                        <p><strong><i class="fas fa-envelope"></i> Email:</strong> <?php echo getConfig('contact_email') ?: 'privacy@ccrs.ug'; ?></p>
                        <p><strong><i class="fas fa-phone"></i> Emergency:</strong> 999 (Police) | 116 (Child Helpline)</p>
                        <p><strong><i class="fas fa-building"></i> Address:</strong> Muni University, Arua City, Uganda</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>