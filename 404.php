<?php
// Set HTTP response code to 404
http_response_code(404);

// Start session if needed for flash messages
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define base path if not defined
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost/ccrs-system1');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Page Not Found - Community Crime Reporting System</title>
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-G1XVD7FLYN"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-G1XVD7FLYN');
</script>
    <style>
        .error-page {
            min-height: calc(100vh - 300px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem 1rem;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .error-container {
            max-width: 600px;
            text-align: center;
            background: white;
            border-radius: 20px;
            padding: 3rem 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 0.5s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error-icon {
            font-size: 6rem;
            color: var(--warning-color);
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: var(--primary-color);
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .error-title {
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 1rem;
        }
        
        .error-message {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--secondary-color);
            color: var(--secondary-color);
        }
        
        .btn-outline:hover {
            background: var(--secondary-color);
            color: white;
        }
        
        .suggestions {
            text-align: left;
            background: var(--light-color);
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1.5rem;
        }
        
        .suggestions h3 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .suggestions ul {
            list-style: none;
            padding: 0;
        }
        
        .suggestions li {
            margin-bottom: 0.5rem;
        }
        
        .suggestions a {
            color: var(--secondary-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .suggestions a:hover {
            text-decoration: underline;
        }
        
        .search-box {
            margin: 1.5rem 0;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--secondary-color);
        }
        
        @media (max-width: 768px) {
            .error-container {
                padding: 2rem 1.5rem;
                margin: 0 1rem;
            }
            
            .error-code {
                font-size: 4rem;
            }
            
            .error-icon {
                font-size: 4rem;
            }
            
            .error-actions {
                flex-direction: column;
            }
            
            .error-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="error-page">
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-map-signs"></i>
            </div>
            <div class="error-code">404</div>
            <div class="error-title">Page Not Found</div>
            <div class="error-message">
                <p>Sorry, the page you're looking for doesn't exist or has been moved.</p>
                <p>The page may have been removed, renamed, or is temporarily unavailable.</p>
            </div>
            
            <div class="error-actions">
                <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Go to Homepage
                </a>
                <a href="<?php echo SITE_URL; ?>/report/report-form.php" class="btn btn-secondary">
                    <i class="fas fa-edit"></i> Report a Case
                </a>
                <a href="javascript:history.back()" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
            </div>
            
            <div class="search-box">
                <input type="text" id="pageSearch" placeholder="Looking for something? Search the site..." onkeyup="searchSite()">
            </div>
            
            <div class="suggestions">
                <h3><i class="fas fa-question-circle"></i> You might be looking for:</h3>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>/faq.php"><i class="fas fa-question"></i> Frequently Asked Questions</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/check-status.php"><i class="fas fa-search"></i> Check Report Status</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/privacy.php"><i class="fas fa-shield-alt"></i> Privacy Policy</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/auth/login.php"><i class="fas fa-sign-in-alt"></i> Staff Login</a></li>
                </ul>
            </div>
            
            <div class="suggestions" style="margin-top: 1rem;">
                <h3><i class="fas fa-exclamation-triangle"></i> Need immediate help?</h3>
                <ul>
                    <li><strong>Police Emergency:</strong> 999</li>
                    <li><strong>Child Helpline:</strong> 116</li>
                    <li><strong>GBV Hotline:</strong> 0800-XXX-XXX</li>
                </ul>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        function searchSite() {
            const searchTerm = document.getElementById('pageSearch').value.toLowerCase();
            if (searchTerm.length > 2) {
                // Suggest search terms based on common pages
                const suggestions = {
                    'report': ['/report/report-form.php', 'Report a Case'],
                    'status': ['/check-status.php', 'Check Report Status'],
                    'track': ['/check-status.php', 'Check Report Status'],
                    'faq': ['/faq.php', 'FAQ'],
                    'question': ['/faq.php', 'FAQ'],
                    'privacy': ['/privacy.php', 'Privacy Policy'],
                    'policy': ['/privacy.php', 'Privacy Policy'],
                    'login': ['/auth/login.php', 'Staff Login'],
                    'dashboard': ['/dashboard/index.php', 'Dashboard'],
                    'counselor': ['/counselor/sessions.php', 'Counselor Portal']
                };
                
                for (const [key, value] of Object.entries(suggestions)) {
                    if (searchTerm.includes(key)) {
                        // Create a temporary suggestion
                        const suggestionDiv = document.createElement('div');
                        suggestionDiv.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: var(--secondary-color); color: white; padding: 10px 20px; border-radius: 5px; cursor: pointer; z-index: 1000;';
                        suggestionDiv.innerHTML = `Did you mean: <a href="${value[0]}" style="color: white; text-decoration: underline;">${value[1]}</a>?`;
                        document.body.appendChild(suggestionDiv);
                        setTimeout(() => suggestionDiv.remove(), 5000);
                        break;
                    }
                }
            }
        }
        
        // Track 404 errors (optional)
        if (typeof fetch === 'function') {
            fetch('<?php echo SITE_URL; ?>/log-404.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    page: window.location.href,
                    referrer: document.referrer,
                    timestamp: new Date().toISOString()
                })
            }).catch(e => console.log('Logging disabled'));
        }
    </script>
</body>
</html>