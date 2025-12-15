<?php
require_once 'config/database.php';
require_once 'classes/Service.php';
require_once 'includes/auth.php';

// Assuming you might want a login check in the future
// $auth = new Auth($pdo);
// $auth->requireLogin();

$service = new Service($pdo);
$stats = $service->getDashboardStats();

$page_title = "Dashboard";
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <h1 class="h2 mb-4 text-gray-800">Dashboard General</h1>

    <!-- Stat Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card bg-gradient-primary">
                <div class="card-body text-center">
                    <div class="card-title-xs">Total Servicios</div>
                    <div class="card-value"><?= number_format($stats['total']) ?></div>
                    <div class="card-icon"><i class="bi bi-briefcase-fill"></i></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card bg-gradient-success">
                <div class="card-body text-center">
                    <div class="card-title-xs">Facturado Total</div>
                    <div class="card-value">S/ <?= number_format($stats['facturacion']['facturado'], 2) ?></div>
                    <div class="card-icon"><i class="bi bi-cash-coin"></i></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card bg-gradient-warning">
                <div class="card-body text-center text-dark">
                    <div class="card-title-xs">Servicios Pendientes</div>
                    <div class="card-value"><?= $stats['por_estado']['Pendiente'] ?? 0 ?></div>
                    <div class="card-icon"><i class="bi bi-clock-history"></i></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card bg-gradient-info">
                <div class="card-body text-center">
                    <div class="card-title-xs">Servicios Este Mes</div>
                    <div class="card-value"><?= $stats['mes_actual'] ?></div>
                    <div class="card-icon"><i class="bi bi-calendar-check-fill"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4">
        <div class="col-xl-8">
            <div class="chart-card">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="m-0 fw-bold text-primary">Rendimiento Mensual (Año Actual)</h6>
                </div>
                <div class="card-body">
                    <div style="height: 350px;">
                        <canvas id="serviciosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="chart-card">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="m-0 fw-bold text-primary">Distribución por Estado</h6>
                </div>
                <div class="card-body">
                    <div style="height: 350px;">
                        <canvas id="estadoChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Chart.defaults.font.family = "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
    Chart.defaults.color = '#555';

    // Line Chart - Monthly Services
    const ctx1 = document.getElementById('serviciosChart').getContext('2d');
    const gradient = ctx1.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(79, 70, 229, 0.5)');
    gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');

    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            datasets: [{
                label: 'Servicios',
                data: <?= json_encode(array_values($stats['servicios_por_mes'] ?? [])) ?>,
                borderColor: 'rgba(79, 70, 229, 1)',
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: 'rgba(79, 70, 229, 1)',
                pointBorderColor: '#fff',
                pointHoverRadius: 6,
                pointRadius: 4,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#e5e7eb',
                        drawBorder: false,
                    },
                    ticks: {
                        precision: 0 // Only show whole numbers
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: '#fff',
                    titleColor: '#333',
                    bodyColor: '#666',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return `Servicios: ${context.raw}`;
                        }
                    }
                }
            }
        }
    });

    // Doughnut Chart - Status Distribution
    const ctx2 = document.getElementById('estadoChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($stats['por_estado'])) ?>,
            datasets: [{
                data: <?= json_encode(array_values($stats['por_estado'])) ?>,
                backgroundColor: [
                    '#f59e0b', // Pendiente
                    '#10b981', // Ejecutado
                    '#3b82f6', // Sustentado
                    '#4f46e5', // Facturado
                    '#6b7280'  // Otro estado
                ],
                borderColor: '#fff',
                borderWidth: 4,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: '#fff',
                    titleColor: '#333',
                    bodyColor: '#666',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: true,
                }
            }
        }
    });
});
</script>

<?php
// We might need to adjust the footer if it doesn't fit the new layout
// For now, let's include it as is.
include 'includes/footer.php';
?>