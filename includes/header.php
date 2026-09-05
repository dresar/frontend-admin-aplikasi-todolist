<?php
require_once __DIR__ . '/../config/config.php';

// Redirect ke halaman login jika belum login
if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php') {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <!-- Bootstrap CSS (diimpor terlebih dahulu) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#4e73df',
                            dark: '#224abe'
                        },
                        success: {
                            DEFAULT: '#1cc88a',
                            dark: '#13855c'
                        },
                        info: {
                            DEFAULT: '#36b9cc',
                            dark: '#258391'
                        },
                        warning: {
                            DEFAULT: '#f6c23e',
                            dark: '#dda20a'
                        },
                        danger: {
                            DEFAULT: '#e74a3b',
                            dark: '#be3c30'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Script untuk menu mobile dan dropdown -->
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('navbarMenu');
            menu.classList.toggle('hidden');
        }

        // Menutup menu mobile saat mengklik di luar menu
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('navbarMenu');
            const menuButton = document.querySelector('button[onclick="toggleMobileMenu()"]');
            const userDropdown = document.getElementById('userDropdown');
            const userDropdownButton = document.querySelector('button[onclick="document.getElementById(\'userDropdown\').classList.toggle(\'hidden\')"]');
            
            // Jika klik di luar menu dan bukan pada tombol menu, tutup menu
            if (!menu.contains(event.target) && event.target !== menuButton && !menuButton.contains(event.target)) {
                menu.classList.add('hidden');
            }
            
            // Jika klik di luar dropdown dan bukan pada tombol dropdown, tutup dropdown
            if (userDropdown && userDropdownButton && !userDropdown.contains(event.target) && event.target !== userDropdownButton && !userDropdownButton.contains(event.target)) {
                userDropdown.classList.add('hidden');
            }
        }, true);
    </script>
</head>
<body class="bg-gray-50">
    <!-- Navbar - Fixed and Smaller -->
    <nav class="bg-primary text-white shadow-md fixed top-0 left-0 right-0 z-50">
        <div class="container mx-auto px-3">
            <div class="flex flex-wrap items-center justify-between py-1">
                <a class="flex items-center text-sm font-bold" href="<?php echo BASE_URL; ?>/dashboard.php">
                    <i class="fas fa-tasks mr-1 text-xs"></i><?php echo APP_NAME; ?>
                </a>
                <button class="lg:hidden rounded-md p-0.5 hover:bg-primary-dark focus:outline-none" type="button" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars text-xs"></i>
                </button>
                <?php if (isLoggedIn()): ?>
                <div class="hidden w-full lg:flex lg:w-auto lg:items-center absolute lg:static top-8 left-0 right-0 bg-primary lg:bg-transparent z-40 shadow-lg lg:shadow-none" id="navbarMenu">
                    <ul class="flex flex-col lg:flex-row lg:space-x-1 mt-1 lg:mt-0 text-xs px-2 lg:px-0 py-1 lg:py-0">
                        <li>
                            <a class="block py-0.5 px-1.5 rounded hover:bg-primary-dark" href="<?php echo BASE_URL; ?>/dashboard.php">
                                <i class="fas fa-tachometer-alt mr-0.5 text-xs"></i>Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="block py-0.5 px-1.5 rounded hover:bg-primary-dark" href="<?php echo BASE_URL; ?>/users.php">
                                <i class="fas fa-users mr-0.5 text-xs"></i>Pengguna
                            </a>
                        </li>
                        <li>
                            <a class="block py-0.5 px-1.5 rounded hover:bg-primary-dark" href="<?php echo BASE_URL; ?>/admins.php">
                                <i class="fas fa-user-shield mr-0.5 text-xs"></i>Admin
                            </a>
                        </li>
                        <li>
                            <a class="block py-0.5 px-1.5 rounded hover:bg-primary-dark" href="<?php echo BASE_URL; ?>/tokens.php">
                                <i class="fas fa-key mr-0.5 text-xs"></i>Token
                            </a>
                        </li>
                        <li>
                            <a class="block py-0.5 px-1.5 rounded hover:bg-primary-dark" href="<?php echo BASE_URL; ?>/categories.php">
                                <i class="fas fa-tags mr-0.5 text-xs"></i>Kategori
                            </a>
                        </li>
                        <li>
                            <a class="block py-0.5 px-1.5 rounded hover:bg-primary-dark" href="<?php echo BASE_URL; ?>/tasks.php">
                                <i class="fas fa-clipboard-list mr-0.5 text-xs"></i>Tugas
                            </a>
                        </li>
                        <li class="mb-1">
                            <div class="block py-0.5 px-1.5 rounded hover:bg-primary-dark cursor-pointer" onclick="toggleSubMenu('activitySubMenu')">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <i class="fas fa-chart-line mr-0.5 text-xs"></i>Aktivitas
                                    </div>
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                            <ul id="activitySubMenu" class="pl-3 mt-1 hidden">
                                <li>
                                    <a class="block py-0.5 px-1.5 rounded hover:bg-primary-dark" href="<?php echo BASE_URL; ?>/dashboard_activity.php">
                                        <i class="fas fa-chart-area mr-0.5 text-xs"></i>Ringkasan
                                    </a>
                                </li>
                                <li>
                                    <a class="block py-0.5 px-1.5 rounded hover:bg-primary-dark" href="<?php echo BASE_URL; ?>/activity_statistics.php">
                                        <i class="fas fa-chart-pie mr-0.5 text-xs"></i>Statistik
                                    </a>
                                </li>
                                <li>
                                    <a class="block py-0.5 px-1.5 rounded hover:bg-primary-dark" href="<?php echo BASE_URL; ?>/user_activities.php">
                                        <i class="fas fa-list mr-0.5 text-xs"></i>Semua Aktivitas
                                    </a>
                                </li>
                            </ul>
                        </li>

                    </ul>
                    <div class="relative ml-auto mt-1 lg:mt-0">
                        <button onclick="document.getElementById('userDropdown').classList.toggle('hidden')" class="flex items-center py-0.5 px-1.5 rounded hover:bg-primary-dark text-xs">
                            <?php 
                            // Ambil data admin untuk mendapatkan foto profil
                            if (isset($_SESSION['admin_id'])) {
                                require_once __DIR__ . '/../models/AdminModel.php';
                                $adminModel = new AdminModel();
                                $admin = $adminModel->getAdminById($_SESSION['admin_id']);
                                
                                if (!empty($admin['profile_photo']) && file_exists(__DIR__ . '/../uploads/profiles/' . $admin['profile_photo'])) {
                                    echo '<img src="' . BASE_URL . '/uploads/profiles/' . $admin['profile_photo'] . '" alt="Foto Profil" class="w-4 h-4 rounded-full mr-0.5 object-cover">';
                                } else {
                                    echo '<i class="fas fa-user-circle mr-0.5 text-xs"></i>';
                                }
                            } else {
                                echo '<i class="fas fa-user-circle mr-0.5 text-xs"></i>';
                            }
                            ?>
                            <span class="hidden sm:inline"><?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></span>
                            <i class="fas fa-chevron-down ml-0.5 text-xs"></i>
                        </button>
                        <div id="userDropdown" class="hidden absolute right-0 mt-1 w-36 bg-white rounded-md shadow-lg py-0.5 z-10">
                            <a href="<?php echo BASE_URL; ?>/profile.php" class="block px-2 py-0.5 text-xs text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user-cog mr-0.5 text-xs"></i>Profil
                            </a>
                            <div class="border-t border-gray-200 my-0.5"></div>
                            <a href="<?php echo BASE_URL; ?>/logout.php" class="block px-2 py-0.5 text-xs text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-0.5 text-xs"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content with padding-top to accommodate fixed header -->
    <div class="container mx-auto px-4 py-6 mt-16">
        <?php
        $message = getMessage();
        if ($message): 
            $alertColor = 'bg-blue-100 text-blue-800 border-blue-200';
            if ($message['type'] == 'success') {
                $alertColor = 'bg-green-100 text-green-800 border-green-200';
            } elseif ($message['type'] == 'danger') {
                $alertColor = 'bg-red-100 text-red-800 border-red-200';
            } elseif ($message['type'] == 'warning') {
                $alertColor = 'bg-yellow-100 text-yellow-800 border-yellow-200';
            } elseif ($message['type'] == 'info') {
                $alertColor = 'bg-blue-100 text-blue-800 border-blue-200';
            }
        ?>
        <div class="<?php echo $alertColor; ?> px-4 py-3 rounded relative border mb-4" role="alert">
            <span class="block sm:inline"><?php echo $message['text']; ?></span>
            <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                <svg class="fill-current h-6 w-6" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
            </button>
        </div>
        <?php endif; ?>