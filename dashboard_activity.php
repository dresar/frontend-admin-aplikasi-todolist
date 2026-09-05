<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/models/UserModel.php';

// Inisialisasi model
$userModel = new UserModel();

// Mendapatkan statistik aktivitas pengguna untuk 30 hari terakhir
$activityStats = $userModel->getUserActivityStats(30);

// Mendapatkan statistik aktivitas berdasarkan jenis
$activityTypeStats = $userModel->getActivityStatsByType(30);

// Mendapatkan aktivitas login terbaru (10 terakhir)
$loginActivities = $userModel->getUserActivitiesByType('login', 10);

// Mendapatkan aktivitas logout terbaru (10 terakhir)
$logoutActivities = $userModel->getUserActivitiesByType('logout', 10);

// Mengubah format data untuk grafik aktivitas harian
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

// Mengubah format data untuk grafik jenis aktivitas
$typeChartLabels = [];
$typeChartData = [];
$typeChartColors = [];

// Warna untuk jenis aktivitas
$colorMap = [
    'login' => 'rgba(52, 211, 153, 0.8)',    // Hijau
    'logout' => 'rgba(239, 68, 68, 0.8)',      // Merah
    'create' => 'rgba(59, 130, 246, 0.8)',     // Biru
    'update' => 'rgba(251, 191, 36, 0.8)',     // Kuning
    'delete' => 'rgba(236, 72, 153, 0.8)',     // Pink
    'view' => 'rgba(139, 92, 246, 0.8)',       // Ungu
];

// Mengisi data untuk jenis aktivitas
foreach ($activityTypeStats as $stat) {
    $typeChartLabels[] = $stat['activity_type'];
    $typeChartData[] = (int)$stat['count'];
    
    // Menentukan warna berdasarkan jenis aktivitas
    $color = 'rgba(156, 163, 175, 0.8)'; // Default abu-abu
    foreach ($colorMap as $type => $typeColor) {
        if (strpos($stat['activity_type'], $type) !== false) {
            $color = $typeColor;
            break;
        }
    }
    
    $typeChartColors[] = $color;
}
?>

<div class="mb-8">
    <div class="w-full flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold flex items-center"><i class="fas fa-chart-line mr-2"></i>Ringkasan Aktivitas Pengguna</h2>
            <p class="text-gray-600">Statistik dan riwayat aktivitas pengguna dalam sistem.</p>
        </div>
        <div class="flex space-x-3">
            <a href="activity_statistics.php" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 flex items-center">
                <i class="fas fa-chart-pie mr-2"></i>Statistik Detail
            </a>
            <a href="user_activities.php" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 flex items-center">
                <i class="fas fa-list mr-2"></i>Semua Aktivitas
            </a>
        </div>
    </div>
</div>

<!-- Grafik Aktivitas Pengguna -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Grafik Aktivitas Harian -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h5 class="text-lg font-semibold mb-4">Aktivitas Harian (30 Hari Terakhir)</h5>
        <div class="h-[300px]">
            <canvas id="userActivityChart" style="display: block; box-sizing: border-box; height: 300px; width: 100%;"></canvas>
        </div>
    </div>
    
    <!-- Grafik Jenis Aktivitas -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h5 class="text-lg font-semibold mb-4">Distribusi Jenis Aktivitas</h5>
        <div class="h-[300px]">
            <canvas id="activityTypeChart" style="display: block; box-sizing: border-box; height: 300px; width: 100%;"></canvas>
        </div>
    </div>
</div>

<!-- Aktivitas Login dan Logout Terbaru -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Aktivitas Login Terbaru -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h5 class="text-lg font-semibold mb-4">Login Terbaru</h5>
        <?php if (count($loginActivities) > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($loginActivities as $activity): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?php echo date('d/m H:i', strtotime($activity['created_at'])); ?></td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                            <a href="users.php?id=<?php echo $activity['user_id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <?php echo htmlspecialchars($activity['username']); ?>
                            </a>
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="bg-blue-50 text-blue-700 p-4 rounded-lg">Tidak ada aktivitas login terbaru.</div>
        <?php endif; ?>
    </div>
    
    <!-- Aktivitas Logout Terbaru -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h5 class="text-lg font-semibold mb-4">Logout Terbaru</h5>
        <?php if (count($logoutActivities) > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($logoutActivities as $activity): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?php echo date('d/m H:i', strtotime($activity['created_at'])); ?></td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                            <a href="users.php?id=<?php echo $activity['user_id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <?php echo htmlspecialchars($activity['username']); ?>
                            </a>
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm"><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="bg-blue-50 text-blue-700 p-4 rounded-lg">Tidak ada aktivitas logout terbaru.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Script untuk inisialisasi grafik -->
<script>
// Data untuk grafik aktivitas pengguna
var activityChartData = {
    labels: <?php echo json_encode($chartLabels); ?>,
    values: <?php echo json_encode($chartData); ?>
};

// Data untuk grafik jenis aktivitas
var activityTypeData = {
    labels: <?php echo json_encode($typeChartLabels); ?>,
    values: <?php echo json_encode($typeChartData); ?>,
    colors: <?php echo json_encode($typeChartColors); ?>
};

// Inisialisasi grafik saat dokumen siap
document.addEventListener('DOMContentLoaded', function() {
    // Grafik Aktivitas Pengguna (Line Chart)
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
    
    // Grafik Jenis Aktivitas (Bar Chart)
    var activityTypeChart = document.getElementById('activityTypeChart');
    if (activityTypeChart) {
        var ctx = activityTypeChart.getContext('2d');
        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: activityTypeData.labels,
                datasets: [{
                    label: 'Jumlah Aktivitas',
                    backgroundColor: activityTypeData.colors,
                    borderColor: activityTypeData.colors.map(color => color.replace('0.8', '1')),
                    borderWidth: 1,
                    data: activityTypeData.values
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
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>