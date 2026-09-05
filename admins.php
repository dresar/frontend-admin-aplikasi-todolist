<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/models/AdminModel.php';

// Inisialisasi model
$adminModel = new AdminModel();

// Proses tambah admin baru
if (isset($_POST['action']) && $_POST['action'] == 'add_admin') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // Validasi input
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username tidak boleh kosong';
    } else if ($adminModel->getAdminByUsername($username)) {
        $errors[] = 'Username sudah digunakan';
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password harus minimal 6 karakter';
    }
    
    if (empty($name)) {
        $errors[] = 'Nama tidak boleh kosong';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid';
    }
    
    if (empty($errors)) {
        if ($adminModel->addAdmin($username, $password, $name, $email)) {
            logActivity($_SESSION['admin_id'], 'add_admin', "Menambahkan admin baru: $username");
            setMessage('success', 'Admin baru berhasil ditambahkan');
            redirect('admins.php');
        } else {
            setMessage('danger', 'Gagal menambahkan admin baru');
        }
    } else {
        setMessage('danger', implode('<br>', $errors));
    }
}

// Proses update admin
if (isset($_POST['action']) && $_POST['action'] == 'update_admin') {
    $adminId = $_POST['admin_id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // Validasi input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Nama tidak boleh kosong';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid';
    }
    
    if (empty($errors)) {
        if ($adminModel->updateAdmin($adminId, $name, $email)) {
            logActivity($_SESSION['admin_id'], 'update_admin', "Mengupdate informasi admin ID: $adminId");
            setMessage('success', 'Informasi admin berhasil diperbarui');
            redirect('admins.php?id=' . $adminId);
        } else {
            setMessage('danger', 'Gagal memperbarui informasi admin');
        }
    } else {
        setMessage('danger', implode('<br>', $errors));
    }
}

// Proses update password
if (isset($_POST['action']) && $_POST['action'] == 'update_password') {
    $adminId = $_POST['admin_id'] ?? 0;
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validasi input
    $errors = [];
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password harus minimal 6 karakter';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Konfirmasi password tidak cocok';
    }
    
    if (empty($errors)) {
        if ($adminModel->updatePassword($adminId, $password)) {
            logActivity($_SESSION['admin_id'], 'update_admin_password', "Mengubah password admin ID: $adminId");
            setMessage('success', 'Password admin berhasil diperbarui');
            redirect('admins.php?id=' . $adminId);
        } else {
            setMessage('danger', 'Gagal memperbarui password admin');
        }
    } else {
        setMessage('danger', implode('<br>', $errors));
    }
}

// Proses hapus admin
if (isset($_POST['action']) && $_POST['action'] == 'delete_admin') {
    $adminId = $_POST['admin_id'] ?? 0;
    
    // Pastikan admin tidak menghapus dirinya sendiri
    if ($adminId == $_SESSION['admin_id']) {
        setMessage('danger', 'Anda tidak dapat menghapus akun admin Anda sendiri');
    } else {
        if ($adminModel->deleteAdmin($adminId)) {
            logActivity($_SESSION['admin_id'], 'delete_admin', "Menghapus admin ID: $adminId");
            setMessage('success', 'Admin berhasil dihapus');
            redirect('admins.php');
        } else {
            setMessage('danger', 'Gagal menghapus admin');
        }
    }
}

// Proses buat token admin
if (isset($_POST['action']) && $_POST['action'] == 'create_token') {
    $adminId = $_POST['admin_id'] ?? 0;
    $description = $_POST['description'] ?? '';
    
    $token = $adminModel->createAdminToken($adminId, $description);
    
    if ($token) {
        logActivity($_SESSION['admin_id'], 'create_admin_token', "Membuat token baru untuk admin ID: $adminId");
        setMessage('success', 'Token admin berhasil dibuat');
        $_SESSION['new_token'] = $token; // Simpan token baru di session untuk ditampilkan
    } else {
        setMessage('danger', 'Gagal membuat token admin');
    }
    
    redirect('admins.php?id=' . $adminId);
}

// Mendapatkan detail admin jika ada parameter ID
$admin = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $adminId = $_GET['id'];
    $admin = $adminModel->getAdminById($adminId);
    
    // Jika admin ditemukan, dapatkan token mereka
    if ($admin) {
        $adminTokens = $adminModel->getAdminTokens($adminId);
    }
}

// Mendapatkan semua admin jika tidak ada parameter ID
if (!$admin) {
    $admins = $adminModel->getAllAdmins();
}
?>

<?php if ($admin): // Tampilkan detail admin ?>

<div class="mb-6">
    <div class="w-full">
        <h2 class="text-2xl font-bold mb-2"><i class="fas fa-user-shield mr-2"></i>Detail Admin</h2>
        <nav class="text-sm">
            <ol class="flex flex-wrap">
                <li class="flex items-center">
                    <a href="dashboard.php" class="text-primary hover:text-primary-dark">Dashboard</a>
                    <span class="mx-2">/</span>
                </li>
                <li class="flex items-center">
                    <a href="admins.php" class="text-primary hover:text-primary-dark">Admin</a>
                    <span class="mx-2">/</span>
                </li>
                <li class="text-gray-600" aria-current="page"><?php echo htmlspecialchars($admin['username']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="flex flex-wrap -mx-3">
    <!-- Informasi Admin -->
    <div class="w-full md:w-1/2 px-3 mb-6">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-primary text-white px-4 py-3">
                <h5 class="font-bold"><i class="fas fa-info-circle mr-2"></i>Informasi Admin</h5>
            </div>
            <div class="p-4">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <tr class="border-b hover:bg-gray-50">
                            <th class="py-2 px-3 text-left w-1/3">ID</th>
                            <td class="py-2 px-3"><?php echo $admin['id']; ?></td>
                        </tr>
                        <tr class="border-b hover:bg-gray-50">
                            <th class="py-2 px-3 text-left">Username</th>
                            <td class="py-2 px-3"><?php echo htmlspecialchars($admin['username']); ?></td>
                        </tr>
                        <tr class="border-b hover:bg-gray-50">
                            <th class="py-2 px-3 text-left">Nama</th>
                            <td class="py-2 px-3"><?php echo htmlspecialchars($admin['name']); ?></td>
                        </tr>
                        <tr class="border-b hover:bg-gray-50">
                            <th class="py-2 px-3 text-left">Email</th>
                            <td class="py-2 px-3"><?php echo htmlspecialchars($admin['email']); ?></td>
                        </tr>
                        <tr class="border-b hover:bg-gray-50">
                            <th class="py-2 px-3 text-left">Tanggal Dibuat</th>
                            <td class="py-2 px-3"><?php echo date('d M Y H:i', strtotime($admin['created_at'])); ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Tombol Aksi -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" class="bg-primary hover:bg-primary-dark text-white py-2 px-4 rounded flex items-center" data-bs-toggle="modal" data-bs-target="#editAdminModal">
                        <i class="fas fa-edit mr-1"></i>Edit Informasi
                    </button>
                    
                    <button type="button" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded flex items-center" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="fas fa-key mr-1"></i>Ubah Password
                    </button>
                    
                    <?php if ($admin['id'] != $_SESSION['admin_id']): // Tidak bisa menghapus diri sendiri ?>
                    <button type="button" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded flex items-center" data-bs-toggle="modal" data-bs-target="#deleteAdminModal">
                        <i class="fas fa-trash-alt mr-1"></i>Hapus Admin
                    </button>
                    <?php endif; ?>
                    
                    <a href="admins.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded flex items-center">
                        <i class="fas fa-arrow-left mr-1"></i>Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Token Admin -->
    <div class="w-full md:w-1/2 px-3 mb-6">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-blue-500 text-white px-4 py-3">
                <h5 class="font-bold"><i class="fas fa-key mr-2"></i>Token Admin</h5>
            </div>
            <div class="p-4">
                <?php if (isset($_SESSION['new_token'])): // Tampilkan token baru yang dibuat ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <h5 class="font-bold flex items-center"><i class="fas fa-check-circle mr-1"></i>Token Berhasil Dibuat</h5>
                    <p class="my-2">Berikut adalah token baru Anda. Simpan token ini dengan aman karena tidak akan ditampilkan lagi.</p>
                    <div class="bg-gray-100 border border-gray-300 rounded p-3 mb-3 font-mono text-sm break-all"><?php echo $_SESSION['new_token']; ?></div>
                    <button class="bg-primary hover:bg-primary-dark text-white text-sm py-1 px-3 rounded flex items-center inline-flex copy-token" data-token="<?php echo $_SESSION['new_token']; ?>">
                        <i class="fas fa-copy mr-1"></i>Salin Token
                    </button>
                </div>
                <?php unset($_SESSION['new_token']); // Hapus token dari session setelah ditampilkan ?>
                <?php endif; ?>
                
                <!-- Form Buat Token Baru -->
                <form method="POST" action="" class="mb-6">
                    <input type="hidden" name="action" value="create_token">
                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                    
                    <div class="mb-4">
                        <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi Token</label>
                        <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="description" name="description" placeholder="Contoh: Token untuk Postman">
                    </div>
                    
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded flex items-center">
                        <i class="fas fa-plus-circle mr-1"></i>Buat Token Baru
                    </button>
                </form>
                
                <!-- Daftar Token -->
                <?php if (isset($adminTokens) && count($adminTokens) > 0): ?>
                <h6 class="font-bold text-gray-700 mb-3">Daftar Token</h6>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="py-2 px-3 text-left">Token (Sebagian)</th>
                                <th class="py-2 px-3 text-left">Deskripsi</th>
                                <th class="py-2 px-3 text-left">Tanggal Dibuat</th>
                                <th class="py-2 px-3 text-left">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adminTokens as $token): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-2 px-3">
                                    <div class="font-mono bg-gray-100 px-2 py-1 rounded">
                                        <?php echo substr($token['token'], 0, 15) . '...'; ?>
                                    </div>
                                </td>
                                <td class="py-2 px-3"><?php echo htmlspecialchars($token['description'] ?? 'Tidak ada deskripsi'); ?></td>
                                <td class="py-2 px-3"><?php echo date('d M Y H:i', strtotime($token['created_at'])); ?></td>
                                <td class="py-2 px-3">
                                    <form method="POST" action="tokens.php" class="inline">
                                        <input type="hidden" name="action" value="revoke_admin_token">
                                        <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white p-1 rounded btn-delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative">
                    <i class="fas fa-info-circle mr-1"></i>Admin ini belum memiliki token.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Admin -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_admin">
                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                
                <div class="flex items-center justify-between p-4 border-b">
                    <h5 class="text-lg font-bold" id="editAdminModalLabel">Edit Informasi Admin</h5>
                    <button type="button" class="text-gray-400 hover:text-gray-500" data-bs-dismiss="modal" aria-label="Close">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="p-4">
                    <div class="mb-4">
                        <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                        <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-100" id="username" value="<?php echo htmlspecialchars($admin['username']); ?>" disabled>
                        <p class="text-gray-500 text-xs mt-1">Username tidak dapat diubah.</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nama</label>
                        <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                        <input type="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>
                </div>
                
                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6 border-t">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded mr-2" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white py-2 px-4 rounded">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ubah Password -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                
                <div class="flex items-center justify-between p-4 border-b">
                    <h5 class="text-lg font-bold" id="changePasswordModalLabel">Ubah Password</h5>
                    <button type="button" class="text-gray-400 hover:text-gray-500" data-bs-dismiss="modal" aria-label="Close">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="p-4">
                    <div class="mb-4">
                        <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password Baru</label>
                        <div class="flex">
                            <input type="password" class="shadow appearance-none border rounded-l w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="password" name="password" required>
                            <button type="button" class="bg-gray-200 hover:bg-gray-300 border border-gray-300 rounded-r px-3 toggle-password" data-target="#password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Konfirmasi Password</label>
                        <div class="flex">
                            <input type="password" class="shadow appearance-none border rounded-l w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="confirm_password" name="confirm_password" required>
                            <button type="button" class="bg-gray-200 hover:bg-gray-300 border border-gray-300 rounded-r px-3 toggle-password" data-target="#confirm_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6 border-t">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded mr-2" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white py-2 px-4 rounded">Ubah Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Admin -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1" aria-labelledby="deleteAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete_admin">
                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                
                <div class="flex items-center justify-between p-4 border-b">
                    <h5 class="text-lg font-bold" id="deleteAdminModalLabel">Konfirmasi Hapus Admin</h5>
                    <button type="button" class="text-gray-400 hover:text-gray-500" data-bs-dismiss="modal" aria-label="Close">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="p-4">
                    <p class="mb-3">Apakah Anda yakin ingin menghapus admin <strong><?php echo htmlspecialchars($admin['username']); ?></strong>?</p>
                    <p class="text-red-600">Tindakan ini tidak dapat dibatalkan dan semua token admin ini akan dihapus.</p>
                </div>
                
                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6 border-t">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded mr-2" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded">Hapus Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php else: // Tampilkan daftar admin ?>

<div class="mb-6">
    <div class="w-full">
        <h2 class="text-2xl font-bold mb-2"><i class="fas fa-user-shield mr-2"></i>Manajemen Admin</h2>
        <p class="text-gray-600">Kelola semua admin yang memiliki akses ke panel admin.</p>
    </div>
</div>

<div class="mb-6">
    <div class="w-full">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-primary text-white px-4 py-3">
                <h5 class="font-bold"><i class="fas fa-plus-circle mr-2"></i>Tambah Admin Baru</h5>
            </div>
            <div class="p-4">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_admin">
                    
                    <div class="flex flex-wrap -mx-3">
                        <div class="w-full md:w-1/2 px-3 mb-4">
                            <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                            <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="username" name="username" required>
                        </div>
                        
                        <div class="w-full md:w-1/2 px-3 mb-4">
                            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                            <div class="flex">
                                <input type="password" class="shadow appearance-none border rounded-l w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="password" name="password" required>
                                <button type="button" class="bg-gray-200 hover:bg-gray-300 border border-gray-300 rounded-r px-3 toggle-password" data-target="#password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap -mx-3">
                        <div class="w-full md:w-1/2 px-3 mb-4">
                            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nama</label>
                            <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" name="name" required>
                        </div>
                        
                        <div class="w-full md:w-1/2 px-3 mb-4">
                            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                            <input type="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded flex items-center">
                        <i class="fas fa-plus-circle mr-1"></i>Tambah Admin
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden mt-6">
    <div class="bg-primary text-white px-4 py-3">
        <h5 class="font-bold"><i class="fas fa-list mr-2"></i>Daftar Admin</h5>
    </div>
    <div class="p-4">
        <?php if (isset($admins) && count($admins) > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white" id="adminsTable">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 border-b text-left">ID</th>
                        <th class="py-2 px-4 border-b text-left">Username</th>
                        <th class="py-2 px-4 border-b text-left">Nama</th>
                        <th class="py-2 px-4 border-b text-left">Email</th>
                        <th class="py-2 px-4 border-b text-left">Tanggal Dibuat</th>
                        <th class="py-2 px-4 border-b text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border-b"><?php echo $admin['id']; ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($admin['username']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($admin['name']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo date('d M Y', strtotime($admin['created_at'])); ?></td>
                        <td class="py-2 px-4 border-b">
                            <a href="admins.php?id=<?php echo $admin['id']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white py-1 px-2 rounded text-sm inline-flex items-center">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4">
            <i class="fas fa-info-circle mr-1"></i>Tidak ada admin yang terdaftar.
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Script untuk DataTables -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

<script>
$(document).ready(function() {
    $('#adminsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
        },
        order: [[0, 'desc']]
    });
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>