<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/models/UserModel.php';

// Inisialisasi model
$userModel = new UserModel();

// Proses perubahan status pengguna
if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $userId = $_POST['user_id'] ?? 0;
    $status = $_POST['status'] ?? 0;
    
    if ($userModel->updateUserStatus($userId, $status)) {
        $statusText = $status ? 'aktif' : 'nonaktif';
        logActivity($_SESSION['admin_id'], 'update_user_status', "Mengubah status pengguna ID: $userId menjadi $statusText");
        setMessage('success', "Status pengguna berhasil diubah menjadi $statusText");
    } else {
        setMessage('danger', 'Gagal mengubah status pengguna');
    }
    
    redirect('users.php');
}

// Proses penambahan pengguna baru
if (isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validasi input
    $errors = [];
    if (empty($username)) {
        $errors[] = 'Username tidak boleh kosong';
    }
    if (empty($email)) {
        $errors[] = 'Email tidak boleh kosong';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    }
    if (empty($password)) {
        $errors[] = 'Password tidak boleh kosong';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }
    
    if (empty($errors)) {
        $result = $userModel->addUser($username, $email, $password, $is_active);
        
        if ($result['success']) {
            logActivity($_SESSION['admin_id'], 'add_user', "Menambahkan pengguna baru: $username");
            setMessage('success', "Pengguna baru berhasil ditambahkan");
            redirect('users.php');
        } else {
            setMessage('danger', $result['message']);
        }
    } else {
        setMessage('danger', implode('<br>', $errors));
    }
}

// Proses penghapusan pengguna
if (isset($_POST['action']) && $_POST['action'] == 'delete_user') {
    $userId = $_POST['user_id'] ?? 0;
    $username = $_POST['username'] ?? 'Pengguna';
    
    if ($userModel->deleteUser($userId)) {
        logActivity($_SESSION['admin_id'], 'delete_user', "Menghapus pengguna: $username (ID: $userId)");
        setMessage('success', "Pengguna $username berhasil dihapus");
    } else {
        setMessage('danger', 'Gagal menghapus pengguna');
    }
    
    redirect('users.php');
}

// Mendapatkan detail pengguna jika ada parameter ID
$user = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $userId = $_GET['id'];
    $user = $userModel->getUserById($userId);
    
    // Jika pengguna ditemukan, dapatkan token mereka
    if ($user) {
        $userTokens = $userModel->getUserTokens($userId);
    }
}

// Mendapatkan semua pengguna jika tidak ada parameter ID
if (!$user) {
    $users = $userModel->getAllUsers();
}
?>

<?php if ($user): // Tampilkan detail pengguna ?>

<div class="mb-6">
    <div class="w-full">
        <h2 class="text-2xl font-bold mb-2"><i class="fas fa-user mr-2"></i>Detail Pengguna</h2>
        <nav class="text-sm">
            <ol class="flex flex-wrap">
                <li class="flex items-center">
                    <a href="dashboard.php" class="text-primary hover:text-primary-dark">Dashboard</a>
                    <span class="mx-2">/</span>
                </li>
                <li class="flex items-center">
                    <a href="users.php" class="text-primary hover:text-primary-dark">Pengguna</a>
                    <span class="mx-2">/</span>
                </li>
                <li class="text-gray-600" aria-current="page"><?php echo htmlspecialchars($user['username']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="flex flex-wrap -mx-3">
    <!-- Informasi Pengguna -->
    <div class="w-full md:w-1/2 px-3 mb-6">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-primary text-white px-4 py-3">
                <h5 class="font-bold"><i class="fas fa-info-circle mr-2"></i>Informasi Pengguna</h5>
            </div>
            <div class="p-4">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <tr class="border-b hover:bg-gray-50">
                            <th class="py-2 px-3 text-left w-1/3">ID</th>
                            <td class="py-2 px-3"><?php echo $user['id']; ?></td>
                        </tr>
                        <tr class="border-b hover:bg-gray-50">
                            <th class="py-2 px-3 text-left">Username</th>
                            <td class="py-2 px-3"><?php echo htmlspecialchars($user['username']); ?></td>
                        </tr>
                        <tr class="border-b hover:bg-gray-50">
                            <th class="py-2 px-3 text-left">Email</th>
                            <td class="py-2 px-3"><?php echo htmlspecialchars($user['email']); ?></td>
                        </tr>
                        <tr class="border-b hover:bg-gray-50">
                            <th class="py-2 px-3 text-left">Nama</th>
                            <td class="py-2 px-3"><?php echo htmlspecialchars($user['name'] ?? 'Tidak ada'); ?></td>
                        </tr>
                        <tr class="border-b hover:bg-gray-50">
                            <th class="py-2 px-3 text-left">Status</th>
                            <td class="py-2 px-3">
                                <?php if ($user['is_active']): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="border-b hover:bg-gray-50">
                            <th class="py-2 px-3 text-left">Tanggal Daftar</th>
                            <td class="py-2 px-3"><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <tr class="border-b hover:bg-gray-50">
                            <th class="py-2 px-3 text-left">Terakhir Login</th>
                            <td class="py-2 px-3">
                                <?php 
                                echo isset($user['last_login']) && $user['last_login'] ? 
                                    date('d M Y H:i', strtotime($user['last_login'])) : 
                                    'Belum pernah login'; 
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Tombol Aksi -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" data-bs-toggle="modal" data-bs-target="#statusModal" class="px-4 py-2 rounded font-medium text-white <?php echo $user['is_active'] ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'; ?>">
                        <?php if ($user['is_active']): ?>
                        <i class="fas fa-user-times mr-1"></i>Nonaktifkan Pengguna
                        <?php else: ?>
                        <i class="fas fa-user-check mr-1"></i>Aktifkan Pengguna
                        <?php endif; ?>
                    </button>
                    
                    <a href="user_activities.php?filter_type=user&user_id=<?php echo $user['id']; ?>" class="px-4 py-2 rounded font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-chart-line mr-1"></i>Lihat Aktivitas
                    </a>
                    
                    <a href="users.php" class="px-4 py-2 rounded font-medium text-white bg-gray-600 hover:bg-gray-700">
                        <i class="fas fa-arrow-left mr-1"></i>Kembali ke Daftar
                    </a>
                </div>
                
                <!-- Modal Konfirmasi Status -->
                <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header <?php echo $user['is_active'] ? 'bg-red-600' : 'bg-green-600'; ?> text-white">
                                <h5 class="modal-title" id="statusModalLabel">
                                    <?php if ($user['is_active']): ?>
                                    <i class="fas fa-user-times mr-1"></i>Konfirmasi Nonaktifkan Pengguna
                                    <?php else: ?>
                                    <i class="fas fa-user-check mr-1"></i>Konfirmasi Aktifkan Pengguna
                                    <?php endif; ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Apakah Anda yakin ingin <?php echo $user['is_active'] ? 'menonaktifkan' : 'mengaktifkan'; ?> pengguna <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <form method="POST" action="" class="d-inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                                    <button type="submit" class="btn <?php echo $user['is_active'] ? 'btn-danger' : 'btn-success'; ?>">
                                        <?php echo $user['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Script untuk Modal Detail Pengguna -->
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Inisialisasi modal detail pengguna secara manual
                    var statusModal = document.getElementById('statusModal');
                    if (statusModal && typeof bootstrap !== 'undefined') {
                        try {
                            var modal = new bootstrap.Modal(statusModal);
                            console.log('Modal detail pengguna berhasil diinisialisasi:', statusModal.id);
                        } catch (e) {
                            console.error('Error saat inisialisasi modal detail pengguna:', e);
                        }
                    }
                });
                </script>
            </div>
        </div>
    </div>
    
    <!-- Token Pengguna -->
    <div class="w-full md:w-1/2 px-3 mb-6">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-info text-white px-4 py-3">
                <h5 class="font-bold"><i class="fas fa-key mr-2"></i>Token Pengguna</h5>
            </div>
            <div class="p-4">
                <?php if (isset($userTokens) && count($userTokens) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-100 border-b">
                                <th class="py-2 px-3 text-left">Token</th>
                                <th class="py-2 px-3 text-left">Deskripsi</th>
                                <th class="py-2 px-3 text-left">Tanggal Dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userTokens as $token): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-2 px-3">
                                    <div class="token-display">
                                        <?php echo substr($token['token'], 0, 15) . '...'; ?>
                                    </div>
                                    <button class="mt-1 px-2 py-1 text-xs font-medium text-primary border border-primary rounded hover:bg-primary hover:text-white copy-token" data-token="<?php echo $token['token']; ?>">
                                        <i class="fas fa-copy mr-1"></i>Salin
                                    </button>
                                </td>
                                <td class="py-2 px-3"><?php echo htmlspecialchars($token['description'] ?? 'Tidak ada deskripsi'); ?></td>
                                <td class="py-2 px-3"><?php echo date('d M Y H:i', strtotime($token['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="bg-blue-100 text-blue-800 p-4 rounded">
                    <i class="fas fa-info-circle mr-1"></i>Pengguna ini belum memiliki token.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: // Tampilkan daftar pengguna ?>

<div class="mb-6">
    <div class="w-full flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold mb-2"><i class="fas fa-users mr-2"></i>Manajemen Pengguna</h2>
            <p class="text-gray-600">Kelola semua pengguna yang terdaftar dalam sistem.</p>
        </div>
        <div>
            <button type="button" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary-dark" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus mr-1"></i>Tambah Pengguna
            </button>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="bg-primary text-white px-4 py-3">
        <h5 class="font-bold"><i class="fas fa-list mr-2"></i>Daftar Pengguna</h5>
    </div>
    <div class="p-4">
        <?php if (isset($users) && count($users) > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full" id="usersTable">
                <thead>
                    <tr class="bg-gray-100 border-b">
                        <th class="py-2 px-3 text-left">ID</th>
                        <th class="py-2 px-3 text-left">Username</th>
                        <th class="py-2 px-3 text-left">Email</th>
                        <th class="py-2 px-3 text-left">Tanggal Daftar</th>
                        <th class="py-2 px-3 text-left">Status</th>
                        <th class="py-2 px-3 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 px-3"><?php echo $user['id']; ?></td>
                        <td class="py-2 px-3"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="py-2 px-3"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="py-2 px-3"><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                        <td class="py-2 px-3">
                            <?php if ($user['is_active']): ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                            <?php else: ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3">
                            <a href="users.php?id=<?php echo $user['id']; ?>" class="px-2 py-1 text-xs font-medium text-white bg-info rounded hover:bg-info-dark mr-1" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="user_activities.php?filter_type=user&user_id=<?php echo $user['id']; ?>" class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 mr-1" title="Lihat Aktivitas">
                                <i class="fas fa-chart-line"></i>
                            </a>
                            <button type="button" class="px-2 py-1 text-xs font-medium text-white <?php echo $user['is_active'] ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'; ?> rounded mr-1" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#statusModalList" 
                                    data-user-id="<?php echo $user['id']; ?>" 
                                    data-username="<?php echo htmlspecialchars($user['username']); ?>" 
                                    data-status="<?php echo $user['is_active'] ? 0 : 1; ?>" 
                                    data-current-status="<?php echo $user['is_active'] ? 'aktif' : 'nonaktif'; ?>" 
                                    title="<?php echo $user['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                <?php if ($user['is_active']): ?>
                                <i class="fas fa-user-times"></i>
                                <?php else: ?>
                                <i class="fas fa-user-check"></i>
                                <?php endif; ?>
                            </button>
                            <button type="button" class="px-2 py-1 text-xs font-medium text-white bg-danger rounded hover:bg-danger-dark" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteUserModal" 
                                    data-user-id="<?php echo $user['id']; ?>" 
                                    data-username="<?php echo htmlspecialchars($user['username']); ?>" 
                                    title="Hapus Pengguna">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="bg-blue-100 text-blue-800 p-4 rounded">
            <i class="fas fa-info-circle mr-1"></i>Tidak ada pengguna yang terdaftar.
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
    $('#usersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
        },
        order: [[0, 'desc']]
    });
});
</script>

<?php endif; ?>

<!-- Script untuk Modal Detail Pengguna -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded untuk modal detail');
    
    // Pastikan Bootstrap tersedia
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap tidak tersedia untuk modal detail');
        return;
    }
    
    console.log('Bootstrap tersedia untuk modal detail');
    
    // Inisialisasi modal secara manual
    var statusModal = document.getElementById('statusModal');
    if (statusModal) {
        console.log('Modal detail element ditemukan:', statusModal.id);
        try {
            var modal = new bootstrap.Modal(statusModal);
            console.log('Modal detail berhasil diinisialisasi:', statusModal.id);
        } catch (e) {
            console.error('Error saat inisialisasi modal detail:', e);
        }
    } else {
        console.log('Modal detail element tidak ditemukan');
    }
});
</script>

<!-- Modal Konfirmasi Status untuk Daftar Pengguna -->
<div class="modal fade" id="statusModalList" tabindex="-1" aria-labelledby="statusModalListLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="statusModalListLabel">Konfirmasi Perubahan Status</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalMessage">Apakah Anda yakin ingin mengubah status pengguna ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="" id="statusForm">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="user_id" id="modalUserId" value="">
                    <input type="hidden" name="status" id="modalStatus" value="">
                    <button type="submit" class="btn" id="confirmButton">Konfirmasi</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Script untuk Modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded untuk modal');
    
    // Pastikan Bootstrap tersedia
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap tidak tersedia untuk modal');
        return;
    }
    
    console.log('Bootstrap tersedia untuk modal');
    
    // Inisialisasi modal secara manual
    var statusModalList = document.getElementById('statusModalList');
    if (statusModalList) {
        console.log('Modal element ditemukan:', statusModalList.id);
        try {
            var modal = new bootstrap.Modal(statusModalList);
            console.log('Modal berhasil diinisialisasi:', statusModalList.id);
        } catch (e) {
            console.error('Error saat inisialisasi modal:', e);
        }
        
        // Menangani event saat modal ditampilkan
        statusModalList.addEventListener('show.bs.modal', function(event) {
            // Tombol yang memicu modal
            var button = event.relatedTarget;
            
            // Ambil data dari atribut data-*
            var userId = button.getAttribute('data-user-id');
            var username = button.getAttribute('data-username');
            var status = button.getAttribute('data-status');
            var currentStatus = button.getAttribute('data-current-status');
            
            console.log('Modal data:', { userId, username, status, currentStatus });
            
            // Perbarui konten modal
            var modalTitle = this.querySelector('.modal-title');
            var modalMessage = this.querySelector('#modalMessage');
            var confirmButton = this.querySelector('#confirmButton');
            var userIdInput = this.querySelector('#modalUserId');
            var statusInput = this.querySelector('#modalStatus');
            
            // Set nilai input tersembunyi
            userIdInput.value = userId;
            statusInput.value = status;
            
            // Perbarui teks berdasarkan status
            var newStatus = status == 1 ? 'mengaktifkan' : 'menonaktifkan';
            modalTitle.innerHTML = status == 1 ? 
                '<i class="fas fa-user-check mr-1"></i>Konfirmasi Aktifkan Pengguna' : 
                '<i class="fas fa-user-times mr-1"></i>Konfirmasi Nonaktifkan Pengguna';
            
            modalMessage.textContent = 'Apakah Anda yakin ingin ' + newStatus + ' pengguna "' + username + '"?';
            
            // Perbarui tombol konfirmasi
            confirmButton.className = 'btn ' + (status == 1 ? 'btn-success' : 'btn-danger');
            confirmButton.textContent = status == 1 ? 'Aktifkan' : 'Nonaktifkan';
            
            // Perbarui header modal
            var modalHeader = this.querySelector('.modal-header');
            modalHeader.className = 'modal-header ' + (status == 1 ? 'bg-green-600' : 'bg-red-600') + ' text-white';
        });
    }
});
</script>

<!-- Modal Tambah Pengguna -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addUserModalLabel"><i class="fas fa-user-plus mr-1"></i>Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="addUserForm">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Password minimal 6 karakter</div>
                    </div>
                    

                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">Aktifkan pengguna</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="addUserForm" class="btn btn-primary">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Pengguna -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel"><i class="fas fa-trash-alt mr-1"></i>Konfirmasi Hapus Pengguna</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="deleteModalMessage">Apakah Anda yakin ingin menghapus pengguna ini?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>Perhatian: Tindakan ini akan menghapus semua data pengguna termasuk token, tugas, dan aktivitas. Tindakan ini tidak dapat dibatalkan.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="" id="deleteUserForm">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId" value="">
                    <input type="hidden" name="username" id="deleteUsername" value="">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Script untuk Modal Hapus Pengguna -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi modal hapus pengguna
    var deleteUserModal = document.getElementById('deleteUserModal');
    if (deleteUserModal) {
        deleteUserModal.addEventListener('show.bs.modal', function(event) {
            // Tombol yang memicu modal
            var button = event.relatedTarget;
            
            // Ambil data dari atribut data-*
            var userId = button.getAttribute('data-user-id');
            var username = button.getAttribute('data-username');
            
            // Perbarui konten modal
            var modalMessage = this.querySelector('#deleteModalMessage');
            var userIdInput = this.querySelector('#deleteUserId');
            var usernameInput = this.querySelector('#deleteUsername');
            
            // Set nilai input tersembunyi
            userIdInput.value = userId;
            usernameInput.value = username;
            
            // Perbarui pesan
            modalMessage.textContent = 'Apakah Anda yakin ingin menghapus pengguna "' + username + '"?';
        });
    }
    
    // Inisialisasi modal tambah pengguna
    var addUserModal = document.getElementById('addUserModal');
    if (addUserModal && typeof bootstrap !== 'undefined') {
        try {
            var modal = new bootstrap.Modal(addUserModal);
        } catch (e) {
            console.error('Error saat inisialisasi modal tambah pengguna:', e);
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>