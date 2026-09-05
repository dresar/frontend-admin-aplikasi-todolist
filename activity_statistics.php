<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/models/UserModel.php';

// Inisialisasi model
$userModel = new UserModel();

// Mendapatkan parameter dari request
$period = isset($_GET['period']) ? $_GET['period'] : '30';

// Validasi periode
$validPeriods = ['7', '30', '90', '365'];
if (!in_array($period, $validPeriods)) {
    $period = '30';
}

// Mendapatkan statistik aktivitas berdasarkan jenis
$activityTypeStats = $userModel->getActivityStatsByType($period);

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
            <h2 class="text-2xl font-bold flex items-center"><i class="fas fa-chart-pie mr-2"></i>Statistik Aktivitas</h2>
            <p class="text-gray-600">Analisis statistik aktivitas pengguna dalam sistem.</p>
        </div>
        <div>
            <a href="user_activities.php" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 flex items-center">
                <i class="fas fa-list mr-2"></i>Lihat Semua Aktivitas
            </a>
        </div>
    </div>
</div>

<!-- Filter Periode -->
<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <h5 class="text-lg font-semibold mb-4">Filter Periode</h5>
    <div class="flex space-x-4">
        <a href="?period=7" class="px-4 py-2 <?php echo $period === '7' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-md hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50">
            7 Hari
        </a>
        <a href="?period=30" class="px-4 py-2 <?php echo $period === '30' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-md hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50">
            30 Hari
        </a>
        <a href="?period=90" class="px-4 py-2 <?php echo $period === '90' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-md hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50">
            90 Hari
        </a>
        <a href="?period=365" class="px-4 py-2 <?php echo $period === '365' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-md hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50">
            1 Tahun
        </a>
    </div>
</div>

<!-- Grafik Statistik -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Grafik Aktivitas Harian -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h5 class="text-lg font-semibold mb-4">Aktivitas Harian (<?php echo $period; ?> Hari Terakhir)</h5>
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

<!-- Tabel Statistik Jenis Aktivitas -->
<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <h5 class="text-lg font-semibold mb-4">Detail Statistik Jenis Aktivitas</h5>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" id="activityStatsTable">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Aktivitas</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Persentase</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                $totalActivities = array_sum($typeChartData);
                foreach ($activityTypeStats as $index => $stat): 
                    $percentage = $totalActivities > 0 ? round(($stat['count'] / $totalActivities) * 100, 2) : 0;
                    $color = isset($typeChartColors[$index]) ? $typeChartColors[$index] : 'rgba(156, 163, 175, 0.8)';
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-3 h-3 rounded-full mr-2" style="background-color: <?php echo $color; ?>"></div>
                            <?php echo htmlspecialchars($stat['activity_type']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap"><?php echo number_format($stat['count']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $percentage; ?>%</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="user_activities.php?filter_type=type&activity_type=<?php echo urlencode($stat['activity_type']); ?>" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye mr-1"></i> Lihat Detail
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Script untuk inisialisasi grafik -->
<script>
// Data untuk grafik jenis aktivitas
var activityTypeData = {
    labels: <?php echo json_encode($typeChartLabels); ?>,
    values: <?php echo json_encode($typeChartData); ?>,
    colors: <?php echo json_encode($typeChartColors); ?>
};

// Inisialisasi grafik saat dokumen siap
document.addEventListener('DOMContentLoaded', function() {
    // Memuat data aktivitas harian dari API
    fetch('api/activity-stats-daily.php?days=<?php echo $period; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Memformat data untuk grafik
                var labels = data.data.map(item => item.formatted_date);
                var values = data.data.map(item => item.count);
                
                // Inisialisasi grafik aktivitas harian
                var userActivityChart = document.getElementById('userActivityChart');
                if (userActivityChart) {
                    var ctx = userActivityChart.getContext('2d');
                    var chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Aktivitas Pengguna',
                                backgroundColor: 'rgba(59, 130, 246, 0.05)',
                                borderColor: 'rgba(59, 130, 246, 1)',
                                pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                                pointBorderColor: '#fff',
                                pointHoverBackgroundColor: '#fff',
                                pointHoverBorderColor: 'rgba(59, 130, 246, 1)',
                                data: values,
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
            }
        })
        .catch(error => console.error('Error loading activity data:', error));
    
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
    
    // Inisialisasi DataTables
    if ($.fn.DataTable) {
        $('#activityStatsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
            },
            order: [[1, 'desc']]
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>