<?php
require_once '../auth/middleware.php';

// Datos simulados de productos
$productos = [
    [
        'id' => 1,
        'nombre' => 'Cámara Bullet 5MP',
        'sku' => 'CAM-001',
        'categoria' => 'Cámaras',
        'stock' => 15,
        'precio' => 1250.00,
        'proveedor' => 'Dahua',
        'imagen' => '../assets/img/camera.png',
        'descripcion' => 'Cámara de seguridad exterior 5MP con visión nocturna',
        'estado' => 'disponible'
    ],
    [
        'id' => 2,
        'nombre' => 'Cable UTP Cat6',
        'sku' => 'CAB-002',
        'categoria' => 'Cables',
        'stock' => 500,
        'precio' => 8.50,
        'proveedor' => 'Syscom',
        'imagen' => '../assets/img/cable.png',
        'descripcion' => 'Cable de red UTP Cat6 305m',
        'estado' => 'disponible'
    ],
    [
        'id' => 3,
        'nombre' => 'DVR 8 Canales',
        'sku' => 'DVR-003',
        'categoria' => 'Grabadores',
        'stock' => 8,
        'precio' => 2800.00,
        'proveedor' => 'Hikvision',
        'imagen' => '../assets/img/dvr.png',
        'descripcion' => 'Grabador digital de video 8 canales',
        'estado' => 'disponible'
    ],
    [
        'id' => 4,
        'nombre' => 'Fuente de Poder 12V',
        'sku' => 'FUENTE-004',
        'categoria' => 'Accesorios',
        'stock' => 25,
        'precio' => 150.00,
        'proveedor' => 'Genérica',
        'imagen' => '../assets/img/power.png',
        'descripcion' => 'Fuente de alimentación 12V 2A',
        'estado' => 'disponible'
    ],
    [
        'id' => 5,
        'nombre' => 'Conector RJ45',
        'sku' => 'CONN-005',
        'categoria' => 'Conectores',
        'stock' => 200,
        'precio' => 2.50,
        'proveedor' => 'Syscom',
        'imagen' => '../assets/img/connector.png',
        'descripcion' => 'Conector RJ45 macho para cable UTP',
        'estado' => 'disponible'
    ],
    [
        'id' => 6,
        'nombre' => 'Alarma Residencial',
        'sku' => 'ALARM-006',
        'categoria' => 'Alarmas',
        'stock' => 3,
        'precio' => 3500.00,
        'proveedor' => 'Bosch',
        'imagen' => '../assets/img/alarm.png',
        'descripcion' => 'Sistema de alarma residencial 8 zonas',
        'estado' => 'bajo_stock'
    ],
    [
        'id' => 7,
        'nombre' => 'Switch POE 8 Puertos',
        'sku' => 'SW-007',
        'categoria' => 'Redes',
        'stock' => 12,
        'precio' => 1800.00,
        'proveedor' => 'TP-Link',
        'imagen' => '../assets/img/switch.png',
        'descripcion' => 'Switch POE 8 puertos para cámaras IP',
        'estado' => 'disponible'
    ],
    [
        'id' => 8,
        'nombre' => 'Sensor de Movimiento',
        'sku' => 'SENS-008',
        'categoria' => 'Sensores',
        'stock' => 0,
        'precio' => 180.00,
        'proveedor' => 'Honeywell',
        'imagen' => '../assets/img/sensor.png',
        'descripcion' => 'Sensor de movimiento PIR para alarmas',
        'estado' => 'agotado'
    ]
];

// Filtros
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

// Aplicar filtros
if ($categoria_filtro) {
    $productos = array_filter($productos, function($p) use ($categoria_filtro) {
        return $p['categoria'] === $categoria_filtro;
    });
}

if ($busqueda) {
    $productos = array_filter($productos, function($p) use ($busqueda) {
        return stripos($p['nombre'], $busqueda) !== false || 
               stripos($p['sku'], $busqueda) !== false ||
               stripos($p['descripcion'], $busqueda) !== false;
    });
}

// Obtener categorías únicas
$categorias = array_unique(array_column($productos, 'categoria'));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario | Gestor de inventarios</title>
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
        .inventory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
        }
        .inventory-title {
            font-size: 2.2rem;
            color: #121866;
            font-weight: 800;
            margin: 0;
        }
        .inventory-stats {
            display: flex;
            gap: 24px;
        }
        .stat-item {
            text-align: center;
            padding: 16px 20px;
            background: linear-gradient(135deg, #121866, #232a7c);
            color: #fff;
            border-radius: 12px;
            min-width: 120px;
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            display: block;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .filters-section {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        .product-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #121866, #232a7c);
        }
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .product-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
            line-height: 1.3;
        }
        .product-sku {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }
        .product-category {
            display: inline-block;
            padding: 4px 12px;
            background: #e3f2fd;
            color: #1565c0;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .product-description {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 16px;
        }
        .product-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .detail-item {
            text-align: center;
            padding: 8px;
            background: #f7f9fc;
            border-radius: 8px;
            border: 1px solid #e3e6f0;
        }
        .detail-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
        }
        .detail-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #121866;
        }
        .stock-status {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-disponible {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .status-bajo_stock {
            background: #fff3e0;
            color: #f57c00;
        }
        .status-agotado {
            background: #ffebee;
            color: #c62828;
        }
        .product-actions {
            display: flex;
            gap: 8px;
        }
        .btn-action {
            flex: 1;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        .btn-edit {
            background: #e3f2fd;
            color: #1565c0;
        }
        .btn-edit:hover {
            background: #1565c0;
            color: #fff;
        }
        .btn-movement {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .btn-movement:hover {
            background: #7b1fa2;
            color: #fff;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .inventory-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            .inventory-stats {
                flex-wrap: wrap;
                justify-content: center;
            }
            .product-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="inventory-header">
            <div>
                <h1 class="inventory-title">
                    <i class="bi bi-boxes"></i> Inventario
                </h1>
                <p class="text-muted mb-0">Gestión visual de productos en almacén</p>
            </div>
            <div class="inventory-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= count($productos) ?></span>
                    <span class="stat-label">Productos</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= count(array_filter($productos, function($p) { return $p['estado'] === 'disponible'; })) ?></span>
                    <span class="stat-label">Disponibles</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= count(array_filter($productos, function($p) { return $p['estado'] === 'bajo_stock'; })) ?></span>
                    <span class="stat-label">Bajo Stock</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= count(array_filter($productos, function($p) { return $p['estado'] === 'agotado'; })) ?></span>
                    <span class="stat-label">Agotados</span>
                </div>
            </div>
        </div>

        <div class="filters-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="busqueda" class="form-label">Buscar producto</label>
                    <input type="text" class="form-control" id="busqueda" name="busqueda" 
                           value="<?= htmlspecialchars($busqueda) ?>" 
                           placeholder="Nombre, SKU, descripción...">
                </div>
                <div class="col-md-3">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" 
                                    <?= $categoria_filtro === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <a href="add.php" class="btn btn-success w-100">
                        <i class="bi bi-plus-circle"></i> Agregar
                    </a>
                </div>
            </form>
        </div>

        <?php if (empty($productos)): ?>
            <div class="empty-state">
                <i class="bi bi-boxes"></i>
                <h5>No se encontraron productos</h5>
                <p>Intenta ajustar los filtros de búsqueda.</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($productos as $producto): ?>
                    <div class="product-card">
                        <div class="product-header">
                            <div>
                                <h5 class="product-title"><?= htmlspecialchars($producto['nombre']) ?></h5>
                                <div class="product-sku">SKU: <?= htmlspecialchars($producto['sku']) ?></div>
                            </div>
                        </div>
                        
                        <div class="product-category"><?= htmlspecialchars($producto['categoria']) ?></div>
                        
                        <div class="product-description">
                            <?= htmlspecialchars($producto['descripcion']) ?>
                        </div>
                        
                        <div class="stock-status">
                            <span class="status-badge status-<?= $producto['estado'] ?>">
                                <?php
                                switch($producto['estado']) {
                                    case 'disponible': echo '<i class="bi bi-check-circle"></i> Disponible'; break;
                                    case 'bajo_stock': echo '<i class="bi bi-exclamation-triangle"></i> Bajo Stock'; break;
                                    case 'agotado': echo '<i class="bi bi-x-circle"></i> Agotado'; break;
                                }
                                ?>
                            </span>
                        </div>
                        
                        <div class="product-details">
                            <div class="detail-item">
                                <div class="detail-label">Stock</div>
                                <div class="detail-value"><?= $producto['stock'] ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Precio</div>
                                <div class="detail-value">$<?= number_format($producto['precio'], 2) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Proveedor</div>
                                <div class="detail-value"><?= htmlspecialchars($producto['proveedor']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Valor Total</div>
                                <div class="detail-value">$<?= number_format($producto['stock'] * $producto['precio'], 2) ?></div>
                            </div>
                        </div>
                        
                        <div class="product-actions">
                            <button class="btn-action btn-edit" onclick="editarProducto(<?= $producto['id'] ?>)">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                            <button class="btn-action btn-movement" onclick="registrarMovimiento(<?= $producto['id'] ?>)">
                                <i class="bi bi-arrow-left-right"></i> Movimiento
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Resalta el menú activo
        document.querySelector('.sidebar-inventario').classList.add('active');
        
        function editarProducto(id) {
            alert('Función de editar producto ' + id + ' (maquetado)');
        }
        
        function registrarMovimiento(id) {
            alert('Función de registrar movimiento para producto ' + id + ' (maquetado)');
        }
    </script>
</body>
</html> 