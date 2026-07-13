<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$page_title = "Frequently Asked Questions";
$page_description = "Find answers to common questions about the Community Crime Reporting System.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Community Crime Reporting System | Muni University</title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="assets/images/logo.png">
    <style>
        .faq-page {
            padding: 0 0 3rem 0;
            background-color: #f5f5f5;
            min-height: calc(100vh - 300px);
        }
        
        .faq-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .faq-category {
            margin-bottom: 2rem;
        }
        
        .faq-category h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light-color);
        }
        
        .faq-item {
            margin-bottom: 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .faq-question {
            background: #f8f9fa;
            padding: 1rem;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s;
        }
        
        .faq-question:hover {
            background: #e9ecef;
        }
        
        .faq-question i {
            color: var(--primary-color);
            transition: transform 0.3s;
        }
        
        .faq-question.active i {
            transform: rotate(180deg);
        }
        
        .faq-answer {
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
        }
        
        .faq-answer.show {
            padding: 1rem;
            max-height: 500px;
        }
        
        .faq-answer p {
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }
        
        .faq-answer ul, .faq-answer ol {
            margin-left: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .faq-answer li {
            margin-bottom: 0.3rem;
        }
        
        .contact-support {
            margin-top: 2rem;
            padding: 1.5rem;
            background: var(--light-color);
            border-radius: 5px;
            text-align: center;
        }
        
        .contact-support h3 {
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .emergency-contact {
            background: #fff3cd;
            border-left: 4px solid var(--warning-color);
            padding: 1rem;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .faq-container {
                padding: 1.5rem;
                margin: 0 15px;
            }
            
            .faq-question {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="faq-page">
        <!-- Page Header Banner -->
        <div class="page-header-banner">
            <div class="container">
                <h1><?php echo $page_title; ?></h1>
                <p><?php echo $page_description; ?></p>
            </div>
        </div>
        
        <div class="container">
            <div class="faq-container">
                <!-- General Questions -->
                <div class="faq-category">
                    <h2><i class="fas fa-info-circle"></i> General Questions</h2>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            What is the Community Crime Reporting System (CCRS)?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>CCRS is a secure, anonymous platform designed to help residents of Muni University and Arua City report cases of gender-based violence and child abuse. The system allows you to report incidents without revealing your identity, while ensuring that authorized officers can investigate and respond to cases effectively.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Is my identity really protected?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p><strong>Yes, absolutely!</strong> The CCRS is designed with anonymity as a top priority:</p>
                            <ul>
                                <li>No account creation or personal information is required to submit a report</li>
                                <li>Reports are assigned a unique tracking ID instead of your name</li>
                                <li>IP addresses are logged but not shared with investigators</li>
                                <li>All metadata from uploaded files is removed</li>
                                <li>Only authorized personnel can access report details</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Who can access my report?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Access to reports is strictly controlled based on roles:</p>
                            <ul>
                                <li><strong>Law Enforcement Officers</strong> - Can view and investigate reports</li>
                                <li><strong>Child Protection Officers</strong> - Can access child abuse cases</li>
                                <li><strong>Counselors</strong> - Only see cases where consent was given</li>
                                <li><strong>NGOs & Local Leaders</strong> - Only see anonymized statistics, not individual cases</li>
                            </ul>
                            <p>All access is logged and audited to ensure accountability.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Reporting Process -->
                <div class="faq-category">
                    <h2><i class="fas fa-edit"></i> Reporting Process</h2>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How do I submit a report?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Submitting a report is simple:</p>
                            <ol>
                                <li>Click the "Report a Case" button on the homepage</li>
                                <li>Select the incident type from the dropdown menu</li>
                                <li>Provide details about what happened, when, and where</li>
                                <li>Upload any evidence you may have (optional)</li>
                                <li>Choose whether you want to be contacted by a counselor</li>
                                <li>Confirm that your information is true and you understand it's anonymous</li>
                                <li>Submit and receive your unique tracking ID</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            I lost my tracking ID. Can I recover it?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p><strong>Unfortunately, no.</strong> For privacy and security reasons, tracking IDs cannot be recovered. This ensures that only the person who submitted the report can access its status. If you need to follow up on a report, you can submit a new report and mention in the description that it's a follow-up to your previous case.</p>
                            <div class="emergency-contact">
                                <strong><i class="fas fa-exclamation-triangle"></i> Tip:</strong> Always save your tracking ID in a safe place immediately after submitting your report. Consider taking a screenshot or writing it down.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Safety & Security -->
                <div class="faq-category">
                    <h2><i class="fas fa-shield-alt"></i> Safety & Security</h2>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Is it safe to report from my phone or computer?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Yes, but here are some safety tips:</p>
                            <ul>
                                <li>Use a private or incognito browser window</li>
                                <li>Clear your browser history after submitting</li>
                                <li>Don't save your tracking ID on shared devices</li>
                                <li>Use a secure internet connection when possible</li>
                                <li>If you're in immediate danger, contact emergency services first</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Can someone track that I visited this website?
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>We've implemented several measures to protect your privacy:</p>
                            <ul>
                                <li>HTTPS encryption secures your connection</li>
                                <li>No cookies are used to track users</li>
                                <li>The site works without JavaScript for basic functions</li>
                                <li>You can use Tor Browser for additional anonymity</li>
                                <li>Clear your browser history after each visit</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Support -->
                <div class="contact-support">
                    <h3><i class="fas fa-headset"></i> Still have questions?</h3>
                    <p>If you couldn't find the answer you're looking for, please contact us:</p>
                    <p><strong>Email:</strong> <?php echo getConfig('contact_email') ?: 'support@ccrs.ug'; ?></p>
                    <p><strong>Emergency:</strong> <strong style="color: var(--secondary-color);">999</strong> (Police) | <strong>116</strong> (Child Helpline)</p>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('i');
            
            // Close all other FAQs
            const allQuestions = document.querySelectorAll('.faq-question');
            allQuestions.forEach(question => {
                if (question !== element) {
                    const otherAnswer = question.nextElementSibling;
                    const otherIcon = question.querySelector('i');
                    otherAnswer.classList.remove('show');
                    question.classList.remove('active');
                    if (otherIcon) otherIcon.style.transform = 'rotate(0deg)';
                }
            });
            
            // Toggle current FAQ
            answer.classList.toggle('show');
            element.classList.toggle('active');
            
            // Rotate icon
            if (answer.classList.contains('show')) {
                icon.style.transform = 'rotate(180deg)';
            } else {
                icon.style.transform = 'rotate(0deg)';
            }
        }
    </script>
</body>
</html>