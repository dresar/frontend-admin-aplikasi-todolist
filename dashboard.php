<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/AdminModel.php';
require_once __DIR__ . '/models/CategoryModel.php';
require_once __DIR__ . '/models/TaskModel.php';
require_once __DIR__ . '/models/TokenModel.php';

// Inisialisasi model
$userModel = new UserModel();
$adminModel = new AdminModel();
$categoryModel = new CategoryModel();
$taskModel = new TaskModel();
$tokenModel = new TokenModel();

// Mendapatkan data untuk dashboard
$totalUsers = $userModel->getTotalUsers();
$totalAdmins = $adminModel->getTotalAdmins();
$totalCategories = $categoryModel->getTotalCategories();
$totalTasks = $taskModel->getTotalTasks();
$totalTokens = $tokenModel->getTotalTokens();

// Mendapatkan statistik tugas berdasarkan status
$taskStats = $taskModel->getTaskCountByStatus();

// Mendapatkan tugas yang hampir jatuh tempo
$upcomingTasks = $taskModel->getUpcomingTasks(3);

// Mendapatkan tugas yang sudah melewati tenggat waktu
$overdueTasks = $taskModel->getOverdueTasks();

// Mendapatkan pengguna baru
$newUsers = $userModel->getNewUsers(7);

// Mendapatkan kategori teratas
$topCategories = $categoryModel->getTopCategories(5);
?>

<div class="mb-8">
    <div class="w-full flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold flex items-center"><i class="fas fa-tachometer-alt mr-2"></i>Dashboard</h2>
            <p class="text-gray-600">Selamat datang, <?php echo $_SESSION['admin_name']; ?>! Berikut adalah ringkasan data sistem.</p>
        </div>
        <div class="flex space-x-3">
            <a href="dashboard_activity.php" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 flex items-center">
                <i class="fas fa-chart-line mr-2"></i>Ringkasan Aktivitas
            </a>
            <a href="user_activities.php" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 flex items-center">
                <i class="fas fa-list mr-2"></i>Semua Aktivitas
            </a>
        </div>
    </div>
</div>

<!-- Statistik Utama -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Pengguna -->
    <div>
        <div class="bg-gradient-to-r from-primary to-primary-dark text-white rounded-xl shadow-md transition-transform duration-300 hover:-translate-y-1">
            <div class="p-6">
                <div class="flex justify-between items-center">
                    <div class="flex-1">
                        <div class="text-sm font-semibold mb-1">Total Pengguna</div>
                        <div class="text-3xl font-bold"><?php echo $totalUsers; ?></div>
                    </div>
                    <div class="text-4xl opacity-80">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Admin -->
    <div>
        <div class="bg-gradient-to-r from-success to-success-dark text-white rounded-xl shadow-md transition-transform duration-300 hover:-translate-y-1">
            <div class="p-6">
                <div class="flex justify-between items-center">
                    <div class="flex-1">
                        <div class="text-sm font-semibold mb-1">Total Admin</div>
                        <div class="text-3xl font-bold"><?php echo $totalAdmins; ?></div>
                    </div>
                    <div class="text-4xl opacity-80">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Kategori -->
    <div>
        <div class="bg-gradient-to-r from-info to-info-dark text-white rounded-xl shadow-md transition-transform duration-300 hover:-translate-y-1">
            <div class="p-6">
                <div class="flex justify-between items-center">
                    <div class="flex-1">
                        <div class="text-sm font-semibold mb-1">Total Kategori</div>
                        <div class="text-3xl font-bold"><?php echo $totalCategories; ?></div>
                    </div>
                    <div class="text-4xl opacity-80">
                        <i class="fas fa-tags"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Tugas -->
    <div>
        <div class="bg-gradient-to-r from-warning to-warning-dark text-white rounded-xl shadow-md transition-transform duration-300 hover:-translate-y-1">
            <div class="p-6">
                <div class="flex justify-between items-center">
                    <div class="flex-1">
                        <div class="text-sm font-semibold mb-1">Total Tugas</div>
                        <div class="text-3xl font-bold"><?php echo $totalTasks; ?></div>
                    </div>
                    <div class="text-4xl opacity-80">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafik dan Statistik -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
    <!-- Grafik Aktivitas Pengguna -->
    <div class="lg:col-span-8">
        <div class="bg-white rounded-xl shadow-md p-6">
            <h5 class="text-lg font-semibold mb-4">Aktivitas Pengguna (30 Hari Terakhir)</h5>
            <div class="h-[300px]">
                <canvas id="userActivityChart" style="display: block; box-sizing: border-box; height: 300px; width: 100%;"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Grafik Status Tugas -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-xl shadow-md p-6">
            <h5 class="text-lg font-semibold mb-4">Status Tugas</h5>
            <div class="h-[300px]">
                <canvas id="tasksStatusChart" style="display: block; box-sizing: border-box; height: 300px; width: 100%;"></canvas>
            </div>
            <div class="mt-4 text-center">
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <div id="completed-count" class="font-bold"><?php echo $taskStats['completed']; ?></div>
                        <div class="text-gray-500 text-sm">Selesai</div>
                    </div>
                    <div>
                        <div id="in-progress-count" class="font-bold"><?php echo $taskStats['in_progress']; ?></div>
                        <div class="text-gray-500 text-sm">Dalam Proses</div>
                    </div>
                    <div>
                        <div id="not-started-count" class="font-bold"><?php echo $taskStats['not_started']; ?></div>
                        <div class="text-gray-500 text-sm">Belum Dimulai</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tugas yang Hampir Jatuh Tempo dan Melewati Tenggat -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Tugas yang Hampir Jatuh Tempo -->
    <div>
        <div class="bg-white rounded-xl shadow-md p-6">
            <h5 class="text-lg font-semibold mb-4">Tugas yang Hampir Jatuh Tempo</h5>
            <?php if (count($upcomingTasks) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenggat</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($upcomingTasks as $task): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="tasks.php?id=<?php echo $task['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($task['user_username']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y', strtotime($task['due_date'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($task['status'] == 'completed'): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>
                                <?php elseif ($task['status'] == 'in_progress'): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Dalam Proses</span>
                                <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Belum Dimulai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="bg-blue-50 text-blue-700 p-4 rounded-lg">Tidak ada tugas yang hampir jatuh tempo.</div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Tugas yang Melewati Tenggat -->
    <div>
        <div class="bg-white rounded-xl shadow-md p-6">
            <h5 class="text-lg font-semibold mb-4">Tugas yang Melewati Tenggat</h5>
            <?php if (count($overdueTasks) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenggat</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($overdueTasks as $task): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="tasks.php?id=<?php echo $task['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($task['user_username']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-red-600"><?php echo date('d M Y', strtotime($task['due_date'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($task['status'] == 'completed'): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>
                                <?php elseif ($task['status'] == 'in_progress'): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Dalam Proses</span>
                                <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Belum Dimulai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="bg-green-50 text-green-700 p-4 rounded-lg">Tidak ada tugas yang melewati tenggat.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Pengguna Baru dan Kategori Teratas -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Pengguna Baru -->
    <div>
        <div class="bg-white rounded-xl shadow-md p-6">
            <h5 class="text-lg font-semibold mb-4">Pengguna Baru (7 Hari Terakhir)</h5>
            <?php if (count($newUsers) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Daftar</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($newUsers as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="users.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (isset($user['is_active']) && $user['is_active'] == 1): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="bg-blue-50 text-blue-700 p-4 rounded-lg">Tidak ada pengguna baru dalam 7 hari terakhir.</div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Kategori Teratas -->
    <div>
        <div class="bg-white rounded-xl shadow-md p-6">
            <h5 class="text-lg font-semibold mb-4">Kategori Teratas</h5>
            <?php if (count($topCategories) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kategori</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Tugas</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($topCategories as $category): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="categories.php?id=<?php echo $category['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo isset($category['description']) ? htmlspecialchars($category['description']) : ''; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"><?php echo $category['task_count']; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="bg-blue-50 text-blue-700 p-4 rounded-lg">Tidak ada kategori yang tersedia.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Script untuk inisialisasi grafik -->
<script>
// Data awal untuk grafik status tugas (akan diperbarui dengan AJAX)
var taskStatusData = {
    completed: <?php echo $taskStats['completed']; ?>,
    in_progress: <?php echo $taskStats['in_progress']; ?>,
    not_started: <?php echo $taskStats['not_started']; ?>
};

// Inisialisasi grafik saat dokumen siap
document.addEventListener('DOMContentLoaded', function() {
    // Grafik Status Tugas
    var tasksStatusChart = document.getElementById('tasksStatusChart');
    if (tasksStatusChart) {
        var ctx = tasksStatusChart.getContext('2d');
        var taskChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Selesai', 'Dalam Proses', 'Belum Dimulai'],
                datasets: [{
                    data: [taskStatusData.completed, taskStatusData.in_progress, taskStatusData.not_started],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    hoverBackgroundColor: ['#059669', '#d97706', '#dc2626'],
                    hoverBorderColor: 'rgba(234, 236, 244, 1)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '70%'
            }
        });
        
        // Memperbarui data grafik status tugas dengan AJAX
        fetch('api/tasks-status.php')
            .then(response => response.json())
            .then(data => {
                taskChart.data.datasets[0].data = [
                    data.completed || 0,
                    data.in_progress || 0,
                    data.not_started || 0
                ];
                taskChart.update();
                
                // Memperbarui tampilan angka di bawah grafik
                document.getElementById('completed-count').innerText = data.completed || 0;
                document.getElementById('in-progress-count').innerText = data.in_progress || 0;
                document.getElementById('not-started-count').innerText = data.not_started || 0;
            })
            .catch(error => console.error('Error loading tasks data:', error));
    }
    
    // Grafik Aktivitas Pengguna
    var userActivityChart = document.getElementById('userActivityChart');
    if (userActivityChart) {
        var ctx = userActivityChart.getContext('2d');
        var activityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['1 Hari Lalu', '2 Hari Lalu', '3 Hari Lalu', '4 Hari Lalu', '5 Hari Lalu', '6 Hari Lalu', 'Hari Ini'],
                datasets: [{
                    label: 'Aktivitas Pengguna',
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(59, 130, 246, 1)',
                    data: [0, 0, 0, 0, 0, 0, 0], // Data awal kosong, akan diisi dengan AJAX
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Memperbarui data grafik aktivitas pengguna dengan AJAX
        fetch('api/chart-data.php')
            .then(response => response.json())
            .then(data => {
                activityChart.data.labels = data.labels;
                activityChart.data.datasets[0].data = data.values;
                activityChart.update();
            })
            .catch(error => console.error('Error loading chart data:', error));
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>