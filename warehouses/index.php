<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Simular datos de almacenes para el maquetado
$warehouses = [
    [
        'warehouse_id' => 1,
        'name' => 'Almacén Principal',
        'location' => 'Av. Insurgentes Sur 123, CDMX',
        'manager' => 'Juan Carlos Martínez',
        'phone' => '55-1234-5678',
        'email' => 'almacen@empresa.com',
        'status' => 'active',
        'total_products' => 89,
        'total_value' => 450000,
        'capacity' => 1000,
        'used_capacity' => 750,
        'last_audit' => '2025-01-10'
    ],
    [
        'warehouse_id' => 2,
        'name' => 'Almacén Norte',
        'location' => 'Blvd. Constitución 456, Monterrey',
        'manager' => 'María González',
        'phone' => '81-9876-5432',
        'email' => 'almacen.norte@empresa.com',
        'status' => 'active',
        'total_products' => 45,
        'total_value' => 280000,
        'capacity' => 500,
        'used_capacity' => 320,
        'last_audit' => '2025-01-08'
    ],
    [
        'warehouse_id' => 3,
        'name' => 'Almacén Sur',
        'location' => 'Av. Vallarta 789, Guadalajara',
        'manager' => 'Carlos Rodríguez',
        'phone' => '33-5555-7777',
        'email' => 'almacen.sur@empresa.com',
        'status' => 'maintenance',
        'total_products' => 32,
        'total_value' => 180000,
        'capacity' => 300,
        'used_capacity' => 280,
        'last_audit' => '2025-01-05'
    ]
];

$status_colors = [
    'active' => ['bg' => 'bg-success', 'text' => 'text-white'],
    'inactive' => ['bg' => 'bg-secondary', 'text' => 'text-white'],
    'maintenance' => ['bg' => 'bg-warning', 'text' => 'text-dark']
];

$status_labels = [
    'active' => 'Activo',
    'inactive' => 'Inactivo',
    'maintenance' => 'Mantenimiento'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Almacenes | Gestor de inventarios</title>
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
        .warehouse-card {
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
        .warehouse-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .warehouse-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #121866, #232a7c);
        }
        .warehouse-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .warehouse-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .warehouse-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .warehouse-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        .warehouse-stats {
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
        .capacity-bar {
            background: #e3e6f0;
            border-radius: 10px;
            height: 8px;
            margin: 8px 0;
            overflow: hidden;
        }
        .capacity-fill {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(135deg, #121866, #232a7c);
            transition: width 0.3s ease;
        }
        .capacity-fill.warning {
            background: linear-gradient(135deg, #ffc107, #ff9800);
        }
        .capacity-fill.danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        .warehouse-actions {
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
        .btn-inventory {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .btn-inventory:hover {
            background: #2e7d32;
            color: #fff;
        }
        .btn-audit {
            background: #fff3cd;
            color: #856404;
        }
        .btn-audit:hover {
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
            .warehouse-stats { grid-template-columns: repeat(2, 1fr); }
            .warehouse-actions { flex-direction: column; }
            .overview-stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="titulo-lista">
            <i class="bi bi-building"></i> 
            Gestión de Almacenes
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
                    <i class="bi bi-building"></i>
                </div>
                <div class="overview-number"><?= count($warehouses) ?></div>
                <div class="overview-label">Almacenes Activos</div>
            </div>
            <div class="overview-card">
                <div class="overview-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="overview-number"><?= array_sum(array_column($warehouses, 'total_products')) ?></div>
                <div class="overview-label">Total de Productos</div>
            </div>
            <div class="overview-card">
                <div class="overview-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="overview-number">$<?= number_format(array_sum(array_column($warehouses, 'total_value')), 0, ',', '.') ?></div>
                <div class="overview-label">Valor Total</div>
            </div>
            <div class="overview-card">
                <div class="overview-icon">
                    <i class="bi bi-percent"></i>
                </div>
                <div class="overview-number"><?= round((array_sum(array_column($warehouses, 'used_capacity')) / array_sum(array_column($warehouses, 'capacity'))) * 100) ?>%</div>
                <div class="overview-label">Capacidad Utilizada</div>
            </div>
        </div>

        <!-- Formulario para nuevo almacén -->
        <div class="form-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nuevo Almacén</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formNuevoAlmacen">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre del almacén</label>
                                <input type="text" class="form-control" name="name" id="name" required 
                                       placeholder="Ej: Almacén Principal">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="manager" class="form-label">Encargado</label>
                                <input type="text" class="form-control" name="manager" id="manager" 
                                       placeholder="Nombre del encargado">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" name="phone" id="phone" 
                                       placeholder="55-1234-5678">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email" 
                                       placeholder="almacen@empresa.com">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Ubicación</label>
                        <textarea class="form-control" name="location" id="location" rows="2" 
                                  placeholder="Dirección completa del almacén"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="capacity" class="form-label">Capacidad máxima</label>
                                <input type="number" class="form-control" name="capacity" id="capacity" 
                                       placeholder="1000" min="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Estado</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="active">Activo</option>
                                    <option value="inactive">Inactivo</option>
                                    <option value="maintenance">Mantenimiento</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Crear Almacén
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de almacenes -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0" style="color: #121866; font-weight: 600;">
                <i class="bi bi-list-ul"></i> Almacenes Registrados
            </h4>
            <small class="text-muted">
                <?= count($warehouses) ?> almacenes
            </small>
        </div>

        <?php if (!empty($warehouses)): ?>
            <div class="row">
                <?php foreach ($warehouses as $warehouse): ?>
                    <div class="col-lg-6 col-xl-4 mb-3">
                        <div class="warehouse-card">
                            <div class="warehouse-header">
                                <h5 class="warehouse-title">
                                    <i class="bi bi-building"></i> 
                                    <?= htmlspecialchars($warehouse['name']) ?>
                                </h5>
                                <span class="warehouse-status <?= $status_colors[$warehouse['status']]['bg'] ?> <?= $status_colors[$warehouse['status']]['text'] ?>">
                                    <?= $status_labels[$warehouse['status']] ?>
                                </span>
                            </div>
                            
                            <div class="warehouse-info">
                                <div class="info-item">
                                    <span class="info-label">Encargado</span>
                                    <span class="info-value"><?= htmlspecialchars($warehouse['manager']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Teléfono</span>
                                    <span class="info-value"><?= htmlspecialchars($warehouse['phone']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email</span>
                                    <span class="info-value"><?= htmlspecialchars($warehouse['email']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Ubicación</span>
                                    <span class="info-value"><?= htmlspecialchars($warehouse['location']) ?></span>
                                </div>
                            </div>
                            
                            <div class="warehouse-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?= $warehouse['total_products'] ?></span>
                                    <span class="stat-label">Productos</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">$<?= number_format($warehouse['total_value'], 0, ',', '.') ?></span>
                                    <span class="stat-label">Valor</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?= $warehouse['used_capacity'] ?>/<?= $warehouse['capacity'] ?></span>
                                    <span class="stat-label">Capacidad</span>
                                </div>
                            </div>
                            
                            <!-- Barra de capacidad -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">Capacidad utilizada</small>
                                    <small class="text-muted"><?= round(($warehouse['used_capacity'] / $warehouse['capacity']) * 100) ?>%</small>
                                </div>
                                <div class="capacity-bar">
                                    <div class="capacity-fill <?= ($warehouse['used_capacity'] / $warehouse['capacity']) > 0.8 ? 'danger' : (($warehouse['used_capacity'] / $warehouse['capacity']) > 0.6 ? 'warning' : '') ?>" 
                                         style="width: <?= min(($warehouse['used_capacity'] / $warehouse['capacity']) * 100, 100) ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="warehouse-actions">
                                <a href="view.php?id=<?= $warehouse['warehouse_id'] ?>" class="btn-action btn-view">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <a href="edit.php?id=<?= $warehouse['warehouse_id'] ?>" class="btn-action btn-edit">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <a href="inventory.php?warehouse_id=<?= $warehouse['warehouse_id'] ?>" class="btn-action btn-inventory">
                                    <i class="bi bi-box-seam"></i> Inventario
                                </a>
                                <a href="audit.php?warehouse_id=<?= $warehouse['warehouse_id'] ?>" class="btn-action btn-audit">
                                    <i class="bi bi-clipboard-check"></i> Auditoría
                                </a>
                                <button type="button" class="btn-action btn-delete" 
                                        onclick="eliminarAlmacen(<?= $warehouse['warehouse_id'] ?>, '<?= htmlspecialchars($warehouse['name']) ?>')">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-building" style="font-size: 4rem; color: #ccc;"></i>
                <h5 class="mt-3">No hay almacenes registrados</h5>
                <p class="text-muted">Crea tu primer almacén para comenzar a gestionar múltiples ubicaciones.</p>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="../dashboard/index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </main>

    <!-- Modal para confirmar eliminación -->
    <div class="modal fade" id="modalEliminarAlmacen" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEliminarAlmacen">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="warehouse_id" id="delete_warehouse_id">
                        <p>¿Estás seguro de que quieres eliminar el almacén <strong id="delete_warehouse_name"></strong>?</p>
                        <p class="text-muted mb-0">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para eliminar almacén
        function eliminarAlmacen(id, nombre) {
            document.getElementById('delete_warehouse_id').value = id;
            document.getElementById('delete_warehouse_name').textContent = nombre;
            
            const modal = new bootstrap.Modal(document.getElementById('modalEliminarAlmacen'));
            modal.show();
        }

        // Auto-focus en el campo de nombre al cargar la página
        document.getElementById('name').focus();
    </script>
</body>
</html> 