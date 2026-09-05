<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/models/TokenModel.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/AdminModel.php';

// Inisialisasi model
$tokenModel = new TokenModel();
$userModel = new UserModel();
$adminModel = new AdminModel();

// Proses revoke token pengguna
if (isset($_POST['action']) && $_POST['action'] == 'revoke_user_token') {
    $tokenId = $_POST['token_id'] ?? 0;
    $userId = $_POST['user_id'] ?? 0;
    
    if ($tokenModel->revokeUserToken($tokenId)) {
        logActivity($_SESSION['admin_id'], 'revoke_user_token', "Mencabut token pengguna ID: $userId");
        setMessage('success', 'Token pengguna berhasil dicabut');
    } else {
        setMessage('danger', 'Gagal mencabut token pengguna');
    }
    
    // Redirect kembali ke halaman detail pengguna jika ada ID pengguna
    if ($userId) {
        redirect('users.php?id=' . $userId);
    } else {
        redirect('tokens.php');
    }
}

// Proses revoke token admin telah dihapus - admin tidak dapat memiliki token

// Proses pembuatan token baru
if (isset($_POST['action']) && $_POST['action'] == 'create_token') {
    $userId = $_POST['user_id'] ?? 0;
    $tokenType = $_POST['token_type'] ?? 'jwt';
    $description = $_POST['description'] ?? '';
    
    if ($userId) {
        $token = $tokenModel->createUserToken($userId, $description, $tokenType);
        
        if ($token) {
            logActivity($_SESSION['admin_id'], 'create_token', "Membuat token {$tokenType} untuk pengguna ID: {$userId}");
            setMessage('success', "Token {$tokenType} berhasil dibuat");
        } else {
            if ($tokenType === 'api_key') {
                // Periksa apakah pengguna sudah memiliki API key
                $userTokens = $tokenModel->getAllUserTokens($userId);
                $hasApiKey = false;
                foreach ($userTokens as $userToken) {
                    if ($userToken['token_type'] === 'api_key') {
                        $hasApiKey = true;
                        break;
                    }
                }
                
                if ($hasApiKey) {
                    setMessage('danger', 'Pengguna sudah memiliki API Key. Satu pengguna hanya diizinkan memiliki satu API Key.');
                } else {
                    setMessage('danger', 'Gagal membuat token');
                }
            } else {
                setMessage('danger', 'Gagal membuat token');
            }
        }
    } else {
        setMessage('danger', 'ID pengguna tidak valid');
    }
    
    redirect('tokens.php');
}

// Token admin telah dihapus - admin tidak dapat memiliki token

// Mendapatkan semua token
$userTokens = $tokenModel->getAllUserTokens();

// Mendapatkan statistik token
$totalUserTokens = count($userTokens);

// Dapatkan semua pengguna untuk form pembuatan token
$users = $userModel->getAllUsers();

// Hitung total token
$totalTokens = $totalUserTokens;

// Gabungkan token pengguna dan admin dalam satu array
$allTokens = [];

// Tambahkan token pengguna
foreach ($userTokens as $token) {
    $user = $userModel->getUserById($token['user_id']);
    $username = $user ? $user['username'] : 'Pengguna tidak ditemukan';
    
    $allTokens[] = [
        'id' => $token['id'],
        'entity_id' => $token['user_id'],
        'username' => $username,
        'token' => $token['token'],
        'token_type' => $token['token_type'] ?? 'jwt',
        'description' => $token['description'] ?? 'Tidak ada deskripsi',
        'created_at' => $token['created_at'],
        'action' => 'revoke_user_token',
        'link' => 'users.php?id=' . $token['user_id']
    ];
}

// Admin tidak dapat memiliki token

// API key sekarang dikelola melalui tabel tokens

// Urutkan token berdasarkan ID
usort($allTokens, function($a, $b) {
    return $a['id'] - $b['id'];
});
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <div class="w-full">
            <h2 class="text-2xl font-bold"><i class="fas fa-key mr-2"></i>Manajemen Token User</h2>
            <p class="text-gray-600">Kelola semua token user yang digunakan untuk autentikasi API.</p>
        </div>
    </div>

    <!-- Form untuk membuat token baru -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="bg-primary text-white px-4 py-3">
            <h3 class="font-semibold"><i class="fas fa-plus-circle mr-1"></i>Buat Token Baru</h3>
        </div>
        
        <div class="p-4">
            <ul class="flex flex-wrap border-b mb-4">
                <li class="mr-1 mb-2">
                    <a class="bg-white inline-block py-2 px-4 text-blue-500 hover:text-blue-800 font-semibold border-l border-t border-r rounded-t active-tab" href="javascript:void(0)" id="userTokenTabBtn">Token Pengguna</a>
                </li>
            </ul>
            
            <!-- Form Pembuatan Token Pengguna -->
            <div id="tokenForm">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_token">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="mb-4">
                            <label for="user_id" class="block text-gray-700 text-sm font-bold mb-2">Pengguna</label>
                            <select name="user_id" id="user_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="">Pilih Pengguna</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="token_type" class="block text-gray-700 text-sm font-bold mb-2">Jenis Token</label>
                            <select name="token_type" id="token_type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="jwt">JWT Token</option>
                                <option value="api_key">API Key</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi</label>
                            <input type="text" name="description" id="description" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Deskripsi token">
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-end">
                        <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            <i class="fas fa-plus-circle mr-1"></i>Buat Token Pengguna
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Statistik Token -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-primary text-white rounded-lg shadow h-full transform transition-transform duration-300 hover:scale-105">
            <div class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-semibold">Total Token User</h6>
                        <h2 class="text-3xl font-bold"><?php echo $totalTokens; ?></h2>
                    </div>
                    <i class="fas fa-key text-4xl opacity-50"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-green-500 text-white rounded-lg shadow h-full transform transition-transform duration-300 hover:scale-105">
            <div class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-semibold">Token JWT</h6>
                        <h2 class="text-3xl font-bold"><?php 
                            $jwtCount = 0;
                            foreach ($allTokens as $token) {
                                if ($token['token_type'] == 'jwt') $jwtCount++;
                            }
                            echo $jwtCount;
                        ?></h2>
                    </div>
                    <i class="fas fa-users text-4xl opacity-50"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-blue-500 text-white rounded-lg shadow h-full transform transition-transform duration-300 hover:scale-105">
            <div class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-semibold">API Keys</h6>
                        <h2 class="text-3xl font-bold"><?php 
                            $apiKeyCount = 0;
                            foreach ($allTokens as $token) {
                                if ($token['token_type'] == 'api_key') $apiKeyCount++;
                            }
                            echo $apiKeyCount;
                        ?></h2>
                    </div>
                    <i class="fas fa-key text-4xl opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Semua Token User dalam Satu Tampilan -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-primary text-white px-4 py-3">
            <h3 class="font-semibold"><i class="fas fa-key mr-1"></i>Semua Token User</h3>
        </div>
        
        <div class="p-4">
            <?php if (count($allTokens) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white" id="allTokensTable">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 border-b text-left">ID</th>
                            <th class="py-2 px-4 border-b text-left">Username</th>
                            <th class="py-2 px-4 border-b text-left">Tipe Token User</th>
                            <th class="py-2 px-4 border-b text-left">Deskripsi</th>
                            <th class="py-2 px-4 border-b text-left">Tanggal Dibuat</th>
                            <th class="py-2 px-4 border-b text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allTokens as $token): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border-b"><?php echo $token['id']; ?></td>
                            <td class="py-2 px-4 border-b">
                                <a href="<?php echo $token['link']; ?>" class="text-blue-600 hover:text-blue-800">
                                    <?php echo htmlspecialchars($token['username']); ?>
                                </a>
                            </td>
                            <td class="py-2 px-4 border-b">
                                <?php if ($token['token_type'] == 'jwt'): ?>
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-semibold">JWT</span>
                                <?php else: ?>
                                    <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs font-semibold">API Key</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($token['description']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo date('d M Y H:i', strtotime($token['created_at'])); ?></td>
                            <td class="py-2 px-4 border-b">
                                <div class="flex space-x-1">
                                    <?php if (!empty($token['action'])): ?>
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="action" value="revoke_user_token">
                                        <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $token['entity_id']; ?>">
                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white py-1 px-2 rounded text-sm inline-flex items-center" title="Cabut Token">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <a href="<?php echo $token['link']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white py-1 px-2 rounded text-sm inline-flex items-center" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button onclick="showTokenModal('<?php echo htmlspecialchars(addslashes($token['token'])); ?>', '<?php echo htmlspecialchars(addslashes($token['username'])); ?>', '<?php echo $token['token_type']; ?>')" class="bg-green-500 hover:bg-green-600 text-white py-1 px-2 rounded text-sm inline-flex items-center" title="Lihat Token">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4">
                <i class="fas fa-info-circle mr-1"></i>Tidak ada token yang aktif.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal untuk menampilkan token -->
<div class="modal fade" id="tokenModal" tabindex="-1" aria-labelledby="tokenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="tokenModalLabel">Detail Token</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label font-semibold">Username:</label>
                    <p id="modalUsername"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label font-semibold">Jenis Token:</label>
                    <p id="modalTokenType"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label font-semibold">Token:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="modalToken" readonly>
                        <button class="btn btn-success" type="button" onclick="copyModalToken()"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-200">
                    <div id="jwtInfo" class="mb-3 token-info">
                        <h6 class="font-semibold text-blue-700"><i class="fas fa-info-circle mr-1"></i>Informasi JWT Token</h6>
                        <ul class="list-disc pl-5 text-sm text-gray-600 mt-2">
                            <li>Token JWT digunakan untuk autentikasi sesi</li>
                            <li>Memiliki masa berlaku terbatas</li>
                            <li>Gunakan untuk aplikasi web dan mobile</li>
                            <li>Format: <code>Authorization: Bearer [token]</code></li>
                        </ul>
                    </div>
                    <div id="apiKeyInfo" class="mb-3 token-info">
                        <h6 class="font-semibold text-purple-700"><i class="fas fa-info-circle mr-1"></i>Informasi API Key</h6>
                        <ul class="list-disc pl-5 text-sm text-gray-600 mt-2">
                            <li>API Key digunakan untuk autentikasi permanen</li>
                            <li>Tidak memiliki masa berlaku</li>
                            <li>Gunakan untuk integrasi sistem dan API</li>
                            <li>Format: <code>X-API-Key: [api_key]</code></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Script untuk modal -->
<script>

// Fungsi untuk menampilkan modal token
function showTokenModal(token, username, tokenType) {
    document.getElementById('modalToken').value = token;
    document.getElementById('modalUsername').textContent = username;
    
    // Tampilkan jenis token dengan badge yang sesuai
    const tokenTypeElement = document.getElementById('modalTokenType');
    const jwtInfoElement = document.getElementById('jwtInfo');
    const apiKeyInfoElement = document.getElementById('apiKeyInfo');
    
    if (tokenType === 'jwt') {
        tokenTypeElement.innerHTML = '<span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-semibold">JWT Token</span>';
        // Tampilkan info JWT dan sembunyikan info API Key
        jwtInfoElement.classList.remove('hidden');
        apiKeyInfoElement.classList.add('hidden');
    } else {
        tokenTypeElement.innerHTML = '<span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs font-semibold">API Key</span>';
        // Tampilkan info API Key dan sembunyikan info JWT
        jwtInfoElement.classList.add('hidden');
        apiKeyInfoElement.classList.remove('hidden');
    }
    
    // Tampilkan modal menggunakan Bootstrap
    var tokenModal = new bootstrap.Modal(document.getElementById('tokenModal'));
    tokenModal.show();
}

// Fungsi untuk menyalin token dari modal
function copyModalToken() {
    const tokenElement = document.getElementById('modalToken');
    tokenElement.select();
    document.execCommand('copy');
    
    // Tampilkan notifikasi
    const notification = document.createElement('div');
    notification.textContent = 'Token berhasil disalin!';
    notification.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50';
    document.body.appendChild(notification);
    
    // Hilangkan notifikasi setelah 2 detik
    setTimeout(() => {
        notification.remove();
    }, 2000);
}

// Inisialisasi DataTables dan elemen token info
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi DataTables jika tersedia
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#allTokensTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
            },
            order: [[0, 'asc']],
            columnDefs: [
                { responsivePriority: 1, targets: 0 },
                { responsivePriority: 2, targets: 1 },
                { responsivePriority: 3, targets: 5 }
            ]
        });
    }
    
    // Sembunyikan semua informasi token saat halaman dimuat
    // Akan ditampilkan sesuai jenis token saat modal dibuka
    document.querySelectorAll('.token-info').forEach(function(element) {
        element.classList.add('hidden');
    });
});
</script>

<!-- Tambahkan CSS untuk tab aktif dan token info -->
<style>
.active-tab {
    background-color: #4e73df;
    color: white !important;
}

/* Tampilan untuk informasi token */
.token-info {
    display: block; /* Tampilkan secara default */
}

.token-info.hidden {
    display: none; /* Sembunyikan jika memiliki kelas hidden */
}

/* Responsif untuk mobile */
@media (max-width: 640px) {
    .modal-dialog {
        margin: 0.5rem;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>