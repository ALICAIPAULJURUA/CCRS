<?php
// No whitespace before this
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-G1XVD7FLYN"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-G1XVD7FLYN');
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCRS - Community Crime Reporting System</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/favicon.ico">
    <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/assets/images/logo.png">
</head>
<body>
<header class="site-header">
    <div class="header-container">
        <div class="logo">
            <a href="<?php echo SITE_URL; ?>/index.php">
                <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="CCRS - Community Crime Reporting System" class="logo-img" onerror="this.src='<?php echo SITE_URL; ?>/assets/images/CCRS.png'; this.onerror=null;">
            </a>
        </div>
        
        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle Menu">
            <i class="fas fa-bars"></i>
        </button>
        
        <nav class="main-nav" id="mainNav">
            <ul>
                <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                <li><a href="<?php echo SITE_URL; ?>/report/report-form.php">Report a Case</a></li>
                <li><a href="<?php echo SITE_URL; ?>/check-status.php">Check Status</a></li>
                <li><a href="<?php echo SITE_URL; ?>/faq.php">FAQ</a></li>
                <li><a href="<?php echo SITE_URL; ?>/privacy.php">Privacy</a></li>
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <li><a href="<?php echo SITE_URL; ?>/dashboard/index.php">Dashboard</a></li>
                <li><a href="<?php echo SITE_URL; ?>/auth/logout.php">Logout</a></li>
                <?php else: ?>
                <li><a href="<?php echo SITE_URL; ?>/auth/login.php">Staff Portal</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<style>
/* Logo Styles */
.logo-img {
    height: 50px;
    width: auto;
    max-height: 50px;
    object-fit: contain;
}

.logo a {
    display: inline-block;
    line-height: 0;
}

/* Mobile menu styles */
.mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
    z-index: 1001;
    transition: transform 0.3s;
}

.mobile-menu-btn:hover {
    transform: scale(1.1);
}

@media (max-width: 768px) {
    .logo-img {
        height: 40px;
    }
    
    .mobile-menu-btn {
        display: block;
    }
    
    .main-nav {
        position: fixed;
        top: 0;
        right: -100%;
        width: 80%;
        max-width: 300px;
        height: 100vh;
        background: var(--primary-color);
        transition: right 0.3s ease-in-out;
        z-index: 1000;
        padding: 4rem 1.5rem 2rem;
        box-shadow: -2px 0 10px rgba(0,0,0,0.2);
    }
    
    .main-nav.active {
        right: 0;
    }
    
    .main-nav ul {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .main-nav a {
        font-size: 1.1rem;
        display: block;
        padding: 0.5rem 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const mainNav = document.getElementById('mainNav');
    
    if (mobileBtn && mainNav) {
        mobileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            mainNav.classList.toggle('active');
            const icon = mobileBtn.querySelector('i');
            if (mainNav.classList.contains('active')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        });
        
        document.addEventListener('click', function(event) {
            if (!mainNav.contains(event.target) && !mobileBtn.contains(event.target)) {
                mainNav.classList.remove('active');
                const icon = mobileBtn.querySelector('i');
                if (icon) icon.className = 'fas fa-bars';
            }
        });
        
        const navLinks = mainNav.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                mainNav.classList.remove('active');
                const icon = mobileBtn.querySelector('i');
                if (icon) icon.className = 'fas fa-bars';
            });
        });
    }
});
</script>
</body>
</html>