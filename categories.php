<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/models/CategoryModel.php';

// Inisialisasi model
$categoryModel = new CategoryModel();

// Proses tambah kategori baru
if (isset($_POST['action']) && $_POST['action'] == 'add_category') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $userId = $_POST['user_id'] ?? null;
    $color = $_POST['color'] ?? '#3498db';
    
    // Validasi input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Nama kategori tidak boleh kosong';
    }
    
    if (empty($errors)) {
        if ($categoryModel->addCategory($name, $description, $userId, $color)) {
            logActivity($_SESSION['admin_id'], 'add_category', "Menambahkan kategori baru: $name" . ($userId ? " untuk user ID: $userId" : ""));
            setMessage('success', 'Kategori baru berhasil ditambahkan');
            redirect('categories.php');
        } else {
            setMessage('danger', 'Gagal menambahkan kategori baru');
        }
    } else {
        setMessage('danger', implode('<br>', $errors));
    }
}

// Proses update kategori
if (isset($_POST['action']) && $_POST['action'] == 'update_category') {
    $categoryId = $_POST['category_id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Prioritaskan nilai warna dari hidden input jika tersedia
    $color = $_POST['color_hidden'] ?? $_POST['color'] ?? null;
    
    $userId = $_POST['user_id'] ?? null;
    
    // Debug: Log nilai yang diterima dari form
    error_log("Update Category - ID: $categoryId, Name: $name, Color: $color, UserID: $userId");
    error_log("Form Data: " . print_r($_POST, true));
    
    // Validasi input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Nama kategori tidak boleh kosong';
    }
    
    // Pastikan warna tidak kosong
    if (empty($color)) {
        $color = '#3498db'; // Default color
        error_log("Menggunakan warna default: $color");
    }
    
    if (empty($errors)) {
        if ($categoryModel->updateCategory($categoryId, $name, $description, $color, $userId)) {
            logActivity($_SESSION['admin_id'], 'update_category', "Mengupdate kategori ID: $categoryId");
            setMessage('success', 'Kategori berhasil diperbarui');
            // Refresh halaman untuk memastikan data terbaru ditampilkan
            // Gunakan URL relatif untuk menghindari masalah dengan BASE_URL
            header("Location: $_SERVER[PHP_SELF]");
            exit();
        } else {
            setMessage('danger', 'Gagal memperbarui kategori');
        }
    } else {
        setMessage('danger', implode('<br>', $errors));
    }
}

// Proses hapus kategori
if (isset($_POST['action']) && $_POST['action'] == 'delete_category') {
    $categoryId = $_POST['category_id'] ?? 0;
    $userId = $_POST['user_id'] ?? null;
    
    // Cek apakah kategori memiliki tugas terkait
    $category = $categoryModel->getCategoryById($categoryId);
    
    if ($category && $category['task_count'] > 0) {
        setMessage('danger', 'Kategori tidak dapat dihapus karena masih memiliki tugas terkait');
    } else {
        if ($categoryModel->deleteCategory($categoryId, $userId)) {
            logActivity($_SESSION['admin_id'], 'delete_category', "Menghapus kategori ID: $categoryId" . ($userId ? " untuk user ID: $userId" : ""));
            setMessage('success', 'Kategori berhasil dihapus');
        } else {
            setMessage('danger', 'Gagal menghapus kategori');
        }
    }
    
    redirect('categories.php');
}

// Mendapatkan detail kategori jika ada parameter ID
$category = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $categoryId = $_GET['id'];
    $category = $categoryModel->getCategoryById($categoryId);
}

// Filter kategori berdasarkan user_id jika ada
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// Mendapatkan semua kategori atau kategori berdasarkan user_id
// Refresh data kategori setiap kali halaman dimuat
$categories = $userId ? $categoryModel->getCategoriesByUserId($userId) : $categoryModel->getAllCategories();

// Debug: Tampilkan data kategori
// echo '<pre>';
// print_r($categories);
// echo '</pre>';
?>

<div class="flex flex-col w-full mb-6">
    <div class="w-full">
        <h2 class="text-2xl font-semibold flex items-center"><i class="fas fa-tags mr-2"></i>Manajemen Kategori</h2>
        <p class="text-gray-600">Kelola kategori untuk tugas-tugas dalam sistem.</p>
    </div>
</div>

<div class="flex flex-col md:flex-row gap-6 mb-6">
    <div class="w-full md:w-1/3">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-blue-600 text-white px-4 py-3">
                <h5 class="font-medium text-lg flex items-center">
                    <?php if ($category): ?>
                    <i class="fas fa-edit mr-2"></i>Edit Kategori
                    <?php else: ?>
                    <i class="fas fa-plus-circle mr-2"></i>Tambah Kategori Baru
                    <?php endif; ?>
                </h5>
            </div>
            <div class="p-4">
                <form method="POST" action="">
                    <?php if ($category): ?>
                    <input type="hidden" name="action" value="update_category">
                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                    <?php else: ?>
                    <input type="hidden" name="action" value="add_category">
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" id="name" name="name" value="<?php echo $category ? htmlspecialchars($category['name']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" id="description" name="description" rows="3"><?php echo $category ? htmlspecialchars($category['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Warna</label>
                        <div class="flex items-center">
                            <?php
                            // Debug: Tampilkan nilai color dari kategori
                            echo "<!-- Category color: " . var_export($category['color'] ?? null, true) . " -->";
                            
                            $colorValue = '#3498db'; // Default color
                            if ($category && isset($category['color']) && 
                                $category['color'] !== null && 
                                $category['color'] !== '' && 
                                $category['color'] !== '0') {
                                $colorValue = $category['color'];
                            }
                            echo "<!-- Color value used: " . $colorValue . " -->";
                            ?>
                            <input type="color" class="h-10 w-10 border border-gray-300 rounded-md mr-2" id="color" name="color" value="<?php echo htmlspecialchars($colorValue); ?>" onchange="document.getElementById('color_text').value = this.value;">
                            <input type="text" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" id="color_text" name="color_text" value="<?php echo htmlspecialchars($colorValue); ?>" readonly>
                            <!-- Tambahkan hidden input untuk memastikan nilai warna selalu terkirim -->
                            <input type="hidden" name="color_hidden" id="color_hidden" value="<?php echo htmlspecialchars($colorValue); ?>">
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Pilih warna untuk kategori ini.</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Pengguna (Opsional)</label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" id="user_id" name="user_id">
                            <option value="">-- Kategori Global --</option>
                            <?php 
                            require_once __DIR__ . '/models/UserModel.php';
                            $userModel = new UserModel();
                            $users = $userModel->getAllUsers();
                            foreach ($users as $user): 
                                // Debug: Tampilkan nilai user_id dari kategori
                                // echo "<!-- Category user_id: " . var_export($category['user_id'] ?? null, true) . " -->";
                                // echo "<!-- User id: " . var_export($user['id'], true) . " -->";
                                
                                $selected = ($category && isset($category['user_id']) && 
                                           $category['user_id'] !== null && 
                                           $category['user_id'] !== '' && 
                                           $category['user_id'] !== 0 && 
                                           $category['user_id'] == $user['id']) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">Jika dipilih, kategori hanya akan terlihat oleh pengguna tersebut.</p>
                    </div>
                    
                    <div class="flex flex-col gap-2">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md flex items-center justify-center transition duration-200" onclick="document.getElementById('color_hidden').value = document.getElementById('color').value; console.log('Form submitted with color: ' + document.getElementById('color').value);">
                            <?php if ($category): ?>
                            <i class="fas fa-save mr-1"></i>Simpan Perubahan
                            <?php else: ?>
                            <i class="fas fa-plus-circle mr-1"></i>Tambah Kategori
                            <?php endif; ?>
                        </button>
                        
                        <?php if ($category): ?>
                        <a href="categories.php" class="w-full bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md flex items-center justify-center transition duration-200">
                            <i class="fas fa-times mr-1"></i>Batal
                        </a>
                        <?php endif; ?>
                        
                        <div class="mt-2 text-xs text-gray-500">
                            <!-- Debug info -->
                            <p>Color value: <span id="debug_color"><?php echo htmlspecialchars($colorValue); ?></span></p>
                            <script>
                                // Update debug info when color changes
                                document.getElementById('color').addEventListener('input', function() {
                                    document.getElementById('debug_color').textContent = this.value;
                                    document.getElementById('color_hidden').value = this.value;
                                });
                            </script>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="w-full md:w-2/3">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-blue-600 text-white px-4 py-3 flex justify-between items-center">
                <h5 class="font-medium text-lg flex items-center"><i class="fas fa-list mr-2"></i>Daftar Kategori</h5>
                <div>
                    <select id="userFilter" class="px-3 py-1 bg-white text-blue-600 rounded-md text-sm" onchange="window.location.href='categories.php' + (this.value ? '?user_id=' + this.value : '')">
                        <option value="">Semua Kategori</option>
                        <?php 
                        if (!isset($userModel)) {
                            require_once __DIR__ . '/models/UserModel.php';
                            $userModel = new UserModel();
                        }
                        $users = $userModel->getAllUsers();
                        foreach ($users as $user): 
                            $selected = (isset($_GET['user_id']) && $_GET['user_id'] == $user['id']) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="p-4">
                <?php if (count($categories) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="categoriesTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Warna</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tugas</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Opsi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap"><?php echo $cat['id']; ?></td>
                                <td class="px-4 py-3 whitespace-nowrap"><span class="text-sm font-medium"><?php echo htmlspecialchars($cat['name']); ?></span></td>
                                <td class="px-4 py-3">
                                    <div class="max-w-xs overflow-hidden text-ellipsis text-xs" style="max-height: 40px;">
                                        <?php echo htmlspecialchars(substr($cat['description'] ?? 'Tidak ada deskripsi', 0, 80)); ?>
                                        <?php if (strlen($cat['description'] ?? '') > 80): ?>
                                            <span class="text-blue-600">...</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php 
                                        // Debug: Tampilkan nilai color
                                        echo "<!-- Color from DB: " . var_export($cat['color'], true) . " -->";
                                        
                                        $color = (isset($cat['color']) && $cat['color'] !== null && $cat['color'] !== '' && $cat['color'] !== '0') 
                                            ? $cat['color'] 
                                            : '#3498db';
                                        
                                        echo "<!-- Color displayed: " . $color . " -->";
                                        ?>
                                        <div class="w-4 h-4 rounded-full mr-2" style="background-color: <?php echo htmlspecialchars($color); ?>"></div>
                                        <span class="text-xs"><?php echo htmlspecialchars($color); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-xs">
                                    <?php 
                                    // Debug: Tampilkan nilai user_id
                                    // echo "User ID: " . var_export($cat['user_id'], true) . "<br>";
                                    
                                    if (isset($cat['user_id']) && $cat['user_id'] !== null && $cat['user_id'] !== '' && $cat['user_id'] !== 0) {
                                        if (!isset($userModel)) {
                                            require_once __DIR__ . '/models/UserModel.php';
                                            $userModel = new UserModel();
                                        }
                                        $user = $userModel->getUserById($cat['user_id']);
                                        echo $user ? htmlspecialchars($user['username']) : 'Pengguna tidak ditemukan';
                                    } else {
                                        echo '<span class="text-gray-500">Global</span>';
                                    }
                                    ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800"><?php echo $cat['task_count']; ?></span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex justify-center">
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" class="inline-flex items-center justify-center p-2 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition duration-200" title="Opsi">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            
                                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 py-1">
                                                <a href="categories.php?id=<?php echo $cat['id']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <i class="fas fa-edit mr-2 text-blue-600"></i> Edit Kategori
                                                </a>
                                                
                                                <a href="tasks.php?category_id=<?php echo $cat['id']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <i class="fas fa-tasks mr-2 text-cyan-600"></i> Lihat Tugas
                                                </a>
                                                
                                                <?php if ($cat['task_count'] == 0): ?>
                                                <button type="button" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal<?php echo $cat['id']; ?>">
                                                    <i class="fas fa-trash-alt mr-2 text-red-600"></i> Hapus Kategori
                                                </button>
                                                <?php else: ?>
                                                <button type="button" class="block w-full text-left px-4 py-2 text-sm text-gray-400 cursor-not-allowed" disabled>
                                                    <i class="fas fa-trash-alt mr-2"></i> Tidak Dapat Dihapus
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal Hapus Kategori -->
                                    <?php if ($cat['task_count'] == 0): ?>
                                    <div class="modal fade" id="deleteCategoryModal<?php echo $cat['id']; ?>" tabindex="-1" aria-labelledby="deleteCategoryModalLabel<?php echo $cat['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                                    <?php if (!empty($cat['user_id'])): ?>
                                                    <input type="hidden" name="user_id" value="<?php echo $cat['user_id']; ?>">
                                                    <?php endif; ?>
                                                    
                                                    <div class="flex items-center justify-between p-4 border-b">
                                                        <h5 class="text-lg font-medium" id="deleteCategoryModalLabel<?php echo $cat['id']; ?>">Konfirmasi Hapus Kategori</h5>
                                                        <button type="button" class="text-gray-400 hover:text-gray-500" data-bs-dismiss="modal" aria-label="Close">
                                                            <span class="text-xl">&times;</span>
                                                        </button>
                                                    </div>
                                                    
                                                    <div class="p-4">
                                                        <p>Apakah Anda yakin ingin menghapus kategori <strong><?php echo htmlspecialchars($cat['name']); ?></strong>?</p>
                                                        <p class="text-red-600 mt-2">Tindakan ini tidak dapat dibatalkan.</p>
                                                    </div>
                                                    
                                                    <div class="flex justify-end space-x-2 p-4 border-t">
                                                        <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition duration-200" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition duration-200">Hapus Kategori</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="bg-blue-100 text-blue-700 p-4 rounded-md flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>Belum ada kategori yang ditambahkan.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Script untuk DataTables dan Alpine.js -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<script>
$(document).ready(function() {
    $('#categoriesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
        },
        order: [[0, 'desc']]
    });
    
    // Update color text input and hidden input when color picker changes
    $('#color').on('input', function() {
        var colorValue = $(this).val();
        $('#color_text').val(colorValue);
        $('#color_hidden').val(colorValue);
        console.log('Color changed to: ' + colorValue);
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>