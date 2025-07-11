<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Verificar si las tablas de plantillas existen
$table_exists = $mysqli->query("SHOW TABLES LIKE 'plantillas_cotizaciones'")->num_rows > 0;

if (!$table_exists) {
    $error = 'Las plantillas de cotizaciones no están disponibles en esta versión del sistema.';
    header('Location: plantillas.php');
    exit;
}

// Obtener datos para selects
$categorias = $mysqli->query("SELECT * FROM categorias_plantillas ORDER BY nombre");
$clientes = $mysqli->query("SELECT cliente_id, nombre, telefono FROM clientes ORDER BY nombre");
$productos = $mysqli->query("
    SELECT 
        p.product_id, 
        p.product_name, 
        p.sku, 
        p.price, 
        p.tipo_gestion,
        c.name as categoria,
        CASE 
            WHEN p.tipo_gestion = 'bobina' THEN 
                COALESCE(SUM(b.metros_actuales), 0)
            ELSE 
                p.quantity
        END as stock_disponible
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN bobinas b ON p.product_id = b.product_id AND b.is_active = 1
    GROUP BY p.product_id, p.product_name, p.sku, p.price, p.tipo_gestion, c.name, p.quantity
    ORDER BY p.product_name ASC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $categoria = trim($_POST['categoria']);
    $tipo_servicio = trim($_POST['tipo_servicio']);
    $cliente_frecuente_id = $_POST['cliente_frecuente_id'] ?: null;
    $es_publica = isset($_POST['es_publica']) ? 1 : 0;
    $productos_json = $_POST['productos_json'] ?? '';
    $productos = json_decode($productos_json, true);
    
    if (!$nombre) {
        $error = 'El nombre de la plantilla es obligatorio.';
    } elseif (!$productos || !is_array($productos) || count($productos) == 0) {
        $error = 'Debes agregar al menos un producto a la plantilla.';
    }
    
    if (!$error) {
        $mysqli->begin_transaction();
        try {
            // Insertar plantilla
            $stmt = $mysqli->prepare("
                INSERT INTO plantillas_cotizaciones (nombre, descripcion, categoria, tipo_servicio, cliente_frecuente_id, user_id, es_publica) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
            $stmt->bind_param('ssssiii', $nombre, $descripcion, $categoria, $tipo_servicio, $cliente_frecuente_id, $user_id, $es_publica);
            $stmt->execute();
            $plantilla_id = $stmt->insert_id;
            $stmt->close();
            
            // Insertar productos de la plantilla
            foreach ($productos as $prod) {
                $stmt = $mysqli->prepare("
                    INSERT INTO plantillas_productos (plantilla_id, product_id, nombre_producto, sku, cantidad, precio_unitario, orden) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $product_id = $prod['product_id'] ?? null;
                $nombre_producto = $prod['nombre'];
                $sku = $prod['sku'] ?? '';
                $cantidad = $prod['cantidad'];
                $precio = $prod['precio'];
                $orden = $prod['orden'] ?? 0;
                
                $stmt->bind_param('iissddi', $plantilla_id, $product_id, $nombre_producto, $sku, $cantidad, $precio, $orden);
                $stmt->execute();
                $stmt->close();
            }
            
            $mysqli->commit();
            $success = "Plantilla '$nombre' creada exitosamente con " . count($productos) . " productos.";
            
            // Redirigir después de 2 segundos
            header("refresh:2;url=plantillas.php");
            
        } catch (Exception $e) {
            $mysqli->rollback();
            $error = 'Error al crear la plantilla: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Plantilla | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body { background: #f4f6fb; }
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
        .form-section { 
            background: #fff; 
            border-radius: 12px; 
            padding: 24px; 
            margin-bottom: 24px; 
            box-shadow: 0 2px 12px rgba(18,24,102,0.07); 
        }
        .section-title { 
            font-size: 1.3rem; 
            font-weight: 700; 
            color: #121866; 
            margin-bottom: 18px; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
        }
        .select2-container--default .select2-selection--single { height: 38px; }
        .select2-selection__rendered { line-height: 38px !important; }
        .select2-selection__arrow { height: 38px !important; }
        .table thead th { background: #121866; color: #fff; }
        .badge-stock { font-size: 0.85rem; }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-plus-circle"></i> Crear Nueva Plantilla</h2>
            <a href="plantillas.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="formCrearPlantilla" autocomplete="off">
            <!-- Información básica -->
            <div class="form-section">
                <div class="section-title"><i class="bi bi-info-circle"></i> Información de la Plantilla</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre de la plantilla *</label>
                        <input type="text" class="form-control" name="nombre" id="nombre" required>
                    </div>
                    <div class="col-md-6">
                        <label for="categoria" class="form-label">Categoría</label>
                        <select class="form-select" name="categoria" id="categoria">
                            <option value="">Seleccionar categoría</option>
                            <?php if ($categorias) while ($cat = $categorias->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($cat['nombre']) ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="tipo_servicio" class="form-label">Tipo de servicio</label>
                        <input type="text" class="form-control" name="tipo_servicio" id="tipo_servicio" placeholder="Ej: Instalación, Mantenimiento, etc.">
                    </div>
                    <div class="col-md-6">
                        <label for="cliente_frecuente_id" class="form-label">Cliente frecuente</label>
                        <select class="form-select" name="cliente_frecuente_id" id="cliente_frecuente_id">
                            <option value="">Sin cliente específico</option>
                            <?php if ($clientes) while ($cl = $clientes->fetch_assoc()): ?>
                                <option value="<?= $cl['cliente_id'] ?>"><?= htmlspecialchars($cl['nombre']) ?> (<?= htmlspecialchars($cl['telefono']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" id="descripcion" rows="3" placeholder="Describe para qué sirve esta plantilla..."></textarea>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="es_publica" id="es_publica">
                            <label class="form-check-label" for="es_publica">
                                Hacer plantilla pública (visible para todos los usuarios)
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos -->
            <div class="form-section">
                <div class="section-title"><i class="bi bi-box"></i> Productos de la Plantilla</div>
                <div class="mb-3">
                    <label for="buscador_producto" class="form-label">Buscar producto en inventario</label>
                    <input type="text" class="form-control" id="buscador_producto" placeholder="Nombre, SKU o descripción...">
                    <div id="sugerencias_productos" class="list-group mt-1"></div>
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-outline-primary" id="btnAltaRapidaProducto">
                        <i class="bi bi-plus-circle"></i> Alta rápida de producto
                    </button>
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
                        <div class="col-md-3">
                            <button type="button" class="btn btn-success" id="btnAgregarProductoRapido">
                                <i class="bi bi-check-circle"></i> Agregar producto
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive mt-4">
                    <table class="table table-striped align-middle" id="tablaProductosPlantilla">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>SKU</th>
                                <th>Categoría</th>
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

            <!-- Resumen -->
            <div class="form-section">
                <div class="section-title"><i class="bi bi-receipt"></i> Resumen</div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Total productos</label>
                        <input type="text" class="form-control" id="total_productos" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valor total</label>
                        <input type="text" class="form-control" id="valor_total" readonly>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mb-5">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle"></i> Crear Plantilla
                </button>
            </div>
        </form>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.querySelector('.sidebar-cotizaciones').classList.add('active');
        
        // Productos del inventario
        const productosArray = <?= json_encode($productos ? $productos->fetch_all(MYSQLI_ASSOC) : []) ?>;
        let productosPlantilla = [];

        // Función para normalizar texto
        function normalizarTexto(texto) {
            return texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
        }

        // Buscador de productos
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
                    sugerencias += `<button type='button' class='list-group-item list-group-item-action' data-id='${p.product_id}' data-nombre='${p.product_name}' data-sku='${p.sku}' data-categoria='${p.categoria||''}' data-stock='${p.stock_disponible}' data-precio='${p.price}'>
                        <b>${p.product_name}</b> <span class='badge bg-${p.stock_disponible > 0 ? 'success' : 'danger'} ms-2'>Stock: ${p.stock_disponible}</span><br>
                        <small>SKU: ${p.sku || '-'} | $${parseFloat(p.price).toFixed(2)}</small>
                    </button>`;
                });
            }
            $('#sugerencias_productos').html(sugerencias).show();
        });

        $('#sugerencias_productos').on('click', 'button', function() {
            agregarProductoATabla({
                product_id: $(this).data('id'),
                nombre: $(this).data('nombre'),
                sku: $(this).data('sku'),
                categoria: $(this).data('categoria'),
                stock: $(this).data('stock'),
                cantidad: 1,
                precio: $(this).data('precio')
            });
            $('#buscador_producto').val('');
            $('#sugerencias_productos').hide();
        });

        // Alta rápida de producto
        $('#btnAltaRapidaProducto').on('click', function() {
            $('#altaRapidaProductoForm').toggle();
        });

        $('#btnAgregarProductoRapido').on('click', function() {
            const nombre = $('#nuevo_nombre_producto').val();
            const precio = parseFloat($('#nuevo_precio_producto').val()) || 0;
            const cantidad = parseInt($('#nuevo_cantidad_producto').val()) || 1;
            if (!nombre || !precio || !cantidad) {
                alert('Completa nombre, precio y cantidad para el producto.');
                return;
            }
            agregarProductoATabla({
                product_id: null,
                nombre: nombre,
                sku: $('#nuevo_sku_producto').val(),
                categoria: '',
                stock: '',
                cantidad: cantidad,
                precio: precio
            });
            $('#nuevo_nombre_producto, #nuevo_sku_producto, #nuevo_precio_producto, #nuevo_cantidad_producto').val('');
            $('#altaRapidaProductoForm').hide();
        });

        function agregarProductoATabla(prod) {
            productosPlantilla.push(prod);
            renderTablaProductos();
        }

        $(document).on('click', '.btn-eliminar-producto', function() {
            const idx = $(this).data('idx');
            productosPlantilla.splice(idx, 1);
            renderTablaProductos();
        });

        function renderTablaProductos() {
            let html = '';
            let totalProductos = productosPlantilla.length;
            let valorTotal = 0;
            
            productosPlantilla.forEach((p, i) => {
                const sub = (parseFloat(p.precio) || 0) * (parseInt(p.cantidad) || 1);
                valorTotal += sub;
                
                html += `
                    <tr>
                        <td>${p.nombre}</td>
                        <td>${p.sku || ''}</td>
                        <td>${p.categoria || ''}</td>
                        <td>${p.stock_disponible}</td>
                        <td>
                            <input type="number" 
                                   min="1" 
                                   step="1" 
                                   value="${p.cantidad}" 
                                   class="form-control form-control-sm cantidad-input" 
                                   data-index="${i}" 
                                   style="width: 80px;">
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
            
            $('#tablaProductosPlantilla tbody').html(html);
            $('#total_productos').val(totalProductos);
            $('#valor_total').val(`$${valorTotal.toFixed(2)}`);
        }

        // Eventos para cantidad y precio
        $(document).on('input', '.cantidad-input', function() {
            const index = parseInt($(this).data('index'));
            const value = parseInt($(this).val()) || 1;
            
            if (productosPlantilla[index]) {
                productosPlantilla[index].cantidad = value;
                renderTablaProductos();
            }
        });

        $(document).on('input', '.precio-input', function() {
            const index = parseInt($(this).data('index'));
            const value = parseFloat($(this).val()) || 0;
            
            if (productosPlantilla[index]) {
                productosPlantilla[index].precio = value;
                renderTablaProductos();
            }
        });

        // Validación del formulario
        $('#formCrearPlantilla').on('submit', function(e) {
            if (productosPlantilla.length === 0) {
                e.preventDefault();
                alert('Debes agregar al menos un producto a la plantilla.');
                return false;
            }
            $('<input>').attr({type:'hidden', name:'productos_json', value: JSON.stringify(productosPlantilla)}).appendTo(this);
            $(this).find('button[type=submit]').prop('disabled', true).text('Creando...');
        });
    </script>
</body>
</html> 