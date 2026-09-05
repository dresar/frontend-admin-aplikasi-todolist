</div> <!-- End of Main Content -->

    <!-- Footer -->
    <footer class="bg-gray-100 py-4 mt-8">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-600">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    
    <!-- Fungsi untuk submenu -->
    <script>
    function toggleSubMenu(menuId) {
        const subMenu = document.getElementById(menuId);
        if (subMenu) {
            if (subMenu.classList.contains('hidden')) {
                subMenu.classList.remove('hidden');
            } else {
                subMenu.classList.add('hidden');
            }
        }
    }
    
    // Cek URL saat ini untuk menampilkan submenu yang aktif
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('dashboard_activity.php') || 
            currentPath.includes('activity_statistics.php') || 
            currentPath.includes('user_activities.php')) {
            const activitySubMenu = document.getElementById('activitySubMenu');
            if (activitySubMenu) {
                activitySubMenu.classList.remove('hidden');
            }
        }
    });
    </script>
    
    <!-- Inisialisasi Bootstrap secara manual -->
    <script>
        // Pastikan jQuery dan Bootstrap tersedia
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof jQuery !== 'undefined') {
                console.log('jQuery is loaded in footer');
                
                if (typeof bootstrap !== 'undefined') {
                    console.log('Bootstrap is loaded in footer');
                    
                    // Inisialisasi semua dropdown
                    var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
                    var dropdownCount = 0;
                    dropdownElementList.forEach(function(element) {
                        try {
                            new bootstrap.Dropdown(element);
                            dropdownCount++;
                        } catch (e) {
                            console.error('Error initializing dropdown:', e);
                        }
                    });
                    console.log('Initialized ' + dropdownCount + ' dropdowns');
                    
                    // Inisialisasi semua modal
                    var modalElementList = [].slice.call(document.querySelectorAll('.modal'));
                    var modalCount = 0;
                    modalElementList.forEach(function(element) {
                        try {
                            new bootstrap.Modal(element);
                            modalCount++;
                        } catch (e) {
                            console.error('Error initializing modal:', e);
                        }
                    });
                    console.log('Initialized ' + modalCount + ' modals');
                } else {
                    console.error('Bootstrap is not loaded in footer');
                }
            } else {
                console.error('jQuery is not loaded in footer');
            }
        });
    </script>
    <script>
        // Close dropdowns when clicking outside (for non-Bootstrap dropdowns)
        window.addEventListener('click', function(e) {
            if (!e.target.closest('#userDropdown') && !e.target.closest('button[onclick*="userDropdown"]')) {
                var userDropdown = document.getElementById('userDropdown');
                if (userDropdown) {
                    userDropdown.classList.add('hidden');
                }
            }
        });
        
        // Pastikan jQuery tersedia sebelum menggunakan
        if (typeof jQuery !== 'undefined') {
            console.log('jQuery is loaded');
        } else {
            console.error('jQuery is not loaded');
        }
    </script>
</body>
</html>