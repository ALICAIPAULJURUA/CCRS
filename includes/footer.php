<footer class="site-footer">
    <div class="footer-content">
        <div class="footer-section">
            <h3>About CCRS</h3>
            <p>Community Crime Reporting System provides a safe, anonymous platform for reporting gender-based violence and child abuse in Arua City and Muni University.</p>
        </div>
        
        <div class="footer-section">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                <li><a href="<?php echo SITE_URL; ?>/report/report-form.php">Report a Case</a></li>
                <li><a href="<?php echo SITE_URL; ?>/check-status.php">Check Status</a></li>
                <li><a href="<?php echo SITE_URL; ?>/faq.php">FAQ</a></li>
                <li><a href="<?php echo SITE_URL; ?>/privacy.php">Privacy Policy</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3>Emergency Contacts</h3>
            <ul>
                <li><i class="fas fa-phone"></i> Police: 999</li>
                <li><i class="fas fa-phone"></i> Child Helpline: 116</li>
                <li><i class="fas fa-phone"></i> GBV Hotline: 0772006065</li>
                <li><i class="fas fa-envelope"></i> <?php echo getConfig('contact_email'); ?></li>
            </ul>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Community Crime Reporting System. All rights reserved.</p>
        <p>Muni University & Arua City, Uganda</p>
    </div>
</footer>