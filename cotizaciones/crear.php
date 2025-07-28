<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

// --- Preparar datos para selects ---
$clientes = $mysqli->query("SELECT cliente_id, nombre, telefono, ubicacion, email FROM clientes ORDER BY nombre ASC");
$clientes_array = $clientes ? $clientes->fetch_all(MYSQLI_ASSOC) : [];
$productos = $mysqli->query("
    SELECT 
        p.product_id, 
        p.product_name, 
        p.sku, 
        p.price, 
        p.tipo_gestion,
        c.name as categoria, 
        s.name as proveedor,
        CASE 
            WHEN p.tipo_gestion = 'bobina' THEN 
                COALESCE(SUM(b.metros_actuales), 0)
            ELSE 
                p.quantity
        END as stock_disponible
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
    LEFT JOIN bobinas b ON p.product_id = b.product_id AND b.is_active = 1
    GROUP BY p.product_id, p.product_name, p.sku, p.price, p.tipo_gestion, c.name, s.name, p.quantity
    ORDER BY p.product_name ASC
");
$productos_array = $productos ? $productos->fetch_all(MYSQLI_ASSOC) : [];
$categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name ASC");
$proveedores = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name ASC");

// Obtener servicios disponibles
$servicios = $mysqli->query("SELECT servicio_id, nombre, descripcion, categoria, precio, imagen FROM servicios WHERE is_active = 1 ORDER BY categoria, nombre ASC");
$servicios_array = $servicios ? $servicios->fetch_all(MYSQLI_ASSOC) : [];

// Obtener insumos disponibles
$insumos = $mysqli->query("SELECT i.insumo_id, i.nombre, i.categoria, s.name as proveedor, i.cantidad as stock, i.precio_unitario as precio
    FROM insumos i
    LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
    WHERE i.is_active = 1
    ORDER BY i.categoria, i.nombre ASC");
$insumos_array = $insumos ? $insumos->fetch_all(MYSQLI_ASSOC) : [];

// Verificar si existen estados de cotización, si no, crearlos
$estados = $mysqli->query("SELECT est_cot_id, nombre_estado FROM est_cotizacion ORDER BY est_cot_id ASC");
if ($estados && $estados->num_rows == 0) {
    // Crear estados básicos si no existen
    $estados_basicos = [
        ['nombre_estado' => 'Borrador'],
        ['nombre_estado' => 'Enviada'],
        ['nombre_estado' => 'Aprobada'],
        ['nombre_estado' => 'Rechazada'],
        ['nombre_estado' => 'Convertida']
    ];
    
    foreach ($estados_basicos as $estado) {
        $stmt = $mysqli->prepare("INSERT INTO est_cotizacion (nombre_estado) VALUES (?)");
        $stmt->bind_param('s', $estado['nombre_estado']);
        $stmt->execute();
        $stmt->close();
    }
    
    // Recargar estados
    $estados = $mysqli->query("SELECT est_cot_id, nombre_estado FROM est_cotizacion ORDER BY est_cot_id ASC");
}

$estados_array = $estados ? $estados->fetch_all(MYSQLI_ASSOC) : [];

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productos_json = $_POST['productos_json'] ?? '';
    $servicios_json = $_POST['servicios_json'] ?? '';
    $productos = json_decode($productos_json, true);
    $servicios = json_decode($servicios_json, true);
    $cliente_id = $_POST['cliente_id'] ?? '';
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $cliente_telefono = trim($_POST['cliente_telefono'] ?? '');
    $cliente_ubicacion = trim($_POST['cliente_ubicacion'] ?? '');
    $cliente_email = trim($_POST['cliente_email'] ?? '');
    if ((!$productos || !is_array($productos) || count($productos) == 0) && (!$servicios || !is_array($servicios) || count($servicios) == 0)) {
        $error = 'Debes agregar al menos un producto o servicio a la cotización.';
    }
    if (!$cliente_id && !$cliente_nombre) {
        $error = 'Debes seleccionar o registrar un cliente.';
    }
    if (!$error) {
        // Cliente: alta si es nuevo
        if (!$cliente_id) {
            // Solo buscar cliente existente si se proporcionan datos completos
            $cliente_id = null;
            if ($cliente_nombre && ($cliente_telefono || $cliente_email)) {
                // Buscar coincidencia exacta (nombre Y teléfono/email)
                $stmt = $mysqli->prepare("SELECT cliente_id FROM clientes WHERE nombre = ? AND (telefono = ? OR email = ?) LIMIT 1");
                $stmt->bind_param('sss', $cliente_nombre, $cliente_telefono, $cliente_email);
                $stmt->execute();
                $stmt->bind_result($cliente_id_encontrado);
                if ($stmt->fetch()) {
                    $cliente_id = $cliente_id_encontrado;
                }
                $stmt->close();
            }
            
            // Si no se encontró cliente existente, crear uno nuevo
            if (!$cliente_id) {
                $stmt = $mysqli->prepare("INSERT INTO clientes (nombre, telefono, ubicacion, email) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('ssss', $cliente_nombre, $cliente_telefono, $cliente_ubicacion, $cliente_email);
                $stmt->execute();
                $cliente_id = $stmt->insert_id;
                $stmt->close();
            }
        }
        // Cotización
        $fecha_cotizacion = $_POST['fecha_cotizacion'];
        $validez_dias = intval($_POST['validez_dias']);
        $condiciones_pago = trim($_POST['condiciones_pago']);
        $observaciones = trim($_POST['observaciones']);
        $descuento_porcentaje = floatval($_POST['descuento_porcentaje']);
        $estado_id = intval($_POST['estado_id']);
        $usuario_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
        
        // Calcular totales
        $subtotal = 0;
        $total_productos = count($productos);
        $total_servicios = count($servicios);
        
        // Sumar productos
        foreach ($productos as $prod) {
            $subtotal += floatval($prod['precio']) * intval($prod['cantidad']);
        }
        
        // Sumar servicios
        foreach ($servicios as $serv) {
            $subtotal += floatval($serv['precio']) * floatval($serv['cantidad']);
        }
        
        $descuento_monto = $subtotal * $descuento_porcentaje / 100;
        $total = $subtotal - $descuento_monto;
        
        // Generar número de cotización automáticamente
        $year = date('Y');
        $stmt_count = $mysqli->prepare("SELECT COUNT(*) FROM cotizaciones WHERE numero_cotizacion LIKE ?");
        $pattern = "COT-$year-%";
        $stmt_count->bind_param('s', $pattern);
        $stmt_count->execute();
        $stmt_count->bind_result($count);
        $stmt_count->fetch();
        $stmt_count->close();
        
        $next_number = $count + 1;
        $numero_cotizacion = sprintf("COT-%s-%04d", $year, $next_number);
        
        $stmt = $mysqli->prepare("INSERT INTO cotizaciones (numero_cotizacion, cliente_id, fecha_cotizacion, validez_dias, subtotal, descuento_porcentaje, descuento_monto, total, condiciones_pago, observaciones, estado_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sisidddssiii', $numero_cotizacion, $cliente_id, $fecha_cotizacion, $validez_dias, $subtotal, $descuento_porcentaje, $descuento_monto, $total, $condiciones_pago, $observaciones, $estado_id, $usuario_id);
        if ($stmt->execute()) {
            $cotizacion_id = $stmt->insert_id;
            $stmt->close();
            
            // Registrar acción en el historial
            require_once 'helpers.php';
            inicializarAccionesCotizacion($mysqli);
            registrarAccionCotizacion(
                $cotizacion_id, 
                'Creada', 
                "Cotización creada con {$total_productos} productos por un total de $" . number_format($total, 2),
                $usuario_id,
                $mysqli
            );
            // Productos
            foreach ($productos as $prod) {
                $product_id = $prod['product_id'] ?? null;
                if (!$product_id) {
                    $stmt_prod = $mysqli->prepare("INSERT INTO products (product_name, sku, price, quantity, category_id, supplier_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $cat_id = $prod['category_id'] ?? null;
                    $prov_id = $prod['supplier_id'] ?? null;
                    
                    // Validar que category_id y supplier_id existan si no son null
                    if ($cat_id && $cat_id !== '') {
                        $check_cat = $mysqli->prepare("SELECT category_id FROM categories WHERE category_id = ?");
                        $check_cat->bind_param('i', $cat_id);
                        $check_cat->execute();
                        if (!$check_cat->get_result()->fetch_assoc()) {
                            $cat_id = null; // Si no existe, usar null
                        }
                        $check_cat->close();
                    } else {
                        $cat_id = null;
                    }
                    
                    if ($prov_id && $prov_id !== '') {
                        $check_prov = $mysqli->prepare("SELECT supplier_id FROM suppliers WHERE supplier_id = ?");
                        $check_prov->bind_param('i', $prov_id);
                        $check_prov->execute();
                        if (!$check_prov->get_result()->fetch_assoc()) {
                            $prov_id = null; // Si no existe, usar null
                        }
                        $check_prov->close();
                    } else {
                        $prov_id = null;
                    }
                    
                    // Debug: verificar valores antes de insertar
                    error_log("Insertando producto: nombre=" . $prod['nombre'] . ", sku=" . $prod['sku'] . ", precio=" . $prod['precio'] . ", cantidad=" . $prod['cantidad'] . ", cat_id=" . var_export($cat_id, true) . ", prov_id=" . var_export($prov_id, true));
                    
                    $stmt_prod->bind_param('ssdiis', $prod['nombre'], $prod['sku'], $prod['precio'], $prod['cantidad'], $cat_id, $prov_id);
                    $stmt_prod->execute();
                    $product_id = $stmt_prod->insert_id;
                    $stmt_prod->close();
                }
                $stmt_cp = $mysqli->prepare("INSERT INTO cotizaciones_productos (cotizacion_id, product_id, cantidad, precio_unitario, precio_total) VALUES (?, ?, ?, ?, ?)");
                $precio_total = floatval($prod['precio']) * intval($prod['cantidad']);
                $stmt_cp->bind_param('iiddd', $cotizacion_id, $product_id, $prod['cantidad'], $prod['precio'], $precio_total);
                $stmt_cp->execute();
                $stmt_cp->close();
                // Descontar stock si estado es aprobada (ID 1)
                if ($estado_id == 1) {
                    $mysqli->query("UPDATE products SET quantity = quantity - " . intval($prod['cantidad']) . " WHERE product_id = " . intval($product_id));
                }
            }
            
            // Procesar servicios
            foreach ($servicios as $serv) {
                $servicio_id = $serv['servicio_id'] ?? null;
                $nombre_servicio = $serv['nombre'];
                $descripcion = $serv['descripcion'] ?? '';
                $cantidad = floatval($serv['cantidad']);
                $precio_unitario = floatval($serv['precio']);
                $precio_total = $cantidad * $precio_unitario;
                $imagen = $serv['imagen'] ?? null;

                $stmt_cs = $mysqli->prepare("INSERT INTO cotizaciones_servicios (cotizacion_id, servicio_id, nombre_servicio, descripcion, cantidad, precio_unitario, precio_total, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_cs->bind_param('iissddds', $cotizacion_id, $servicio_id, $nombre_servicio, $descripcion, $cantidad, $precio_unitario, $precio_total, $imagen);
                $stmt_cs->execute();
                $stmt_cs->close();
            }
            
            // Después de guardar productos y servicios, antes de redirigir:
            $insumos_json = $_POST['insumos_json'] ?? '';
            $insumos = json_decode($insumos_json, true);
            if ($insumos && is_array($insumos)) {
                foreach ($insumos as $ins) {
                    $insumo_id = $ins['insumo_id'] ?? null;
                    $nombre_insumo = $ins['nombre'] ?? '';
                    $categoria = $ins['categoria'] ?? '';
                    $proveedor = $ins['proveedor'] ?? '';
                    $cantidad = floatval($ins['cantidad'] ?? 1);
                    $precio_unitario = floatval($ins['precio'] ?? 0);
                    $precio_total = $cantidad * $precio_unitario;
                    $stock_disponible = isset($ins['stock']) ? floatval($ins['stock']) : null;
                    if ($insumo_id) {
                        $stmt_ci = $mysqli->prepare("INSERT INTO cotizaciones_insumos (cotizacion_id, insumo_id, nombre_insumo, categoria, proveedor, cantidad, precio_unitario, precio_total, stock_disponible) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt_ci->bind_param('iisssdddd', $cotizacion_id, $insumo_id, $nombre_insumo, $categoria, $proveedor, $cantidad, $precio_unitario, $precio_total, $stock_disponible);
                        $stmt_ci->execute();
                        $stmt_ci->close();
                        // Descontar stock si estado es aprobada (ID 1)
                        if ($estado_id == 1) {
                            $mysqli->query("UPDATE insumos SET cantidad = cantidad - $cantidad WHERE insumo_id = $insumo_id");
                        }
                    }
                }
            }
            
            header("Location: ver.php?id=$cotizacion_id");
            exit;
        } else {
            $error = 'Error al guardar la cotización: ' . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Cotización</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            background: #f4f6fb;
        }
        .main-content {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 32px rgba(18,24,102,0.07);
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
        .form-section { background: #fff; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 2px 12px rgba(18,24,102,0.07); }
        .section-title { font-size: 1.3rem; font-weight: 700; color: #121866; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
        .select2-container--default .select2-selection--single { height: 38px; }
        .select2-selection__rendered { line-height: 38px !important; }
        .select2-selection__arrow { height: 38px !important; }
        .table thead th { background: #121866; color: #fff; }
        .badge-stock { font-size: 0.85rem; }
        /* Icono de búsqueda discreto en la tabla */
        .icon-buscar-google {
            color: #888;
            font-size: 1.05em;
            margin-left: 6px;
            opacity: 0;
            cursor: pointer;
            transition: opacity 0.15s;
            vertical-align: middle;
        }
        #tablaProductosCotizacion td:hover .icon-buscar-google {
            opacity: 1;
        }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <form method="POST" id="formCrearCotizacion" autocomplete="off">
        <!-- Sección Cliente -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-person"></i> Cliente</div>
            <div class="mb-3">
                <label for="cliente_select" class="form-label">Seleccionar cliente</label>
                <select class="form-select" id="cliente_select" name="cliente_id">
                    <option value="">-- Nuevo cliente --</option>
                    <?php foreach ($clientes_array as $cl): ?>
                        <option value="<?= $cl['cliente_id'] ?>" data-nombre="<?= htmlspecialchars($cl['nombre']) ?>" data-telefono="<?= htmlspecialchars($cl['telefono']) ?>" data-ubicacion="<?= htmlspecialchars($cl['ubicacion']) ?>" data-email="<?= htmlspecialchars($cl['email']) ?>">
                            <?= htmlspecialchars($cl['nombre']) ?><?= $cl['telefono'] ? ' (' . htmlspecialchars($cl['telefono']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="camposNuevoCliente">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="cliente_nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="cliente_nombre" id="cliente_nombre">
                    </div>
                    <div class="col-md-3">
                        <label for="cliente_telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="cliente_telefono" id="cliente_telefono">
                    </div>
                    <div class="col-md-3">
                        <label for="cliente_ubicacion" class="form-label">Ubicación</label>
                        <input type="text" class="form-control" name="cliente_ubicacion" id="cliente_ubicacion">
                    </div>
                    <div class="col-md-3">
                        <label for="cliente_email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="cliente_email" id="cliente_email">
                    </div>
                </div>
            </div>
        </div>
        <!-- Sección Productos -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-box"></i> Productos</div>
            <div class="mb-3">
                <label for="buscador_producto" class="form-label">Buscar producto en inventario</label>
                <input type="text" class="form-control" id="buscador_producto" placeholder="Nombre, SKU o descripción...">
                <div id="sugerencias_productos" class="list-group mt-1"></div>
            </div>
            <div class="mb-3">
                <button type="button" class="btn btn-outline-primary" id="btnAltaRapidaProducto"><i class="bi bi-plus-circle"></i> Alta rápida de producto</button>
            </div>
            <div id="altaRapidaProductoForm" style="display:none;">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nuevo_nombre_producto">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">SKU</label>
                        <input type="text" class="form-control" id="nuevo_sku_producto">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Precio</label>
                        <input type="number" class="form-control" id="nuevo_precio_producto" min="0" step="0.01">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Costo</label>
                        <input type="number" class="form-control" id="nuevo_costo_producto" min="0" step="0.01">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="nuevo_cantidad_producto" min="1" value="1">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Categoría</label>
                        <select class="form-select" id="nuevo_categoria_producto">
                            <option value="">-</option>
                            <?php if ($categorias) while ($cat = $categorias->fetch_assoc()): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Proveedor</label>
                        <select class="form-select" id="nuevo_proveedor_producto">
                            <option value="">-</option>
                            <?php if ($proveedores) while ($prov = $proveedores->fetch_assoc()): ?>
                                <option value="<?= $prov['supplier_id'] ?>"><?= htmlspecialchars($prov['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-2">
                    <button type="button" class="btn btn-success" id="btnAgregarProductoRapido"><i class="bi bi-check-circle"></i> Agregar producto</button>
                </div>
            </div>
            <div class="mb-3">
                <button type="button" class="btn btn-outline-info" id="btnGestionarPaquetes">
                    <i class="bi bi-boxes"></i> Gestionar paquetes inteligentes
                </button>
            </div>
            <!-- Modal de gestión de paquetes -->
            <div class="modal fade" id="modalPaquetes" tabindex="-1" aria-labelledby="modalPaquetesLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalPaquetesLabel">Paquetes inteligentes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>
                  <div class="modal-body" id="paquetesPanel">
                    <!-- Aquí se renderizará el panel de paquetes por JS -->
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped align-middle" id="tablaProductosCotizacion">
                    <thead class="table-dark">
                        <tr>
                            <th>Nombre</th>
                            <th>SKU</th>
                            <th>Enlace</th>
                            <th>Proveedor</th>
                            <th>Stock</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- Sección Insumos -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-tools"></i> Insumos</div>
            <div class="mb-3">
                <label for="buscador_insumo" class="form-label">Buscar insumo</label>
                <input type="text" class="form-control" id="buscador_insumo" placeholder="Nombre, categoría o proveedor...">
                <div id="sugerencias_insumos" class="list-group mt-1"></div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped align-middle" id="tablaInsumosCotizacion">
                    <thead class="table-dark">
                        <tr>
                            <th>Nombre</th>
                            <th>Enlace</th>
                            <th>Proveedor</th>
                            <th>Stock</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- Sección Servicios -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-tools"></i> Servicios</div>
            <div class="mb-3">
                <label for="buscador_servicio" class="form-label">Buscar servicio</label>
                <input type="text" class="form-control" id="buscador_servicio" placeholder="Nombre del servicio...">
                <div id="sugerencias_servicios" class="list-group mt-1"></div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped align-middle" id="tablaServiciosCotizacion">
                    <thead class="table-dark">
                        <tr>
                            <th>Servicio</th>
                            <th>Enlace</th>
                            <th>Descripción</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <!-- Sección Estado y Condiciones -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-info-circle"></i> Estado y Condiciones</div>
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Fecha de cotización</label>
                    <input type="date" name="fecha_cotizacion" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Validez (días)</label>
                    <input type="number" name="validez_dias" class="form-control" value="30" min="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado de la cotización <i class="bi bi-exclamation-triangle text-warning" title="Solo al aprobar se descuenta stock"></i></label>
                    <select class="form-select" name="estado_id" id="estado_id" required>
                        <?php foreach ($estados_array as $e): ?>
                            <option value="<?= $e['est_cot_id'] ?>"<?= $e['est_cot_id'] == 2 ? ' selected' : '' ?>><?= htmlspecialchars($e['nombre_estado']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Solo cuando el estado sea <b>Aprobada</b> se descontarán los productos del stock.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Condiciones de pago</label>
                    <input type="text" name="condiciones_pago" class="form-control">
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>
        <!-- Sección Resumen -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-receipt"></i> Resumen</div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Subtotal</label>
                    <input type="text" class="form-control" id="subtotal" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Descuento (%)</label>
                    <input type="number" name="descuento_porcentaje" class="form-control" id="descuento_porcentaje" min="0" max="100" value="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Descuento ($)</label>
                    <input type="text" class="form-control" id="descuento_monto" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Total</label>
                    <input type="text" class="form-control" id="total" readonly>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end mb-5">
            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle"></i> Guardar Cotización</button>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><i class="bi bi-exclamation-triangle"></i> <?= $error ?></div>
        <?php endif; ?>
    </form>
</main>
<script src="../assets/js/script.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="paquetes.js?v=999"></script>
<script>
// --- CLIENTES ---
const clientesArray = <?= json_encode($clientes_array) ?>;
$(document).ready(function() {
    $('#cliente_select').select2({
        placeholder: 'Selecciona un cliente o deja vacío para nuevo',
        allowClear: true,
        width: '100%'
    });
    function toggleCamposCliente() {
        var clienteId = $('#cliente_select').val();
        if (clienteId) {
            var cliente = clientesArray.find(c => c.cliente_id == clienteId);
            if (cliente) {
                $('#cliente_nombre').val(cliente.nombre).prop('readonly', true);
                $('#cliente_telefono').val(cliente.telefono).prop('readonly', true);
                $('#cliente_ubicacion').val(cliente.ubicacion).prop('readonly', true);
                $('#cliente_email').val(cliente.email).prop('readonly', true);
            }
        } else {
            $('#cliente_nombre, #cliente_telefono, #cliente_ubicacion, #cliente_email').val('').prop('readonly', false);
        }
    }
    $('#cliente_select').on('change', toggleCamposCliente);
    toggleCamposCliente();
});

// Manejo del cliente
document.getElementById('cliente_select').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (this.value) {
        // Cliente existente seleccionado
        document.getElementById('cliente_nombre').value = selectedOption.dataset.nombre || '';
        document.getElementById('cliente_telefono').value = selectedOption.dataset.telefono || '';
        document.getElementById('cliente_ubicacion').value = selectedOption.dataset.ubicacion || '';
        document.getElementById('cliente_email').value = selectedOption.dataset.email || '';
        
        // Hacer campos readonly y cambiar estilo
        document.getElementById('cliente_nombre').readOnly = true;
        document.getElementById('cliente_telefono').readOnly = true;
        document.getElementById('cliente_ubicacion').readOnly = true;
        document.getElementById('cliente_email').readOnly = true;
        
        // Cambiar estilo visual
        document.getElementById('camposNuevoCliente').style.opacity = '0.6';
        mostrarNotificacion('Cliente existente seleccionado. Los campos están bloqueados.', 'info');
    } else {
        // Crear cliente nuevo
        document.getElementById('cliente_nombre').value = '';
        document.getElementById('cliente_telefono').value = '';
        document.getElementById('cliente_ubicacion').value = '';
        document.getElementById('cliente_email').value = '';
        
        // Hacer campos editables
        document.getElementById('cliente_nombre').readOnly = false;
        document.getElementById('cliente_telefono').readOnly = false;
        document.getElementById('cliente_ubicacion').readOnly = false;
        document.getElementById('cliente_email').readOnly = false;
        
        // Cambiar estilo visual
        document.getElementById('camposNuevoCliente').style.opacity = '1';
        mostrarNotificacion('Modo: Crear cliente nuevo. Completa al menos el nombre.', 'success');
    }
});

// --- PRODUCTOS ---
const productosArray = <?= json_encode($productos_array) ?>.map(p => ({
    ...p,
    tipo_gestion: p.tipo_gestion || 'pieza'
}));
let productosCotizacion = [];

// --- SERVICIOS ---
const serviciosArray = <?= json_encode($servicios_array) ?>;
let serviciosCotizacion = [];
// Hacer serviciosArray global INMEDIATAMENTE para que paquetes.js pueda acceder
window.serviciosArray = serviciosArray;

// --- INSUMOS ---
const insumosArray = <?= json_encode($insumos_array) ?>;
window.insumosArray = insumosArray;
let insumosCotizacion = [];
// ... existing code ...

// Función para normalizar texto (quitar acentos y convertir a minúsculas)
function normalizarTexto(texto) {
    return texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
}

$('#buscador_producto').on('input', function() {
    const query = normalizarTexto($(this).val());
    let sugerencias = '';
    if (query.length > 0) {
        const filtrados = productosArray.filter(p => {
            const nombreNormalizado = normalizarTexto(p.product_name || '');
            const skuNormalizado = normalizarTexto(p.sku || '');
            const categoriaNormalizada = normalizarTexto(p.categoria || '');
            
            return nombreNormalizado.includes(query) || 
                   skuNormalizado.includes(query) || 
                   categoriaNormalizada.includes(query);
        });
        
        filtrados.forEach(p => {
            sugerencias += `<button type='button' class='list-group-item list-group-item-action' data-id='${p.product_id}' data-nombre='${p.product_name}' data-sku='${p.sku}' data-categoria='${p.categoria||''}' data-proveedor='${p.proveedor||''}' data-stock='${p.stock_disponible}' data-precio='${p.price}'>
                <b>${p.product_name}</b> <span class='badge bg-${p.stock_disponible > 0 ? 'success' : 'danger'} ms-2'>Stock: ${p.stock_disponible}</span><br>
                <small>SKU: ${p.sku || '-'} | $${parseFloat(p.price).toFixed(2)}</small>
            </button>`;
        });
    }
    $('#sugerencias_productos').html(sugerencias).show();
});
$('#sugerencias_productos').on('click', 'button', function() {
    const prod = productosArray.find(p => p.product_id == $(this).data('id'));
    agregarProductoATabla({
        product_id: $(this).data('id'),
        nombre: $(this).data('nombre'),
        sku: $(this).data('sku'),
        categoria: $(this).data('categoria'),
        proveedor: $(this).data('proveedor'),
        stock: prod ? (prod.tipo_gestion === 'bobina' ? (prod.stock_disponible || 0) : prod.stock_disponible) : $(this).data('stock'),
        cantidad: prod && prod.tipo_gestion === 'bobina' ? 1.00 : 1,
        precio: $(this).data('precio'),
        tipo_gestion: prod ? prod.tipo_gestion : 'pieza'
    });
    $('#buscador_producto').val('');
    $('#sugerencias_productos').hide();
});
$('#btnAltaRapidaProducto').on('click', function() {
    $('#altaRapidaProductoForm').toggle();
});
$('#btnAgregarProductoRapido').on('click', function() {
    const nombre = $('#nuevo_nombre_producto').val();
    const precio = parseFloat($('#nuevo_precio_producto').val()) || 0;
    const cantidad = parseInt($('#nuevo_cantidad_producto').val()) || 1;
    if (!nombre || !precio || !cantidad) {
        mostrarNotificacion('Completa nombre, precio y cantidad para el producto.', 'warning');
        return;
    }
    agregarProductoATabla({
        product_id: null,
        nombre: nombre,
        sku: $('#nuevo_sku_producto').val(),
        categoria: $('#nuevo_categoria_producto option:selected').text(),
        category_id: $('#nuevo_categoria_producto').val() || null,
        proveedor: $('#nuevo_proveedor_producto option:selected').text(),
        supplier_id: $('#nuevo_proveedor_producto').val() || null,
        stock: '',
        cantidad: cantidad,
        precio: precio,
        tipo_gestion: 'pieza' // Default to 'pieza'
    });
    $('#nuevo_nombre_producto, #nuevo_sku_producto, #nuevo_precio_producto, #nuevo_cantidad_producto').val('');
    $('#nuevo_categoria_producto, #nuevo_proveedor_producto').val('');
    $('#altaRapidaProductoForm').hide();
    mostrarNotificacion('Producto agregado correctamente.', 'success');
});
function agregarProductoATabla(prod) {
    if (!prod.tipo_gestion) prod.tipo_gestion = 'pieza';
    productosCotizacion.push(prod);
    renderTablaProductos();
    guardarBorrador();
}
$(document).on('click', '.btn-eliminar-producto', function() {
    const idx = $(this).data('idx');
    productosCotizacion.splice(idx, 1);
    renderTablaProductos();
    guardarBorrador();
});
function renderTablaProductos() {
    let html = '';
    let subtotal = 0;
    productosCotizacion.forEach((p, i) => {
        const sub = (parseFloat(p.precio) || 0) * (parseFloat(p.cantidad) || 1);
        subtotal += sub;
        const esBobina = p.tipo_gestion === 'bobina';
        const step = esBobina ? '0.01' : '1';
        const min = esBobina ? '0.01' : '1';
        const unidad = esBobina ? ' m' : '';
        const nombreGoogle = encodeURIComponent(p.nombre || '');
        const skuGoogle = encodeURIComponent(p.sku || '');
        let stockStr = '';
        if (typeof p.stock !== 'undefined' && p.stock !== null && p.stock !== '') {
            stockStr = esBobina ? parseFloat(p.stock).toFixed(2) : parseInt(p.stock);
        }
        if (typeof p.iva === 'undefined') p.iva = false;
        const ivaMonto = p.iva ? sub * 0.16 : 0;
        html += `
            <tr>
                <td>${p.nombre}
                    ${p.nombre ? `<a href="https://www.google.com/search?q=${nombreGoogle}" target="_blank" title="Buscar en Google" class="icon-buscar-google"><i class="bi bi-search"></i></a>` : ''}
                </td>
                <td>${p.sku || ''}
                    ${p.sku ? `<a href="https://www.google.com/search?q=${skuGoogle}" target="_blank" title="Buscar SKU en Google" class="icon-buscar-google"><i class="bi bi-search"></i></a>` : ''}
                </td>
                <td style="text-align:center;">
                    ${p.paquete_id ? `<input type='checkbox' class='sync-checkbox' data-index='${i}' ${p.sincronizado !== false ? 'checked' : ''} title='Sincronizar con principal'> <i class='bi bi-link-45deg'></i>` : ''}
                </td>
                <td>${p.proveedor || ''}</td>
                <td>${stockStr}</td>
                <td>
                    <input type="number" 
                           min="${min}" 
                           step="${step}" 
                           value="${p.cantidad}" 
                           class="form-control form-control-sm cantidad-input" 
                           data-index="${i}" 
                           data-paquete-id="${p.paquete_id || ''}"
                           data-tipo-paquete="${p.tipo_paquete || ''}"
                           style="width: 80px; display:inline-block;">${unidad}
                </td>
                <td>
                    <input type="number" 
                           min="0" 
                           step="0.01" 
                           value="${p.precio}" 
                           class="form-control form-control-sm precio-input" 
                           data-index="${i}" 
                           style="width: 110px;">
                </td>
                <td>
                    <div style="display:flex; flex-direction:column; align-items:flex-start;">
                        <span>$${p.iva ? (sub + (sub * 0.16)).toFixed(2) : sub.toFixed(2)}${p.iva ? ' <span class=\'badge bg-warning ms-1\'>IVA incluido</span>' : ''}</span>
                        ${p.iva ? `<span style='font-size:0.85em; color:#888;'>Base: $${sub.toFixed(2)} | IVA: $${(sub * 0.16).toFixed(2)}</span>` : ''}
                    </div>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm btn-eliminar-producto" data-idx="${i}">
                        <i class="bi bi-trash"></i>
                    </button>
                    <div style="margin-top:6px;">
                        <label class="form-check-label" style="font-size:0.95em;">
                            <input type="checkbox" class="form-check-input iva-toggle-producto" data-index="${i}" ${p.iva ? 'checked' : ''}> IVA 16%
                        </label>
                        ${p.iva ? `<span class='badge bg-warning ms-1'>IVA: $${ivaMonto.toFixed(2)}</span>` : ''}
                    </div>
                </td>
            </tr>
        `;
// Evento para toggle IVA en productos
$(document).on('change', '.iva-toggle-producto', function() {
    const index = parseInt($(this).data('index'));
    productosCotizacion[index].iva = this.checked;
    renderTablaProductos();
    guardarBorrador();
});
    });
    $('#tablaProductosCotizacion tbody').html(html);
    $('#subtotal').val(`$${subtotal.toFixed(2)}`);
    recalcularTotales();
}

// Eventos para cantidad
$(document).on('input', '.cantidad-input', function() {
    const index = parseInt($(this).data('index'));
    let value = $(this).val();
    productosCotizacion[index].cantidad = value;
    // Validación visual
    if (!value || isNaN(value) || value <= 0) {
        $(this).addClass('is-invalid');
    } else {
        $(this).removeClass('is-invalid');
    }
    recalcularTotales();
});

$(document).on('blur', '.cantidad-input', function() {
    const index = parseInt($(this).data('index'));
    let value = $(this).val();
    const prod = productosCotizacion[index];
    if (prod) {
        if (prod.tipo_gestion === 'bobina') {
            value = parseFloat(value) || 0.01;
        } else {
            value = Math.max(1, Math.round(parseFloat(value) || 1));
        }
        productosCotizacion[index].cantidad = value;
        // Actualiza el input visualmente
        $(this).val(value);
        // Si es principal de paquete, sincronizar relacionados
        if (prod.paquete_id && prod.tipo_paquete === 'principal') {
            sincronizarCantidadesPaqueteV2(prod.paquete_id);
        } else {
            renderTablaProductos();
        }
    }
});

$(document).on('focus', '.cantidad-input', function() {
    $(this).select();
});

// Eventos para precio
$(document).on('input', '.precio-input', function() {
    const index = parseInt($(this).data('index'));
    let value = $(this).val();
    productosCotizacion[index].precio = value;
    // Validación visual
    if (!value || isNaN(value) || value < 0) {
        $(this).addClass('is-invalid');
    } else {
        $(this).removeClass('is-invalid');
    }
    recalcularTotales();
});

$(document).on('blur', '.precio-input', function() {
    const index = parseInt($(this).data('index'));
    let value = $(this).val();
    value = parseFloat(value) || 0;
    if (value < 0) value = 0;
    productosCotizacion[index].precio = value;
    $(this).val(value);
    renderTablaProductos();
});

$(document).on('focus', '.precio-input', function() {
    $(this).select();
});

// --- SERVICIOS ---
$('#buscador_servicio').on('input', function() {
    const query = normalizarTexto($(this).val());
    let sugerencias = '';
    if (query.length > 0) {
        const filtrados = serviciosArray.filter(s => {
            const nombreNormalizado = normalizarTexto(s.nombre || '');
            const categoriaNormalizada = normalizarTexto(s.categoria || '');
            const descripcionNormalizada = normalizarTexto(s.descripcion || '');
            
            return nombreNormalizado.includes(query) || 
                   categoriaNormalizada.includes(query) || 
                   descripcionNormalizada.includes(query);
        });
        
        filtrados.forEach(s => {
            sugerencias += `<button type='button' class='list-group-item list-group-item-action' data-id='${s.servicio_id}' data-nombre='${s.nombre}' data-categoria='${s.categoria||''}' data-descripcion='${s.descripcion||''}' data-precio='${s.precio}' data-imagen='${s.imagen||''}'>
                <b>${s.nombre}</b> <span class='badge bg-info ms-2'>${s.categoria || 'Sin categoría'}</span><br>
                <small>$${parseFloat(s.precio).toFixed(2)}</small>
            </button>`;
        });
    }
    $('#sugerencias_servicios').html(sugerencias).show();
});

$('#sugerencias_servicios').on('click', 'button', function() {
    const servicio = serviciosArray.find(s => s.servicio_id == $(this).data('id'));
    agregarServicioATabla({
        servicio_id: $(this).data('id'),
        nombre: $(this).data('nombre'),
        categoria: $(this).data('categoria'),
        descripcion: $(this).data('descripcion'),
        precio: $(this).data('precio'),
        cantidad: 1,
        imagen: $(this).data('imagen')
    });
    $('#buscador_servicio').val('');
    $('#sugerencias_servicios').hide();
});

function agregarServicioATabla(servicio) {
    serviciosCotizacion.push(servicio);
    renderTablaServicios();
    guardarBorrador();
}

$(document).on('click', '.btn-eliminar-servicio', function() {
    const idx = $(this).data('idx');
    serviciosCotizacion.splice(idx, 1);
    renderTablaServicios();
    guardarBorrador();
});

function renderTablaServicios() {
    let html = '';
    serviciosCotizacion.forEach((s, i) => {
        const sub = (parseFloat(s.precio) || 0) * (parseFloat(s.cantidad) || 1);
        html += `
            <tr>
                <td>
                    <strong>${s.nombre}</strong>
                    ${s.tiempo_estimado ? `<br><small class="text-muted">Tiempo estimado: ${s.tiempo_estimado}h</small>` : ''}
                </td>
                <td style="text-align:center;">
                    ${s.paquete_id ? `<input type='checkbox' class='sync-checkbox-servicio' data-index='${i}' ${s.sincronizado !== false ? 'checked' : ''} title='Sincronizar con principal'> <i class='bi bi-link-45deg'></i>` : ''}
                </td>
                <td>${s.descripcion || ''}</td>
                <td>
                    <input type="number" 
                           min="1" 
                           step="1" 
                           value="${Math.round(s.cantidad)}" 
                           class="form-control form-control-sm cantidad-servicio-input" 
                           data-index="${i}"
                           data-paquete-id="${s.paquete_id || ''}"
                           data-tipo-paquete="${s.tipo_paquete || ''}"
                           style="width: 80px; display:inline-block;">
                    ${s.unidad_medida ? ` ${s.unidad_medida}` : ''}
                </td>
                <td>
                    <input type="number" 
                           min="0" 
                           step="0.01" 
                           value="${s.precio}" 
                           class="form-control form-control-sm precio-servicio-input" 
                           data-index="${i}" 
                           style="width: 110px;">
                </td>
                <td>$${sub.toFixed(2)}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm btn-eliminar-servicio" data-idx="${i}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    $('#tablaServiciosCotizacion tbody').html(html);
    recalcularTotales();
}

// Eventos para cantidad de servicios
$(document).on('input', '.cantidad-servicio-input', function() {
    const index = parseInt($(this).data('index'));
    let value = Math.max(1, Math.round($(this).val()));
    serviciosCotizacion[index].cantidad = value;
    if (!value || isNaN(value) || value <= 0) {
        $(this).addClass('is-invalid');
    } else {
        $(this).removeClass('is-invalid');
    }
    recalcularTotales();
});

$(document).on('blur', '.cantidad-servicio-input', function() {
    const index = parseInt($(this).data('index'));
    let value = Math.max(1, Math.round($(this).val()));
    serviciosCotizacion[index].cantidad = value;
    $(this).val(value);
    // Si es principal de paquete, sincronizar relacionados
    const s = serviciosCotizacion[index];
    if (s && s.paquete_id && s.tipo_paquete === 'principal') {
        sincronizarCantidadesPaqueteV2(s.paquete_id);
    } else {
        renderTablaServicios();
    }
});

$(document).on('focus', '.cantidad-servicio-input', function() {
    $(this).select();
});

// Eventos para precio de servicios
$(document).on('input', '.precio-servicio-input', function() {
    const index = parseInt($(this).data('index'));
    let value = $(this).val();
    serviciosCotizacion[index].precio = value;
    if (!value || isNaN(value) || value < 0) {
        $(this).addClass('is-invalid');
    } else {
        $(this).removeClass('is-invalid');
    }
    recalcularTotales();
});

$(document).on('blur', '.precio-servicio-input', function() {
    const index = parseInt($(this).data('index'));
    let value = parseFloat($(this).val()) || 0;
    if (value < 0) value = 0;
    serviciosCotizacion[index].precio = value;
    $(this).val(value);
    renderTablaServicios();
});

$(document).on('focus', '.precio-servicio-input', function() {
    $(this).select();
});

function recalcularTotales() {
    const subtotalProductos = productosCotizacion.reduce((sum, p) => sum + ((parseFloat(p.precio)||0)*(parseFloat(p.cantidad)||1)), 0);
    const subtotalServicios = serviciosCotizacion.reduce((sum, s) => sum + ((parseFloat(s.precio)||0)*(parseFloat(s.cantidad)||1)), 0);
    const subtotal = subtotalProductos + subtotalServicios;
    const descuentoPorcentaje = parseFloat($('#descuento_porcentaje').val()) || 0;
    const descuentoMonto = subtotal * descuentoPorcentaje / 100;
    const total = subtotal - descuentoMonto;
    $('#subtotal').val(`$${subtotal.toFixed(2)}`);
    $('#descuento_monto').val(`$${descuentoMonto.toFixed(2)}`);
    $('#total').val(`$${total.toFixed(2)}`);
}
$('#descuento_porcentaje').on('input', recalcularTotales);
$('#formCrearCotizacion').on('submit', function(e) {
    let error = '';
    const clienteId = $('#cliente_select').val();
    const nombre = $('#cliente_nombre').val().trim();
    const telefono = $('#cliente_telefono').val().trim();
    const ubicacion = $('#cliente_ubicacion').val().trim();
    const email = $('#cliente_email').val().trim();
    
    // Validar cliente: debe tener ID seleccionado O al menos nombre
    if (!clienteId && !nombre) {
        error = 'Debes seleccionar un cliente existente o registrar uno nuevo con al menos el nombre.';
    }
    
    if (productosCotizacion.length === 0 && serviciosCotizacion.length === 0) {
        error = 'Debes agregar al menos un producto o servicio a la cotización.';
    }
    
    // Validar cantidades según tipo
    productosCotizacion.forEach(p => {
        if (p.tipo_gestion === 'bobina') {
            p.cantidad = parseFloat(p.cantidad) || 0.01;
        } else {
            p.cantidad = Math.max(1, Math.round(parseFloat(p.cantidad) || 1));
        }
    });
    
    if (error) {
        e.preventDefault();
        mostrarNotificacion(error, 'danger');
        return false;
    }
    
    $('<input>').attr({type:'hidden', name:'productos_json', value: JSON.stringify(productosCotizacion)}).appendTo(this);
    $('<input>').attr({type:'hidden', name:'servicios_json', value: JSON.stringify(serviciosCotizacion)}).appendTo(this);
    $('<input>').attr({type:'hidden', name:'insumos_json', value: JSON.stringify(insumosCotizacion)}).appendTo(this);
    $(this).find('button[type=submit]').prop('disabled', true).text('Guardando...');
});

// Prevenir submit por Enter accidental
$('#formCrearCotizacion').on('keydown', function(e) {
    if (e.key === 'Enter') {
        // Permitir Enter solo si el foco está en el botón de submit
        const isSubmitBtn = document.activeElement && document.activeElement.type === 'submit';
        if (!isSubmitBtn) {
            e.preventDefault();
            return false;
        }
    }
});

document.getElementById('btnGestionarPaquetes').addEventListener('click', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalPaquetes'));
    renderPaquetesPanel();
    modal.show();
});

// --- NUEVO GESTOR DE PAQUETES VISUAL ---
function renderPaquetesPanel() {
    const panel = document.getElementById('paquetesPanel');
    const paquetes = window.PaquetesCotizacion.getPaquetes();
    let html = '';
    if (paquetes.length === 0) {
        html += '<div class="alert alert-info">No hay paquetes definidos. Crea uno nuevo para empezar.</div>';
    } else {
        html += '<div class="row g-3">';
        paquetes.forEach((paq, idx) => {
            html += `<div class="col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0" style="font-size:1.1rem;">${paq.nombre}</h5>
                            <span class="badge bg-primary">${paq.items.length} productos</span>
                        </div>
                        <ul class="list-group list-group-flush mb-2" style="font-size:0.97rem;">
                            ${paq.items.map(item => `<li class="list-group-item py-1 px-2 d-flex justify-content-between align-items-center">
                                <span>${item.nombre || 'Producto'} <span class="text-muted">x${item.factor || 1}</span></span>
                                <span class="badge bg-light text-dark">${item.tipo === 'principal' ? 'Principal' : 'Relacionado'}</span>
                            </li>`).join('')}
                        </ul>
                        <div class="mt-auto d-flex gap-2 justify-content-end">
                            <button class="btn btn-sm btn-success" onclick="aplicarPaqueteCotizacion(${idx});"><i class="bi bi-play"></i></button>
                            <button class="btn btn-sm btn-outline-primary" onclick="editarPaquete(${idx});"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="duplicarPaquete(${idx});"><i class="bi bi-files"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarPaquete(${idx});"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>`;
        });
        html += '</div>';
    }
    html += '<div class="mt-4"><button class="btn btn-success" onclick="nuevoPaquete(); return false;" type="button"><i class="bi bi-plus-circle"></i> Nuevo paquete</button></div>';
    panel.innerHTML = html;
}

// Duplicar paquete
function duplicarPaquete(idx) {
    const paquetes = window.PaquetesCotizacion.getPaquetes();
    const paq = paquetes[idx];
    if (!paq) return;
    const copia = JSON.parse(JSON.stringify(paq));
    copia.nombre = paq.nombre + ' (Copia)';
    window.PaquetesCotizacion.addPaquete(copia);
    renderPaquetesPanel();
    mostrarNotificacion('Paquete duplicado correctamente.', 'success');
}

// --- PAQUETES INTELIGENTES ---
function aplicarPaqueteCotizacion(idx) {
    const paquetes = window.PaquetesCotizacion.getPaquetes();
    const paquete = paquetes[idx];
    if (!paquete) return;
    
    // Prevenir que se dispare el submit del formulario
    event.preventDefault();
    event.stopPropagation();
    
    // Preservar datos del cliente antes de aplicar paquete
    const clienteSeleccionado = {
        cliente_id: $('#cliente_select').val(),
        cliente_nombre: $('#cliente_nombre').val(),
        cliente_telefono: $('#cliente_telefono').val(),
        cliente_ubicacion: $('#cliente_ubicacion').val(),
        cliente_email: $('#cliente_email').val()
    };
    
    productosCotizacion = [];
    serviciosCotizacion = [];
    insumosCotizacion = [];
    const paqueteId = 'paq_' + Date.now();
    // Determinar principal por tipo
    let principalProducto = null, principalServicio = null, principalInsumo = null;
    paquete.items.forEach(item => {
        if (item.tipo_item === 'producto' && item.tipo === 'principal') principalProducto = item.product_id;
        if (item.tipo_item === 'servicio' && item.tipo === 'principal') principalServicio = item.servicio_id;
        if (item.tipo_item === 'insumo' && item.tipo === 'principal') principalInsumo = item.insumo_id;
    });
    paquete.items.forEach(item => {
        if (item.product_id) {
            const prod = productosArray.find(p => p.product_id == item.product_id);
            if (prod) {
                productosCotizacion.push({
                    product_id: prod.product_id,
                    nombre: prod.product_name,
                    sku: prod.sku,
                    categoria: prod.categoria,
                    proveedor: prod.proveedor,
                    stock: prod.tipo_gestion === 'bobina' ? (prod.stock_disponible || 0) : prod.stock_disponible,
                    cantidad: prod.tipo_gestion === 'bobina' ? (item.factor || 1.00) : (item.factor || 1),
                    precio: prod.price,
                    tipo_gestion: prod.tipo_gestion,
                    paquete_id: paqueteId,
                    tipo_paquete: (item.tipo_item === 'producto' && item.product_id == principalProducto) ? 'principal' : 'relacionado',
                    factor_paquete: item.factor,
                    sincronizado: true
                });
            }
        } else if (item.servicio_id) {
            let serv = null;
            if (window.serviciosArray && Array.isArray(window.serviciosArray)) {
                serv = window.serviciosArray.find(s => s.servicio_id == item.servicio_id);
            }
            if (!serv && item.nombre) {
                serv = item;
            }
            if (serv) {
                serviciosCotizacion.push({
                    servicio_id: serv.servicio_id,
                    nombre: serv.nombre,
                    categoria: serv.categoria || '',
                    descripcion: serv.descripcion || '',
                    precio: serv.precio || item.precio || 0,
                    cantidad: item.factor || serv.cantidad || 1,
                    imagen: serv.imagen || '',
                    paquete_id: paqueteId,
                    tipo_paquete: (item.tipo_item === 'servicio' && item.servicio_id == principalServicio) ? 'principal' : 'relacionado',
                    factor_paquete: item.factor,
                    sincronizado: true
                });
            }
        } else if (item.tipo_item === 'insumo' && item.insumo_id) {
            const ins = (window.insumosArray || []).find(i => i.insumo_id == item.insumo_id);
            if (ins) {
                insumosCotizacion.push({
                    insumo_id: ins.insumo_id,
                    nombre: ins.nombre,
                    categoria: ins.categoria || '',
                    proveedor: ins.proveedor || '',
                    stock: ins.stock,
                    cantidad: item.factor || 1,
                    precio: ins.precio,
                    paquete_id: paqueteId,
                    tipo_paquete: (item.tipo_item === 'insumo' && item.insumo_id == principalInsumo) ? 'principal' : 'relacionado',
                    factor_paquete: item.factor,
                    sincronizado: true
                });
            }
        }
    });
    // --- DEPURACIÓN ---
    console.log('Productos del paquete:', productosCotizacion);
    console.log('Servicios del paquete:', serviciosCotizacion);
    console.log('Insumos del paquete:', insumosCotizacion);
    if (typeof renderTablaProductos !== 'function') {
        console.error('renderTablaProductos no existe');
    }
    if (typeof renderTablaServicios !== 'function') {
        console.error('renderTablaServicios no existe');
    }
    if (typeof renderTablaInsumos !== 'function') {
        console.error('renderTablaInsumos no existe');
    }
    // --- FIN DEPURACIÓN ---
    // Restaurar datos del cliente INMEDIATAMENTE después de aplicar paquete
    $('#cliente_select').val(clienteSeleccionado.cliente_id).trigger('change');
    $('#cliente_nombre').val(clienteSeleccionado.cliente_nombre);
    $('#cliente_telefono').val(clienteSeleccionado.cliente_telefono);
    $('#cliente_ubicacion').val(clienteSeleccionado.cliente_ubicacion);
    $('#cliente_email').val(clienteSeleccionado.cliente_email);
    
    // Restaurar estado de readonly si había cliente seleccionado
    if (clienteSeleccionado.cliente_id) {
        $('#cliente_nombre, #cliente_telefono, #cliente_ubicacion, #cliente_email').prop('readonly', true);
    } else {
        $('#cliente_nombre, #cliente_telefono, #cliente_ubicacion, #cliente_email').prop('readonly', false);
    }
    
    // Ahora renderizar las tablas
    renderTablaProductos();
    renderTablaServicios();
    renderTablaInsumos();
    
    // Configurar sincronización de cantidades
    setTimeout(() => {
        document.querySelectorAll('.cantidad-input').forEach(inp => {
            inp.addEventListener('input', function() {
                sincronizarCantidadesPaqueteV2(paqueteId);
            });
        });
    }, 200);
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalPaquetes'));
    if (modal) modal.hide();
    
    // Mostrar notificación de éxito
    mostrarNotificacion(`Paquete "${paquete.nombre}" aplicado correctamente.`, 'success');
    
    return false; // Prevenir cualquier comportamiento por defecto
}
function sincronizarCantidadesPaqueteV2(paqueteId) {
    // Encuentra el principal en productos, servicios o insumos
    let principal = productosCotizacion.find(p => p.paquete_id === paqueteId && p.tipo_paquete === 'principal');
    if (!principal) principal = serviciosCotizacion.find(s => s.paquete_id === paqueteId && s.tipo_paquete === 'principal');
    if (!principal) principal = insumosCotizacion.find(i => i.paquete_id === paqueteId && i.tipo_paquete === 'principal');
    if (!principal) return;
    const cantidadPrincipal = parseFloat(principal.cantidad) || 1;
    // Sincroniza productos relacionados
    productosCotizacion.forEach((p, idx) => {
        if (p.paquete_id === paqueteId && p.tipo_paquete === 'relacionado' && p.sincronizado !== false) {
            const factor = parseFloat(p.factor_paquete) || 1;
            p.cantidad = p.tipo_gestion === 'bobina' ? (cantidadPrincipal * factor).toFixed(2) : Math.round(cantidadPrincipal * factor);
            const input = document.querySelector(`.cantidad-input[data-index='${idx}']`);
            if (input) input.value = p.cantidad;
        }
    });
    // Sincroniza servicios relacionados
    serviciosCotizacion.forEach((s, idx) => {
        if (s.paquete_id === paqueteId && s.tipo_paquete === 'relacionado' && s.sincronizado !== false) {
            const factor = parseFloat(s.factor_paquete) || 1;
            s.cantidad = Math.round(cantidadPrincipal * factor);
            const input = document.querySelector(`.cantidad-servicio-input[data-index='${idx}']`);
            if (input) input.value = s.cantidad;
        }
    });
    // Sincroniza insumos relacionados
    insumosCotizacion.forEach((ins, idx) => {
        if (ins.paquete_id === paqueteId && ins.tipo_paquete === 'relacionado' && ins.sincronizado !== false) {
            const factor = parseFloat(ins.factor_paquete) || 1;
            ins.cantidad = Math.round(cantidadPrincipal * factor);
            const input = document.querySelector(`.cantidad-insumo-input[data-index='${idx}']`);
            if (input) input.value = ins.cantidad;
        }
    });
    renderTablaProductos();
    renderTablaServicios();
    renderTablaInsumos();
}
function eliminarPaquete(idx) {
    // Prevenir que se dispare el submit del formulario
    event.preventDefault();
    event.stopPropagation();
    
    const paquetes = window.PaquetesCotizacion.getPaquetes();
    const paquete = paquetes[idx];
    if (!paquete) return;
    
    // Crear modal de confirmación elegante
    const modalId = 'modalConfirmarEliminar';
    const modalHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="${modalId}Label">
                            <i class="bi bi-exclamation-triangle text-warning"></i> Confirmar eliminación
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que quieres eliminar el paquete <strong>"${paquete.nombre}"</strong>?</p>
                        <p class="text-muted mb-0">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-danger" onclick="confirmarEliminarPaquete(${idx})">
                            <i class="bi bi-trash"></i> Eliminar paquete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal anterior si existe
    const modalAnterior = document.getElementById(modalId);
    if (modalAnterior) {
        modalAnterior.remove();
    }
    
    // Agregar modal al body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
    
    // Limpiar modal después de cerrar
    document.getElementById(modalId).addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
    
    return false;
}

function confirmarEliminarPaquete(idx) {
    window.PaquetesCotizacion.deletePaquete(idx);
    renderPaquetesPanel();
    mostrarNotificacion('Paquete eliminado correctamente.', 'info');
    
    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarEliminar'));
    if (modal) modal.hide();
}

// Funciones de edición de paquetes (movidas desde paquetes.js)
function editarPaquete(idx) {
    const paquetes = window.PaquetesCotizacion.getPaquetes();
    const paquete = paquetes[idx];
    if (!paquete) return;
    
    window._paqEdit = { 
        nombre: paquete.nombre, 
        items: [...paquete.items] 
    };
    window._paqEditIndex = idx;
    
    window._paqRender = function() {
        // Guardar el nombre antes de renderizar
        const nombreInput = document.getElementById('paqNombre');
        if (nombreInput && window._paqEdit) {
            window._paqEdit.nombre = nombreInput.value;
        }
        const panel = document.getElementById('paquetesPanel');
        panel.innerHTML = window.renderPaqueteForm({
            productos: productosArray.map(p => ({ product_id: p.product_id, product_name: p.product_name })),
            paquete: window._paqEdit,
            onSave: guardarPaqueteEditado,
            onCancel: renderPaquetesPanel
        });
        document.getElementById('paqProductoSelect').onchange = function() {
            const pid = this.value;
            if (!pid) return;
            const prod = productosArray.find(p => p.product_id == pid);
            if (!prod) return;
            // Prevenir duplicados
            if (window._paqEdit.items.some(i => i.tipo_item === 'producto' && i.product_id == prod.product_id)) return;
            window._paqEdit.items.push({ tipo_item: 'producto', product_id: prod.product_id, nombre: prod.product_name, tipo: 'relacionado', factor: 1 });
            window._paqRender();
        };
        // --- CORRECCIÓN: Agregar servicio al paquete ---
        const paqServicioSelect = document.getElementById('paqServicioSelect');
        if (paqServicioSelect) {
            paqServicioSelect.onchange = function() {
                const sid = this.value;
                if (!sid) return;
                const serv = (window.serviciosArray || []).find(s => s.servicio_id == sid);
                if (!serv) return;
                // Prevenir duplicados
                if (window._paqEdit.items.some(i => i.tipo_item === 'servicio' && i.servicio_id == serv.servicio_id)) return;
                window._paqEdit.items.push({ tipo_item: 'servicio', servicio_id: serv.servicio_id, nombre: serv.nombre, tipo: 'relacionado', factor: 1 });
                window._paqRender();
            };
        }
        document.getElementById('btnGuardarPaquete').onclick = guardarPaqueteEditado;
        document.getElementById('btnCancelarPaquete').onclick = renderPaquetesPanel;
        document.querySelectorAll('.paq-tipo').forEach(sel => {
            sel.onchange = function() {
                window._paqEdit.items[this.dataset.idx].tipo = this.value;
            };
        });
        document.querySelectorAll('.paq-factor').forEach(inp => {
            inp.oninput = function() {
                window._paqEdit.items[this.dataset.idx].factor = parseFloat(this.value) || 1;
            };
        });
    };
    window._paqRender();
}

function guardarPaqueteEditado() {
    window._paqEdit.nombre = document.getElementById('paqNombre').value.trim();
    if (!window._paqEdit.nombre || window._paqEdit.items.length === 0) {
        mostrarNotificacion('Ponle nombre y al menos un producto al paquete.', 'warning');
        return;
    }
    window.PaquetesCotizacion.updatePaquete(window._paqEditIndex, window._paqEdit);
    renderPaquetesPanel();
    mostrarNotificacion('Paquete actualizado correctamente.', 'success');
}

// Función para crear nuevo paquete
function nuevoPaquete() {
    window._paqEdit = { nombre: '', items: [] };
    window._paqRender = function() {
        // Guardar el nombre antes de renderizar
        const nombreInput = document.getElementById('paqNombre');
        if (nombreInput && window._paqEdit) {
            window._paqEdit.nombre = nombreInput.value;
        }
        const panel = document.getElementById('paquetesPanel');
        panel.innerHTML = window.renderPaqueteForm({
            productos: productosArray.map(p => ({ product_id: p.product_id, product_name: p.product_name })),
            paquete: window._paqEdit,
            onSave: guardarPaquete,
            onCancel: renderPaquetesPanel
        });
        document.getElementById('paqProductoSelect').onchange = function() {
            const pid = this.value;
            if (!pid) return;
            const prod = productosArray.find(p => p.product_id == pid);
            if (!prod) return;
            // Prevenir duplicados
            if (window._paqEdit.items.some(i => i.tipo_item === 'producto' && i.product_id == prod.product_id)) return;
            window._paqEdit.items.push({ tipo_item: 'producto', product_id: prod.product_id, nombre: prod.product_name, tipo: 'relacionado', factor: 1 });
            window._paqRender();
        };
        // --- CORRECCIÓN: Agregar servicio al paquete ---
        const paqServicioSelect = document.getElementById('paqServicioSelect');
        if (paqServicioSelect) {
            paqServicioSelect.onchange = function() {
                const sid = this.value;
                if (!sid) return;
                const serv = (window.serviciosArray || []).find(s => s.servicio_id == sid);
                if (!serv) return;
                // Prevenir duplicados
                if (window._paqEdit.items.some(i => i.tipo_item === 'servicio' && i.servicio_id == serv.servicio_id)) return;
                window._paqEdit.items.push({ tipo_item: 'servicio', servicio_id: serv.servicio_id, nombre: serv.nombre, tipo: 'relacionado', factor: 1 });
                window._paqRender();
                       };
        }
        document.getElementById('btnGuardarPaquete').onclick = guardarPaquete;
        document.getElementById('btnCancelarPaquete').onclick = renderPaquetesPanel;
        document.querySelectorAll('.paq-tipo').forEach(sel => {
            sel.onchange = function() {
                window._paqEdit.items[this.dataset.idx].tipo = this.value;
            };
        });
        document.querySelectorAll('.paq-factor').forEach(inp => {
            inp.oninput = function() {
                window._paqEdit.items[this.dataset.idx].factor = parseFloat(this.value) || 1;
            };
        });
    };
    window._paqRender();
}

function guardarPaquete() {
    window._paqEdit.nombre = document.getElementById('paqNombre').value.trim();
    if (!window._paqEdit.nombre || window._paqEdit.items.length === 0) {
        mostrarNotificacion('Ponle nombre y al menos un producto al paquete.', 'warning');
        return;
    }
    window.PaquetesCotizacion.addPaquete(window._paqEdit);
    renderPaquetesPanel();
    mostrarNotificacion('Paquete guardado correctamente.', 'success');
}

// Función para mostrar notificaciones elegantes
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear toast notification
    const toastId = 'toast-' + Date.now();
    const iconClass = {
        'success': 'bi-check-circle',
        'warning': 'bi-exclamation-triangle',
        'danger': 'bi-x-circle',
        'info': 'bi-info-circle'
    }[tipo] || 'bi-info-circle';
    
    const toastHtml = `
        <div class="toast align-items-center text-bg-${tipo} border-0" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi ${iconClass}"></i> ${mensaje}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
        </div>
    `;
    
    // Agregar toast al contenedor
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '1100';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Mostrar toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 4000 });
    toast.show();
    
    // Remover toast después de que se oculte
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

// --- GUARDADO AUTOMÁTICO DE BORRADOR EN LOCALSTORAGE ---
const BORRADOR_KEY = 'borrador_cotizacion';

function guardarBorrador() {
    const datos = {
        cliente_id: $('#cliente_select').val(),
        cliente_nombre: $('#cliente_nombre').val(),
        cliente_telefono: $('#cliente_telefono').val(),
        cliente_ubicacion: $('#cliente_ubicacion').val(),
        cliente_email: $('#cliente_email').val(),
        productos: productosCotizacion,
        servicios: serviciosCotizacion,
        insumos: insumosCotizacion,
        fecha_cotizacion: $('input[name="fecha_cotizacion"]').val(),
        validez_dias: $('input[name="validez_dias"]').val(),
        estado_id: $('#estado_id').val(),
        condiciones_pago: $('input[name="condiciones_pago"]').val(),
        observaciones: $('textarea[name="observaciones"]').val(),
        descuento_porcentaje: $('#descuento_porcentaje').val()
    };
    localStorage.setItem(BORRADOR_KEY, JSON.stringify(datos));
}

function limpiarBorrador() {
    localStorage.removeItem(BORRADOR_KEY);
}

function restaurarBorrador() {
    const datos = JSON.parse(localStorage.getItem(BORRADOR_KEY));
    if (!datos) return;
    if (datos.cliente_id) $('#cliente_select').val(datos.cliente_id).trigger('change');
    $('#cliente_nombre').val(datos.cliente_nombre || '');
    $('#cliente_telefono').val(datos.cliente_telefono || '');
    $('#cliente_ubicacion').val(datos.cliente_ubicacion || '');
    $('#cliente_email').val(datos.cliente_email || '');
    productosCotizacion = datos.productos || [];
    serviciosCotizacion = datos.servicios || [];
    insumosCotizacion = datos.insumos || [];
    renderTablaProductos();
    renderTablaServicios();
    renderTablaInsumos();
    $('input[name="fecha_cotizacion"]').val(datos.fecha_cotizacion || '');
    $('input[name="validez_dias"]').val(datos.validez_dias || '');
    $('#estado_id').val(datos.estado_id || '');
    $('input[name="condiciones_pago"]').val(datos.condiciones_pago || '');
    $('textarea[name="observaciones"]').val(datos.observaciones || '');
    $('#descuento_porcentaje').val(datos.descuento_porcentaje || 0);
    recalcularTotales();
}

// Guardar borrador al cambiar datos relevantes
$(document).on('input change', '#cliente_select, #cliente_nombre, #cliente_telefono, #cliente_ubicacion, #cliente_email, #estado_id, input[name="fecha_cotizacion"], input[name="validez_dias"], input[name="condiciones_pago"], textarea[name="observaciones"], #descuento_porcentaje', function() {
    guardarBorrador();
});
$(document).on('input change', '.cantidad-input, .precio-input, .cantidad-servicio-input, .precio-servicio-input', function() {
    guardarBorrador();
});
// Guardar también al agregar/eliminar productos
$('#btnAgregarProductoRapido, #btnAltaRapidaProducto').on('click', function() { setTimeout(guardarBorrador, 300); });
$(document).on('click', '.btn-eliminar-producto', function() { setTimeout(guardarBorrador, 300); });

// Guardar también al agregar/eliminar servicios
$('#btnAgregarServicioRapido, #btnAltaRapidaServicio').on('click', function() { setTimeout(guardarBorrador, 300); });
$(document).on('click', '.btn-eliminar-servicio', function() { setTimeout(guardarBorrador, 300); });

// Al cargar la página, preguntar si hay borrador
$(document).ready(function() {
    if (localStorage.getItem(BORRADOR_KEY)) {
        // Crear modal si no existe
        if (!document.getElementById('modalRestaurarBorrador')) {
            const modalHtml = `
            <div class="modal fade" id="modalRestaurarBorrador" tabindex="-1" aria-labelledby="modalRestaurarBorradorLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalRestaurarBorradorLabel"><i class="bi bi-exclamation-triangle text-warning"></i> Borrador de cotización encontrado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>
                  <div class="modal-body">
                    <p>Se detectó un borrador de cotización sin guardar. ¿Deseas restaurar tu avance anterior o descartarlo?</p>
                    <ul>
                      <li><b>Restaurar:</b> Recupera todos los datos y productos que tenías antes de salir.</li>
                      <li><b>Descartar:</b> Elimina el borrador y comienza una nueva cotización.</li>
                    </ul>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btnDescartarBorrador" data-bs-dismiss="modal">Descartar</button>
                    <button type="button" class="btn btn-primary" id="btnRestaurarBorrador">Restaurar borrador</button>
                  </div>
                </div>
              </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }
        const modal = new bootstrap.Modal(document.getElementById('modalRestaurarBorrador'));
        modal.show();
        document.getElementById('btnRestaurarBorrador').onclick = function() {
            restaurarBorrador();
            mostrarNotificacion('Borrador restaurado correctamente.', 'info');
            modal.hide();
        };
        document.getElementById('btnDescartarBorrador').onclick = function() {
            limpiarBorrador();
            modal.hide();
        };
    }
});
// Al guardar exitosamente, limpiar el borrador (esto se hace al hacer submit y redirigir)
$('#formCrearCotizacion').on('submit', function() {
    limpiarBorrador();
});

// --- INSUMOS ---
// let insumosCotizacion = [];
$('#buscador_insumo').on('input', function() {
    const query = $(this).val().trim();
    if (query.length === 0) {
        $('#sugerencias_insumos').hide();
        return;
    }
    $.getJSON('../insumos/ajax_list.php', { busqueda: query }, function(resp) {
        let sugerencias = '';
        if (resp.success && resp.data.length > 0) {
            resp.data.forEach(ins => {
                sugerencias += `<button type='button' class='list-group-item list-group-item-action' data-id='${ins.insumo_id}' data-nombre='${ins.nombre}' data-categoria='${ins.categoria_nombre||''}' data-proveedor='${ins.proveedor||''}' data-stock='${ins.cantidad}' data-precio='${ins.precio_unitario}'>
                    <b>${ins.nombre}</b> <span class='badge bg-${ins.cantidad > 0 ? 'success' : 'danger'} ms-2'>Stock: ${ins.cantidad}</span><br>
                    <small>${ins.categoria_nombre || '-'} | ${ins.proveedor || '-'}</small>
                </button>`;
            });
        } else {
            sugerencias = '<div class="list-group-item">Sin resultados</div>';
        }
        $('#sugerencias_insumos').html(sugerencias).show();
    });
});
$('#sugerencias_insumos').on('click', 'button', function() {
    const unidad = $(this).data('unidad') || '';
    let equivalencia = 1;
    let equivalenciaStr = '';
    const match = /\((\d+) piezas\)/.exec(unidad);
    if (match) {
        equivalencia = parseInt(match[1]);
        equivalenciaStr = `1 bolsa = ${equivalencia} piezas`;
    }
    const precioBolsa = parseFloat($(this).data('precio')) || 0;
    let precioFinal = '';
    if (equivalencia > 1 && precioBolsa > 0) {
        precioFinal = (precioBolsa / equivalencia).toFixed(4);
    } else {
        precioFinal = precioBolsa;
    }
    const insumo = {
        insumo_id: $(this).data('id'),
        nombre: $(this).data('nombre'),
        categoria: $(this).data('categoria'),
        proveedor: $(this).data('proveedor'),
        stock: equivalencia > 1 ? Math.floor($(this).data('stock') * equivalencia) : $(this).data('stock'),
        cantidad: 1,
        precio: precioFinal,
        equivalencia: equivalencia,
        equivalenciaStr: equivalenciaStr,
        unidad: unidad
    };
    // Evitar duplicados
    if (insumosCotizacion.some(i => i.insumo_id == insumo.insumo_id)) {
        mostrarNotificacion('Este insumo ya está agregado.', 'warning');
        return;
    }
    insumosCotizacion.push(insumo);
    renderTablaInsumos();
    guardarBorrador();
    $('#buscador_insumo').val('');
    $('#sugerencias_insumos').hide();
});
function renderTablaInsumos() {
    let html = '';
    let subtotal = 0;
    insumosCotizacion.forEach((ins, i) => {
        if (typeof ins.iva === 'undefined') ins.iva = false;
        const sub = (parseFloat(ins.precio) || 0) * (parseFloat(ins.cantidad) || 1);
        const ivaMonto = ins.iva ? sub * 0.16 : 0;
        subtotal += sub;
        html += `
            <tr>
                <td>${ins.nombre}${ins.equivalenciaStr ? `<br><small class='text-muted'>${ins.equivalenciaStr}</small>` : ''}</td>
                <td style="text-align:center;">
                    ${ins.paquete_id ? `<input type='checkbox' class='sync-checkbox-insumo' data-index='${i}' ${ins.sincronizado !== false ? 'checked' : ''} title='Sincronizar con principal'> <i class='bi bi-link-45deg'></i>` : ''}
                </td>
                <td>${ins.proveedor || ''}</td>
                <td>${ins.stock}</td>
                <td><input type="number" min="1" step="1" value="${ins.cantidad}" class="form-control form-control-sm cantidad-insumo-input" data-index="${i}" data-paquete-id="${ins.paquete_id || ''}" data-tipo-paquete="${ins.tipo_paquete || ''}" style="width: 80px;"></td>
                <td><input type="number" min="0" step="0.0001" value="${ins.precio || ''}" class="form-control form-control-sm precio-insumo-input" data-index="${i}" style="width: 110px;"></td>
                <td>$${sub.toFixed(2)}</td>
                <td>
                    <label class="form-check-label" style="font-size:0.95em;">
                        <input type="checkbox" class="form-check-input iva-toggle-insumo" data-index="${i}" ${ins.iva ? 'checked' : ''}> IVA 16%
                    </label>
                    ${ins.iva ? `<span class='badge bg-warning ms-1'>IVA: $${ivaMonto.toFixed(2)}</span>` : ''}
                </td>
                <td><button type="button" class="btn btn-danger btn-sm btn-eliminar-insumo" data-idx="${i}"><i class="bi bi-trash"></i></button></td>
            </tr>
        `;
    });
    $('#tablaInsumosCotizacion tbody').html(html);
    recalcularTotales();
// Evento para toggle IVA en productos
$(document).on('change', '.iva-toggle-producto', function() {
    const index = parseInt($(this).data('index'));
    productosCotizacion[index].iva = this.checked;
    renderTablaProductos();
    guardarBorrador();
});
// Evento para toggle IVA en insumos
$(document).on('change', '.iva-toggle-insumo', function() {
    const index = parseInt($(this).data('index'));
    insumosCotizacion[index].iva = this.checked;
    renderTablaInsumos();
    guardarBorrador();
});
}
$(document).on('input', '.cantidad-insumo-input', function() {
    const index = parseInt($(this).data('index'));
    let value = Math.max(1, Math.round($(this).val()));
    insumosCotizacion[index].cantidad = value;
    if (!value || isNaN(value) || value <= 0) {
        $(this).addClass('is-invalid');
    } else {
        $(this).removeClass('is-invalid');
    }
    recalcularTotales();
});
$(document).on('blur', '.cantidad-insumo-input', function() {
    const index = parseInt($(this).data('index'));
    let value = Math.max(1, Math.round($(this).val()));
    insumosCotizacion[index].cantidad = value;
    $(this).val(value);
    // Si es principal de paquete, sincronizar relacionados
    const ins = insumosCotizacion[index];
    if (ins && ins.paquete_id && ins.tipo_paquete === 'principal') {
        sincronizarCantidadesPaqueteV2(ins.paquete_id);
    } else {
        renderTablaInsumos();
    }
});
$(document).on('input', '.precio-insumo-input', function() {
    const index = parseInt($(this).data('index'));
    let value = $(this).val();
    insumosCotizacion[index].precio = value;
    if (!value || isNaN(value) || value < 0) {
        $(this).addClass('is-invalid');
    } else {
        $(this).removeClass('is-invalid');
    }
    recalcularTotales();
});
$(document).on('blur', '.precio-insumo-input', function() {
    const index = parseInt($(this).data('index'));
    let value = parseFloat($(this).val()) || 0;
    if (value < 0) value = 0;
    insumosCotizacion[index].precio = value;
    $(this).val(value);
    renderTablaInsumos();
});
$(document).on('click', '.btn-eliminar-insumo', function() {
    const idx = $(this).data('idx');
    insumosCotizacion.splice(idx, 1);
    renderTablaInsumos();
    guardarBorrador();
});
// Incluir insumos en el cálculo de totales
function recalcularTotales() {
    const subtotalProductos = productosCotizacion.reduce((sum, p) => sum + ((parseFloat(p.precio)||0)*(parseFloat(p.cantidad)||1)), 0);
    const subtotalServicios = serviciosCotizacion.reduce((sum, s) => sum + ((parseFloat(s.precio)||0)*(parseFloat(s.cantidad)||1)), 0);
    const subtotalInsumos = insumosCotizacion.reduce((sum, i) => sum + ((parseFloat(i.precio)||0)*(parseFloat(i.cantidad)||1)), 0);
    const ivaProductos = productosCotizacion.reduce((sum, p) => {
        const sub = (parseFloat(p.precio)||0)*(parseFloat(p.cantidad)||1);
        return sum + (p.iva ? sub * 0.16 : 0);
    }, 0);
    const ivaInsumos = insumosCotizacion.reduce((sum, i) => {
        const sub = (parseFloat(i.precio)||0)*(parseFloat(i.cantidad)||1);
        return sum + (i.iva ? sub * 0.16 : 0);
    }, 0);
    const subtotal = subtotalProductos + subtotalServicios + subtotalInsumos;
    const descuentoPorcentaje = parseFloat($('#descuento_porcentaje').val()) || 0;
    const descuentoMonto = subtotal * descuentoPorcentaje / 100;
    const total = subtotal - descuentoMonto;
    const totalIVA = ivaProductos + ivaInsumos;
    $('#subtotal').val(`$${subtotal.toFixed(2)}`);
    $('#descuento_monto').val(`$${descuentoMonto.toFixed(2)}`);
    $('#total').val(`$${total.toFixed(2)}`);
    // Mostrar IVA total en el resumen si existe el campo
    if ($('#iva_total').length) {
        $('#iva_total').val(`$${totalIVA.toFixed(2)}`);
    }
// Agregar campo visual de IVA total en el resumen si no existe
$(document).ready(function() {
    if ($('#iva_total').length === 0) {
        const ivaInput = `<div class="col-md-4"><label class="form-label">IVA 16% Total</label><input type="text" id="iva_total" class="form-control" readonly value="$0.00"></div>`;
        // Busca el resumen y lo inserta antes del total
        const resumenRow = $('.section-title:contains("Resumen")').parent().find('.row.g-3');
        if (resumenRow.length) {
            resumenRow.append(ivaInput);
        }
    }
});
}

// Evento para check de sincronización en productos
$(document).on('change', '.sync-checkbox', function() {
    const index = parseInt($(this).data('index'));
    productosCotizacion[index].sincronizado = this.checked;
});
// Evento para check de sincronización en servicios
$(document).on('change', '.sync-checkbox-servicio', function() {
    const index = parseInt($(this).data('index'));
    serviciosCotizacion[index].sincronizado = this.checked;
});
// Evento para check de sincronización en insumos
$(document).on('change', '.sync-checkbox-insumo', function() {
    const index = parseInt($(this).data('index'));
    insumosCotizacion[index].sincronizado = this.checked;
});
</script>
</body>
</html>