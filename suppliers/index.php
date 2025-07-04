<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Simular datos de proveedores para el maquetado
$suppliers = [
    [
        'supplier_id' => 1,
        'name' => 'Syscom',
        'contact_person' => 'Juan Pérez',
        'email' => 'ventas@syscom.com.mx',
        'phone' => '55-1234-5678',
        'address' => 'Av. Insurgentes Sur 123, CDMX',
        'status' => 'active',
        'total_products' => 45,
        'last_order' => '2025-01-15',
        'rating' => 4.8
    ],
    [
        'supplier_id' => 2,
        'name' => 'PCH',
        'contact_person' => 'María González',
        'email' => 'compras@pch.com',
        'phone' => '81-9876-5432',
        'address' => 'Blvd. Constitución 456, Monterrey',
        'status' => 'active',
        'total_products' => 32,
        'last_order' => '2025-01-10',
        'rating' => 4.5
    ],
    [
        'supplier_id' => 3,
        'name' => 'Dahua Technology',
        'contact_person' => 'Carlos Rodríguez',
        'email' => 'sales@dahua.com',
        'phone' => '33-5555-7777',
        'address' => 'Av. Vallarta 789, Guadalajara',
        'status' => 'inactive',
        'total_products' => 28,
        'last_order' => '2024-12-20',
        'rating' => 4.2
    ]
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Proveedores | Gestor de inventarios</title>
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
        .supplier-card {
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
        .supplier-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .supplier-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #121866, #232a7c);
        }
        .supplier-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .supplier-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .supplier-status {
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
        .supplier-info {
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
        .supplier-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 16px;
            margin-top: 16px;
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
        .supplier-actions {
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
        .btn-edit {
            background: #e3f2fd;
            color: #1565c0;
        }
        .btn-edit:hover {
            background: #1565c0;
            color: #fff;
        }
        .btn-view {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .btn-view:hover {
            background: #7b1fa2;
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
        .rating-stars {
            color: #ffc107;
            font-size: 0.9rem;
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
            .supplier-stats { grid-template-columns: repeat(2, 1fr); }
            .supplier-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="titulo-lista">
            <i class="bi bi-truck"></i> 
            Gestión de Proveedores
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

        <!-- Formulario para nuevo proveedor -->
        <div class="form-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nuevo Proveedor</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formNuevoProveedor">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre del proveedor</label>
                                <input type="text" class="form-control" name="name" id="name" required 
                                       placeholder="Ej: Syscom, PCH, etc.">
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
                                       placeholder="email@proveedor.com">
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
                                  placeholder="Dirección completa del proveedor"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Agregar Proveedor
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de proveedores -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0" style="color: #121866; font-weight: 600;">
                <i class="bi bi-list-ul"></i> Proveedores Registrados
            </h4>
            <small class="text-muted">
                <?= count($suppliers) ?> proveedores
            </small>
        </div>

        <?php if (!empty($suppliers)): ?>
            <div class="row">
                <?php foreach ($suppliers as $supplier): ?>
                    <div class="col-lg-6 col-xl-4 mb-3">
                        <div class="supplier-card">
                            <div class="supplier-header">
                                <h5 class="supplier-title">
                                    <i class="bi bi-building"></i> 
                                    <?= htmlspecialchars($supplier['name']) ?>
                                </h5>
                                <span class="supplier-status status-<?= $supplier['status'] ?>">
                                    <?= ucfirst($supplier['status']) ?>
                                </span>
                            </div>
                            
                            <div class="supplier-info">
                                <div class="info-item">
                                    <span class="info-label">Contacto</span>
                                    <span class="info-value"><?= htmlspecialchars($supplier['contact_person']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email</span>
                                    <span class="info-value"><?= htmlspecialchars($supplier['email']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Teléfono</span>
                                    <span class="info-value"><?= htmlspecialchars($supplier['phone']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Dirección</span>
                                    <span class="info-value"><?= htmlspecialchars($supplier['address']) ?></span>
                                </div>
                            </div>
                            
                            <div class="supplier-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?= $supplier['total_products'] ?></span>
                                    <span class="stat-label">Productos</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?= $supplier['rating'] ?></span>
                                    <span class="stat-label">
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?= $i <= $supplier['rating'] ? '-fill' : '' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?= date('d/m/Y', strtotime($supplier['last_order'])) ?></span>
                                    <span class="stat-label">Última orden</span>
                                </div>
                            </div>
                            
                            <div class="supplier-actions">
                                <a href="view.php?id=<?= $supplier['supplier_id'] ?>" class="btn-action btn-view">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <a href="edit.php?id=<?= $supplier['supplier_id'] ?>" class="btn-action btn-edit">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <button type="button" class="btn-action btn-delete" 
                                        onclick="eliminarProveedor(<?= $supplier['supplier_id'] ?>, '<?= htmlspecialchars($supplier['name']) ?>')">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-truck" style="font-size: 4rem; color: #ccc;"></i>
                <h5 class="mt-3">No hay proveedores registrados</h5>
                <p class="text-muted">Agrega tu primer proveedor para comenzar a gestionar tus relaciones comerciales.</p>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="../dashboard/index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </main>

    <!-- Modal para confirmar eliminación -->
    <div class="modal fade" id="modalEliminarProveedor" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEliminarProveedor">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="supplier_id" id="delete_supplier_id">
                        <p>¿Estás seguro de que quieres eliminar al proveedor <strong id="delete_supplier_name"></strong>?</p>
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
        // Función para eliminar proveedor
        function eliminarProveedor(id, nombre) {
            document.getElementById('delete_supplier_id').value = id;
            document.getElementById('delete_supplier_name').textContent = nombre;
            
            const modal = new bootstrap.Modal(document.getElementById('modalEliminarProveedor'));
            modal.show();
        }

        // Auto-focus en el campo de nombre al cargar la página
        document.getElementById('name').focus();
    </script>
</body>
</html> 