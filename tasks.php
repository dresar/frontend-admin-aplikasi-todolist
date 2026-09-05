<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/models/TaskModel.php';
require_once __DIR__ . '/models/CategoryModel.php';
require_once __DIR__ . '/models/UserModel.php';

// Inisialisasi model
$taskModel = new TaskModel();
$categoryModel = new CategoryModel();
$userModel = new UserModel();

// Proses update status tugas
if (isset($_POST['action']) && $_POST['action'] == 'update_task_status') {
    $taskId = $_POST['task_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    
    if ($taskModel->updateTaskStatus($taskId, $status)) {
        logActivity($_SESSION['admin_id'], 'update_task_status', "Mengubah status tugas ID: $taskId menjadi $status");
        setMessage('success', 'Status tugas berhasil diperbarui');
    } else {
        setMessage('danger', 'Gagal memperbarui status tugas');
    }
    
    // Redirect kembali ke halaman sebelumnya
    $redirectUrl = 'tasks.php';
    if (isset($_POST['redirect_url'])) {
        $redirectUrl = $_POST['redirect_url'];
    }
    redirect($redirectUrl);
}

// Menambahkan tugas baru
if (isset($_POST['action']) && $_POST['action'] == 'add_task') {
    $userId = $_POST['user_id'] ?? 0;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $categoryId = $_POST['category_id'] ?? 0;
    $dueDate = $_POST['due_date'] ?? null;
    $priority = $_POST['priority'] ?? 'medium';
    $status = $_POST['status'] ?? 'pending';
    
    if ($taskModel->addTask($userId, $title, $description, $categoryId, $dueDate, $priority, $status)) {
        logActivity($_SESSION['admin_id'], 'add_task', "Menambahkan tugas baru: $title");
        setMessage('success', 'Tugas berhasil ditambahkan');
    } else {
        setMessage('danger', 'Gagal menambahkan tugas');
    }
    
    redirect('tasks.php');
}

// Proses hapus tugas
if (isset($_POST['action']) && $_POST['action'] == 'delete_task') {
    $taskId = $_POST['task_id'] ?? 0;
    
    if ($taskModel->deleteTask($taskId)) {
        logActivity($_SESSION['admin_id'], 'delete_task', "Menghapus tugas ID: $taskId");
        setMessage('success', 'Tugas berhasil dihapus');
    } else {
        setMessage('danger', 'Gagal menghapus tugas');
    }
    
    // Redirect kembali ke halaman sebelumnya
    $redirectUrl = 'tasks.php';
    if (isset($_POST['redirect_url'])) {
        $redirectUrl = $_POST['redirect_url'];
    }
    redirect($redirectUrl);
}

// Filter berdasarkan kategori
$categoryId = isset($_GET['category_id']) ? $_GET['category_id'] : null;

// Filter berdasarkan pengguna
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

// Filter berdasarkan status
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Mendapatkan detail tugas jika ada parameter ID
$task = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $taskId = $_GET['id'];
    $task = $taskModel->getTaskById($taskId);
    
    // Jika tugas ditemukan, dapatkan informasi tambahan
    if ($task) {
        $task['category'] = $categoryModel->getCategoryById($task['category_id']);
        $task['user'] = $userModel->getUserById($task['user_id']);
    }
}

// Mendapatkan tugas berdasarkan filter
$tasks = [];
if ($categoryId) {
    $tasks = $taskModel->getTasksByCategory($categoryId);
    $category = $categoryModel->getCategoryById($categoryId);
    $pageTitle = 'Tugas dalam Kategori: ' . ($category ? htmlspecialchars($category['name']) : 'Tidak Ditemukan');
} elseif ($userId) {
    $tasks = $taskModel->getTasksByUser($userId);
    $user = $userModel->getUserById($userId);
    $pageTitle = 'Tugas oleh Pengguna: ' . ($user ? htmlspecialchars($user['username']) : 'Tidak Ditemukan');
} elseif ($status) {
    $tasks = $taskModel->getTasksByStatus($status);
    $pageTitle = 'Tugas dengan Status: ' . ucfirst($status);
} else {
    $tasks = $taskModel->getAllTasks(100, 0, $userId);
    $pageTitle = 'Semua Tugas';
}

// Mendapatkan semua kategori untuk dropdown filter
$categories = $categoryModel->getAllCategories();

// Mendapatkan statistik tugas
if ($categoryId) {
    // Jika ada filter kategori, hitung statistik hanya untuk kategori tersebut
    $totalTasks = $taskModel->getTotalTasks($categoryId);
    $tasksByStatus = $taskModel->getTaskCountByStatus($categoryId);
} else {
    // Jika tidak ada filter kategori, hitung statistik untuk semua tugas
    $totalTasks = $taskModel->getTotalTasks();
    $tasksByStatus = $taskModel->getTaskCountByStatus();
}
?>

<div class="flex flex-wrap -mx-3 mb-6">
    <div class="w-full px-3">
        <h2 class="text-2xl font-semibold flex items-center"><i class="fas fa-tasks mr-2"></i><?php echo $pageTitle; ?></h2>
        <p class="text-gray-600">Kelola tugas-tugas dalam sistem.</p>
    </div>
</div>

<?php if ($task): // Tampilkan detail tugas ?>

<div class="flex flex-wrap -mx-3 mb-6">
    <div class="w-full px-3">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 text-white px-4 py-3">
                <h5 class="font-semibold m-0 flex items-center"><i class="fas fa-info-circle mr-2"></i>Detail Tugas</h5>
            </div>
            <div class="p-4">
                <div class="flex flex-wrap -mx-3">
                    <div class="w-full md:w-2/3 px-3">
                        <h4 class="text-xl font-semibold"><?php echo htmlspecialchars($task['title']); ?></h4>
                        <div class="mb-3 flex flex-wrap gap-1 mt-2">
                            <?php 
                            $statusColors = [
                                'pending' => 'bg-yellow-500',
                                'in_progress' => 'bg-blue-500',
                                'completed' => 'bg-green-500',
                                'cancelled' => 'bg-red-500',
                                'default' => 'bg-gray-500'
                            ];
                            $priorityColors = [
                                'low' => 'bg-green-500',
                                'medium' => 'bg-yellow-500',
                                'high' => 'bg-red-500',
                                'default' => 'bg-gray-500'
                            ];
                            $statusColor = $statusColors[$task['status']] ?? $statusColors['default'];
                            ?>
                            <span class="<?php echo $statusColor; ?> text-white text-xs font-semibold px-2.5 py-0.5 rounded">
                                <?php echo getStatusLabel($task['status']); ?>
                            </span>
                            
                            <?php if ($task['priority']): 
                                $priorityColor = $priorityColors[$task['priority']] ?? $priorityColors['default'];
                            ?>
                            <span class="<?php echo $priorityColor; ?> text-white text-xs font-semibold px-2.5 py-0.5 rounded">
                                <?php echo getPriorityLabel($task['priority']); ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($task['category']): ?>
                            <a href="tasks.php?category_id=<?php echo $task['category_id']; ?>" class="bg-blue-500 text-white text-xs font-semibold px-2.5 py-0.5 rounded inline-flex items-center">
                                <i class="fas fa-tag mr-1"></i><?php echo htmlspecialchars($task['category']['name']); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <strong class="font-medium">Dibuat oleh:</strong>
                            <?php if ($task['user']): ?>
                            <a href="users.php?id=<?php echo $task['user_id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <?php echo htmlspecialchars($task['user']['username']); ?>
                            </a>
                            <?php else: ?>
                            <span class="text-gray-500">Pengguna tidak ditemukan</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <strong class="font-medium">Tanggal Dibuat:</strong> <span class="text-gray-700"><?php echo date('d M Y H:i', strtotime($task['created_at'])); ?></span>
                        </div>
                        
                        <?php if ($task['due_date']): ?>
                        <div class="mb-3">
                            <strong class="font-medium">Tenggat Waktu:</strong> 
                            <span class="<?php echo (strtotime($task['due_date']) < time() && $task['status'] != 'completed') ? 'text-red-600' : 'text-gray-700'; ?>">
                                <?php echo date('d M Y H:i', strtotime($task['due_date'])); ?>
                                <?php if (strtotime($task['due_date']) < time() && $task['status'] != 'completed'): ?>
                                <i class="fas fa-exclamation-circle ml-1" title="Terlambat"></i>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <strong class="font-medium">Deskripsi:</strong>
                            <div class="mt-2 p-3 bg-gray-50 rounded-lg text-gray-700">
                                <?php echo nl2br(htmlspecialchars($task['description'] ?? 'Tidak ada deskripsi')); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="w-full md:w-1/3 px-3">
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-gray-700 text-white px-4 py-3">
                                <h6 class="font-semibold m-0">Aksi</h6>
                            </div>
                            <div class="p-4">
                                <!-- Form Update Status -->
                                <form method="POST" action="" class="mb-3">
                                    <input type="hidden" name="action" value="update_task_status">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <input type="hidden" name="redirect_url" value="tasks.php?id=<?php echo $task['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Update Status</label>
                                        <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" id="status" name="status">
                                            <option value="pending" <?php echo ($task['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="in_progress" <?php echo ($task['status'] == 'in_progress') ? 'selected' : ''; ?>>Sedang Dikerjakan</option>
                                            <option value="completed" <?php echo ($task['status'] == 'completed') ? 'selected' : ''; ?>>Selesai</option>
                                            <option value="cancelled" <?php echo ($task['status'] == 'cancelled') ? 'selected' : ''; ?>>Dibatalkan</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md flex items-center justify-center transition duration-150 ease-in-out">
                                        <i class="fas fa-save mr-1"></i>Update Status
                                    </button>
                                </form>
                                
                                <!-- Tombol Hapus -->
                                <button type="button" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md flex items-center justify-center transition duration-150 ease-in-out mb-3" data-bs-toggle="modal" data-bs-target="#deleteTaskModal">
                                    <i class="fas fa-trash-alt mr-1"></i>Hapus Tugas
                                </button>
                                
                                <hr class="my-3 border-gray-200">
                                
                                <!-- Tombol Kembali -->
                                <a href="tasks.php" class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md flex items-center justify-center transition duration-150 ease-in-out">
                                    <i class="fas fa-arrow-left mr-1"></i>Kembali ke Daftar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hapus Tugas -->
<div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete_task">
                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                <input type="hidden" name="redirect_url" value="tasks.php">
                
                <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                    <h5 class="text-lg font-semibold text-gray-900" id="deleteTaskModalLabel">Konfirmasi Hapus Tugas</h5>
                    <button type="button" class="text-gray-400 hover:text-gray-500" data-bs-dismiss="modal" aria-label="Close">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="px-4 py-4">
                    <p class="mb-2">Apakah Anda yakin ingin menghapus tugas <strong class="font-semibold"><?php echo htmlspecialchars($task['title']); ?></strong>?</p>
                    <p class="text-red-600">Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                
                <div class="px-4 py-3 bg-gray-50 flex justify-end space-x-2">
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">Hapus Tugas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php else: // Tampilkan daftar tugas ?>

<!-- Statistik Tugas -->
<div class="flex flex-wrap -mx-3 mb-6">
    <div class="w-full md:w-1/4 px-3 mb-3">
        <div class="bg-blue-600 text-white rounded-lg shadow-md h-full">
            <div class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-semibold uppercase">Total Tugas</h6>
                        <h2 class="text-3xl font-bold mt-1"><?php echo $totalTasks; ?></h2>
                    </div>
                    <i class="fas fa-tasks text-4xl opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="w-full md:w-1/4 px-3 mb-3">
        <div class="bg-yellow-500 text-white rounded-lg shadow-md h-full">
            <div class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-semibold uppercase">Pending</h6>
                        <h2 class="text-3xl font-bold mt-1"><?php echo $tasksByStatus['pending'] ?? 0; ?></h2>
                    </div>
                    <i class="fas fa-clock text-4xl opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="w-full md:w-1/4 px-3 mb-3">
        <div class="bg-blue-500 text-white rounded-lg shadow-md h-full">
            <div class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-semibold uppercase">Sedang Dikerjakan</h6>
                        <h2 class="text-3xl font-bold mt-1"><?php echo $tasksByStatus['in_progress'] ?? 0; ?></h2>
                    </div>
                    <i class="fas fa-spinner text-4xl opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="w-full md:w-1/4 px-3 mb-3">
        <div class="bg-green-500 text-white rounded-lg shadow-md h-full">
            <div class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h6 class="text-sm font-semibold uppercase">Selesai</h6>
                        <h2 class="text-3xl font-bold mt-1"><?php echo $tasksByStatus['completed'] ?? 0; ?></h2>
                    </div>
                    <i class="fas fa-check-circle text-4xl opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Tugas -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4">
        <div class="flex items-center">
            <svg class="h-5 w-5 text-blue-100 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            <h2 class="text-lg font-semibold">Filter Tugas</h2>
        </div>
    </div>
    <div class="p-4">
        <form method="GET" action="" class="flex flex-wrap -mx-2">
            <div class="w-full md:w-1/3 px-2 mb-4 md:mb-0">
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1 flex items-center">
                    <svg class="h-4 w-4 text-gray-500 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    Kategori
                </label>
                <div class="relative">
                    <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 pl-3 pr-10 py-2 text-base appearance-none" id="category_id" name="category_id">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($categoryId == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?> (<?php echo $cat['task_count']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="w-full md:w-1/3 px-2 mb-4 md:mb-0">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1 flex items-center">
                    <svg class="h-4 w-4 text-gray-500 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Status
                </label>
                <div class="relative">
                    <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 pl-3 pr-10 py-2 text-base appearance-none" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo ($status == 'in_progress') ? 'selected' : ''; ?>>Sedang Dikerjakan</option>
                        <option value="completed" <?php echo ($status == 'completed') ? 'selected' : ''; ?>>Selesai</option>
                        <option value="cancelled" <?php echo ($status == 'cancelled') ? 'selected' : ''; ?>>Dibatalkan</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="w-full md:w-1/3 px-2 flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md flex items-center justify-center transition duration-150 ease-in-out mr-2 shadow-sm">
                    <svg class="h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                </button>
                
                <a href="tasks.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-md flex items-center justify-center transition duration-150 ease-in-out border border-gray-300 shadow-sm">
                    <svg class="h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Daftar Tugas -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg class="h-6 w-6 text-blue-100 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <h2 class="text-lg font-semibold">Daftar Tugas</h2>
            </div>
            <a href="task_add.php" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                <svg class="-ml-0.5 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Tambah Tugas
            </a>
        </div>
    </div>
    <div class="p-4">
        <?php if (count($tasks) > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" id="tasksTable">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioritas</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenggat Waktu</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($tasks as $task): 
                        $category = $categoryModel->getCategoryById($task['category_id']);
                        $user = $userModel->getUserById($task['user_id']);
                    ?>
                    <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700">#<?php echo $task['id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="tasks.php?id=<?php echo $task['id']; ?>" class="text-blue-600 hover:text-blue-900 font-medium">
                                <?php echo htmlspecialchars($task['title']); ?>
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($category): ?>
                            <a href="tasks.php?category_id=<?php echo $task['category_id']; ?>" class="bg-blue-500 text-white text-xs font-semibold px-2.5 py-0.5 rounded inline-flex items-center">
                                <i class="fas fa-tag mr-1"></i><?php echo htmlspecialchars($category['name']); ?>
                            </a>
                            <?php else: ?>
                            <span class="bg-gray-500 text-white text-xs font-semibold px-2.5 py-0.5 rounded">Tidak Ada</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($user): ?>
                            <a href="tasks.php?user_id=<?php echo $task['user_id']; ?>" class="text-blue-600 hover:text-blue-900 flex items-center">
                                <div class="flex-shrink-0 h-7 w-7 rounded-full bg-blue-100 flex items-center justify-center text-blue-500 mr-2">
                                    <span class="text-xs font-medium"><?php echo substr(htmlspecialchars($user['username']), 0, 2); ?></span>
                                </div>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </a>
                            <?php else: ?>
                            <span class="text-gray-500">Pengguna tidak ditemukan</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php 
                            $statusColors = [
                                'pending' => 'bg-yellow-500',
                                'in_progress' => 'bg-blue-500',
                                'completed' => 'bg-green-500',
                                'cancelled' => 'bg-red-500',
                                'default' => 'bg-gray-500'
                            ];
                            $statusColor = $statusColors[$task['status']] ?? $statusColors['default'];
                            ?>
                            <span class="<?php echo $statusColor; ?> text-white text-xs font-semibold px-2.5 py-0.5 rounded">
                                <?php echo getStatusLabel($task['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($task['priority']): 
                                $priorityColors = [
                                    'low' => 'bg-green-500',
                                    'medium' => 'bg-yellow-500',
                                    'high' => 'bg-red-500',
                                    'default' => 'bg-gray-500'
                                ];
                                $priorityColor = $priorityColors[$task['priority']] ?? $priorityColors['default'];
                            ?>
                            <span class="<?php echo $priorityColor; ?> text-white text-xs font-semibold px-2.5 py-0.5 rounded">
                                <?php echo getPriorityLabel($task['priority']); ?>
                            </span>
                            <?php else: ?>
                            <span class="bg-gray-500 text-white text-xs font-semibold px-2.5 py-0.5 rounded">Normal</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($task['due_date']): ?>
                            <span class="<?php echo (strtotime($task['due_date']) < time() && $task['status'] != 'completed') ? 'text-red-600' : 'text-gray-700'; ?> flex items-center">
                                <svg class="h-4 w-4 <?php echo (strtotime($task['due_date']) < time() && $task['status'] != 'completed') ? 'text-red-500' : 'text-gray-400'; ?> mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <?php echo date('d M Y', strtotime($task['due_date'])); ?>
                                <?php if (strtotime($task['due_date']) < time() && $task['status'] != 'completed'): ?>
                                <i class="fas fa-exclamation-circle ml-1" title="Terlambat"></i>
                                <?php endif; ?>
                            </span>
                            <?php else: ?>
                            <span class="text-gray-500">Tidak Ada</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="relative inline-block text-left">
                                <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="dropdownMenuButton<?php echo $task['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span>Aksi</span>
                                    <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div class="dropdown-menu absolute z-50 origin-top-right right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none divide-y divide-gray-100" style="min-width: 12rem; position: absolute; top: 100%; right: 0;" aria-labelledby="dropdownMenuButton<?php echo $task['id']; ?>">
                                    <div class="py-1">
                                        <a class="dropdown-item text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100 transition duration-150 ease-in-out" href="tasks.php?id=<?php echo $task['id']; ?>">
                                            <svg class="inline-block w-4 h-4 mr-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Detail
                                        </a>
                                    </div>
                                    <div class="py-1">
                                        <a class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100 transition duration-150 ease-in-out" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $task['id']; ?>">
                                            <svg class="inline-block w-4 h-4 mr-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Update Status
                                        </a>
                                    </div>
                                    <div class="py-1">
                                        <a class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100 transition duration-150 ease-in-out" href="#" data-bs-toggle="modal" data-bs-target="#deleteTaskModal<?php echo $task['id']; ?>">
                                            <svg class="inline-block w-4 h-4 mr-2 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Hapus
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Modal Update Status -->
<div class="modal fade fixed inset-0 overflow-y-auto hidden bg-gray-500 bg-opacity-75 transition-opacity z-50" id="updateStatusModal<?php echo $task['id']; ?>" tabindex="-1" aria-labelledby="updateStatusModalLabel<?php echo $task['id']; ?>" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_task_status">
                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="updateStatusModalLabel<?php echo $task['id']; ?>">
                                Update Status Tugas
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 mb-4">Tugas: <strong><?php echo htmlspecialchars($task['title']); ?></strong></p>
                                
                                <div class="mb-4">
                                    <label for="status<?php echo $task['id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" id="status<?php echo $task['id']; ?>" name="status">
                                        <option value="pending" <?php echo ($task['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="in_progress" <?php echo ($task['status'] == 'in_progress') ? 'selected' : ''; ?>>Sedang Dikerjakan</option>
                                        <option value="completed" <?php echo ($task['status'] == 'completed') ? 'selected' : ''; ?>>Selesai</option>
                                        <option value="cancelled" <?php echo ($task['status'] == 'cancelled') ? 'selected' : ''; ?>>Dibatalkan</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Simpan Perubahan
                    </button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" data-bs-dismiss="modal">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
                            
                            <!-- Modal Hapus Tugas -->
<div class="modal fade fixed inset-0 overflow-y-auto hidden bg-gray-500 bg-opacity-75 transition-opacity z-50" id="deleteTaskModal<?php echo $task['id']; ?>" tabindex="-1" aria-labelledby="deleteTaskModalLabel<?php echo $task['id']; ?>" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete_task">
                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="deleteTaskModalLabel<?php echo $task['id']; ?>">
                                Konfirmasi Hapus Tugas
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Apakah Anda yakin ingin menghapus tugas <span class="font-semibold"><?php echo htmlspecialchars($task['title']); ?></span>?</p>
                                <p class="text-sm text-red-600 mt-2">Tindakan ini tidak dapat dibatalkan.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition duration-150 ease-in-out">
                        Hapus Tugas
                    </button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition duration-150 ease-in-out" data-bs-dismiss="modal">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 mb-4 text-center">
            <div class="flex flex-col items-center justify-center">
                <div class="rounded-full bg-gray-100 p-3 mb-3">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-700 mb-2">Tidak ada tugas yang ditemukan</p>
                <p class="text-sm text-gray-500 mb-4">Coba ubah filter atau tambahkan tugas baru</p>
                <a href="#" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Tambah Tugas Baru
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Script untuk DataTables -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<style>
    /* Custom DataTables styling with Tailwind */
    .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_processing, .dataTables_wrapper .dataTables_paginate {
        color: #374151;
        margin-bottom: 1rem;
    }
    .dataTables_wrapper .dataTables_length select {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.25rem 2rem 0.25rem 0.75rem;
        background-color: white;
    }
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        margin-left: 0.5rem;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.5rem 1rem;
        margin-left: 0.25rem;
        background-color: white;
        color: #374151 !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background-color: #3b82f6 !important;
        color: white !important;
        border-color: #3b82f6;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background-color: #f3f4f6 !important;
        color: #1f2937 !important;
        border-color: #d1d5db;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background-color: #2563eb !important;
        color: white !important;
        border-color: #2563eb;
    }
</style>

<script>
$(document).ready(function() {
    $('#taskTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
        },
        order: [[0, 'desc']],
        drawCallback: function() {
            console.log('DataTable redrawn');
            // Tidak perlu memanggil initializeDropdownsAndModals karena sudah menggunakan event delegation
            
            // Pastikan dropdown menu memiliki z-index yang tinggi
            $('.dropdown-menu').addClass('z-50');
            
            // Log untuk debugging
            console.log('Dropdown buttons in table:', $('#taskTable [data-bs-toggle="dropdown"]').length);
            console.log('Modal buttons in table:', $('#taskTable [data-bs-toggle="modal"]').length);
            
            // Tidak perlu menambahkan event handler khusus karena sudah menggunakan event delegation
            // Event delegation akan menangani semua elemen yang cocok dengan selector, termasuk yang baru ditambahkan
        }
    });
    
    // Hapus kode custom untuk dropdown dan modal karena sudah menggunakan Bootstrap
    console.log('DataTable initialized, using Bootstrap for dropdowns and modals');
    
    // Tambahkan debugging untuk memeriksa Bootstrap
    if (typeof bootstrap !== 'undefined') {
        console.log('Bootstrap JS is loaded');
        
        // Cek apakah Bootstrap Dropdown tersedia
        if (typeof bootstrap.Dropdown !== 'undefined') {
            console.log('Bootstrap Dropdown is available');
        } else {
            console.error('Bootstrap Dropdown is not available');
        }
        
        // Cek apakah Bootstrap Modal tersedia
        if (typeof bootstrap.Modal !== 'undefined') {
            console.log('Bootstrap Modal is available');
        } else {
            console.error('Bootstrap Modal is not available');
        }
    } else {
        console.error('Bootstrap JS is not loaded');
    }
         
         // Hapus event handler kustom untuk tombol tutup modal karena sudah menggunakan Bootstrap
         console.log('Using Bootstrap modal dismiss functionality');
         
         // Tambahkan debugging untuk modal
         $('.modal').each(function() {
             console.log('Modal found:', $(this).attr('id'), 'classes:', $(this).attr('class'));
         });
         
         // Tambahkan debugging untuk dropdown
         $('.dropdown-menu').each(function() {
             console.log('Dropdown menu found:', $(this).attr('class'), 'parent:', $(this).parent().prop('tagName'));
         });
         
         // Tambahkan debugging untuk dropdown toggle
         $('[data-bs-toggle="dropdown"]').each(function() {
             console.log('Dropdown toggle found:', $(this).attr('id'), 'target:', $(this).attr('data-bs-target'));
         });
    }
    
    // Hapus event handler kustom untuk menutup dropdown karena sudah menggunakan Bootstrap
    console.log('Using Bootstrap dropdown auto-close functionality');
    
    // Pastikan dropdown menu memiliki z-index yang tinggi
    $('.dropdown-menu').addClass('z-50');
    
    // Tambahkan debugging untuk memeriksa dropdown dan modal
    console.log('Total dropdown buttons:', $('[data-bs-toggle="dropdown"]').length);
    console.log('Total dropdown menus:', $('.dropdown-menu').length);
    console.log('Total modal buttons:', $('[data-bs-toggle="modal"]').length);
    console.log('Total modals:', $('.modal').length);
    
    // Tambahkan debugging untuk memeriksa Bootstrap
    if (typeof bootstrap !== 'undefined') {
        // Coba inisialisasi ulang dropdown secara manual
        $('[data-bs-toggle="dropdown"]').each(function() {
            try {
                new bootstrap.Dropdown(this);
                console.log('Dropdown initialized:', $(this).attr('id'));
            } catch (e) {
                console.error('Error initializing dropdown:', e);
            }
        });
    }
     
     // Tambahkan debugging untuk memeriksa struktur DOM
     console.log('Struktur dropdown pertama:');
     if ($('[data-bs-toggle="dropdown"]').length > 0) {
         const firstDropdown = $('[data-bs-toggle="dropdown"]').first();
         console.log('- Button ID:', firstDropdown.attr('id'));
         console.log('- Button parent:', firstDropdown.parent().prop('tagName'));
         console.log('- Button data attributes:', {
             'data-bs-toggle': firstDropdown.attr('data-bs-toggle'),
             'aria-expanded': firstDropdown.attr('aria-expanded'),
             'data-bs-auto-close': firstDropdown.attr('data-bs-auto-close')
         });
         
         // Tambahkan debugging untuk memeriksa posisi dan dimensi dropdown
         const buttonRect = firstDropdown[0].getBoundingClientRect();
         console.log('- Button position:', {
             top: buttonRect.top,
             left: buttonRect.left,
             width: buttonRect.width,
             height: buttonRect.height
         });
         
         // Cek dropdown menu
         const dropdownMenu = firstDropdown.next('.dropdown-menu');
         if (dropdownMenu.length > 0) {
             console.log('- Dropdown menu found as next sibling');
             console.log('- Dropdown menu classes:', dropdownMenu.attr('class'));
             console.log('- Dropdown menu style:', dropdownMenu.attr('style'));
         } else {
             console.log('- Dropdown menu not found as next sibling, checking parent');
             const parentDropdownMenu = firstDropdown.parent().find('.dropdown-menu');
             if (parentDropdownMenu.length > 0) {
                 console.log('- Dropdown menu found in parent');
                 console.log('- Dropdown menu classes:', parentDropdownMenu.attr('class'));
                 console.log('- Dropdown menu style:', parentDropdownMenu.attr('style'));
             }
         }
     }
     
     // Tambahkan debugging untuk memeriksa struktur modal
     console.log('Struktur modal pertama:');
     if ($('.modal').length > 0) {
         const firstModal = $('.modal').first();
         console.log('- Modal ID:', firstModal.attr('id'));
         console.log('- Modal classes:', firstModal.attr('class'));
         console.log('- Modal style:', firstModal.attr('style'));
         console.log('- Modal content:', firstModal.find('.modal-content').length);
         console.log('- Modal attributes:', {
             'aria-hidden': firstModal.attr('aria-hidden'),
             'tabindex': firstModal.attr('tabindex')
         });
     }
     
     // Gunakan event delegation untuk dropdown buttons dengan pendekatan yang lebih sederhana dan posisi yang benar
     $(document).off('click.dropdown').on('click.dropdown', '[data-bs-toggle="dropdown"]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const dropdownId = $(this).attr('id') || 'unnamed-dropdown';
        
        // Cari dropdown menu dengan cara yang lebih sederhana - hanya cari di parent
        const dropdownMenu = $(this).parent().find('.dropdown-menu');
        console.log('Dropdown clicked:', dropdownId, 'Menu found:', dropdownMenu.length);
        
        // Tutup semua dropdown lainnya
        $('.dropdown-menu').not(dropdownMenu).addClass('hidden').css('display', 'none');
        
        // Toggle dropdown yang diklik dengan cara yang lebih sederhana
        if (dropdownMenu.hasClass('hidden')) {
            // Tampilkan dropdown
            dropdownMenu.removeClass('hidden').css({
                'display': 'block',
                'z-index': '9999',
                'position': 'absolute',
                'top': '100%',
                'right': '0',
                'margin-top': '0.5rem'
            });
            
            // Pastikan dropdown menu terlihat di viewport
            const buttonRect = this.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const dropdownHeight = dropdownMenu.outerHeight();
            
            // Jika dropdown akan keluar dari viewport, tampilkan di atas tombol
            if (buttonRect.bottom + dropdownHeight > viewportHeight) {
                dropdownMenu.css({
                    'top': 'auto',
                    'bottom': '100%',
                    'margin-top': '0',
                    'margin-bottom': '0.5rem'
                });
            }
            
            console.log('Showing dropdown menu');
        } else {
            // Sembunyikan dropdown
            dropdownMenu.addClass('hidden').css('display', 'none');
            console.log('Hiding dropdown menu');
        }
     });
    
    // Menutup modal saat mengklik di luar dengan pendekatan yang lebih sederhana
     $(document).off('click.closeModal').on('click.closeModal', '.modal', function(e) {
        // Jika klik pada background modal (bukan konten modal)
        if ($(e.target).hasClass('modal') || 
            !$(e.target).closest('.modal-content').length) {
            console.log('Clicked outside modal content, closing modal:', this.id);
            // Sembunyikan modal dengan satu operasi
            $(this).addClass('hidden').css({
                'display': 'none'
            });
        }
     });
     
     // Menutup modal dengan tombol Escape dengan pendekatan yang lebih sederhana
     $(document).off('keydown.escapeModal').on('keydown.escapeModal', function(e) {
        if (e.key === 'Escape') {
            console.log('Escape key pressed, closing all visible modals');
            // Cari semua modal yang terlihat dan tutup
            $('.modal').each(function() {
                if (!$(this).hasClass('hidden') || $(this).css('display') === 'block') {
                    console.log('Closing modal:', $(this).attr('id'));
                    // Sembunyikan modal dengan satu operasi
                    $(this).addClass('hidden').css({
                        'display': 'none'
                    });
                }
            });
        }
     });
     
     // Gunakan event delegation untuk tombol modal dengan pendekatan yang lebih sederhana
      $(document).off('click.modal').on('click.modal', '[data-bs-toggle="modal"]', function(e) {
          e.preventDefault();
          e.stopPropagation();
          const modalId = $(this).data('bs-target');
          console.log('Modal clicked, target:', modalId, 'Modal exists:', $(modalId).length);
          
          // Tutup semua dropdown
          $('.dropdown-menu').addClass('hidden').css('display', 'none');
          
          // Tampilkan modal dengan cara yang lebih sederhana
          const modal = $(modalId);
          if (modal.length > 0) {
              modal.removeClass('hidden').css({
                  'display': 'block',
                  'z-index': '9999'
              });
              console.log('Modal displayed:', modalId);
          } else {
              console.error('Modal not found:', modalId);
          }
      });
});

// Fungsi untuk mendapatkan kelas badge berdasarkan status
function getStatusBadgeClass(status) {
    switch(status) {
        case 'pending': return 'warning';
        case 'in_progress': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

// Fungsi untuk mendapatkan label status
function getStatusLabel(status) {
    switch(status) {
        case 'pending': return 'Pending';
        case 'in_progress': return 'Sedang Dikerjakan';
        case 'completed': return 'Selesai';
        case 'cancelled': return 'Dibatalkan';
        default: return 'Tidak Diketahui';
    }
}

// Fungsi untuk mendapatkan kelas badge berdasarkan prioritas
function getPriorityBadgeClass(priority) {
    switch(priority) {
        case 'low': return 'success';
        case 'medium': return 'warning';
        case 'high': return 'danger';
        default: return 'secondary';
    }
}

// Fungsi untuk mendapatkan label prioritas
function getPriorityLabel(priority) {
    switch(priority) {
        case 'low': return 'Rendah';
        case 'medium': return 'Sedang';
        case 'high': return 'Tinggi';
        default: return 'Normal';
    }
}
</script>

<?php endif; ?>

<?php
// Fungsi helper untuk mendapatkan kelas badge berdasarkan status
function getStatusBadgeClass($status) {
    switch($status) {
        case 'pending': return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200';
        case 'in_progress': return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200';
        case 'completed': return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200';
        case 'cancelled': return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200';
        default: return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200';
    }
}

// Fungsi helper untuk mendapatkan label status
function getStatusLabel($status) {
    switch($status) {
        case 'pending': return '<svg class="inline-block w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>Pending';
        case 'in_progress': return '<svg class="inline-block w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path></svg>Sedang Dikerjakan';
        case 'completed': return '<svg class="inline-block w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Selesai';
        case 'cancelled': return '<svg class="inline-block w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>Dibatalkan';
        default: return '<svg class="inline-block w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path></svg>Unknown';
    }
}

// Fungsi helper untuk mendapatkan kelas badge berdasarkan prioritas
function getPriorityBadgeClass($priority) {
    switch($priority) {
        case 'low': return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200';
        case 'medium': return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200';
        case 'high': return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200';
        default: return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200';
    }
}

// Fungsi helper untuk mendapatkan label prioritas
function getPriorityLabel($priority) {
    switch($priority) {
        case 'low': return '<svg class="inline-block w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>Rendah';
        case 'medium': return '<svg class="inline-block w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path><path fill-rule="evenodd" d="M3 6a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>Sedang';
        case 'high': return '<svg class="inline-block w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path><path fill-rule="evenodd" d="M3 6a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path><path fill-rule="evenodd" d="M3 14a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>Tinggi';
        default: return '<svg class="inline-block w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path></svg>Tidak Diketahui';
    }
}
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>