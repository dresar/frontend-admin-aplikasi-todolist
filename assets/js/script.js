// Custom JavaScript for Admin Panel

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Copy token to clipboard functionality
    var copyButtons = document.querySelectorAll('.copy-token');
    copyButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var tokenText = this.getAttribute('data-token');
            navigator.clipboard.writeText(tokenText).then(function() {
                // Change button text temporarily
                var originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Disalin!';
                setTimeout(function() {
                    button.innerHTML = originalText;
                }, 2000);
            });
        });
    });

    // Confirm delete actions
    var deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Apakah Anda yakin ingin menghapus item ini?')) {
                e.preventDefault();
            }
        });
    });

    // Toggle password visibility
    var togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var passwordField = document.querySelector(this.getAttribute('data-target'));
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordField.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });

    // Initialize dashboard charts if they exist
    initializeCharts();
});

// Function to initialize charts on dashboard
function initializeCharts() {
    // Users Activity Chart
    var userActivityChart = document.getElementById('userActivityChart');
    if (userActivityChart) {
        var ctx = userActivityChart.getContext('2d');
        var chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [], // Will be populated with AJAX
                datasets: [{
                    label: 'Aktivitas Pengguna',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                    data: [], // Will be populated with AJAX
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

        // Load chart data via AJAX
        fetch('api/chart-data.php')
            .then(response => response.json())
            .then(data => {
                chart.data.labels = data.labels;
                chart.data.datasets[0].data = data.values;
                chart.update();
            })
            .catch(error => console.error('Error loading chart data:', error));
    }

    // Tasks Status Chart
    var tasksStatusChart = document.getElementById('tasksStatusChart');
    if (tasksStatusChart) {
        var ctx = tasksStatusChart.getContext('2d');
        var chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Selesai', 'Dalam Proses', 'Belum Dimulai'],
                datasets: [{
                    data: [0, 0, 0], // Will be populated with AJAX
                    backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
                    hoverBackgroundColor: ['#169b6b', '#dda20a', '#be3c30'],
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

        // Load chart data via AJAX
        fetch('api/tasks-status.php')
            .then(response => response.json())
            .then(data => {
                chart.data.datasets[0].data = [
                    data.completed || 0,
                    data.in_progress || 0,
                    data.not_started || 0
                ];
                chart.update();
            })
            .catch(error => console.error('Error loading tasks data:', error));
    }
}

// Function to handle AJAX form submissions
function submitFormAjax(formId, successCallback) {
    var form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(form);

        fetch(form.action, {
            method: form.method,
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof successCallback === 'function') {
                    successCallback(data);
                } else {
                    // Default success behavior
                    alert(data.message || 'Operasi berhasil!');
                    window.location.reload();
                }
            } else {
                alert(data.message || 'Terjadi kesalahan!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengirim data!');
        });
    });
}