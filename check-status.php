<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$page_title = "Check Report Status";
$page_description = "Enter your tracking ID to see the current status of your report and communicate with your counselor.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Status - Community Crime Reporting System | Muni University</title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="assets/images/logo.png">
    <style>
        .status-check-page {
            padding: 0 0 3rem 0;
            background-color: #f5f5f5;
            min-height: calc(100vh - 300px);
        }
        
        .status-check-form-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .help-note {
            max-width: 500px;
            margin: 2rem auto 0;
            background: #fff3cd;
            border-radius: 10px;
            padding: 1.5rem;
            border-left: 4px solid var(--warning-color);
        }
        
        .help-note h3 {
            margin-bottom: 0.5rem;
            color: #856404;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="status-check-page">
        <!-- Page Header Banner -->
        <div class="page-header-banner">
            <div class="container">
                <h1><?php echo $page_title; ?></h1>
                <p><?php echo $page_description; ?></p>
            </div>
        </div>
        
        <div class="container">
            <?php echo displayFlash(); ?>
            
            <div class="status-check-form-container">
                <form action="status.php" method="GET" class="status-check-form">
                    <div class="form-group">
                        <label for="tracking_id">Tracking ID *</label>
                        <input type="text" 
                               name="tracking_id" 
                               id="tracking_id" 
                               placeholder="e.g., CCRS-20231225-ABC123"
                               pattern="<?php echo getConfig('tracking_id_prefix'); ?>-[0-9]{8}-[A-Z0-9]{6}"
                               required>
                        <small class="form-hint">Format: <?php echo getConfig('tracking_id_prefix'); ?>-YYYYMMDD-XXXXXX</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                        <i class="fas fa-search"></i> Check Status
                    </button>
                </form>
            </div>
            
            <div class="help-note">
                <h3><i class="fas fa-question-circle"></i> Lost your tracking ID?</h3>
                <p>Unfortunately, for privacy reasons, we cannot recover lost tracking IDs. If you need assistance, please submit a new report and mention it's a follow-up in the description.</p>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>