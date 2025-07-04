<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Simular datos de auditorías para el maquetado
$audits = [
    [
        'audit_id' => 1,
        'warehouse_name' => 'Almacén Principal',
        'auditor' => 'Juan Carlos Martínez',
        'audit_date' => '2025-01-15',
        'status' => 'completed',
        'total_items' => 89,
        'discrepancies' => 3,
        'accuracy_rate' => 96.6,
        'notes' => 'Auditoría rutinaria mensual'
    ],
    [
        'audit_id' => 2,
        'warehouse_name' => 'Almacén Norte',
        'auditor' => 'María González',
        'audit_date' => '2025-01-10',
        'status' => 'in_progress',
        'total_items' => 45,
        'discrepancies' => 0,
        'accuracy_rate' => 100,
        'notes' => 'Auditoría de fin de año'
    ],
    [
        'audit_id' => 3,
        'warehouse_name' => 'Almacén Sur',
        'auditor' => 'Carlos Rodríguez',
        'audit_date' => '2025-01-05',
        'status' => 'pending',
        'total_items' => 32,
        'discrepancies' => 0,
        'accuracy_rate' => 0,
        'notes' => 'Auditoría programada'
    ]
];

$status_colors = [
    'completed' => ['bg' => 'bg-success', 'text' => 'text-white'],
    'in_progress' => ['bg' => 'bg-warning', 'text' => 'text-dark'],
    'pending' => ['bg' => 'bg-secondary', 'text' => 'text-white']
];

$status_labels = [
    'completed' => 'Completada',
    'in_progress' => 'En Progreso',
    'pending' => 'Pendiente'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditorías de Inventario | Gestor de inventarios</title>
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
        .audit-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .audit-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .audit-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #121866, #232a7c);
        }
        .audit-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .audit-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .audit-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .audit-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .info-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
        }
        .info-value {
            font-size: 0.95rem;
            color: #121866;
            font-weight: 600;
        }
        .audit-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        .stat-item {
            text-align: center;
            padding: 12px 8px;
            background: #f7f9fc;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
        }
        .stat-number {
            font-size: 1.4rem;
            font-weight: 700;
            color: #121866;
            display: block;
            margin-bottom: 4px;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
        }
        .accuracy-bar {
            background: #e3e6f0;
            border-radius: 10px;
            height: 8px;
            margin: 8px 0;
            overflow: hidden;
        }
        .accuracy-fill {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(135deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }
        .accuracy-fill.warning {
            background: linear-gradient(135deg, #ffc107, #ff9800);
        }
        .accuracy-fill.danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        .audit-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        .btn-action {
            padding: 8px 12px;
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
        .btn-view {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .btn-view:hover {
            background: #7b1fa2;
            color: #fff;
        }
        .btn-edit {
            background: #e3f2fd;
            color: #1565c0;
        }
        .btn-edit:hover {
            background: #1565c0;
            color: #fff;
        }
        .btn-start {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .btn-start:hover {
            background: #2e7d32;
            color: #fff;
        }
        .btn-report {
            background: #fff3cd;
            color: #856404;
        }
        .btn-report:hover {
            background: #856404;
            color: #fff;
        }
        .btn-delete {
            background: #ffebee;
            color: #c62828;
        }
        .btn-delete:hover {
            background: #c62828;
            color: #fff;
        }
        .form-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            margin-bottom: 24px;
        }
        .form-card .card-header {
            background: linear-gradient(135deg, #121866, #232a7c);
            color: #fff;
            border-radius: 16px 16px 0 0;
            padding: 20px 24px;
            border: none;
        }
        .form-card .card-body {
            padding: 24px;
        }
        .overview-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .overview-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(18,24,102,0.07);
        }
        .overview-icon {
            font-size: 2rem;
            color: #121866;
            margin-bottom: 10px;
        }
        .overview-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #121866;
            margin-bottom: 5px;
        }
        .overview-label {
            font-size: 0.9rem;
            color: #666;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .titulo-lista { font-size: 1.4rem; }
            .audit-stats { grid-template-columns: repeat(2, 1fr); }
            .audit-actions { flex-direction: column; }
            .overview-stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="titulo-lista">
            <i class="bi bi-clipboard-check"></i> 
            Auditorías de Inventario
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i>
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas generales -->
        <div class="overview-stats">
            <div class="overview-card">
                <div class="overview-icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div class="overview-number"><?= count($audits) ?></div>
                <div class="overview-label">Auditorías Totales</div>
            </div>
            <div class="overview-card">
                <div class="overview-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="overview-number"><?= count(array_filter($audits, function($a) { return $a['status'] === 'completed'; })) ?></div>
                <div class="overview-label">Completadas</div>
            </div>
            <div class="overview-card">
                <div class="overview-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="overview-number"><?= array_sum(array_column($audits, 'discrepancies')) ?></div>
                <div class="overview-label">Discrepancias</div>
            </div>
            <div class="overview-card">
                <div class="overview-icon">
                    <i class="bi bi-percent"></i>
                </div>
                <div class="overview-number"><?= round(array_sum(array_column($audits, 'accuracy_rate')) / count($audits), 1) ?>%</div>
                <div class="overview-label">Precisión Promedio</div>
            </div>
        </div>

        <!-- Formulario para nueva auditoría -->
        <div class="form-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nueva Auditoría</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formNuevaAuditoria">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="warehouse_id" class="form-label">Almacén</label>
                                <select class="form-select" name="warehouse_id" id="warehouse_id" required>
                                    <option value="">Selecciona un almacén</option>
                                    <option value="1">Almacén Principal</option>
                                    <option value="2">Almacén Norte</option>
                                    <option value="3">Almacén Sur</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="auditor" class="form-label">Auditor</label>
                                <input type="text" class="form-control" name="auditor" id="auditor" 
                                       placeholder="Nombre del auditor">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="audit_date" class="form-label">Fecha de auditoría</label>
                                <input type="date" class="form-control" name="audit_date" id="audit_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="audit_type" class="form-label">Tipo de auditoría</label>
                                <select class="form-select" name="audit_type" id="audit_type">
                                    <option value="routine">Rutinaria</option>
                                    <option value="cycle">Ciclo</option>
                                    <option value="full">Completa</option>
                                    <option value="random">Aleatoria</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas</label>
                        <textarea class="form-control" name="notes" id="notes" rows="2" 
                                  placeholder="Notas sobre la auditoría"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Crear Auditoría
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de auditorías -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0" style="color: #121866; font-weight: 600;">
                <i class="bi bi-list-ul"></i> Auditorías Registradas
            </h4>
            <small class="text-muted">
                <?= count($audits) ?> auditorías
            </small>
        </div>

        <?php if (!empty($audits)): ?>
            <div class="row">
                <?php foreach ($audits as $audit): ?>
                    <div class="col-lg-6 col-xl-4 mb-3">
                        <div class="audit-card">
                            <div class="audit-header">
                                <h5 class="audit-title">
                                    <i class="bi bi-clipboard-check"></i> 
                                    Auditoría #<?= $audit['audit_id'] ?>
                                </h5>
                                <span class="audit-status <?= $status_colors[$audit['status']]['bg'] ?> <?= $status_colors[$audit['status']]['text'] ?>">
                                    <?= $status_labels[$audit['status']] ?>
                                </span>
                            </div>
                            
                            <div class="audit-info">
                                <div class="info-item">
                                    <span class="info-label">Almacén</span>
                                    <span class="info-value"><?= htmlspecialchars($audit['warehouse_name']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Auditor</span>
                                    <span class="info-value"><?= htmlspecialchars($audit['auditor']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Fecha</span>
                                    <span class="info-value"><?= date('d/m/Y', strtotime($audit['audit_date'])) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Notas</span>
                                    <span class="info-value"><?= htmlspecialchars($audit['notes']) ?></span>
                                </div>
                            </div>
                            
                            <div class="audit-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?= $audit['total_items'] ?></span>
                                    <span class="stat-label">Artículos</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?= $audit['discrepancies'] ?></span>
                                    <span class="stat-label">Discrepancias</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?= $audit['accuracy_rate'] ?>%</span>
                                    <span class="stat-label">Precisión</span>
                                </div>
                            </div>
                            
                            <!-- Barra de precisión -->
                            <?php if ($audit['status'] === 'completed'): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">Precisión de la auditoría</small>
                                        <small class="text-muted"><?= $audit['accuracy_rate'] ?>%</small>
                                    </div>
                                    <div class="accuracy-bar">
                                        <div class="accuracy-fill <?= $audit['accuracy_rate'] < 90 ? 'danger' : ($audit['accuracy_rate'] < 95 ? 'warning' : '') ?>" 
                                             style="width: <?= $audit['accuracy_rate'] ?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="audit-actions">
                                <a href="view.php?id=<?= $audit['audit_id'] ?>" class="btn-action btn-view">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <?php if ($audit['status'] === 'pending'): ?>
                                    <button type="button" class="btn-action btn-start" 
                                            onclick="iniciarAuditoria(<?= $audit['audit_id'] ?>)">
                                        <i class="bi bi-play-circle"></i> Iniciar
                                    </button>
                                <?php endif; ?>
                                <?php if ($audit['status'] === 'in_progress'): ?>
                                    <a href="conduct.php?id=<?= $audit['audit_id'] ?>" class="btn-action btn-edit">
                                        <i class="bi bi-pencil"></i> Continuar
                                    </a>
                                <?php endif; ?>
                                <?php if ($audit['status'] === 'completed'): ?>
                                    <a href="report.php?id=<?= $audit['audit_id'] ?>" class="btn-action btn-report">
                                        <i class="bi bi-file-text"></i> Reporte
                                    </a>
                                <?php endif; ?>
                                <button type="button" class="btn-action btn-delete" 
                                        onclick="eliminarAuditoria(<?= $audit['audit_id'] ?>)">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-clipboard-check" style="font-size: 4rem; color: #ccc;"></i>
                <h5 class="mt-3">No hay auditorías registradas</h5>
                <p class="text-muted">Crea tu primera auditoría para verificar la precisión del inventario.</p>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="../dashboard/index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para iniciar auditoría
        function iniciarAuditoria(auditId) {
            if (confirm('¿Estás seguro de que quieres iniciar esta auditoría?')) {
                // Aquí iría la lógica para iniciar la auditoría
                console.log('Iniciando auditoría:', auditId);
            }
        }

        // Función para eliminar auditoría
        function eliminarAuditoria(auditId) {
            if (confirm('¿Estás seguro de que quieres eliminar esta auditoría?')) {
                // Aquí iría la lógica para eliminar la auditoría
                console.log('Eliminando auditoría:', auditId);
            }
        }

        // Auto-focus en el campo de almacén al cargar la página
        document.getElementById('warehouse_id').focus();
    </script>
</body>
</html> 