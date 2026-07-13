<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$page_title = "Community Crime Reporting System";
$page_description = "Safe, anonymous reporting platform for gender-based violence and child abuse in Muni University and Arua City, Uganda.";

// Get active slides from database
$db = Database::getInstance()->getConnection();
$slides = $db->query("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();

// If no slides exist in database, use default content
if (empty($slides)) {
    $slides = [
        [
            'image_path' => 'assets/images/default-slide.jpg',
            'title' => 'Report Anonymously. Seek Help Safely.',
            'subtitle' => 'Community Crime Reporting System for Muni University and Arua City',
            'description' => 'A safe, anonymous platform to report gender-based violence and child abuse. Your identity remains protected while authorities take action.',
            'button_text' => 'Report a Case',
            'button_link' => 'report/report-form.php'
        ],
        [
            'image_path' => 'assets/images/default-slide.jpg',
            'title' => 'Your Voice Matters',
            'subtitle' => 'Speak Up Against Gender-Based Violence',
            'description' => 'Every report helps us understand the scale of violence in our community and take appropriate action to protect survivors.',
            'button_text' => 'Report Now',
            'button_link' => 'report/report-form.php'
        ],
        [
            'image_path' => 'assets/images/default-slide.jpg',
            'title' => 'Protect Our Children',
            'subtitle' => 'Child Abuse is a Crime - Report It Anonymously',
            'description' => 'Children are our future. If you suspect child abuse, don\'t stay silent. Your anonymous report could save a child\'s life.',
            'button_text' => 'Report Child Abuse',
            'button_link' => 'report/report-form.php'
        ],
        [
            'image_path' => 'assets/images/default-slide.jpg',
            'title' => 'You Are Not Alone',
            'subtitle' => 'Free Anonymous Counseling Support Available',
            'description' => 'Professional counselors are available to support you through your healing journey. Request counseling when you submit your report.',
            'button_text' => 'Get Support',
            'button_link' => 'report/report-form.php'
        ],
        [
            'image_path' => 'assets/images/default-slide.jpg',
            'title' => 'Join the Fight Against Violence',
            'subtitle' => 'Every Report Makes Our Community Safer',
            'description' => 'Your anonymous report helps authorities track patterns, allocate resources, and prevent future incidents.',
            'button_text' => 'Make a Difference',
            'button_link' => 'report/report-form.php'
        ]
    ];
}

// Get statistics
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM reports
")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Hero Slideshow Styles */
        .hero-slideshow {
            position: relative;
            height: 550px;
            overflow: hidden;
        }
        
        .slideshow-container {
            position: relative;
            height: 100%;
            width: 100%;
        }
        
        .slide {
            position: absolute;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }
        
        .slide.active {
            opacity: 1;
        }
        
        .slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
        }
        
        .glass-card-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            max-width: 700px;
            z-index: 10;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .glass-card h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: fadeInUp 0.8s ease;
        }
        
        .glass-subtitle {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.95);
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            animation: fadeInUp 0.8s ease 0.2s both;
        }
        
        .glass-description {
            font-size: 1rem;
            margin-bottom: 1.5rem;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            animation: fadeInUp 0.8s ease 0.4s both;
        }
        
        .glass-card .btn {
            animation: fadeInUp 0.8s ease 0.6s both;
            background: linear-gradient(135deg, var(--secondary-color), #2980b9);
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .glass-card .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        .slide-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.3);
            backdrop-filter: blur(5px);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 15px 20px;
            cursor: pointer;
            font-size: 1.5rem;
            border-radius: 50%;
            transition: all 0.3s;
            z-index: 20;
        }
        
        .slide-nav:hover {
            background: rgba(255,255,255,0.5);
            transform: translateY(-50%) scale(1.1);
        }
        
        .nav-prev { left: 20px; }
        .nav-next { right: 20px; }
        
        .dots-container {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 20;
        }
        
        .dot {
            cursor: pointer;
            height: 12px;
            width: 12px;
            background-color: rgba(255,255,255,0.5);
            border-radius: 50%;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .dot:hover { background-color: rgba(255,255,255,0.8); }
        .dot.active { background-color: white; transform: scale(1.2); }
        
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
        
        @media (max-width: 768px) {
            .hero-slideshow { height: 500px; }
            .glass-card { padding: 1.5rem; }
            .glass-card h1 { font-size: 1.5rem; }
            .glass-subtitle { font-size: 0.9rem; }
            .glass-description { font-size: 0.85rem; }
            .slide-nav { padding: 10px 15px; font-size: 1rem; }
        }
        
        @media (max-width: 480px) {
            .hero-slideshow { height: 550px; }
            .glass-card h1 { font-size: 1.25rem; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <!-- Hero Slideshow Section -->
        <section class="hero-slideshow">
            <div class="slideshow-container">
                <?php foreach ($slides as $index => $slide): ?>
                <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" 
                     style="background-image: url('<?php echo !empty($slide['image_path']) ? $slide['image_path'] : 'assets/images/default-slide.jpg'; ?>');">
                </div>
                <?php endforeach; ?>
                
                <div class="glass-card-container">
                    <?php foreach ($slides as $index => $slide): ?>
                    <div class="glass-card" id="glassCard<?php echo $index + 1; ?>" style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>;">
                        <h1><?php echo htmlspecialchars($slide['title']); ?></h1>
                        <?php if (!empty($slide['subtitle'])): ?>
                        <div class="glass-subtitle"><?php echo htmlspecialchars($slide['subtitle']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($slide['description'])): ?>
                        <div class="glass-description"><?php echo htmlspecialchars($slide['description']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($slide['button_text'])): ?>
                        <a href="<?php echo htmlspecialchars($slide['button_link']); ?>" class="btn btn-primary">
                            <i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($slide['button_text']); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button class="slide-nav nav-prev" onclick="changeSlide(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="slide-nav nav-next" onclick="changeSlide(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
                
                <div class="dots-container">
                    <?php foreach ($slides as $index => $slide): ?>
                    <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="currentSlide(<?php echo $index + 1; ?>)"></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="how-it-works">
            <div class="container">
                <h2>How It Works</h2>
                <div class="steps">
                    <div class="step">
                        <div class="step-icon">1</div>
                        <h3>Submit Report</h3>
                        <p>Fill out the simple form with details about the incident. No personal information required.</p>
                    </div>
                    <div class="step">
                        <div class="step-icon">2</div>
                        <h3>Get Tracking ID</h3>
                        <p>Receive a unique tracking ID to check your report status anonymously.</p>
                    </div>
                </div>
                <div class="steps" style="margin-top: 1rem;">
                    <div class="step">
                        <div class="step-icon">3</div>
                        <h3>Authorities Act</h3>
                        <p>Law enforcement and protection officers review and respond to cases.</p>
                    </div>
                    <div class="step">
                        <div class="step-icon">4</div>
                        <h3>Track Progress</h3>
                        <p>Use your tracking ID anytime to see updates on your case.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Impact Statistics -->
        <section class="statistics">
            <div class="container">
                <h2>Community Impact</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['total'] ?? 0); ?></div>
                        <div class="stat-label">Total Reports</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['pending'] ?? 0); ?></div>
                        <div class="stat-label">Under Investigation</div>
                    </div>
                </div>
                <div class="stats-grid" style="margin-top: 1rem;">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['resolved'] ?? 0); ?></div>
                        <div class="stat-label">Cases Resolved</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Anonymous Reporting</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="features">
            <div class="container">
                <h2>Why Use CCRS?</h2>
                <div class="features-grid">
                    <div class="feature">
                        <i class="fas fa-user-secret feature-icon"></i>
                        <h3>Complete Anonymity</h3>
                        <p>Your identity is never revealed. No accounts, no tracking.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-mobile-alt feature-icon"></i>
                        <h3>Mobile Friendly</h3>
                        <p>Works on all devices, even with slow internet connections.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-lock feature-icon"></i>
                        <h3>Secure & Private</h3>
                        <p>All reports are encrypted and handled with confidentiality.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-hand-holding-heart feature-icon"></i>
                        <h3>Counselor Support</h3>
                        <p>Optional connection to professional counselors for support.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Emergency Contact - Clickable Buttons -->
         <section class="emergency">
    <div class="container">
        <div class="emergency-wrapper">
            <h2><i class="fas fa-exclamation-triangle"></i> Emergency Help</h2>
            <p>If you are in danger, act immediately. Tap a service below to call.</p>

            <div class="emergency-buttons">

                <a href="tel:999" class="emergency-btn police">
                    <i class="fas fa-shield-alt"></i>
                    <span>Police - 999</span>
                </a>

                <a href="tel:116" class="emergency-btn child">
                    <i class="fas fa-child"></i>
                    <span>Child Helpline - 116</span>
                </a>

                <a href="tel:0772006065" class="emergency-btn gbv">
                    <i class="fas fa-heartbeat"></i>
                    <span>GBV Hotline - 0772006065</span>
                </a>

            </div>
        </div>
    </div>
</section>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        let slideIndex = 1;
        let slideInterval;
        let totalSlides = <?php echo count($slides); ?>;
        
        if (totalSlides > 0) {
            showSlides(slideIndex);
            startAutoSlide();
            
            const container = document.querySelector('.slideshow-container');
            if (container) {
                container.addEventListener('mouseenter', pauseAutoSlide);
                container.addEventListener('mouseleave', startAutoSlide);
            }
        }
        
        function startAutoSlide() {
            if (slideInterval) clearInterval(slideInterval);
            slideInterval = setInterval(function() {
                changeSlide(1);
            }, 5000);
        }
        
        function pauseAutoSlide() {
            if (slideInterval) {
                clearInterval(slideInterval);
                slideInterval = null;
            }
        }
        
        function changeSlide(n) {
            showSlides(slideIndex += n);
            resetAutoSlide();
        }
        
        function currentSlide(n) {
            showSlides(slideIndex = n);
            resetAutoSlide();
        }
        
        function showSlides(n) {
            let slides = document.getElementsByClassName("slide");
            let dots = document.getElementsByClassName("dot");
            let glassCards = document.querySelectorAll('.glass-card');
            
            if (!slides.length) return;
            
            if (n > slides.length) { slideIndex = 1; }
            if (n < 1) { slideIndex = slides.length; }
            
            for (let i = 0; i < slides.length; i++) {
                slides[i].classList.remove('active');
            }
            for (let i = 0; i < glassCards.length; i++) {
                glassCards[i].style.display = 'none';
            }
            for (let i = 0; i < dots.length; i++) {
                dots[i].classList.remove('active');
            }
            
            slides[slideIndex - 1].classList.add('active');
            if (glassCards[slideIndex - 1]) {
                glassCards[slideIndex - 1].style.display = 'block';
            }
            if (dots[slideIndex - 1]) {
                dots[slideIndex - 1].classList.add('active');
            }
        }
        
        function resetAutoSlide() {
            pauseAutoSlide();
            startAutoSlide();
        }
    </script>
</body>
</html>