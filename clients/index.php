<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Simular datos de clientes para el maquetado
$clients = [
    [
        'client_id' => 1,
        'name' => 'Empresa ABC S.A. de C.V.',
        'contact_person' => 'Juan Carlos Martínez',
        'email' => 'compras@empresaabc.com',
        'phone' => '55-1234-5678',
        'address' => 'Av. Insurgentes Sur 123, Col. Del Valle, CDMX',
        'status' => 'active',
        'total_orders' => 15,
        'total_spent' => 125000,
        'last_order' => '2025-01-15',
        'credit_limit' => 50000,
        'current_balance' => 15000
    ],
    [
        'client_id' => 2,
        'name' => 'Seguridad Integral del Norte',
        'contact_person' => 'María González',
        'email' => 'admin@seguridadnorte.com',
        'phone' => '81-9876-5432',
        'address' => 'Blvd. Constitución 456, Monterrey, NL',
        'status' => 'active',
        'total_orders' => 8,
        'total_spent' => 89000,
        'last_order' => '2025-01-10',
        'credit_limit' => 75000,
        'current_balance' => 0
    ],
    [
        'client_id' => 3,
        'name' => 'Alarmas Express',
        'contact_person' => 'Carlos Rodríguez',
        'email' => 'ventas@alarmasexpress.com',
        'phone' => '33-5555-7777',
        'address' => 'Av. Vallarta 789, Guadalajara, Jal',
        'status' => 'inactive',
        'total_orders' => 3,
        'total_spent' => 45000,
        'last_order' => '2024-12-20',
        'credit_limit' => 25000,
        'current_balance' => 5000
    ]
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Clientes | Gestor de inventarios</title>
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
        .client-card {
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
        .client-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .client-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #121866, #232a7c);
        }
        .client-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .client-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .client-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-active {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .status-inactive {
            background: #ffebee;
            color: #c62828;
        }
        .client-info {
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
        .client-stats {
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
        .credit-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }
        .credit-info.warning {
            background: #f8d7da;
            border-color: #f5c6cb;
        }
        .client-actions {
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
        .btn-orders {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .btn-orders:hover {
            background: #2e7d32;
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
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .titulo-lista { font-size: 1.4rem; }
            .client-stats { grid-template-columns: repeat(2, 1fr); }
            .client-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="titulo-lista">
            <i class="bi bi-people"></i> 
            Gestión de Clientes
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

        <!-- Formulario para nuevo cliente -->
        <div class="form-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nuevo Cliente</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formNuevoCliente">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre de la empresa</label>
                                <input type="text" class="form-control" name="name" id="name" required 
                                       placeholder="Ej: Empresa ABC S.A. de C.V.">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact_person" class="form-label">Persona de contacto</label>
                                <input type="text" class="form-control" name="contact_person" id="contact_person" 
                                       placeholder="Nombre del contacto principal">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email" 
                                       placeholder="email@empresa.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" name="phone" id="phone" 
                                       placeholder="55-1234-5678">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Dirección</label>
                        <textarea class="form-control" name="address" id="address" rows="2" 
                                  placeholder="Dirección completa"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="credit_limit" class="form-label">Límite de crédito</label>
                                <input type="number" class="form-control" name="credit_limit" id="credit_limit" 
                                       placeholder="0.00" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Estado</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="active">Activo</option>
                                    <option value="inactive">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Agregar Cliente
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de clientes -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0" style="color: #121866; font-weight: 600;">
                <i class="bi bi-list-ul"></i> Clientes Registrados
            </h4>
            <small class="text-muted">
                <?= count($clients) ?> clientes
            </small>
        </div>

        <?php if (!empty($clients)): ?>
            <div class="row">
                <?php foreach ($clients as $client): ?>
                    <div class="col-lg-6 col-xl-4 mb-3">
                        <div class="client-card">
                            <div class="client-header">
                                <h5 class="client-title">
                                    <i class="bi bi-building"></i> 
                                    <?= htmlspecialchars($client['name']) ?>
                                </h5>
                                <span class="client-status status-<?= $client['status'] ?>">
                                    <?= ucfirst($client['status']) ?>
                                </span>
                            </div>
                            
                            <div class="client-info">
                                <div class="info-item">
                                    <span class="info-label">Contacto</span>
                                    <span class="info-value"><?= htmlspecialchars($client['contact_person']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email</span>
                                    <span class="info-value"><?= htmlspecialchars($client['email']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Teléfono</span>
                                    <span class="info-value"><?= htmlspecialchars($client['phone']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Dirección</span>
                                    <span class="info-value"><?= htmlspecialchars($client['address']) ?></span>
                                </div>
                            </div>
                            
                            <div class="client-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?= $client['total_orders'] ?></span>
                                    <span class="stat-label">Órdenes</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">$<?= number_format($client['total_spent'], 0, ',', '.') ?></span>
                                    <span class="stat-label">Total Gastado</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?= date('d/m/Y', strtotime($client['last_order'])) ?></span>
                                    <span class="stat-label">Última Orden</span>
                                </div>
                            </div>
                            
                            <!-- Información de crédito -->
                            <div class="credit-info <?= $client['current_balance'] > ($client['credit_limit'] * 0.8) ? 'warning' : '' ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><strong>Límite de Crédito:</strong> $<?= number_format($client['credit_limit'], 0, ',', '.') ?></span>
                                    <span><strong>Saldo Actual:</strong> $<?= number_format($client['current_balance'], 0, ',', '.') ?></span>
                                </div>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar <?= $client['current_balance'] > ($client['credit_limit'] * 0.8) ? 'bg-danger' : 'bg-success' ?>" 
                                         style="width: <?= min(($client['current_balance'] / $client['credit_limit']) * 100, 100) ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="client-actions">
                                <a href="view.php?id=<?= $client['client_id'] ?>" class="btn-action btn-view">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <a href="edit.php?id=<?= $client['client_id'] ?>" class="btn-action btn-edit">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <a href="orders.php?client_id=<?= $client['client_id'] ?>" class="btn-action btn-orders">
                                    <i class="bi bi-cart"></i> Órdenes
                                </a>
                                <button type="button" class="btn-action btn-delete" 
                                        onclick="eliminarCliente(<?= $client['client_id'] ?>, '<?= htmlspecialchars($client['name']) ?>')">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-people" style="font-size: 4rem; color: #ccc;"></i>
                <h5 class="mt-3">No hay clientes registrados</h5>
                <p class="text-muted">Agrega tu primer cliente para comenzar a gestionar tus relaciones comerciales.</p>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="../dashboard/index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </main>

    <!-- Modal para confirmar eliminación -->
    <div class="modal fade" id="modalEliminarCliente" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEliminarCliente">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="client_id" id="delete_client_id">
                        <p>¿Estás seguro de que quieres eliminar al cliente <strong id="delete_client_name"></strong>?</p>
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
        // Función para eliminar cliente
        function eliminarCliente(id, nombre) {
            document.getElementById('delete_client_id').value = id;
            document.getElementById('delete_client_name').textContent = nombre;
            
            const modal = new bootstrap.Modal(document.getElementById('modalEliminarCliente'));
            modal.show();
        }

        // Auto-focus en el campo de nombre al cargar la página
        document.getElementById('name').focus();
    </script>
</body>
</html> 