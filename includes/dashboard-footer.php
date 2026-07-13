<?php
// NO WHITESPACE BEFORE THIS OPENING TAG
// This file should have NO output before the closing div
?>
        </main>
    </div>
    
    <script src="../public/js/dashboard.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile sidebar toggle
        const sidebar = document.querySelector('.dashboard-sidebar');
        if (sidebar && !document.querySelector('.mobile-sidebar-toggle')) {
            const toggleBtn = document.createElement('button');
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            toggleBtn.className = 'mobile-sidebar-toggle';
            toggleBtn.setAttribute('aria-label', 'Toggle Menu');
            toggleBtn.onclick = function() {
                sidebar.classList.toggle('active');
                const icon = this.querySelector('i');
                if (sidebar.classList.contains('active')) {
                    icon.className = 'fas fa-times';
                } else {
                    icon.className = 'fas fa-bars';
                }
            };
            document.body.insertBefore(toggleBtn, document.body.firstChild);
        }
        
        // Auto-hide alerts
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) alert.remove();
                }, 300);
            }, 5000);
        });
    });
    </script>
    <style>
    .mobile-sidebar-toggle {
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1001;
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 12px 15px;
        border-radius: 5px;
        cursor: pointer;
        display: none;
        font-size: 18px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    @media (max-width: 768px) {
        .mobile-sidebar-toggle {
            display: block;
        }
        
        .dashboard-sidebar {
            position: fixed;
            left: -280px;
            top: 0;
            z-index: 1000;
            transition: left 0.3s ease;
            height: 100vh;
            overflow-y: auto;
        }
        
        .dashboard-sidebar.active {
            left: 0;
        }
        
        .dashboard-main {
            margin-left: 0;
            padding-top: 70px;
        }
    }
    </style>
</body>
</html>
<?php
// End output buffering and flush
ob_end_flush();
?>