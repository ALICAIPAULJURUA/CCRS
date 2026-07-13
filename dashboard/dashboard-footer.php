<?php
// Close any open divs if needed
?>
        </main>
    </div>
    
    <script src="../public/js/dashboard.js"></script>
    <script>
        // Mobile menu toggle for sidebar
        const sidebar = document.querySelector('.dashboard-sidebar');
        const toggleBtn = document.createElement('button');
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        toggleBtn.className = 'mobile-sidebar-toggle';
        toggleBtn.onclick = function() {
            sidebar.classList.toggle('active');
        };
        
        // Add toggle button if on mobile
        if (window.innerWidth <= 768) {
            document.body.insertBefore(toggleBtn, document.body.firstChild);
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
    <style>
        .mobile-sidebar-toggle {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: none;
        }
        
        @media (max-width: 768px) {
            .mobile-sidebar-toggle {
                display: block;
            }
            
            .dashboard-sidebar {
                position: fixed;
                left: -280px;
                top: 0;
                z-index: 999;
                transition: left 0.3s ease;
                height: 100vh;
            }
            
            .dashboard-sidebar.active {
                left: 0;
            }
            
            .dashboard-main {
                margin-left: 0;
                padding-top: 60px;
            }
        }
    </style>
</body>
</html>