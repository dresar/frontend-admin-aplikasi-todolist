<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/models/TaskModel.php';
require_once __DIR__ . '/models/CategoryModel.php';
require_once __DIR__ . '/models/UserModel.php';

// Inisialisasi model
$taskModel = new TaskModel();
$categoryModel = new CategoryModel();
$userModel = new UserModel();

// Proses penambahan tugas baru
if (isset($_POST['action']) && $_POST['action'] == 'add_task') {
    $userId = $_POST['user_id'] ?? 0;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $categoryId = $_POST['category_id'] ?? 0;
    $dueDate = $_POST['due_date'] ?? null;
    $priority = $_POST['priority'] ?? 'medium';
    $status = $_POST['status'] ?? 'pending';
    
    // Validasi input
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Judul tugas tidak boleh kosong';
    }
    
    if (empty($userId)) {
        $errors[] = 'Pengguna harus dipilih';
    }
    
    // Jika tidak ada error, tambahkan tugas
    if (empty($errors)) {
        if ($taskModel->addTask($userId, $title, $description, $categoryId, $dueDate, $priority, $status)) {
            logActivity($_SESSION['admin_id'], 'add_task', "Menambahkan tugas baru: $title");
            setMessage('success', 'Tugas berhasil ditambahkan');
            redirect('tasks.php');
        } else {
            setMessage('danger', 'Gagal menambahkan tugas');
        }
    } else {
        // Tampilkan pesan error
        foreach ($errors as $error) {
            setMessage('danger', $error);
        }
    }
}

// Proses penambahan kategori baru
if (isset($_POST['action']) && $_POST['action'] == 'add_category') {
    $categoryName = $_POST['category_name'] ?? '';
    $categoryDescription = $_POST['category_description'] ?? '';
    $categoryUserId = $_POST['category_user_id'] ?? null;
    $categoryColor = $_POST['category_color'] ?? '#3498db';
    
    // Validasi input
    $errors = [];
    
    if (empty($categoryName)) {
        $errors[] = 'Nama kategori tidak boleh kosong';
    }
    
    // Jika tidak ada error, tambahkan kategori
    if (empty($errors)) {
        if ($categoryModel->addCategory($categoryName, $categoryDescription, $categoryUserId, $categoryColor)) {
            logActivity($_SESSION['admin_id'], 'add_category', "Menambahkan kategori baru: $categoryName");
            setMessage('success', 'Kategori berhasil ditambahkan');
            // Refresh halaman untuk menampilkan kategori baru
            redirect('task_add.php');
        } else {
            setMessage('danger', 'Gagal menambahkan kategori');
        }
    } else {
        // Tampilkan pesan error
        foreach ($errors as $error) {
            setMessage('danger', $error);
        }
    }
}

// Mendapatkan semua pengguna aktif
$users = $userModel->getAllUsers();

// Mendapatkan semua kategori (termasuk global dan user-specific)
$categories = $categoryModel->getAllCategories();
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-plus-circle mr-2 text-blue-600"></i> Tambah Tugas Baru
        </h1>
        <a href="tasks.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md inline-flex items-center transition duration-150 ease-in-out">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4">
            <div class="flex items-center">
                <i class="fas fa-clipboard-list text-xl mr-2"></i>
                <h2 class="text-lg font-semibold">Form Tambah Tugas</h2>
            </div>
        </div>
        
        <div class="p-6">
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="action" value="add_task">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Judul Tugas -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                            Judul Tugas <span class="text-red-600">*</span>
                        </label>
                        <input type="text" id="title" name="title" required 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                            placeholder="Masukkan judul tugas">
                    </div>
                    
                    <!-- Pilih Pengguna -->
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Pengguna <span class="text-red-600">*</span>
                        </label>
                        <select id="user_id" name="user_id" required 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            <option value="">Pilih Pengguna</option>
                            <?php foreach ($users as $user): ?>
                                <?php if ($user['is_active'] == 1): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Kategori -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Kategori
                        </label>
                        <div class="flex space-x-2">
                            <select id="category_id" name="category_id" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($categories as $category): ?>
                                    <?php 
                                    // Tampilkan kategori global (user_id = null) atau kategori milik user tertentu
                                    $isGlobalCategory = empty($category['user_id']);
                                    $categoryOwner = $isGlobalCategory ? 'Global' : 'User: ' . $category['user_id'];
                                    ?>
                                    <option value="<?php echo $category['id']; ?>" data-user-id="<?php echo $category['user_id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                        <?php if (!empty($category['color'])): ?>
                                            <span style="color: <?php echo $category['color']; ?>;">■</span>
                                        <?php endif; ?>
                                        <?php if ($isGlobalCategory): ?> (Global)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" 
                                class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-3 rounded-md inline-flex items-center transition duration-150 ease-in-out"
                                onclick="openAddCategoryModal()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Tenggat Waktu -->
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">
                            Tenggat Waktu
                        </label>
                        <input type="date" id="due_date" name="due_date" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>
                    
                    <!-- Prioritas -->
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">
                            Prioritas
                        </label>
                        <select id="priority" name="priority" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            <option value="low">Rendah</option>
                            <option value="medium" selected>Sedang</option>
                            <option value="high">Tinggi</option>
                        </select>
                    </div>
                    
                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                            Status
                        </label>
                        <select id="status" name="status" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            <option value="pending" selected>Pending</option>
                            <option value="in_progress">Sedang Dikerjakan</option>
                            <option value="completed">Selesai</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>
                </div>
                
                <!-- Deskripsi -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Deskripsi
                    </label>
                    <textarea id="description" name="description" rows="4" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                        placeholder="Masukkan deskripsi tugas"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <a href="tasks.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md inline-flex items-center transition duration-150 ease-in-out">
                        Batal
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md inline-flex items-center transition duration-150 ease-in-out">
                        <i class="fas fa-save mr-2"></i> Simpan Tugas
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Script untuk memfilter kategori berdasarkan user yang dipilih
    document.addEventListener('DOMContentLoaded', function() {
        const userSelect = document.getElementById('user_id');
        const categorySelect = document.getElementById('category_id');
        const originalOptions = Array.from(categorySelect.options);
        
        userSelect.addEventListener('change', function() {
            const selectedUserId = this.value;
            
            // Reset kategori
            categorySelect.innerHTML = '';
            
            // Tambahkan opsi default
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Pilih Kategori';
            categorySelect.appendChild(defaultOption);
            
            // Filter kategori berdasarkan user_id
            originalOptions.forEach(option => {
                if (option.value === '') return; // Skip opsi default
                
                const categoryUserId = option.getAttribute('data-user-id');
                
                // Tampilkan kategori jika: kategori global (user_id kosong) atau milik user yang dipilih
                if (!categoryUserId || categoryUserId === selectedUserId) {
                    categorySelect.appendChild(option.cloneNode(true));
                }
            });
        });
    });
    
    // Fungsi untuk membuka modal tambah kategori dan mengisi user_id berdasarkan user yang dipilih di form tugas
    function openAddCategoryModal() {
        // Dapatkan user_id yang dipilih di form tugas
        const selectedUserId = document.getElementById('user_id').value;
        
        // Set user_id di form tambah kategori
        if (selectedUserId) {
            const categoryUserSelect = document.getElementById('category_user_id');
            if (categoryUserSelect) {
                categoryUserSelect.value = selectedUserId;
            }
        }
        
        // Tampilkan modal
        document.getElementById('addCategoryModal').classList.remove('hidden');
    }
    
    // Fungsi untuk menutup modal tambah kategori
    function closeAddCategoryModal() {
        document.getElementById('addCategoryModal').classList.add('hidden');
    }
    </script>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade fixed inset-0 overflow-y-auto hidden bg-gray-500 bg-opacity-75 transition-opacity z-50" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_category">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-tag text-green-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="addCategoryModalLabel">
                                Tambah Kategori Baru
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="category_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Nama Kategori <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" id="category_name" name="category_name" required 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                                        placeholder="Masukkan nama kategori">
                                </div>
                                
                                <div>
                                    <label for="category_color" class="block text-sm font-medium text-gray-700 mb-1">
                                        Warna
                                    </label>
                                    <input type="color" id="category_color" name="category_color" value="#3498db" 
                                        class="w-full h-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>
                                
                                <div>
                                    <label for="category_user_id" class="block text-sm font-medium text-gray-700 mb-1">
                                        Pengguna (Kosongkan untuk kategori global)
                                    </label>
                                    <select id="category_user_id" name="category_user_id" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        <option value="">Kategori Global</option>
                                        <?php foreach ($users as $user): ?>
                                            <?php if ($user['is_active'] == 1): ?>
                                            <option value="<?php echo $user['id']; ?>">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="category_description" class="block text-sm font-medium text-gray-700 mb-1">
                                        Deskripsi
                                    </label>
                                    <textarea id="category_description" name="category_description" rows="3" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                                        placeholder="Masukkan deskripsi kategori"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm transition duration-150 ease-in-out">
                        Simpan Kategori
                    </button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition duration-150 ease-in-out"
                        onclick="closeAddCategoryModal()">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Fungsi untuk menutup modal tambah kategori
function closeAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.add('hidden');
}

// Script untuk menutup modal saat mengklik di luar
document.addEventListener('click', function(event) {
    const modal = document.getElementById('addCategoryModal');
    if (event.target === modal) {
        closeAddCategoryModal();
    }
});

// Script untuk menutup modal dengan tombol Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAddCategoryModal();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>