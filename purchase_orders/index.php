<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Simular datos de órdenes de compra para el maquetado
$purchase_orders = [
    [
        'order_id' => 1,
        'order_number' => 'PO-2025-001',
        'supplier_name' => 'Syscom',
        'order_date' => '2025-01-15',
        'delivery_date' => '2025-01-25',
        'status' => 'pending',
        'total_amount' => 45000,
        'items_count' => 5,
        'contact_person' => 'Juan Pérez',
        'notes' => 'Entrega urgente para proyecto de seguridad'
    ],
    [
        'order_id' => 2,
        'order_number' => 'PO-2025-002',
        'supplier_name' => 'PCH',
        'order_date' => '2025-01-12',
        'delivery_date' => '2025-01-22',
        'status' => 'approved',
        'total_amount' => 32000,
        'items_count' => 3,
        'contact_person' => 'María González',
        'notes' => 'Productos para mantenimiento'
    ],
    [
        'order_id' => 3,
        'order_number' => 'PO-2025-003',
        'supplier_name' => 'Dahua Technology',
        'order_date' => '2025-01-10',
        'delivery_date' => '2025-01-20',
        'status' => 'delivered',
        'total_amount' => 78000,
        'items_count' => 8,
        'contact_person' => 'Carlos Rodríguez',
        'notes' => 'Cámaras de alta resolución'
    ],
    [
        'order_id' => 4,
        'order_number' => 'PO-2025-004',
        'supplier_name' => 'Syscom',
        'order_date' => '2025-01-08',
        'delivery_date' => '2025-01-18',
        'status' => 'cancelled',
        'total_amount' => 25000,
        'items_count' => 2,
        'contact_person' => 'Juan Pérez',
        'notes' => 'Cancelado por cambio de especificaciones'
    ]
];

$status_colors = [
    'pending' => ['bg' => 'bg-warning', 'text' => 'text-dark'],
    'approved' => ['bg' => 'bg-info', 'text' => 'text-dark'],
    'delivered' => ['bg' => 'bg-success', 'text' => 'text-white'],
    'cancelled' => ['bg' => 'bg-danger', 'text' => 'text-white']
];

$status_labels = [
    'pending' => 'Pendiente',
    'approved' => 'Aprobada',
    'delivered' => 'Entregada',
    'cancelled' => 'Cancelada'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Órdenes de Compra | Gestor de inventarios</title>
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
        .order-card {
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
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #121866, #232a7c);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .order-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .order-info {
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
        .order-stats {
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
        .order-actions {
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
        .btn-approve {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .btn-approve:hover {
            background: #2e7d32;
            color: #fff;
        }
        .btn-cancel {
            background: #ffebee;
            color: #c62828;
        }
        .btn-cancel:hover {
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
        .filters-bar {
            background: #fff;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .titulo-lista { font-size: 1.4rem; }
            .order-stats { grid-template-columns: repeat(2, 1fr); }
            .order-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="titulo-lista">
            <i class="bi bi-cart-check"></i> 
            Órdenes de Compra
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

        <!-- Filtros -->
        <div class="filters-bar">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <label for="filterStatus" class="form-label">Estado</label>
                    <select class="form-select" id="filterStatus">
                        <option value="">Todos los estados</option>
                        <option value="pending">Pendiente</option>
                        <option value="approved">Aprobada</option>
                        <option value="delivered">Entregada</option>
                        <option value="cancelled">Cancelada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterSupplier" class="form-label">Proveedor</label>
                    <select class="form-select" id="filterSupplier">
                        <option value="">Todos los proveedores</option>
                        <option value="Syscom">Syscom</option>
                        <option value="PCH">PCH</option>
                        <option value="Dahua Technology">Dahua Technology</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterDate" class="form-label">Fecha</label>
                    <input type="date" class="form-control" id="filterDate">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button class="btn btn-primary" onclick="filtrarOrdenes()">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario para nueva orden -->
        <div class="form-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nueva Orden de Compra</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formNuevaOrden">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">Proveedor</label>
                                <select class="form-select" name="supplier_id" id="supplier_id" required>
                                    <option value="">Selecciona un proveedor</option>
                                    <option value="1">Syscom</option>
                                    <option value="2">PCH</option>
                                    <option value="3">Dahua Technology</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="delivery_date" class="form-label">Fecha de entrega esperada</label>
                                <input type="date" class="form-control" name="delivery_date" id="delivery_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas</label>
                        <textarea class="form-control" name="notes" id="notes" rows="2" 
                                  placeholder="Notas adicionales sobre la orden"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Crear Orden
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de órdenes -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0" style="color: #121866; font-weight: 600;">
                <i class="bi bi-list-ul"></i> Órdenes de Compra
            </h4>
            <small class="text-muted">
                <?= count($purchase_orders) ?> órdenes
            </small>
        </div>

        <?php if (!empty($purchase_orders)): ?>
            <div class="row">
                <?php foreach ($purchase_orders as $order): ?>
                    <div class="col-lg-6 col-xl-4 mb-3">
                        <div class="order-card">
                            <div class="order-header">
                                <h5 class="order-title">
                                    <i class="bi bi-file-earmark-text"></i> 
                                    <?= htmlspecialchars($order['order_number']) ?>
                                </h5>
                                <span class="order-status <?= $status_colors[$order['status']]['bg'] ?> <?= $status_colors[$order['status']]['text'] ?>">
                                    <?= $status_labels[$order['status']] ?>
                                </span>
                            </div>
                            
                            <div class="order-info">
                                <div class="info-item">
                                    <span class="info-label">Proveedor</span>
                                    <span class="info-value"><?= htmlspecialchars($order['supplier_name']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Contacto</span>
                                    <span class="info-value"><?= htmlspecialchars($order['contact_person']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Fecha de Orden</span>
                                    <span class="info-value"><?= date('d/m/Y', strtotime($order['order_date'])) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Entrega Esperada</span>
                                    <span class="info-value"><?= date('d/m/Y', strtotime($order['delivery_date'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="order-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?= $order['items_count'] ?></span>
                                    <span class="stat-label">Artículos</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">$<?= number_format($order['total_amount'], 0, ',', '.') ?></span>
                                    <span class="stat-label">Total</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">
                                        <?php
                                        $days_diff = (strtotime($order['delivery_date']) - time()) / (60 * 60 * 24);
                                        echo $days_diff > 0 ? round($days_diff) : 0;
                                        ?>
                                    </span>
                                    <span class="stat-label">Días Restantes</span>
                                </div>
                            </div>
                            
                            <?php if (!empty($order['notes'])): ?>
                                <div class="alert alert-info" style="font-size: 0.9rem; margin-bottom: 16px;">
                                    <i class="bi bi-info-circle"></i> <?= htmlspecialchars($order['notes']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="order-actions">
                                <a href="view.php?id=<?= $order['order_id'] ?>" class="btn-action btn-view">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <a href="edit.php?id=<?= $order['order_id'] ?>" class="btn-action btn-edit">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <button type="button" class="btn-action btn-approve" 
                                            onclick="aprobarOrden(<?= $order['order_id'] ?>)">
                                        <i class="bi bi-check-circle"></i> Aprobar
                                    </button>
                                    <button type="button" class="btn-action btn-cancel" 
                                            onclick="cancelarOrden(<?= $order['order_id'] ?>)">
                                        <i class="bi bi-x-circle"></i> Cancelar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-cart-check" style="font-size: 4rem; color: #ccc;"></i>
                <h5 class="mt-3">No hay órdenes de compra</h5>
                <p class="text-muted">Crea tu primera orden de compra para gestionar tus adquisiciones.</p>
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
        // Función para filtrar órdenes
        function filtrarOrdenes() {
            const status = document.getElementById('filterStatus').value;
            const supplier = document.getElementById('filterSupplier').value;
            const date = document.getElementById('filterDate').value;
            
            // Aquí iría la lógica de filtrado
            console.log('Filtros aplicados:', { status, supplier, date });
        }

        // Función para aprobar orden
        function aprobarOrden(orderId) {
            if (confirm('¿Estás seguro de que quieres aprobar esta orden de compra?')) {
                // Aquí iría la lógica para aprobar la orden
                console.log('Aprobando orden:', orderId);
            }
        }

        // Función para cancelar orden
        function cancelarOrden(orderId) {
            if (confirm('¿Estás seguro de que quieres cancelar esta orden de compra?')) {
                // Aquí iría la lógica para cancelar la orden
                console.log('Cancelando orden:', orderId);
            }
        }

        // Auto-focus en el campo de proveedor al cargar la página
        document.getElementById('supplier_id').focus();
    </script>
</body>
</html> 