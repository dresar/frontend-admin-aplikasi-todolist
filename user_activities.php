<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/models/UserModel.php';

// Inisialisasi model
$userModel = new UserModel();

// Mendapatkan parameter filter dari request
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$activityType = isset($_GET['activity_type']) ? $_GET['activity_type'] : '';
$filterType = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'date';

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

// Mendapatkan daftar pengguna untuk dropdown
$users = $userModel->getAllUsers();

// Mendapatkan aktivitas pengguna berdasarkan filter
if ($filterType === 'user' && $userId > 0) {
    $activities = $userModel->getUserActivitiesByUserId($userId);
    $user = $userModel->getUserById($userId);
    $filterTitle = 'Pengguna: ' . ($user ? htmlspecialchars($user['username']) : 'Tidak Ditemukan');
} elseif ($filterType === 'type' && !empty($activityType)) {
    $activities = $userModel->getUserActivitiesByType($activityType);
    $filterTitle = 'Jenis Aktivitas: ' . htmlspecialchars($activityType);
} else {
    $activities = $userModel->getUserActivitiesByDate($date);
    $filterTitle = 'Tanggal: ' . date('d F Y', strtotime($date));
    $filterType = 'date'; // Reset ke default jika filter tidak valid
}

// Mendapatkan statistik aktivitas pengguna untuk 30 hari terakhir
$activityStats = $userModel->getUserActivityStats(30);

// Mengubah format data untuk grafik
$chartLabels = [];
$chartData = [];

// Mengisi data untuk 30 hari terakhir
for ($i = 29; $i >= 0; $i--) {
    $currentDate = date('Y-m-d', strtotime("-$i days"));
    $count = 0;
    
    // Mencari data untuk tanggal ini
    foreach ($activityStats as $stat) {
        if ($stat['date'] == $currentDate) {
            $count = (int)$stat['count'];
            break;
        }
    }
    
    // Format tanggal untuk label
    $chartLabels[] = date('d/m', strtotime($currentDate));
    $chartData[] = $count;
}
?>

<div class="mb-8">
    <div class="w-full">
        <h2 class="text-2xl font-bold flex items-center"><i class="fas fa-chart-line mr-2"></i>Aktivitas Pengguna</h2>
        <p class="text-gray-600">Statistik dan riwayat aktivitas pengguna dalam sistem.</p>
    </div>
</div>

<!-- Grafik Aktivitas Pengguna -->
<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <h5 class="text-lg font-semibold mb-4">Aktivitas Pengguna (30 Hari Terakhir)</h5>
    <div class="h-[300px]">
        <canvas id="userActivityChart" style="display: block; box-sizing: border-box; height: 300px; width: 100%;"></canvas>
    </div>
</div>

<!-- Filter Aktivitas -->
<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <h5 class="text-lg font-semibold mb-4">Filter Aktivitas Pengguna</h5>
    <div class="mb-4">
        <div class="flex space-x-4">
            <button type="button" onclick="showDateFilter()" class="px-3 py-1 <?php echo $filterType === 'date' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-md hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50">
                <i class="fas fa-calendar-alt mr-1"></i> Berdasarkan Tanggal
            </button>
            <button type="button" onclick="showUserFilter()" class="px-3 py-1 <?php echo $filterType === 'user' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-md hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50">
                <i class="fas fa-user mr-1"></i> Berdasarkan Pengguna
            </button>
            <button type="button" onclick="showTypeFilter()" class="px-3 py-1 <?php echo $filterType === 'type' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-md hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50">
                <i class="fas fa-tag mr-1"></i> Berdasarkan Jenis Aktivitas
            </button>
        </div>
    </div>
    
    <!-- Filter Berdasarkan Tanggal -->
    <form method="GET" action="" id="dateFilterForm" class="<?php echo $filterType === 'date' ? 'block' : 'hidden'; ?> flex items-end space-x-4">
        <input type="hidden" name="filter_type" value="date">
        <div class="flex-1">
            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
            <input type="date" id="date" name="date" value="<?php echo $date; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
        </div>
        <div>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
        </div>
    </form>
    
    <!-- Filter Berdasarkan Pengguna -->
    <form method="GET" action="" id="userFilterForm" class="<?php echo $filterType === 'user' ? 'block' : 'hidden'; ?> flex items-end space-x-4">
        <input type="hidden" name="filter_type" value="user">
        <div class="flex-1">
            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Pengguna</label>
            <select id="user_id" name="user_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                <option value="">-- Pilih Pengguna --</option>
                <?php foreach ($users as $user): ?>
                <option value="<?php echo $user['id']; ?>" <?php echo $userId == $user['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['name'] ?? $user['email']); ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
        </div>
    </form>
</div>

<!-- Filter Berdasarkan Jenis Aktivitas -->
    <form method="GET" action="" id="typeFilterForm" class="<?php echo $filterType === 'type' ? 'block' : 'hidden'; ?> flex items-end space-x-4">
        <input type="hidden" name="filter_type" value="type">
        <div class="flex-1">
            <label for="activity_type" class="block text-sm font-medium text-gray-700 mb-1">Jenis Aktivitas</label>
            <select id="activity_type" name="activity_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                <option value="">-- Pilih Jenis Aktivitas --</option>
                <option value="login" <?php echo $activityType === 'login' ? 'selected' : ''; ?>>Login</option>
                <option value="logout" <?php echo $activityType === 'logout' ? 'selected' : ''; ?>>Logout</option>
                <option value="create" <?php echo $activityType === 'create' ? 'selected' : ''; ?>>Create</option>
                <option value="update" <?php echo $activityType === 'update' ? 'selected' : ''; ?>>Update</option>
                <option value="delete" <?php echo $activityType === 'delete' ? 'selected' : ''; ?>>Delete</option>
                <option value="view" <?php echo $activityType === 'view' ? 'selected' : ''; ?>>View</option>
            </select>
        </div>
        <div>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
        </div>
    </form>
</div>

<script>
function showDateFilter() {
    document.getElementById('dateFilterForm').classList.remove('hidden');
    document.getElementById('userFilterForm').classList.add('hidden');
    document.getElementById('typeFilterForm').classList.add('hidden');
}

function showUserFilter() {
    document.getElementById('dateFilterForm').classList.add('hidden');
    document.getElementById('userFilterForm').classList.remove('hidden');
    document.getElementById('typeFilterForm').classList.add('hidden');
}

function showTypeFilter() {
    document.getElementById('dateFilterForm').classList.add('hidden');
    document.getElementById('userFilterForm').classList.add('hidden');
    document.getElementById('typeFilterForm').classList.remove('hidden');
}
</script>

<!-- Daftar Aktivitas -->
<div class="bg-white rounded-xl shadow-md p-6">
    <h5 class="text-lg font-semibold mb-4">Aktivitas Pengguna - <?php echo $filterTitle; ?></h5>
    
    <?php if (count($activities) > 0): ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" id="activitiesTable">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Aktivitas</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($activities as $activity): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap"><?php echo date('H:i:s', strtotime($activity['created_at'])); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="users.php?id=<?php echo $activity['user_id']; ?>" class="text-blue-600 hover:text-blue-800">
                            <?php echo htmlspecialchars($activity['username']); ?>
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php 
                        $activityType = $activity['activity_type'];
                        $badgeClass = '';
                        
                        if (strpos($activityType, 'login') !== false) {
                            $badgeClass = 'bg-green-100 text-green-800';
                        } else if (strpos($activityType, 'logout') !== false) {
                            $badgeClass = 'bg-red-100 text-red-800';
                        } else if (strpos($activityType, 'create') !== false) {
                            $badgeClass = 'bg-blue-100 text-blue-800';
                        } else if (strpos($activityType, 'update') !== false) {
                            $badgeClass = 'bg-yellow-100 text-yellow-800';
                        } else if (strpos($activityType, 'delete') !== false) {
                            $badgeClass = 'bg-red-100 text-red-800';
                        } else {
                            $badgeClass = 'bg-gray-100 text-gray-800';
                        }
                        ?>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $badgeClass; ?>">
                            <?php echo htmlspecialchars($activityType); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($activity['description']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="bg-blue-50 text-blue-700 p-4 rounded-lg">Tidak ada aktivitas pengguna pada tanggal ini.</div>
    <?php endif; ?>
</div>

<!-- Script untuk inisialisasi grafik -->
<script>
// Data untuk grafik aktivitas pengguna
var activityChartData = {
    labels: <?php echo json_encode($chartLabels); ?>,
    values: <?php echo json_encode($chartData); ?>
};

// Inisialisasi grafik saat dokumen siap
document.addEventListener('DOMContentLoaded', function() {
    // Grafik Aktivitas Pengguna
    var userActivityChart = document.getElementById('userActivityChart');
    if (userActivityChart) {
        var ctx = userActivityChart.getContext('2d');
        var chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: activityChartData.labels,
                datasets: [{
                    label: 'Aktivitas Pengguna',
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(59, 130, 246, 1)',
                    data: activityChartData.values,
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
    }
    
    // Inisialisasi DataTables
    if ($.fn.DataTable) {
        $('#activitiesTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
            },
            order: [[0, 'desc']]
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>