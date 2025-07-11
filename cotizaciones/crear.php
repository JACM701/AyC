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
    $productos = json_decode($productos_json, true);
    $cliente_id = $_POST['cliente_id'] ?? '';
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $cliente_telefono = trim($_POST['cliente_telefono'] ?? '');
    $cliente_ubicacion = trim($_POST['cliente_ubicacion'] ?? '');
    $cliente_email = trim($_POST['cliente_email'] ?? '');
    if (!$productos || !is_array($productos) || count($productos) == 0) {
        $error = 'Debes agregar al menos un producto a la cotización.';
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
        foreach ($productos as $prod) {
            $subtotal += floatval($prod['precio']) * intval($prod['cantidad']);
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
                    <div class="col-md-2">
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
                            <th>Categoría</th>
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
<script src="paquetes.js"></script>
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
}
$(document).on('click', '.btn-eliminar-producto', function() {
    const idx = $(this).data('idx');
    productosCotizacion.splice(idx, 1);
    renderTablaProductos();
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
        html += `
            <tr>
                <td>${p.nombre}</td>
                <td>${p.sku || ''}</td>
                <td>${p.categoria || ''}</td>
                <td>${p.proveedor || ''}</td>
                <td>${p.stock}</td>
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
                <td>$${sub.toFixed(2)}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm btn-eliminar-producto" data-idx="${i}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    $('#tablaProductosCotizacion tbody').html(html);
    $('#subtotal').val(`$${subtotal.toFixed(2)}`);
    recalcularTotales();
}

// Eventos para cantidad
$(document).on('input', '.cantidad-input', function() {
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
    const value = parseFloat($(this).val()) || 0;
    
    if (productosCotizacion[index]) {
        productosCotizacion[index].precio = value;
        renderTablaProductos();
    }
});

$(document).on('focus', '.precio-input', function() {
    $(this).select();
});
function recalcularTotales() {
    const subtotal = productosCotizacion.reduce((sum, p) => sum + ((parseFloat(p.precio)||0)*(parseFloat(p.cantidad)||1)), 0);
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
    
    if (productosCotizacion.length === 0) {
        error = 'Debes agregar al menos un producto a la cotización.';
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
    $(this).find('button[type=submit]').prop('disabled', true).text('Guardando...');
});

document.getElementById('btnGestionarPaquetes').addEventListener('click', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalPaquetes'));
    renderPaquetesPanel();
    modal.show();
});

function renderPaquetesPanel() {
    const panel = document.getElementById('paquetesPanel');
    const paquetes = window.PaquetesCotizacion.getPaquetes();
    let html = '';
    if (paquetes.length === 0) {
        html += '<div class="alert alert-info">No hay paquetes definidos. Crea uno nuevo para empezar.</div>';
    } else {
        html += '<ul class="list-group mb-3">';
        paquetes.forEach((paq, idx) => {
            html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                <span><b>${paq.nombre}</b> (${paq.items.length} productos)</span>
                <span>
                    <button class="btn btn-sm btn-outline-primary me-2" onclick="aplicarPaqueteCotizacion(${idx}); return false;" type="button">
                        <i class="bi bi-play"></i> Aplicar
                    </button>
                    <button class="btn btn-sm btn-outline-secondary me-2" onclick="editarPaquete(${idx}); return false;" type="button">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarPaquete(${idx}); return false;" type="button">
                        <i class="bi bi-trash"></i>
                    </button>
                </span>
            </li>`;
        });
        html += '</ul>';
    }
    html += '<button class="btn btn-success" onclick="nuevoPaquete(); return false;" type="button"><i class="bi bi-plus-circle"></i> Nuevo paquete</button>';
    panel.innerHTML = html;
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
    const paqueteId = 'paq_' + Date.now();
    paquete.items.forEach(item => {
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
                tipo_paquete: item.tipo,
                factor_paquete: item.factor
            });
        }
    });
    
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
    
    // Ahora renderizar la tabla
    renderTablaProductos();
    
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
    // Encuentra el principal
    const principal = productosCotizacion.find(p => p.paquete_id === paqueteId && p.tipo_paquete === 'principal');
    if (!principal) return;
    const cantidadPrincipal = parseFloat(principal.cantidad) || 1;
    productosCotizacion.forEach((p, idx) => {
        if (p.paquete_id === paqueteId && p.tipo_paquete === 'relacionado') {
            const factor = parseFloat(p.factor_paquete) || 1;
            p.cantidad = p.tipo_gestion === 'bobina' ? (cantidadPrincipal * factor).toFixed(2) : Math.round(cantidadPrincipal * factor);
            // Actualizar input visual
            const input = document.querySelector(`.cantidad-input[data-index='${idx}']`);
            if (input) input.value = p.cantidad;
        }
    });
    renderTablaProductos();
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
            if (window._paqEdit.items.some(i => i.product_id == prod.product_id)) return;
            window._paqEdit.items.push({ product_id: prod.product_id, nombre: prod.product_name, tipo: 'relacionado', factor: 1 });
            window._paqRender();
        };
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
            if (window._paqEdit.items.some(i => i.product_id == prod.product_id)) return;
            window._paqEdit.items.push({ product_id: prod.product_id, nombre: prod.product_name, tipo: 'relacionado', factor: 1 });
            window._paqRender();
        };
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
</script>
</body>
</html> 