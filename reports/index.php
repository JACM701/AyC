<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

// Simular datos para los reportes
$report_data = [
    'total_products' => 156,
    'total_value' => 1250000,
    'low_stock_products' => 12,
    'out_of_stock' => 3,
    'monthly_movements' => [
        ['month' => 'Ene', 'in' => 45, 'out' => 32],
        ['month' => 'Feb', 'in' => 38, 'out' => 41],
        ['month' => 'Mar', 'in' => 52, 'out' => 29],
        ['month' => 'Abr', 'in' => 41, 'out' => 35],
        ['month' => 'May', 'in' => 47, 'out' => 38],
        ['month' => 'Jun', 'in' => 39, 'out' => 42]
    ],
    'top_categories' => [
        ['name' => 'Cámaras de Seguridad', 'count' => 45, 'value' => 450000],
        ['name' => 'Alarmas', 'count' => 32, 'value' => 320000],
        ['name' => 'Cables y Conectores', 'count' => 28, 'value' => 28000],
        ['name' => 'Fuentes de Poder', 'count' => 15, 'value' => 150000],
        ['name' => 'Accesorios', 'count' => 36, 'value' => 72000]
    ],
    'top_suppliers' => [
        ['name' => 'Syscom', 'products' => 45, 'total_value' => 450000],
        ['name' => 'PCH', 'products' => 32, 'total_value' => 320000],
        ['name' => 'Dahua Technology', 'products' => 28, 'total_value' => 280000]
    ]
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes y Estadísticas | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6fb;
        }
        .main-content {
            margin-top: 40px;
            margin-left: 250px;
            padding: 24px;
            width: calc(100vw - 250px);
            box-sizing: border-box;
        }
        .sidebar.collapsed ~ .main-content {
            margin-left: 70px !important;
            width: calc(100vw - 70px) !important;
            transition: margin-left 0.25s cubic-bezier(.4,2,.6,1), width 0.25s;
        }
        .titulo-lista {
            font-size: 2rem;
            color: #121866;
            font-weight: 700;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, #121866, #232a7c);
        }
        .stat-icon {
            font-size: 2.5rem;
            color: #121866;
            margin-bottom: 12px;
        }
        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: #121866;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 1rem;
            color: #666;
            font-weight: 500;
        }
        .report-section {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f0f0f0;
        }
        .report-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        .table th {
            background: #121866 !important;
            color: #fff !important;
            font-weight: 600;
            border: none;
        }
        .progress-custom {
            height: 8px;
            border-radius: 4px;
            background: #e3e6f0;
            overflow: hidden;
        }
        .progress-bar-custom {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(135deg, #121866, #232a7c);
            transition: width 0.3s ease;
        }
        .export-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-export {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }
        .btn-pdf {
            background: #dc3545;
            color: #fff;
        }
        .btn-pdf:hover {
            background: #c82333;
            color: #fff;
        }
        .btn-excel {
            background: #28a745;
            color: #fff;
        }
        .btn-excel:hover {
            background: #218838;
            color: #fff;
        }
        .btn-csv {
            background: #17a2b8;
            color: #fff;
        }
        .btn-csv:hover {
            background: #138496;
            color: #fff;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .titulo-lista { font-size: 1.4rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .export-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="titulo-lista">
            <i class="bi bi-graph-up"></i> 
            Reportes y Estadísticas
        </div>

        <!-- Estadísticas principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stat-number"><?= number_format($report_data['total_products']) ?></div>
                <div class="stat-label">Total de Productos</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-number">$<?= number_format($report_data['total_value'], 0, ',', '.') ?></div>
                <div class="stat-label">Valor Total del Inventario</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stat-number"><?= $report_data['low_stock_products'] ?></div>
                <div class="stat-label">Productos con Stock Bajo</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-number"><?= $report_data['out_of_stock'] ?></div>
                <div class="stat-label">Productos Sin Stock</div>
            </div>
        </div>

        <!-- Gráfica de movimientos mensuales -->
        <div class="report-section">
            <div class="report-header">
                <h5 class="report-title">
                    <i class="bi bi-bar-chart"></i> 
                    Movimientos Mensuales
                </h5>
                <div class="export-buttons">
                    <button class="btn-export btn-pdf">
                        <i class="bi bi-file-pdf"></i> PDF
                    </button>
                    <button class="btn-export btn-excel">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="movementsChart"></canvas>
            </div>
        </div>

        <!-- Top categorías -->
        <div class="report-section">
            <div class="report-header">
                <h5 class="report-title">
                    <i class="bi bi-tags"></i> 
                    Top Categorías por Valor
                </h5>
                <div class="export-buttons">
                    <button class="btn-export btn-csv">
                        <i class="bi bi-file-earmark-text"></i> CSV
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th>Productos</th>
                            <th>Valor Total</th>
                            <th>Porcentaje</th>
                            <th>Progreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['top_categories'] as $category): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($category['name']) ?></strong></td>
                                <td><?= $category['count'] ?></td>
                                <td>$<?= number_format($category['value'], 0, ',', '.') ?></td>
                                <td><?= round(($category['value'] / $report_data['total_value']) * 100, 1) ?>%</td>
                                <td style="width: 200px;">
                                    <div class="progress-custom">
                                        <div class="progress-bar-custom" style="width: <?= ($category['value'] / $report_data['total_value']) * 100 ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top proveedores -->
        <div class="report-section">
            <div class="report-header">
                <h5 class="report-title">
                    <i class="bi bi-truck"></i> 
                    Top Proveedores
                </h5>
                <div class="export-buttons">
                    <button class="btn-export btn-excel">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Productos</th>
                            <th>Valor Total</th>
                            <th>Promedio por Producto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['top_suppliers'] as $supplier): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($supplier['name']) ?></strong></td>
                                <td><?= $supplier['products'] ?></td>
                                <td>$<?= number_format($supplier['total_value'], 0, ',', '.') ?></td>
                                <td>$<?= number_format($supplier['total_value'] / $supplier['products'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reportes adicionales -->
        <div class="row">
            <div class="col-md-6">
                <div class="report-section">
                    <div class="report-header">
                        <h5 class="report-title">
                            <i class="bi bi-calendar-event"></i> 
                            Reportes por Fecha
                        </h5>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-calendar-week"></i> Reporte Semanal
                        </button>
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-calendar-month"></i> Reporte Mensual
                        </button>
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-calendar-range"></i> Reporte Personalizado
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="report-section">
                    <div class="report-header">
                        <h5 class="report-title">
                            <i class="bi bi-gear"></i> 
                            Configuración de Reportes
                        </h5>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-secondary">
                            <i class="bi bi-clock"></i> Programar Reportes
                        </button>
                        <button class="btn btn-outline-secondary">
                            <i class="bi bi-envelope"></i> Envío por Email
                        </button>
                        <button class="btn btn-outline-secondary">
                            <i class="bi bi-person"></i> Destinatarios
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="../dashboard/index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </main>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfica de movimientos mensuales
        const ctx = document.getElementById('movementsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($report_data['monthly_movements'], 'month')) ?>,
                datasets: [
                    {
                        label: 'Entradas',
                        data: <?= json_encode(array_column($report_data['monthly_movements'], 'in')) ?>,
                        backgroundColor: '#28a745',
                        borderColor: '#28a745',
                        borderWidth: 1
                    },
                    {
                        label: 'Salidas',
                        data: <?= json_encode(array_column($report_data['monthly_movements'], 'out')) ?>,
                        backgroundColor: '#dc3545',
                        borderColor: '#dc3545',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html> 