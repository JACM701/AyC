<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Obtener productos del inventario para el selector
$productos_query = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.quantity > 0 
    ORDER BY p.product_name ASC
";
$productos = $mysqli->query($productos_query);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_nombre = trim($_POST['cliente_nombre']);
    $cliente_telefono = trim($_POST['cliente_telefono']);
    $cliente_ubicacion = trim($_POST['cliente_ubicacion']);
    $fecha_cotizacion = $_POST['fecha_cotizacion'];
    $validez_dias = intval($_POST['validez_dias']);
    $condiciones_pago = trim($_POST['condiciones_pago']);
    $observaciones = trim($_POST['observaciones']);
    $descuento_porcentaje = floatval($_POST['descuento_porcentaje']);
    
    if ($cliente_nombre && $fecha_cotizacion) {
        // Insertar cotización
        $stmt = $mysqli->prepare("
            INSERT INTO cotizaciones (
                cliente_nombre, cliente_telefono, cliente_ubicacion, 
                fecha_cotizacion, validez_dias, descuento_porcentaje,
                condiciones_pago, observaciones, usuario_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $usuario_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
        $stmt->bind_param('ssssidssi', 
            $cliente_nombre, $cliente_telefono, $cliente_ubicacion,
            $fecha_cotizacion, $validez_dias, $descuento_porcentaje,
            $condiciones_pago, $observaciones, $usuario_id
        );
        
        if ($stmt->execute()) {
            $cotizacion_id = $stmt->insert_id;
            
            // Insertar productos
            $productos_data = json_decode($_POST['productos_json'], true);
            $orden = 1;
            
            foreach ($productos_data as $producto) {
                $stmt_producto = $mysqli->prepare("
                    INSERT INTO cotizaciones_productos (
                        cotizacion_id, product_id, descripcion, imagen_url,
                        cantidad, precio_unitario, precio_total, costo_total, orden
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $product_id = $producto['product_id'] ?: null;
                $descripcion = $producto['descripcion'];
                $imagen_url = $producto['imagen_url'];
                $cantidad = intval($producto['cantidad']);
                $precio_unitario = floatval($producto['precio_unitario']);
                $precio_total = floatval($producto['precio_total']);
                $costo_total = floatval($producto['costo_total']);
                
                $stmt_producto->bind_param('iissddddi',
                    $cotizacion_id, $product_id, $descripcion, $imagen_url,
                    $cantidad, $precio_unitario, $precio_total, $costo_total, $orden
                );
                $stmt_producto->execute();
                $orden++;
            }
            
            // Actualizar totales
            $total_query = "
                UPDATE cotizaciones 
                SET subtotal = (SELECT SUM(precio_total) FROM cotizaciones_productos WHERE cotizacion_id = ?),
                    descuento_monto = subtotal * descuento_porcentaje / 100,
                    total = subtotal - (subtotal * descuento_porcentaje / 100)
                WHERE cotizacion_id = ?
            ";
            $stmt_total = $mysqli->prepare($total_query);
            $stmt_total->bind_param('ii', $cotizacion_id, $cotizacion_id);
            $stmt_total->execute();
            
            $success = "Cotización creada exitosamente.";
            // Redirigir a la vista de la cotización
            header("Location: ver.php?id=$cotizacion_id");
            exit;
        } else {
            $error = "Error al crear la cotización: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Por favor, completa todos los campos requeridos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Cotización | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
        }
        .producto-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #121866;
        }
        .producto-item .row {
            align-items: center;
        }
        .producto-imagen {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
        }
        .btn-eliminar-producto {
            color: #dc3545;
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
        }
        .btn-eliminar-producto:hover {
            color: #c82333;
        }
        .producto-selector {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .producto-option {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
        }
        .producto-option:hover {
            background: #f8f9fa;
        }
        .producto-option:last-child {
            border-bottom: none;
        }
        .producto-option img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
        }
        .producto-info {
            flex: 1;
        }
        .producto-nombre {
            font-weight: 600;
            color: #121866;
        }
        .producto-detalles {
            font-size: 0.85rem;
            color: #666;
        }
        .producto-stock {
            font-weight: 600;
            color: #28a745;
        }
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
            <h2><i class="bi bi-plus-circle"></i> Crear Nueva Cotización</h2>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="formCotizacion">
            <!-- Datos del cliente -->
            <div class="form-section">
                <h5 class="mb-3"><i class="bi bi-person"></i> Datos del Cliente</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nombre del cliente *</label>
                            <input type="text" name="cliente_nombre" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="cliente_telefono" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Ubicación</label>
                            <input type="text" name="cliente_ubicacion" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Fecha de cotización *</label>
                            <input type="date" name="fecha_cotizacion" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Validez (días)</label>
                            <input type="number" name="validez_dias" class="form-control" value="30" min="1">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos -->
            <div class="form-section">
                <h5 class="mb-3"><i class="bi bi-box"></i> Productos</h5>
                
                <!-- Selector de productos -->
                <div class="producto-selector">
                    <label class="form-label">Buscar producto del inventario:</label>
                    <input type="text" id="buscarProducto" class="form-control" placeholder="Buscar por nombre, SKU, categoría...">
                    <div id="resultadosProductos" class="mt-2" style="max-height: 200px; overflow-y: auto;"></div>
                </div>

                <!-- Lista de productos seleccionados -->
                <div id="productosSeleccionados"></div>
                
                <button type="button" class="btn btn-outline-primary" onclick="agregarProductoManual()">
                    <i class="bi bi-plus-circle"></i> Agregar producto manual
                </button>
            </div>

            <!-- Totales y condiciones -->
            <div class="form-section">
                <h5 class="mb-3"><i class="bi bi-calculator"></i> Totales y Condiciones</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Descuento (%)</label>
                            <input type="number" name="descuento_porcentaje" id="descuentoPorcentaje" class="form-control" value="0" min="0" max="100" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Subtotal</label>
                            <input type="text" id="subtotal" class="form-control" readonly value="$0.00">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Descuento</label>
                            <input type="text" id="descuentoMonto" class="form-control" readonly value="$0.00">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Total</label>
                            <input type="text" id="total" class="form-control" readonly value="$0.00">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Condiciones de pago</label>
                            <textarea name="condiciones_pago" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="productos_json" id="productosJson" value="[]">
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Crear Cotización
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
            </div>
        </form>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-cotizaciones').classList.add('active');
        
        let productosSeleccionados = [];
        let productosInventario = <?= json_encode($productos->fetch_all(MYSQLI_ASSOC)) ?>;
        
        // Búsqueda de productos
        document.getElementById('buscarProducto').addEventListener('input', function() {
            const busqueda = this.value.toLowerCase();
            const resultados = document.getElementById('resultadosProductos');
            
            if (busqueda.length < 2) {
                resultados.innerHTML = '';
                return;
            }
            
            const filtrados = productosInventario.filter(p => 
                p.product_name.toLowerCase().includes(busqueda) ||
                p.sku.toLowerCase().includes(busqueda) ||
                (p.category_name && p.category_name.toLowerCase().includes(busqueda))
            );
            
            resultados.innerHTML = filtrados.map(p => `
                <div class="producto-option" onclick="seleccionarProducto(${p.product_id})">
                    <img src="${p.image || '../assets/img/LogoWeb.png'}" alt="${p.product_name}">
                    <div class="producto-info">
                        <div class="producto-nombre">${p.product_name}</div>
                        <div class="producto-detalles">
                            SKU: ${p.sku} | ${p.category_name || 'Sin categoría'} | 
                            <span class="producto-stock">Stock: ${p.quantity}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        });
        
        function seleccionarProducto(productId) {
            const producto = productosInventario.find(p => p.product_id == productId);
            if (producto) {
                agregarProducto({
                    product_id: producto.product_id,
                    descripcion: producto.product_name,
                    imagen_url: producto.image || '',
                    cantidad: 1,
                    precio_unitario: parseFloat(producto.price),
                    precio_total: parseFloat(producto.price),
                    costo_total: 0
                });
                document.getElementById('buscarProducto').value = '';
                document.getElementById('resultadosProductos').innerHTML = '';
            }
        }
        
        function agregarProducto(producto) {
            productosSeleccionados.push(producto);
            actualizarProductosSeleccionados();
            recalcularTotales();
        }
        
        function agregarProductoManual() {
            agregarProducto({
                product_id: null,
                descripcion: '',
                imagen_url: '',
                cantidad: 1,
                precio_unitario: 0,
                precio_total: 0,
                costo_total: 0
            });
        }
        
        function eliminarProducto(index) {
            productosSeleccionados.splice(index, 1);
            actualizarProductosSeleccionados();
            recalcularTotales();
        }
        
        function actualizarProductosSeleccionados() {
            const container = document.getElementById('productosSeleccionados');
            container.innerHTML = productosSeleccionados.map((p, index) => `
                <div class="producto-item">
                    <div class="row">
                        <div class="col-md-1">
                            <img src="${p.imagen_url || '../assets/img/LogoWeb.png'}" class="producto-imagen" alt="Producto">
                        </div>
                        <div class="col-md-4">
                            <textarea class="form-control" placeholder="Descripción del producto" 
                                onchange="actualizarProducto(${index}, 'descripcion', this.value)">${p.descripcion}</textarea>
                        </div>
                        <div class="col-md-1">
                            <input type="number" class="form-control" placeholder="Cant." value="${p.cantidad}" min="1"
                                onchange="actualizarProducto(${index}, 'cantidad', this.value)">
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control" placeholder="Precio unit." value="${p.precio_unitario}" step="0.01"
                                onchange="actualizarProducto(${index}, 'precio_unitario', this.value)">
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" value="$${p.precio_total.toFixed(2)}" readonly>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn-eliminar-producto" onclick="eliminarProducto(${index})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
            
            document.getElementById('productosJson').value = JSON.stringify(productosSeleccionados);
        }
        
        function actualizarProducto(index, campo, valor) {
            productosSeleccionados[index][campo] = valor;
            if (campo === 'cantidad' || campo === 'precio_unitario') {
                const cantidad = parseFloat(productosSeleccionados[index].cantidad) || 0;
                const precio = parseFloat(productosSeleccionados[index].precio_unitario) || 0;
                productosSeleccionados[index].precio_total = cantidad * precio;
                actualizarProductosSeleccionados();
                recalcularTotales();
            }
        }
        
        function recalcularTotales() {
            const subtotal = productosSeleccionados.reduce((sum, p) => sum + (p.precio_total || 0), 0);
            const descuentoPorcentaje = parseFloat(document.getElementById('descuentoPorcentaje').value) || 0;
            const descuentoMonto = subtotal * descuentoPorcentaje / 100;
            const total = subtotal - descuentoMonto;
            
            document.getElementById('subtotal').value = `$${subtotal.toFixed(2)}`;
            document.getElementById('descuentoMonto').value = `$${descuentoMonto.toFixed(2)}`;
            document.getElementById('total').value = `$${total.toFixed(2)}`;
        }
        
        document.getElementById('descuentoPorcentaje').addEventListener('input', recalcularTotales);
    </script>
</body>
</html> 