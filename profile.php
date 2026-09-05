<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/models/AdminModel.php';

// Inisialisasi model
$adminModel = new AdminModel();

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('login.php');
}

// Dapatkan data admin yang sedang login
$adminId = $_SESSION['admin_id'];
$admin = $adminModel->getAdminById($adminId);

if (!$admin) {
    setMessage('danger', 'Admin tidak ditemukan');
    redirect('dashboard.php');
}

// Proses update profil
if (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    
    // Validasi input
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username tidak boleh kosong';
    }
    
    if (empty($email)) {
        $errors[] = 'Email tidak boleh kosong';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    } else {
        // Cek apakah email sudah digunakan oleh admin lain
        $emailExists = $adminModel->checkEmailExists($email, $adminId);
        if ($emailExists) {
            $errors[] = 'Email sudah digunakan';
        }
        
        // Pengecekan email sudah dilakukan di atas
    }
    
    // Validasi username
    if (empty($username)) {
        $errors[] = 'Username tidak boleh kosong';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username minimal 3 karakter';
    } else {
        // Cek apakah username sudah digunakan oleh admin lain
        $usernameExists = $adminModel->checkUsernameExists($username, $adminId);
        if ($usernameExists) {
            $errors[] = 'Username sudah digunakan';
        }
    }
    
    // Proses upload foto profil jika ada
    $profilePhoto = $admin['profile_photo'] ?? null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        // Validasi tipe file
        if (!in_array($_FILES['profile_photo']['type'], $allowedTypes)) {
            $errors[] = 'Tipe file tidak didukung. Gunakan JPG, PNG, atau GIF';
        }
        // Validasi ukuran file
        elseif ($_FILES['profile_photo']['size'] > $maxSize) {
            $errors[] = 'Ukuran file terlalu besar. Maksimal 2MB';
        }
        else {
            // Buat direktori jika belum ada
            $uploadDir = __DIR__ . '/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate nama file unik
            $fileExt = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $newFileName = 'profile_' . $adminId . '_' . time() . '.' . $fileExt;
            $uploadPath = $uploadDir . $newFileName;
            
            // Upload file
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadPath)) {
                // Hapus foto lama jika ada
                if (!empty($admin['profile_photo'])) {
                    $oldPhotoPath = $uploadDir . $admin['profile_photo'];
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }
                
                $profilePhoto = $newFileName;
            } else {
                $errors[] = 'Gagal mengupload foto profil';
            }
        }
    }
    
    // Update profil jika tidak ada error
    if (empty($errors)) {
        if ($adminModel->updateProfile($adminId, $username, $email, $name, $profilePhoto)) {
            logActivity($adminId, 'update_profile', "Memperbarui profil");
            setMessage('success', 'Profil berhasil diperbarui');
            redirect('profile.php');
        } else {
            setMessage('danger', 'Gagal memperbarui profil');
        }
    } else {
        // Tampilkan pesan error
        foreach ($errors as $error) {
            setMessage('danger', $error);
        }
    }
}

// Proses hapus akun
if (isset($_POST['action']) && $_POST['action'] == 'delete_account') {
    $password = $_POST['password'] ?? '';
    
    // Validasi password
    if (empty($password)) {
        setMessage('danger', 'Password tidak boleh kosong');
    } else {
        // Verifikasi password
        if ($adminModel->verifyPassword($adminId, $password)) {
            // Hapus foto profil jika ada
            if (!empty($admin['profile_photo'])) {
                $uploadDir = __DIR__ . '/uploads/profiles/';
                $photoPath = $uploadDir . $admin['profile_photo'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
            
            // Hapus akun
            if ($adminModel->deleteUser($adminId)) {
                // Logout
                session_destroy();
                setMessage('success', 'Akun berhasil dihapus');
                redirect('login.php');
            } else {
                setMessage('danger', 'Gagal menghapus akun');
            }
        } else {
            setMessage('danger', 'Password salah');
        }
    }
}

// Metode addMethod dan __call sudah ditambahkan ke class AdminModel
// Metode updateProfile, verifyPassword, dan deleteUser sudah ada di AdminModel.php
?>

<div class="container mx-auto px-4 py-2">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-2">
        <h1 class="text-lg font-bold text-gray-800 mb-1 sm:mb-0">
            <i class="fas fa-user-circle mr-1 text-primary"></i> Profil Saya
        </h1>
        <a href="dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-0.5 px-2 text-xs rounded-md inline-flex items-center transition duration-150 ease-in-out">
            <i class="fas fa-arrow-left mr-0.5"></i> Kembali ke Dashboard
        </a>
    </div>
    
    <?php $message = getMessage(); ?>
    <?php if ($message): ?>
    <div class="mb-4 p-4 rounded-md <?php echo $message['type'] == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
        <?php echo $message['text']; ?>
    </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
        <!-- Informasi Profil -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-primary to-primary-dark text-white px-3 py-1">
                    <h2 class="text-sm font-semibold">Informasi Profil</h2>
                </div>
                
                <div class="p-3">
                    <form method="POST" action="" enctype="multipart/form-data" class="space-y-2">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <!-- Username -->
                            <div>
                                <label for="username" class="block text-xs font-medium text-gray-700 mb-0.5">
                                    Username <span class="text-red-600">*</span>
                                </label>
                                <input type="text" id="username" name="username" required 
                                    value="<?php echo htmlspecialchars($admin['username']); ?>"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-xs py-0.5">
                            </div>
                            
                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-xs font-medium text-gray-700 mb-0.5">
                                    Email <span class="text-red-600">*</span>
                                </label>
                                <input type="email" id="email" name="email" required 
                                    value="<?php echo htmlspecialchars($admin['email']); ?>"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-xs py-0.5">
                            </div>
                            
                            <!-- Nama -->
                            <div>
                                <label for="name" class="block text-xs font-medium text-gray-700 mb-0.5">
                                    Nama Lengkap
                                </label>
                                <input type="text" id="name" name="name" 
                                    value="<?php echo htmlspecialchars($admin['name'] ?? ''); ?>"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-xs py-0.5">
                            </div>
                            
                            <!-- Foto Profil -->
                            <div>
                                <label for="profile_photo" class="block text-xs font-medium text-gray-700 mb-0.5">
                                    Foto Profil
                                </label>
                                <input type="file" id="profile_photo" name="profile_photo" accept="image/*"
                                    class="w-full text-xs text-gray-500 file:mr-2 file:py-0.5 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark">
                                <p class="mt-0.5 text-xs text-gray-500">Format: JPG, PNG, GIF. Maksimal 2MB.</p>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-0.5 px-2 text-xs rounded-md inline-flex items-center transition duration-150 ease-in-out">
                                <i class="fas fa-save mr-0.5 text-xs"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Profil -->
        <div>
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-2">
                <div class="bg-gradient-to-r from-primary to-primary-dark text-white px-3 py-1">
                    <h2 class="text-sm font-semibold">Foto Profil</h2>
                </div>
                
                <div class="p-3 text-center">
                    <?php if (!empty($admin['profile_photo']) && file_exists(__DIR__ . '/uploads/profiles/' . $admin['profile_photo'])): ?>
                        <img src="<?php echo BASE_URL . '/uploads/profiles/' . $admin['profile_photo']; ?>" 
                             alt="Foto Profil" 
                             class="w-16 h-16 rounded-full mx-auto object-cover border-2 border-primary">
                    <?php else: ?>
                        <div class="w-16 h-16 rounded-full mx-auto bg-gray-200 flex items-center justify-center border-2 border-primary">
                            <i class="fas fa-user text-xl text-gray-400"></i>
                        </div>
                    <?php endif; ?>
                    
                    <h3 class="mt-2 text-base font-semibold text-gray-800">
                        <?php echo htmlspecialchars($admin['name'] ?? $admin['username']); ?>
                    </h3>
                    <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($admin['email']); ?></p>
                    
                    <div class="mt-2 pt-2 border-t border-gray-200">
                        <p class="text-xs text-gray-600 mb-0.5">
                            <i class="fas fa-calendar-alt mr-0.5 text-xs"></i> Bergabung: 
                            <?php echo date('d M Y', strtotime($admin['created_at'])); ?>
                        </p>
                        <p class="text-xs text-gray-600">
                            <i class="fas fa-circle mr-0.5 text-xs <?php echo isset($admin['is_active']) && $admin['is_active'] ? 'text-green-500' : 'text-red-500'; ?>"></i>
                            Status: <?php echo isset($admin['is_active']) && $admin['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Hapus Akun -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-red-600 to-red-700 text-white px-3 py-1">
                    <h2 class="text-sm font-semibold">Hapus Akun</h2>
                </div>
                
                <div class="p-3">
                    <p class="text-gray-600 mb-2 text-xs">
                        Tindakan ini akan menghapus akun Anda secara permanen beserta semua data terkait. 
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                    
                    <button type="button" onclick="document.getElementById('deleteAccountModal').classList.remove('hidden');"
                        class="bg-red-600 hover:bg-red-700 text-white font-medium py-0.5 px-2 text-xs rounded-md inline-flex items-center transition duration-150 ease-in-out w-full justify-center">
                        <i class="fas fa-trash-alt mr-0.5 text-xs"></i> Hapus Akun Saya
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hapus Akun -->
<div class="modal fade fixed inset-0 overflow-y-auto hidden bg-gray-500 bg-opacity-75 transition-opacity z-50" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full">
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete_account">
                
                <div class="bg-white px-3 pt-2 pb-2 sm:p-3 sm:pb-2">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-8 w-8 rounded-full bg-red-100 sm:mx-0 sm:h-6 sm:w-6">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xs"></i>
                        </div>
                        <div class="mt-2 text-center sm:mt-0 sm:ml-2 sm:text-left w-full">
                            <h3 class="text-sm leading-6 font-medium text-gray-900" id="deleteAccountModalLabel">
                                Konfirmasi Hapus Akun
                            </h3>
                            <div class="mt-1 space-y-2">
                                <p class="text-xs text-gray-500">
                                    Apakah Anda yakin ingin menghapus akun Anda? Semua data Anda akan dihapus secara permanen.
                                    Tindakan ini tidak dapat dibatalkan.
                                </p>
                                <div>
                                    <label for="password" class="block text-xs font-medium text-gray-700 mb-0.5">
                                        Masukkan Password Anda untuk Konfirmasi <span class="text-red-600">*</span>
                                    </label>
                                    <input type="password" id="password" name="password" required 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-500 focus:ring-opacity-50 text-xs py-0.5"
                                        placeholder="Masukkan password Anda">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-2 py-1.5 sm:px-3 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-2 py-0.5 bg-red-600 text-xs font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-red-500 sm:ml-2 sm:w-auto transition duration-150 ease-in-out">
                        Hapus Akun Saya
                    </button>
                    <button type="button" class="mt-2 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-2 py-0.5 bg-white text-xs font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-blue-500 sm:mt-0 sm:ml-2 sm:w-auto transition duration-150 ease-in-out"
                        onclick="document.getElementById('deleteAccountModal').classList.add('hidden');">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Script untuk menutup modal saat mengklik di luar
document.addEventListener('click', function(event) {
    const modal = document.getElementById('deleteAccountModal');
    if (event.target === modal) {
        modal.classList.add('hidden');
    }
});

// Script untuk menutup modal dengan tombol Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.getElementById('deleteAccountModal').classList.add('hidden');
    }
});

// Preview foto profil sebelum upload
document.getElementById('profile_photo').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const profileImages = document.querySelectorAll('.profile-image');
            profileImages.forEach(img => {
                img.src = e.target.result;
            });
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>