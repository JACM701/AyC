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
        p.cost_price,
        p.description,
        p.tipo_gestion,
        p.image,
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
    GROUP BY p.product_id, p.product_name, p.sku, p.price, p.cost_price, p.description, p.tipo_gestion, p.image, c.name, s.name, p.quantity
    ORDER BY p.product_name ASC
");
$productos_array = $productos ? $productos->fetch_all(MYSQLI_ASSOC) : [];
$categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name ASC");
$proveedores = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name ASC");

// Obtener servicios disponibles
$servicios = $mysqli->query("SELECT servicio_id, nombre, descripcion, categoria, precio, imagen FROM servicios WHERE is_active = 1 ORDER BY categoria, nombre ASC");
$servicios_array = $servicios ? $servicios->fetch_all(MYSQLI_ASSOC) : [];

// Obtener insumos disponibles
$insumos = $mysqli->query("SELECT i.insumo_id, i.nombre, i.categoria, i.imagen, s.name as proveedor, i.cantidad as stock, i.precio_unitario as precio, i.cost_price as costo
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
    
    // CONTINUAR CON EL PROCESAMIENTO NORMAL - NO SALIR
    
    // Leer IVA especial si viene del formulario
    $iva_especial = isset($_POST['condicion_iva']) ? trim($_POST['condicion_iva']) : '';
    $productos_json = $_POST['productos_json'] ?? '';
    $servicios_json = $_POST['servicios_json'] ?? '';
    $productos = json_decode($productos_json, true);
    $servicios = json_decode($servicios_json, true);
    $cliente_id = $_POST['cliente_id'] ?? '';
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $cliente_telefono = trim($_POST['cliente_telefono'] ?? '');
    $cliente_ubicacion = trim($_POST['cliente_ubicacion'] ?? '');
    $cliente_email = trim($_POST['cliente_email'] ?? '');
    $insumos_json = $_POST['insumos_json'] ?? '';
    $insumos = json_decode($insumos_json, true);
    if ((!$productos || !is_array($productos) || count($productos) == 0)
        && (!$servicios || !is_array($servicios) || count($servicios) == 0)
        && (!$insumos || !is_array($insumos) || count($insumos) == 0)) {
        $error = 'Debes agregar al menos un producto, servicio o insumo a la cotización.';
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
        $fecha_cotizacion = $_POST['fecha_cotizacion'] ?? date('Y-m-d');
        $validez_dias = isset($_POST['validez_dias']) ? intval($_POST['validez_dias']) : 30;
        $condiciones_pago = isset($_POST['condiciones_pago']) ? trim($_POST['condiciones_pago']) : '';
        $condiciones_pago = $condiciones_pago ?: (isset($_POST['condiciones_pago_forced']) ? trim($_POST['condiciones_pago_forced']) : '');
        $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
        $observaciones = $observaciones ?: (isset($_POST['observaciones_debug']) ? trim($_POST['observaciones_debug']) : '');
        $descuento_porcentaje = isset($_POST['descuento_porcentaje']) ? floatval($_POST['descuento_porcentaje']) : 0;
        $estado_id = isset($_POST['estado_id']) ? intval($_POST['estado_id']) : 2;
        $usuario_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
        
        // Si hay datos calculados en frontend, usarlos (más precisos)
        if (isset($_POST['descuento_porcentaje_frontend']) && !empty($_POST['descuento_porcentaje_frontend'])) {
            $descuento_porcentaje = floatval($_POST['descuento_porcentaje_frontend']);
        }
        
        // Tomar totales del frontend si están disponibles, sino calcular en backend
        if (isset($_POST['subtotal_frontend']) && isset($_POST['descuento_monto_frontend']) && isset($_POST['total_frontend'])) {
            // Usar valores calculados por el frontend
            $subtotal = floatval($_POST['subtotal_frontend']);
            $descuento_monto = floatval($_POST['descuento_monto_frontend']);
            $total = floatval($_POST['total_frontend']);
        } else {
            // Calcular totales en backend (fallback)
            $subtotal = 0;
            // Sumar productos
            foreach ($productos as $prod) {
                // 🎯 CÁLCULO CORRECTO DEL SUBTOTAL PARA BOBINAS
                $cantidad = floatval($prod['cantidad']); // Permitir decimales para bobinas
                $precio = floatval($prod['precio']);
                
                // Verificar si es bobina y calcular según el modo
                $esBobina = isset($prod['tipo_gestion']) && $prod['tipo_gestion'] === 'bobina';
                $modoPrecio = $prod['_modoPrecio'] ?? null;
                
                if ($esBobina && $modoPrecio === 'POR_BOBINA') {
                    // Para bobinas en modo POR_BOBINA: calcular según bobinas completas
                    $metrosPorBobina = 305;
                    $bobinasCompletas = $cantidad / $metrosPorBobina;
                    $subtotalProducto = $bobinasCompletas * $precio;
                } else {
                    // Para productos normales o bobinas en modo metros
                    $subtotalProducto = $cantidad * $precio;
                }
                
                $subtotal += $subtotalProducto;
            }
            // Sumar servicios
            foreach ($servicios as $serv) {
                $subtotal += floatval($serv['precio']) * floatval($serv['cantidad']);
            }
            // Sumar insumos
            $insumos_json = $_POST['insumos_json'] ?? '';
            $insumos = json_decode($insumos_json, true);
            if ($insumos && is_array($insumos)) {
                foreach ($insumos as $ins) {
                    $cantidad = floatval($ins['cantidad'] ?? 1);
                    $precio_unitario = floatval($ins['precio'] ?? 0);
                    $subtotal += $cantidad * $precio_unitario;
                }
            }
            $descuento_monto = $subtotal * $descuento_porcentaje / 100;
            $total_sin_iva = $subtotal - $descuento_monto;
            $iva_especial = isset($_POST['condicion_iva']) ? trim($_POST['condicion_iva']) : '';
            $iva_monto = 0;
            $total = $total_sin_iva;
            if ($iva_especial !== '' && is_numeric($iva_especial) && floatval($iva_especial) > 0) {
                $iva_val = floatval($iva_especial);
                if ($iva_val <= 1) {
                    $iva_monto = $total_sin_iva * $iva_val;
                } elseif ($iva_val > 1 && $iva_val <= 100) {
                    $iva_monto = $total_sin_iva * ($iva_val / 100);
                } else {
                    $iva_monto = $iva_val;
                }
                $total = $total_sin_iva + $iva_monto;
            }
        }
        
        $total_productos = count($productos);
        $total_servicios = count($servicios);
        
        // Guardar referencia del IVA en observaciones si se especificó
        $iva_especial = isset($_POST['condicion_iva']) ? trim($_POST['condicion_iva']) : '';
        if ($iva_especial !== '' && is_numeric($iva_especial) && floatval($iva_especial) > 0) {
            $observaciones = trim($observaciones);
            $observaciones = preg_replace('/\[IVA_ESPECIAL:[^\]]*\]/', '', $observaciones);
            $observaciones .= ' [IVA_ESPECIAL:' . $iva_especial . ']';
            $observaciones = trim($observaciones);
        }

        // Guardar descripciones personalizadas de productos en observaciones
        $descripcionesPersonalizadas = [];
        foreach ($productos as $prod) {
            if (isset($prod['description']) && !empty(trim($prod['description']))) {
                $product_id = $prod['product_id'] ?? null;
                if ($product_id) {
                    $descripcionesPersonalizadas[$product_id] = trim($prod['description']);
                }
            }
        }
        
        if (!empty($descripcionesPersonalizadas)) {
            $observaciones = trim($observaciones);
            // Eliminar cualquier referencia anterior de descripciones
            $observaciones = preg_replace('/\[DESCRIPCIONES:[^\]]*\]/', '', $observaciones);
            // Agregar las nuevas descripciones
            $observaciones .= ' [DESCRIPCIONES:' . base64_encode(json_encode($descripcionesPersonalizadas)) . ']';
            $observaciones = trim($observaciones);
        }

        // Guardar descripciones personalizadas de insumos en observaciones
        $descripcionesPersonalizadasInsumos = [];
        foreach ($insumos as $ins) {
            if (isset($ins['descripcion']) && !empty(trim($ins['descripcion']))) {
                $insumo_id = $ins['insumo_id'] ?? null;
                if ($insumo_id) {
                    $descripcionesPersonalizadasInsumos[$insumo_id] = trim($ins['descripcion']);
                }
            }
        }
        
        if (!empty($descripcionesPersonalizadasInsumos)) {
            $observaciones = trim($observaciones);
            // Eliminar cualquier referencia anterior de descripciones de insumos
            $observaciones = preg_replace('/\[DESCRIPCIONES_INSUMOS:[^\]]*\]/', '', $observaciones);
            // Agregar las nuevas descripciones de insumos
            $observaciones .= ' [DESCRIPCIONES_INSUMOS:' . base64_encode(json_encode($descripcionesPersonalizadasInsumos)) . ']';
            $observaciones = trim($observaciones);
        }

        
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
        
        // === SOLUCIÓN: ASEGURAR QUE USUARIO_ID NO SEA NULL ===
        if ($usuario_id === null) {
            $usuario_id = 1; // Default user ID
        }
        
        $stmt = $mysqli->prepare("INSERT INTO cotizaciones (numero_cotizacion, cliente_id, fecha_cotizacion, validez_dias, subtotal, descuento_porcentaje, descuento_monto, total, observaciones, condiciones_pago, estado_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            $error = 'Error en la preparación de la consulta: ' . $mysqli->error;
        } else {
            $stmt->bind_param('sisidddssiii', $numero_cotizacion, $cliente_id, $fecha_cotizacion, $validez_dias, $subtotal, $descuento_porcentaje, $descuento_monto, $total, $observaciones, $condiciones_pago, $estado_id, $usuario_id);
        
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
                    
                    $stmt_prod->bind_param('ssdiis', $prod['nombre'], $prod['sku'], $prod['precio'], $prod['cantidad'], $cat_id, $prov_id);
                    $stmt_prod->execute();
                    $product_id = $stmt_prod->insert_id;
                    $stmt_prod->close();
                }
                $stmt_cp = $mysqli->prepare("INSERT INTO cotizaciones_productos (cotizacion_id, product_id, cantidad, precio_unitario, precio_total) VALUES (?, ?, ?, ?, ?)");
                
                // 🎯 CÁLCULO CORRECTO DEL PRECIO TOTAL PARA BOBINAS
                // Para bobinas, necesitamos calcular el precio total según el modo de precio
                $cantidad = floatval($prod['cantidad']); // Usar floatval para permitir decimales (457.5m)
                $precio_unitario = floatval($prod['precio']);
                
                // Verificar si es bobina y tiene modo de precio especial
                $esBobina = isset($prod['tipo_gestion']) && $prod['tipo_gestion'] === 'bobina';
                $modoPrecio = $prod['_modoPrecio'] ?? null;
                
                if ($esBobina && $modoPrecio === 'POR_BOBINA') {
                    // Para bobinas en modo POR_BOBINA: calcular precio total según bobinas completas
                    $metrosPorBobina = 305; // Configuración estándar
                    $bobinasCompletas = $cantidad / $metrosPorBobina;
                    $precio_total = $bobinasCompletas * $precio_unitario;
                    
                    // DEBUG: Log para verificar cálculo
                    error_log("🎯 INSERTAR BOBINA: {$cantidad}m ÷ {$metrosPorBobina}m = {$bobinasCompletas} bobinas × \${$precio_unitario} = \${$precio_total}");
                } else {
                    // Para productos normales o bobinas en modo metros
                    $precio_total = $cantidad * $precio_unitario;
                }
                
                $stmt_cp->bind_param('iiddd', $cotizacion_id, $product_id, $cantidad, $precio_unitario, $precio_total);
                $stmt_cp->execute();
                $stmt_cp->close();
                // Descontar stock si estado es aprobada (ID 1)
                if ($estado_id == 1) {
                    // 🎯 USAR FLOATVAL PARA PERMITIR DESCUENTOS DE STOCK DECIMALES (BOBINAS)
                    $cantidad_descuento = floatval($prod['cantidad']);
                    $mysqli->query("UPDATE products SET quantity = quantity - " . $cantidad_descuento . " WHERE product_id = " . intval($product_id));
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
            
            // Enviar respuesta JSON para AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true, 'redirect_url' => "ver.php?id=$cotizacion_id"]);
            } else {
                header("Location: ver.php?id=$cotizacion_id");
            }
            exit;
            
        } else {
            $error = 'Error al guardar la cotización: ' . $stmt->error;
        }
        }
    }
    // Si hay un error, y es una petición AJAX, devolver el error en JSON
    if ($error && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
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
    <!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> -->
    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .main-content {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-top: 40px;
            margin-left: 250px;
            padding: 32px;
            width: calc(100vw - 250px);
            box-sizing: border-box;
        }
        .sidebar.collapsed ~ .main-content {
            margin-left: 70px !important;
            width: calc(100vw - 70px) !important;
            transition: margin-left 0.25s cubic-bezier(.4,2,.6,1), width 0.25s;
        }
        .form-section { 
            background: #ffffff;
            border-radius: 12px; 
            padding: 28px; 
            margin-bottom: 28px; 
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }
        .section-title { 
            font-size: 1.4rem; 
            font-weight: 700; 
            color: #2c3e50; 
            margin-bottom: 20px; 
            display: flex; 
            align-items: center; 
            gap: 10px;
        }
        /* Estilos para select nativo mejorado */
        #cliente_select {
            height: 42px !important; 
            border: 2px solid #dee2e6 !important;
            border-radius: 8px !important;
            transition: all 0.3s ease !important;
            box-shadow: none !important;
            outline: none !important;
            background: #fff !important;
            color: #495057 !important;
            font-size: 1rem !important;
            padding: 8px 16px !important;
            width: 100% !important;
            cursor: pointer !important;
            appearance: none !important;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right 12px center !important;
            background-size: 16px !important;
        }
        #cliente_select:focus {
            border-color: #007bff !important;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15) !important;
            outline: none !important;
        }
        #cliente_select:hover {
            border-color: #80bdff !important;
        }
        #cliente_select option {
            padding: 8px 12px !important;
            background: #fff !important;
            color: #495057 !important;
        }
        .form-control {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            color: #495057;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
        }
        .table thead th { 
            background: #343a40;
            color: #fff; 
            border: none;
            padding: 8px 12px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.3px;
        }
        .th-accion-dark {
            background: #343a40 !important;
            color: #fff !important;
            width: 50px !important;
            text-align: center;
            padding: 8px 6px !important;
        }
        /* Asegura que los td de la columna Acción tengan el mismo ancho */
        #tablaInsumosCotizacion td:last-child,
        #tablaProductosCotizacion td:last-child {
            width: 50px !important;
            text-align: center;
            padding: 6px !important;
        }
        
        /* Hacer todas las celdas más compactas */
        #tablaProductosCotizacion td,
        #tablaInsumosCotizacion td,
        #tablaServiciosCotizacion td {
            padding: 8px 10px !important;
            vertical-align: middle;
        }
        .badge-stock { 
            font-size: 0.85rem; 
        }
        /* Icono de búsqueda discreto en la tabla */
        .icon-buscar-google {
            color: #6c757d;
            font-size: 1.05em;
            margin-left: 6px;
            opacity: 0;
            cursor: pointer;
            transition: all 0.3s ease;
            vertical-align: middle;
        }
        #tablaProductosCotizacion td:hover .icon-buscar-google,
        #tablaInsumosCotizacion td:hover .icon-buscar-google {
            opacity: 1;
            color: #007bff;
            transform: scale(1.1);
        }
        .btn-primary {
            background: #007bff;
            border: #007bff;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.25);
        }
        .btn-primary:hover {
            background: #0056b3;
            border-color: #0056b3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.35);
        }
        .btn-outline-primary {
            border: 2px solid #007bff;
            color: #007bff;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-outline-primary:hover {
            background: #007bff;
            border-color: #007bff;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.25);
        }
        .btn-outline-info {
            border: 2px solid #17a2b8;
            color: #17a2b8;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-outline-info:hover {
            background: #17a2b8;
            border-color: #17a2b8;
            transform: translateY(-1px);
        }
        .btn-success {
            background: #28a745;
            border: #28a745;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            background: #218838;
            border-color: #218838;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.25);
        }
        .btn-outline-danger {
            border: 2px solid #dc3545;
            color: #dc3545;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-outline-danger:hover {
            background: #dc3545;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.25);
        }
        .badge {
            border-radius: 6px;
            padding: 6px 12px;
            font-weight: 600;
        }
        .badge.bg-info {
            background: #17a2b8 !important;
        }
        .badge.bg-success {
            background: #28a745 !important;
        }
        .badge.bg-danger {
            background: #dc3545 !important;
        }
        .list-group-item {
            border: 2px solid transparent;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.3s ease;
        }
        .list-group-item:hover {
            border-color: #007bff;
            background: #f8f9fa;
            transform: translateX(4px);
        }
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        }
        .modal-header {
            background: #343a40;
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 20px 24px;
        }
        
        /* Mejoras específicas para el modal de paquetes */
        #modalPaquetes .modal-dialog {
            max-width: 90vw;
        }
        #modalPaquetes .card {
            transition: transform 0.2s ease;
            min-height: 280px;
        }
        #modalPaquetes .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1) !important;
        }
        #modalPaquetes .card-title {
            max-width: calc(100% - 50px);
        }
        #modalPaquetes .list-group-item {
            border: none !important;
            padding: 4px 8px !important;
            margin-bottom: 2px;
            background: #f8f9fa !important;
            border-radius: 4px !important;
        }
        #modalPaquetes .btn-sm {
            padding: 4px 8px;
            font-size: 0.75rem;
        }
        .alert {
            border-radius: 8px;
            border: none;
            padding: 16px 20px;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        /* Efectos de hover para las filas de las tablas */
        #tablaProductosCotizacion tbody tr:hover,
        #tablaInsumosCotizacion tbody tr:hover,
        #tablaServiciosCotizacion tbody tr:hover {
            transform: translateY(-0.5px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
            border-left: 3px solid #007bff !important;
        }
        /* Efectos de animación para botones */
        .btn:hover {
            transform: translateY(-1px);
        }
        .btn-outline-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.25);
        }
        /* Animación para inputs */
        .form-control:focus {
            transform: scale(1.01);
            outline: none;
        }
        
        /* Mejoras para inputs de cantidad y precio */
        .cantidad-input:focus,
        .precio-input:focus,
        .cantidad-insumo-input:focus,
        .precio-insumo-input:focus,
        .cantidad-servicio-input:focus,
        .precio-servicio-input:focus {
            background: #fff !important;
            border-color: #007bff !important;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15) !important;
            transform: scale(1.02);
            z-index: 2;
            position: relative;
        }
        
        /* Transiciones suaves para los inputs */
        .cantidad-input,
        .precio-input,
        .cantidad-insumo-input,
        .precio-insumo-input,
        .cantidad-servicio-input,
        .precio-servicio-input {
            transition: all 0.2s ease;
            cursor: text;
            /* Mejorar área clickeable */
            min-height: 38px;
            display: block;
            width: 100%;
            text-align: center;
        }
        
        /* Hover sutil en inputs */
        .cantidad-input:hover,
        .precio-input:hover,
        .cantidad-insumo-input:hover,
        .precio-insumo-input:hover,
        .cantidad-servicio-input:hover,
        .precio-servicio-input:hover {
            border-color: #80bdff;
            box-shadow: 0 0 0 1px rgba(0, 123, 255, 0.1);
            background: #fafbff;
        }
        
        /* Mejorar celdas que contienen inputs */
        #tablaProductosCotizacion td:has(.cantidad-input),
        #tablaProductosCotizacion td:has(.precio-input),
        #tablaInsumosCotizacion td:has(.cantidad-insumo-input),
        #tablaInsumosCotizacion td:has(.precio-insumo-input),
        #tablaServiciosCotizacion td:has(.cantidad-servicio-input),
        #tablaServiciosCotizacion td:has(.precio-servicio-input) {
            padding: 6px 8px !important;
            cursor: text;
        }
        /* Loading spinner personalizado */
        .spinner-border {
            border-color: #007bff;
            border-top-color: transparent;
        }
        /* Mejoras para notificaciones */
        .alert {
            border-left: 4px solid;
        }
        .alert-success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .alert-danger {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .alert-warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        /* Mejoras para select2 */
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background: #007bff;
            color: white;
        }
        
        /* ===== CAMPOS EDITABLES MEJORADOS ===== */
        .nombre-producto-input:hover,
        .nombre-servicio-input:hover,
        .nombre-insumo-input:hover {
            background: #f8f9ff !important;
            border: 1px solid #007bff !important;
        }
        
        .nombre-producto-input:focus,
        .nombre-servicio-input:focus,
        .nombre-insumo-input:focus {
            background: #fff !important;
            border: 2px solid #007bff !important;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25) !important;
            outline: none !important;
        }
        
        .costo-input:hover,
        .costo-servicio-input:hover,
        .costo-insumo-input:hover {
            border-color: #6c757d !important;
            background: #f8f9fa !important;
        }
        
        .costo-input:focus,
        .costo-servicio-input:focus,
        .costo-insumo-input:focus {
            border-color: #6c757d !important;
            box-shadow: 0 0 0 2px rgba(108, 117, 125, 0.25) !important;
            outline: none !important;
        }
        
        /* Mejorar tamaño y usabilidad de inputs de costo */
        .costo-input,
        .costo-servicio-input,
        .costo-insumo-input {
            min-width: 80px !important;
            font-size: 0.9rem !important;
            padding: 6px 8px !important;
            font-weight: 600 !important;
        }
        
        /* Asegurar que los input-group tengan el ancho correcto */
        .input-group:has(.costo-input),
        .input-group:has(.costo-servicio-input),
        .input-group:has(.costo-insumo-input) {
            min-width: 110px !important;
        }
        
        /* Indicador visual para campos con costo */
        .input-group:has(.costo-input),
        .input-group:has(.costo-servicio-input),
        .input-group:has(.costo-insumo-input) {
            transition: all 0.2s ease;
        }
        
        .input-group:has(.costo-input):hover,
        .input-group:has(.costo-servicio-input):hover,
        .input-group:has(.costo-insumo-input):hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(108, 117, 125, 0.15);
        }
        
        /* Tooltips para campos de costo */
        .costo-input[title]:hover::after,
        .costo-servicio-input[title]:hover::after,
        .costo-insumo-input[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            white-space: nowrap;
            z-index: 1000;
        }
        
        /* Mejorar apariencia del icono de búsqueda de Google */
        .icon-buscar-google:hover {
            background: #0056b3 !important;
            transform: scale(1.1);
            box-shadow: 0 2px 6px rgba(0, 86, 179, 0.3);
        }
        
        /* Mejorar inputs de nombres editables */
        .nombre-producto-input,
        .nombre-servicio-input,
        .nombre-insumo-input {
            transition: all 0.3s ease !important;
            cursor: text !important;
            word-wrap: break-word !important;
            white-space: pre-wrap !important;
            resize: vertical !important;
            font-family: inherit !important;
        }
        
        .nombre-producto-input:hover,
        .nombre-servicio-input:hover,
        .nombre-insumo-input:hover {
            border-color: #007bff !important;
            background: #f8f9fa !important;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.1) !important;
        }
        
        .nombre-producto-input:focus,
        .nombre-servicio-input:focus,
        .nombre-insumo-input:focus {
            background: #fff !important;
            border: 2px solid #007bff !important;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25) !important;
            outline: none !important;
            transform: none !important;
            z-index: 10 !important;
        }
        
        /* Mejorar tabla para dar más espacio a los nombres */
        table th:nth-child(2),
        table td:nth-child(2) {
            min-width: 250px !important;
            max-width: 400px !important;
            width: auto !important;
        }
        
        /* Auto-resize para textareas */
        .nombre-producto-input,
        .nombre-insumo-input {
            overflow: hidden !important;
            resize: none !important;
        }
        
        /* Scrollbar personalizado */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f3f4;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: #6c757d;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #495057;
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
                <select class="form-control" id="cliente_select" name="cliente_id">
                    <option value="">-- Selecciona un cliente o deja vacío para nuevo --</option>
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
                        <input type="text" class="form-control" name="cliente_telefono" id="cliente_telefono" maxlength="10" pattern="[0-9]{10}" placeholder="1234567890">
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
                    <div class="col-md-3">
                        <label class="form-label">Descripción</label>
                        <input type="text" class="form-control" id="nuevo_descripcion_producto" placeholder="Descripción breve...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">SKU</label>
                        <input type="text" class="form-control" id="nuevo_sku_producto">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Precio de venta</label>
                        <input type="number" class="form-control" id="nuevo_precio_producto" min="0" step="0.01">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Costo</label>
                        <input type="number" class="form-control" id="nuevo_costo_producto" min="0" step="0.01">
                    </div>
                </div>
                <div class="row g-3 align-items-end mt-2">
                    <div class="col-md-1">
                        <label class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="nuevo_cantidad_producto" min="0" value="0">
                    </div>
                    <div class="col-md-2">
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
                    <div class="col-md-2">
                        <label class="form-label">Imagen</label>
                        <input type="file" class="form-control" id="nuevo_imagen_producto" accept="image/*">
                    </div>
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="nuevo_agregar_inventario_producto">
                            <label class="form-check-label" for="nuevo_agregar_inventario_producto">Agregar al inventario</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-success" id="btnAgregarProductoRapido"><i class="bi bi-check-circle"></i> Agregar producto</button>
                    </div>
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
                            <th style="border-radius: 8px 0 0 0; padding: 8px 12px; text-align: center; font-size: 0.8rem;">Imagen</th>
                            <th style="padding: 8px 12px; font-size: 0.8rem;">Nombre</th>
                            <th style="padding: 8px 12px; text-align: center; font-size: 0.8rem;">Enlace</th>
                            <th style="padding: 8px 12px; text-align: center; font-size: 0.8rem;">Cantidad</th>
                            <th style="padding: 8px 12px; text-align: center; font-size: 0.8rem;">Precio</th>
                            <th style="padding: 8px 12px; text-align: center; font-size: 0.8rem; background: #6c757d;">Costo</th>
                            <th style="text-align:center; color:#ffffff; min-width:70px; padding: 8px 12px; font-size: 0.8rem;">Margen</th>
                            <th style="padding: 8px 12px; text-align: center; font-size: 0.8rem;">Subtotal</th>
                            <th style="border-radius: 0 8px 0 0; padding: 8px 6px; text-align: center; font-size: 0.8rem; width: 50px;">Acción</th>
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
            <div class="mb-3">
                <button type="button" class="btn btn-outline-primary" id="btnAltaRapidaInsumo"><i class="bi bi-plus-circle"></i> Alta rápida de insumo</button>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped align-middle" id="tablaInsumosCotizacion">
                    <thead class="table-dark">
                        <tr>
                            <th style="border-radius: 8px 0 0 0; padding: 8px 12px; text-align: center; font-size: 0.8rem;">Imagen</th>
                            <th style="min-width:140px; padding: 8px 12px; font-size: 0.8rem;">Nombre</th>
                            <th style="text-align:center; padding: 8px 12px; font-size: 0.8rem;">Enlace</th>
                            <th style="padding: 8px 12px; text-align: center; font-size: 0.8rem;">Cantidad</th>
                            <th style="padding: 8px 12px; text-align: center; font-size: 0.8rem;">Precio</th>
                            <th style="padding: 8px 12px; text-align: center; font-size: 0.8rem; background: #6c757d;">Costo</th>
                            <th style="text-align:center; color:#ffffff; min-width:70px; padding: 8px 12px; font-size: 0.8rem;">Margen</th>
                            <th style="padding: 8px 12px; text-align: center; font-size: 0.8rem;">Subtotal</th>
                            <th class="th-accion-dark" style="border-radius: 0 8px 0 0; padding: 8px 6px; font-size: 0.8rem;">Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <!-- Modal Alta Rápida Insumo -->
        <div class="modal fade" id="modalAltaRapidaInsumo" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Crear Nuevo Insumo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formAltaRapidaInsumo" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Nombre del insumo *</label>
                                    <input type="text" class="form-control" id="nuevo_nombre_insumo" required placeholder="Ej: Cable UTP Cat6">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Categoría *</label>
                                    <select class="form-select" id="nuevo_categoria_insumo" required>
                                        <option value="">-- Selecciona una categoría --</option>
                                        <?php
                                        $categorias_query = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name");
                                        while ($categoria = $categorias_query->fetch_assoc()) {
                                            echo '<option value="' . $categoria['category_id'] . '">' . htmlspecialchars($categoria['name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Proveedor *</label>
                                    <select class="form-select" id="nuevo_proveedor_insumo" required>
                                        <option value="">-- Selecciona un proveedor --</option>
                                        <?php
                                        $proveedores_query = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name");
                                        while ($proveedor = $proveedores_query->fetch_assoc()) {
                                            echo '<option value="' . $proveedor['supplier_id'] . '">' . htmlspecialchars($proveedor['name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Unidad de medida</label>
                                    <select class="form-select" id="nuevo_unidad_insumo">
                                        <option value="Pieza" selected>Pieza</option>
                                        <option value="Metro">Metro</option>
                                        <option value="Kilogramo">Kilogramo</option>
                                        <option value="Litro">Litro</option>
                                        <option value="Caja">Caja</option>
                                        <option value="Paquete">Paquete</option>
                                        <option value="Rollo">Rollo</option>
                                        <option value="Bolsa">Bolsa</option>
                                        <option value="Unidad">Unidad</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Precio de venta</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="nuevo_precio_insumo" placeholder="0.00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Costo</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="nuevo_costo_insumo" placeholder="0.00">
                                    <small class="text-muted">Costo de compra por unidad</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Cantidad inicial</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="nuevo_cantidad_insumo" value="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Stock mínimo</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="nuevo_minimo_insumo" value="10">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Imagen del insumo</label>
                                    <input type="file" class="form-control" id="nuevo_imagen_insumo" accept="image/*">
                                    <div class="mt-2 text-muted">Seleccionar archivo &nbsp;&nbsp;&nbsp; Sin archivos seleccionados</div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="nuevo_agregar_inventario_insumo" checked>
                                        <label class="form-check-label" for="nuevo_agregar_inventario_insumo">
                                            Agregar al inventario de insumos
                                        </label>
                                    </div>
                                    <small class="text-muted">Si no está marcado, el insumo solo se agregará a esta cotización</small>
                                </div>
                            </div>
                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i>
                                <strong>Información:</strong> Los insumos son materiales independientes para gestión de stock.<br>
                                Ejemplo: Si tienes 100 conectores, selecciona "Pieza" como unidad y registra 100 piezas.
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btnGuardarInsumoRapido"><i class="bi bi-check-circle"></i> Crear Insumo</button>
                    </div>
                </div>
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
                            <th style="border-radius: 12px 0 0 0; padding: 16px; text-align: center;">Imagen</th>
                            <th style="padding: 16px;">Servicio</th>
                            <th style="padding: 16px; text-align: center;">Enlace</th>
                            <th style="padding: 16px; text-align: center;">Cantidad</th>
                            <th style="padding: 16px; text-align: center;">Precio</th>
                            <th style="padding: 16px; text-align: center; background: #6c757d;">Costo</th>
                            <th style="padding: 16px; text-align: center;">Subtotal</th>
                            <th style="border-radius: 0 12px 0 0; padding: 16px; text-align: center;">Acción</th>
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

            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <label class="form-label">Condición especial de IVA</label>
                    <input type="number" id="condicion_iva" name="condicion_iva" class="form-control" min="0" max="100" step="0.01" placeholder="Porcentaje de IVA especial (ejemplo: 16)" pattern="[0-9]+(\.[0-9]{1,2})?" oninput="this.value = this.value.replace(/[^0-9.]/g, ''); if(parseFloat(this.value) > 100) this.value = 100;">
                    <small class="text-muted">Solo números del 0 al 100. Ejemplo: 16 para 16% de IVA.</small>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" id="observaciones" class="form-control" rows="2" placeholder="Escriba aquí las observaciones..."></textarea>
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
        
        <!-- Campos ocultos para enviar totales calculados por el frontend -->
        <input type="hidden" name="subtotal_frontend" id="subtotal_frontend">
        <input type="hidden" name="descuento_porcentaje_frontend" id="descuento_porcentaje_frontend">
        <input type="hidden" name="descuento_monto_frontend" id="descuento_monto_frontend">
        <input type="hidden" name="total_frontend" id="total_frontend">
        
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
<!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> -->
<script src="paquetes.js?v=999"></script>
<script>
// --- CLIENTES ---
const clientesArray = <?= json_encode($clientes_array) ?>;
$(document).ready(function() {
    // Select nativo - sin Select2
    // No necesita inicialización especial
    
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

// 🎯 CONFIGURACIÓN SIMPLE PARA BOBINAS DE CABLE
const PRECIO_CONFIG = {
    // Configuración de detección
    metrosPorBobina: 305,    // Metros estándar por bobina
    tolerancia: 10,          // ±metros para considerar bobina completa
    
    // Modos de precio para bobinas completas
    modosPrecio: {
        POR_METRO: 'por_metro',           // Precio por metro
        POR_BOBINA: 'por_bobina_completa' // Precio por bobina completa
    }
};

// Variable global para rastrear paquetes promocionales
window.paquetesPromocionales = [];

// Función para limpiar paquetes promocionales cuando se eliminan items
function limpiarPaquetesPromocionales() {
    if (!window.paquetesPromocionales) return;
    
    window.paquetesPromocionales = window.paquetesPromocionales.filter(paquete => {
        // Verificar si todos los items del paquete aún existen
        const itemsExistentes = [
            ...productosCotizacion.filter(p => p.paquete_id === paquete.id),
            ...serviciosCotizacion.filter(s => s.paquete_id === paquete.id),
            ...insumosCotizacion.filter(i => i.paquete_id === paquete.id)
        ];
        
        return itemsExistentes.length > 0;
    });
}

// --- SERVICIOS ---
const serviciosArray = <?= json_encode($servicios_array) ?>;
let serviciosCotizacion = [];
// Hacer serviciosArray global INMEDIATAMENTE para que paquetes.js pueda acceder
window.serviciosArray = serviciosArray;

// --- INSUMOS ---
const insumosArray = <?= json_encode($insumos_array) ?>;
window.insumosArray = insumosArray;
let insumosCotizacion = [];

// --- ALTA RÁPIDA INSUMO ---
$('#btnAltaRapidaInsumo').on('click', function() {
    $('#modalAltaRapidaInsumo').modal('show');
});

$('#btnGuardarInsumoRapido').on('click', function() {
    const nombre = $('#nuevo_nombre_insumo').val().trim();
    const categoria_id = $('#nuevo_categoria_insumo').val();
    const proveedor_id = $('#nuevo_proveedor_insumo').val();
    const unidad = $('#nuevo_unidad_insumo').val();
    const precio = parseFloat($('#nuevo_precio_insumo').val()) || 0;
    const costo = parseFloat($('#nuevo_costo_insumo').val()) || 0;
    const cantidad = parseFloat($('#nuevo_cantidad_insumo').val()) || 0;
    const minimo = parseFloat($('#nuevo_minimo_insumo').val()) || 0;
    const agregarInventario = $('#nuevo_agregar_inventario_insumo').is(':checked');
    const imagenFile = $('#nuevo_imagen_insumo')[0] ? $('#nuevo_imagen_insumo')[0].files[0] : null;

    // Validación básica
    if (!nombre || !categoria_id || !proveedor_id || !unidad || precio < 0 || cantidad < 0) {
        mostrarNotificacion('Completa todos los campos obligatorios: nombre, categoría, proveedor, unidad, precio y cantidad (>=0).', 'warning');
        return;
    }

    let formData = new FormData();
    formData.append('nombre', nombre);
    formData.append('category_id', categoria_id); // CAMBIO
    formData.append('supplier_id', proveedor_id); // CAMBIO
    formData.append('unidad', unidad);
    formData.append('precio_unitario', precio);   // CAMBIO
    formData.append('cost_price', costo);         // CAMBIO
    formData.append('cantidad', cantidad);
    formData.append('minimo', minimo);
    formData.append('agregar_inventario', agregarInventario ? 1 : 0);
    if (imagenFile) formData.append('imagen', imagenFile);

    $.ajax({
        url: 'ajax_add_insumo.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(resp) {
            let res;
            try { res = JSON.parse(resp); } catch(e) { res = null; }
            if (res && res.success && res.insumo) {
                const insumo = {
                    insumo_id: res.insumo.insumo_id,
                    nombre: res.insumo.nombre,
                    categoria: res.insumo.categoria,
                    proveedor: res.insumo.proveedor,
                    cantidad: res.insumo.cantidad,
                    precio: res.insumo.precio,
                    stock: res.insumo.stock,
                    unidad: res.insumo.unidad,
                    imagen: res.insumo.imagen || '',
                    costo: res.insumo.costo,
                    agregar_inventario: agregarInventario ? 1 : 0
                };
                agregarInsumoATabla(insumo);
                $('#modalAltaRapidaInsumo').modal('hide');
                const form = $('#formAltaRapidaInsumo')[0];
                if (form) form.reset();
                mostrarNotificacion('Insumo creado y agregado correctamente.', 'success');
            } else if (res && res.error) {
                mostrarNotificacion('Error: ' + res.error, 'danger');
            } else {
                mostrarNotificacion('Error inesperado al crear insumo. Respuesta: ' + (resp || ''), 'danger');
            }
        },
        error: function(xhr) {
            let msg = 'Error de conexión al crear insumo.';
            if (xhr && xhr.responseText) {
                msg += ' ' + xhr.responseText;
            }
            mostrarNotificacion(msg, 'danger');
        }
    });
});

function agregarInsumoATabla(insumo) {
    insumosCotizacion.push(insumo);
    renderTablaInsumos();
    guardarBorrador();
    recalcularTotales();
}

$(document).on('click', '.btn-eliminar-insumo', function() {
    const idx = $(this).data('idx');
    insumosCotizacion.splice(idx, 1);
    limpiarPaquetesPromocionales();
    renderTablaInsumos();
    recalcularTotales();
    guardarBorrador();
});

function recalcularTotales() {
    let subtotalProductos = 0;
    let subtotalServicios = 0;
    let subtotalInsumos = 0;
    
    // Agrupar items por paquetes promocionales
    const paquetesPromocionales = window.paquetesPromocionales || [];
    const itemsPorPaquete = {};
    
    // Calcular subtotal de productos
    productosCotizacion.forEach(p => {
        if (p.es_promocional && p.paquete_promocional) {
            // Es parte de un paquete promocional
            const paqueteId = p.paquete_promocional.id;
            if (!itemsPorPaquete[paqueteId]) {
                itemsPorPaquete[paqueteId] = {
                    precio_promocional: p.paquete_promocional.precio_promocional,
                    procesado: false
                };
            }
        } else {
            // Producto normal
            // 🎯 CÁLCULO CORRECTO SEGÚN EL MODO DE PRECIO
            const esBobina = p.tipo_gestion === 'bobina';
            const cantidad = parseFloat(p.cantidad) || 1;
            const precio = parseFloat(p.precio) || 0;
            
            // DEBUG: Mostrar información del producto para diagnosticar
            console.log(`DEBUG Producto ${p.nombre || 'sin nombre'}:`, {
                tipo_gestion: p.tipo_gestion,
                esBobina: esBobina,
                _modoPrecio: p._modoPrecio,
                cantidad: cantidad,
                precio: precio,
                modo_constante: PRECIO_CONFIG.modosPrecio.POR_BOBINA
            });
            
            if (esBobina && p._modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
                // Para bobinas en modo bobina: número de bobinas × precio por bobina
                // Permitir fracciones de bobinas (ej: 1.5 bobinas)
                const bobinasCompletas = cantidad / PRECIO_CONFIG.metrosPorBobina;
                const subtotalCalculado = bobinasCompletas * precio;
                console.log(`DEBUG Bobina POR_BOBINA: ${cantidad}m ÷ ${PRECIO_CONFIG.metrosPorBobina}m = ${bobinasCompletas} bobinas × $${precio} = $${subtotalCalculado}`);
                subtotalProductos += subtotalCalculado;
            } else {
                // Para metros o productos normales: cantidad × precio
                const subtotalCalculado = cantidad * precio;
                console.log(`DEBUG Producto normal/metro: ${cantidad} × $${precio} = $${subtotalCalculado}`);
                subtotalProductos += subtotalCalculado;
            }
        }
    });
    
    // Calcular subtotal de servicios
    serviciosCotizacion.forEach(s => {
        if (s.es_promocional && s.paquete_promocional) {
            // Es parte de un paquete promocional
            const paqueteId = s.paquete_promocional.id;
            if (!itemsPorPaquete[paqueteId]) {
                itemsPorPaquete[paqueteId] = {
                    precio_promocional: s.paquete_promocional.precio_promocional,
                    procesado: false
                };
            }
        } else {
            // Servicio normal
            subtotalServicios += ((parseFloat(s.precio)||0)*(parseFloat(s.cantidad)||1));
        }
    });
    
    // Calcular subtotal de insumos
    insumosCotizacion.forEach(i => {
        if (i.es_promocional && i.paquete_promocional) {
            // Es parte de un paquete promocional
            const paqueteId = i.paquete_promocional.id;
            if (!itemsPorPaquete[paqueteId]) {
                itemsPorPaquete[paqueteId] = {
                    precio_promocional: i.paquete_promocional.precio_promocional,
                    procesado: false
                };
            }
        } else {
            // Insumo normal
            subtotalInsumos += ((parseFloat(i.precio)||0)*(parseFloat(i.cantidad)||1));
        }
    });
    
    // Agregar precios de paquetes promocionales (una vez por paquete)
    let subtotalPaquetesPromocionales = 0;
    Object.values(itemsPorPaquete).forEach(paquete => {
        subtotalPaquetesPromocionales += paquete.precio_promocional;
    });
    
    const subtotal = subtotalProductos + subtotalServicios + subtotalInsumos + subtotalPaquetesPromocionales;
    const descuentoPorcentaje = parseFloat($('#descuento_porcentaje').val()) || 0;
    const descuentoMonto = subtotal * descuentoPorcentaje / 100;
    let total = subtotal - descuentoMonto;
    // Obtener el valor del campo de condición especial de IVA
    let ivaManual = 0;
    const ivaCondicion = $("#condicion_iva").val();
    if (ivaCondicion && !isNaN(parseFloat(ivaCondicion))) {
        let ivaVal = parseFloat(ivaCondicion);
        // Si el valor es <= 1, se interpreta como porcentaje (ejemplo: 0.16 o 0.08)
        // Si el valor es > 1 y <= 100, se interpreta como porcentaje (ejemplo: 16 para 16%)
        // Si el valor es > 100, se interpreta como monto directo
        if (ivaVal <= 1) {
            ivaManual = (subtotal - descuentoMonto) * ivaVal;
        } else if (ivaVal > 1 && ivaVal <= 100) {
            ivaManual = (subtotal - descuentoMonto) * (ivaVal / 100);
        } else {
            ivaManual = ivaVal;
        }
    }
    total += ivaManual;
    $('#subtotal').val(`$${subtotal.toFixed(2)}`);
    $('#descuento_monto').val(`$${descuentoMonto.toFixed(2)}`);
    $('#total').val(`$${total.toFixed(2)}`);
    
    // Actualizar campos ocultos para enviar al backend
    $('#subtotal_frontend').val(subtotal.toFixed(2));
    $('#descuento_porcentaje_frontend').val(descuentoPorcentaje.toFixed(2));
    $('#descuento_monto_frontend').val(descuentoMonto.toFixed(2));
    $('#total_frontend').val(total.toFixed(2));
    
    // Mejor visualización: mostrar el IVA especial debajo del total, con icono y texto claro
    $('#iva_manual_monto').remove();
    $('#paquetes_promocionales_resumen').remove();
    
    if (ivaManual > 0) {
        // Si el input está dentro de una celda de tabla, insertar el aviso después de la celda
        var $totalInput = $('#total');
        var $td = $totalInput.closest('td');
        if ($td.length) {
            $td.after(`
                <tr id="iva_manual_monto_tr">
                    <td colspan="4"></td>
                    <td colspan="1" style="padding-top:0;">
                        <div id="iva_manual_monto" style="margin-top:0; color:#198754; font-weight:600; background:#e9fbe9; border-radius:6px; padding:6px 14px; font-size:1em; display:flex; align-items:center; gap:8px;">
                            <i class="bi bi-info-circle" style="font-size:1.2em;"></i>
                            <span>IVA especial aplicado: <b>$${ivaManual.toFixed(2)}</b></span>
                        </div>
                    </td>
                </tr>
            `);
        } else {
            $totalInput.after(`
                <div id="iva_manual_monto" style="margin-top:8px; color:#198754; font-weight:600; background:#e9fbe9; border-radius:6px; padding:6px 14px; font-size:1em; display:flex; align-items:center; gap:8px;">
                    <i class="bi bi-info-circle" style="font-size:1.2em;"></i>
                    <span>IVA especial aplicado: <b>$${ivaManual.toFixed(2)}</b></span>
                </div>
            `);
        }
    }
    
    // Mostrar resumen de paquetes promocionales si hay alguno
    if (Object.keys(itemsPorPaquete).length > 0) {
        let ahorroTotal = 0;
        let resumenHtml = '';
        
        // Calcular ahorro total y crear resumen
        Object.values(itemsPorPaquete).forEach(paquete => {
            const paqueteInfo = (window.paquetesPromocionales || []).find(p => p.precio_promocional === paquete.precio_promocional);
            if (paqueteInfo) {
                const ahorro = paqueteInfo.precio_original - paqueteInfo.precio_promocional;
                ahorroTotal += ahorro;
                resumenHtml += `<div style="margin-bottom:4px;"><strong>${paqueteInfo.nombre}:</strong> $${paqueteInfo.precio_promocional.toFixed(2)} (ahorro: $${ahorro.toFixed(2)})</div>`;
            }
        });
        
        var $totalInput = $('#total');
        var $td = $totalInput.closest('td');
        if ($td.length) {
            $td.after(`
                <tr id="paquetes_promocionales_resumen_tr">
                    <td colspan="4"></td>
                    <td colspan="1" style="padding-top:8px;">
                        <div id="paquetes_promocionales_resumen" style="color:#28a745; font-weight:600; background:#d4edda; border-radius:6px; padding:8px 14px; font-size:0.9em;">
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                                <i class="bi bi-percent" style="font-size:1.2em;"></i>
                                <span>Paquetes promocionales aplicados</span>
                            </div>
                            ${resumenHtml}
                            <div style="border-top:1px solid #28a745; padding-top:4px; margin-top:6px; font-size:1em;">
                                <strong>Ahorro total: $${ahorroTotal.toFixed(2)}</strong>
                            </div>
                        </div>
                    </td>
                </tr>
            `);
        } else {
            $totalInput.after(`
                <div id="paquetes_promocionales_resumen" style="margin-top:8px; color:#28a745; font-weight:600; background:#d4edda; border-radius:6px; padding:8px 14px; font-size:0.9em;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                        <i class="bi bi-percent" style="font-size:1.2em;"></i>
                        <span>Paquetes promocionales aplicados</span>
                    </div>
                    ${resumenHtml}
                    <div style="border-top:1px solid #28a745; padding-top:4px; margin-top:6px; font-size:1em;">
                        <strong>Ahorro total: $${ahorroTotal.toFixed(2)}</strong>
                    </div>
                </div>
            `);
        }
    }
}

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
            sugerencias += `<button type='button' class='list-group-item list-group-item-action' data-id='${p.product_id}' data-nombre='${p.product_name}' data-descripcion='${p.description||''}' data-sku='${p.sku}' data-categoria='${p.categoria||''}' data-proveedor='${p.proveedor||''}' data-stock='${p.stock_disponible}' data-precio='${p.price}' data-image='${p.image||''}'>
                <b>${p.product_name}</b> <span class='badge bg-${p.stock_disponible > 0 ? 'success' : 'danger'} ms-2'>Stock: ${p.stock_disponible}</span><br>
                <small>SKU: ${p.sku || '-'} | $${parseFloat(p.price).toFixed(2)}</small>
            </button>`;
        });
    }
    $('#sugerencias_productos').html(sugerencias).show();
});
$('#sugerencias_productos').on('click', 'button', function() {
    const prod = productosArray.find(p => p.product_id == $(this).data('id'));
    const precio = parseFloat($(this).data('precio')) || 0;
    const esBobina = prod && prod.tipo_gestion === 'bobina';
    
    // 🎯 CONFIGURAR CANTIDAD Y MODO INICIAL PARA BOBINAS
    let cantidadInicial, precioInicial, modoInicial, precioBase;
    if (esBobina) {
        // Para bobinas, el precio en DB es el precio de toda la bobina ($983.68)
        // Pero el precio real de venta es $7/metro
        if (precio > 50) { // Si el precio es alto, es precio por bobina completa
            cantidadInicial = PRECIO_CONFIG.metrosPorBobina; // 305m = 1 bobina completa
            precioBase = 7.00; // Precio real: $7 pesos por metro
            precioInicial = precio; // MANTENER precio original de bobina ($983.68)
            modoInicial = PRECIO_CONFIG.modosPrecio.POR_BOBINA;
            console.log(`🔄 Bobina desde inventario: Bobina $${precio}, Metro $${precioBase} (precios independientes)`);
        } else {
            cantidadInicial = 1.00; // 1 metro por defecto
            precioBase = precio; // Es precio por metro
            precioInicial = precio;
            modoInicial = PRECIO_CONFIG.modosPrecio.POR_METRO;
            console.log(`📏 Bobina desde inventario: $${precio}/metro (precio base)`);
        }
    } else {
        cantidadInicial = 1;
        precioInicial = precio;
        modoInicial = null;
        precioBase = precio;
    }
    
    agregarProductoATabla({
        product_id: $(this).data('id'),
        nombre: $(this).data('nombre'),
        description: $(this).data('descripcion') || '',
        sku: $(this).data('sku'),
        categoria: $(this).data('categoria'),
        proveedor: $(this).data('proveedor'),
        stock: prod ? (prod.tipo_gestion === 'bobina' ? (prod.stock_disponible || 0) : prod.stock_disponible) : $(this).data('stock'),
        cantidad: cantidadInicial,
        precio: precioInicial,
        tipo_gestion: prod ? prod.tipo_gestion : 'pieza',
        cost_price: prod && typeof prod.cost_price !== 'undefined' ? prod.cost_price : '',
        image: $(this).data('image') || (prod ? prod.image : ''),
        _modoPrecio: modoInicial, // ✅ Establecer modo inicial
        _precioBase: precioBase, // ✅ Guardar precio base por metro ($7/metro)
        _precioBobinaOriginal: esBobina && precio > 50 ? precio : undefined // ✅ Guardar precio original de bobina
    });
    $('#buscador_producto').val('');
    $('#sugerencias_productos').hide();
});
$('#btnAltaRapidaProducto').on('click', function() {
    $('#altaRapidaProductoForm').toggle();
});
$('#btnAgregarProductoRapido').on('click', function() {
    const nombre = $('#nuevo_nombre_producto').val();
    const descripcion = $('#nuevo_descripcion_producto').val();
    const precio = parseFloat($('#nuevo_precio_producto').val()) || 0;
    const cantidad = parseInt($('#nuevo_cantidad_producto').val()) || 0;
    const sku = $('#nuevo_sku_producto').val();
    const categoria_id = $('#nuevo_categoria_producto').val();
    const proveedor_id = $('#nuevo_proveedor_producto').val();
    const costo = parseFloat($('#nuevo_costo_producto').val()) || 0;
    const agregarInventario = $('#nuevo_agregar_inventario_producto').is(':checked');
    const imagenFile = $('#nuevo_imagen_producto')[0] ? $('#nuevo_imagen_producto')[0].files[0] : null;
    if (!nombre || !precio || cantidad < 0) {
        mostrarNotificacion('Completa nombre, precio y cantidad (>=0) para el producto.', 'warning');
        return;
    }
    let formData = new FormData();
    formData.append('nombre', nombre);
    formData.append('descripcion', descripcion);
    formData.append('sku', sku);
    formData.append('categoria_id', categoria_id);
    formData.append('proveedor_id', proveedor_id);
    formData.append('precio', precio);
    formData.append('costo', costo);
    formData.append('cantidad', cantidad);
    formData.append('agregar_inventario', agregarInventario ? 1 : 0);
    if (imagenFile) formData.append('imagen', imagenFile);

    $.ajax({
        url: 'ajax_add_producto.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(resp) {
            let res;
            try { res = JSON.parse(resp); } catch(e) { res = null; }
            if (res && res.success && res.producto) {
                agregarProductoATabla({
                    product_id: res.producto.product_id,
                    nombre: res.producto.nombre,
                    description: descripcion, // ✅ Usar descripción del formulario
                    sku: res.producto.sku,
                    categoria: res.producto.categoria,
                    proveedor: res.producto.proveedor,
                    stock: res.producto.stock,
                    cantidad: cantidad,
                    precio: precio,
                    tipo_gestion: 'pieza',
                    cost_price: costo,
                    agregar_inventario: agregarInventario ? 1 : 0
                });
                $('#nuevo_nombre_producto, #nuevo_descripcion_producto, #nuevo_sku_producto, #nuevo_precio_producto, #nuevo_cantidad_producto').val('');
                $('#nuevo_categoria_producto, #nuevo_proveedor_producto').val('');
                $('#altaRapidaProductoForm').hide();
                mostrarNotificacion('Producto creado y agregado correctamente.', 'success');
            } else {
                mostrarNotificacion(res && res.error ? res.error : 'Error al crear producto.', 'danger');
            }
        },
        error: function() {
            mostrarNotificacion('Error de conexión al crear producto.', 'danger');
        }
    });
});

// Función para normalizar producto antes de agregarlo
function normalizarProducto(producto) {
    // Asegurar que tengan el campo description y no descripcion
    if (producto.descripcion && !producto.description) {
        producto.description = producto.descripcion;
        delete producto.descripcion; // Eliminar la propiedad antigua
    }
    // Si no tiene description, asegurar que esté vacío
    if (!producto.description) {
        producto.description = '';
    }
    return producto;
}

function agregarProductoATabla(prod) {
    // Normalizar el producto antes de agregarlo
    prod = normalizarProducto(prod);
    
    if (!prod.tipo_gestion) prod.tipo_gestion = 'pieza';
    
    // Si el producto existe en productosArray, copiar cost_price
    if (typeof prod.product_id !== 'undefined' && prod.product_id !== null) {
        const prodBase = productosArray.find(p => p.product_id == prod.product_id);
        if (prodBase && typeof prodBase.cost_price !== 'undefined') {
            prod.cost_price = prodBase.cost_price;
        }
    }
    
    // 🎯 CONFIGURAR MODO INICIAL CORRECTO PARA BOBINAS DESDE INVENTARIO
    if (prod.tipo_gestion === 'bobina') {
        const cantidad = parseFloat(prod.cantidad) || 1;
        const precio = parseFloat(prod.precio) || 0;
        
        // Si no tiene precio base calculado, calcularlo
        if (!prod._precioBase) {
            if (precio > 50) { // Precio alto = precio por bobina completa
                // 🎯 PRECIO CORRECTO: Mantener precio de bobina original, $7/metro para metros sueltos
                prod._precioBase = 7.00; // Precio real de venta por metro
                prod._modoPrecio = PRECIO_CONFIG.modosPrecio.POR_BOBINA;
                prod.cantidad = PRECIO_CONFIG.metrosPorBobina; // 305m por defecto
                prod.precio = precio; // MANTENER precio original de bobina ($983.68)
                prod._precioBobinaOriginal = precio; // ✅ Guardar precio original
                console.log(`🔄 Bobina desde inventario: Bobina $${precio}, Metro $${prod._precioBase} (precios independientes)`);
            } else {
                prod._precioBase = precio; // Es precio por metro
                prod._modoPrecio = PRECIO_CONFIG.modosPrecio.POR_METRO;
                console.log(`📏 Bobina desde inventario: $${precio}/metro (precio base)`);
            }
        }
    }
    
    productosCotizacion.push(prod);
    renderTablaProductos();
    guardarBorrador();
}
$(document).on('click', '.btn-eliminar-producto', function() {
    const idx = $(this).data('idx');
    productosCotizacion.splice(idx, 1);
    limpiarPaquetesPromocionales();
    renderTablaProductos();
    recalcularTotales();
    guardarBorrador();
});
function renderTablaProductos() {
    let html = '';
    let subtotal = 0;
    productosCotizacion.forEach((p, i) => {
        // 🎯 CÁLCULO CORRECTO DEL SUBTOTAL SEGÚN EL MODO DE PRECIO
        let sub;
        const esBobina = p.tipo_gestion === 'bobina';
        const cantidad = parseFloat(p.cantidad) || 1;
        const precio = parseFloat(p.precio) || 0;
        
        // DEBUG: Mostrar información del producto para diagnosticar
        console.log(`DEBUG renderTabla Producto ${p.nombre || 'sin nombre'}:`, {
            tipo_gestion: p.tipo_gestion,
            esBobina: esBobina,
            _modoPrecio: p._modoPrecio,
            cantidad: cantidad,
            precio: precio
        });
        
        if (esBobina && p._modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
            // Para bobinas en modo bobina: número de bobinas × precio por bobina
            // Permitir fracciones de bobinas (ej: 1.5 bobinas)
            const bobinasCompletas = cantidad / PRECIO_CONFIG.metrosPorBobina;
            sub = bobinasCompletas * precio;
            console.log(`DEBUG renderTabla Bobina POR_BOBINA: ${cantidad}m ÷ ${PRECIO_CONFIG.metrosPorBobina}m = ${bobinasCompletas} bobinas × $${precio} = $${sub}`);
        } else {
            // Para metros o productos normales: cantidad × precio
            sub = cantidad * precio;
            console.log(`DEBUG renderTabla Producto normal/metro: ${cantidad} × $${precio} = $${sub}`);
        }
        
        subtotal += sub;
        
        // 🎯 DETECCIÓN INTELIGENTE DE MODO DE VENTA PARA BOBINAS
        let step, min, unidad, cantidadMostrar, modoPrecio;
        
        if (esBobina) {
            const metrosPorBobina = PRECIO_CONFIG.metrosPorBobina;
            const tolerancia = PRECIO_CONFIG.tolerancia;
            // Permitir fracciones de bobinas - usar valor exacto sin redondear
            const bobinasCompletas = cantidad / metrosPorBobina;
            const bobinasRedondeadas = Math.round(bobinasCompletas);
            const metrosEsperados = bobinasRedondeadas * metrosPorBobina;
            const diferencia = Math.abs(cantidad - metrosEsperados);
            
            // 🎯 PRIORIZAR MODO GUARDADO EN EL PRODUCTO sobre detección automática
            if (p._modoPrecio) {
                modoPrecio = p._modoPrecio;
            } else {
                // Solo usar detección automática si no hay modo guardado
                if (bobinasRedondeadas > 0 && diferencia <= tolerancia) {
                    modoPrecio = PRECIO_CONFIG.modosPrecio.POR_BOBINA;
                } else {
                    modoPrecio = PRECIO_CONFIG.modosPrecio.POR_METRO;
                }
                // Guardar el modo detectado para futuras referencias
                p._modoPrecio = modoPrecio;
            }
            
            // Configurar interfaz según el modo actual
            if (modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
                // 🔄 MODO BOBINAS COMPLETAS (permitir fracciones)
                step = '0.1'; // Permitir décimas de bobina
                min = '0.1';
                // Mostrar con precisión de décimas si es fracción
                const bobinasConDecimales = Math.round(bobinasCompletas * 10) / 10;
                unidad = ` bobina${bobinasConDecimales !== 1 ? 's' : ''}`;
                cantidadMostrar = bobinasConDecimales;
                p._bobinasCompletas = bobinasConDecimales;
            } else {
                // 📏 MODO POR METROS
                step = '0.01';
                min = '0.01';
                unidad = ' m';
                cantidadMostrar = cantidad;
            }
        } else {
            // Productos normales (no bobinas)
            step = '1';
            min = '1';
            unidad = '';
            cantidadMostrar = p.cantidad;
            modoPrecio = 'normal';
        }
        
        const nombreGoogle = encodeURIComponent(p.nombre || '');
        const skuGoogle = encodeURIComponent(p.sku || '');
        let stockStr = '';
        if (typeof p.stock !== 'undefined' && p.stock !== null && p.stock !== '') {
            stockStr = esBobina ? parseFloat(p.stock).toFixed(2) : parseInt(p.stock);
        }
        let margen = '';
        let margenNegativo = false;
        let costoUnitario = undefined;
        if (esBobina && typeof p.cost_price !== 'undefined' && p.cost_price !== null && p.cost_price !== '' && !isNaN(parseFloat(p.cost_price))) {
            // 🎯 CÁLCULO CORRECTO DEL COSTO SEGÚN EL MODO DE VENTA
            const cantidad = parseFloat(p.cantidad) || 1;
            const costPrice = parseFloat(p.cost_price);
            const precioVenta = parseFloat(p.precio) || 0;
            
            // Verificar el modo de precio actual
            if (p._modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
                // 🔄 MODO BOBINA ENTERA: Comparar precio de bobina vs costo de bobina
                // Permitir fracciones de bobinas
                const bobinasCompletas = cantidad / PRECIO_CONFIG.metrosPorBobina;
                // Para fracciones de bobinas, el costo unitario es el costo por bobina completa
                costoUnitario = costPrice; // Costo por bobina completa
                
                console.log(`🔄 Margen bobina entera: Precio $${precioVenta} vs Costo $${costoUnitario} (${bobinasCompletas.toFixed(1)} bobina(s))`);
            } else {
                // 📏 MODO POR METROS: Comparar precio por metro vs costo por metro
                costoUnitario = costPrice / PRECIO_CONFIG.metrosPorBobina; // Costo por metro
                
                console.log(`📏 Margen por metros: Precio $${precioVenta}/m vs Costo $${costoUnitario.toFixed(4)}/m`);
            }
        } else if (typeof p.cost_price !== 'undefined' && p.cost_price !== null && p.cost_price !== '' && !isNaN(parseFloat(p.cost_price))) {
            costoUnitario = parseFloat(p.cost_price);
        }
        if (typeof p.margen !== 'undefined' && p.margen !== null && p.margen !== '') {
            margenNegativo = parseFloat(p.margen) < 0;
            margen = parseFloat(p.margen).toFixed(2);
        } else if (costoUnitario !== undefined && parseFloat(p.precio) > 0) {
            // Margen sobre precio de venta, igual que en Excel
            let margenCalc;
            
            // Para bobinas en modo POR_BOBINA, calcular margen considerando las fracciones
            if (esBobina && p._modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
                // Calcular margen basado en el total de la transacción
                const bobinasCompletas = cantidad / PRECIO_CONFIG.metrosPorBobina;
                const precioTotal = bobinasCompletas * parseFloat(p.precio); // Precio total
                const costoTotal = bobinasCompletas * costoUnitario; // Costo total
                margenCalc = ((precioTotal - costoTotal) / precioTotal) * 100;
                
                console.log(`📊 Margen bobina: Precio total $${precioTotal.toFixed(2)} vs Costo total $${costoTotal.toFixed(2)} = ${margenCalc.toFixed(2)}%`);
            } else {
                // Para productos normales o bobinas en modo metros
                margenCalc = (((parseFloat(p.precio) - costoUnitario) / parseFloat(p.precio)) * 100);
            }
            
            margenNegativo = margenCalc < 0;
            margen = isFinite(margenCalc) ? margenCalc.toFixed(2) : '0.00';
        } else if (costoUnitario !== undefined && parseFloat(p.precio) === 0) {
            margen = '0.00';
        }
        
        // Verificar si es parte de un paquete promocional
        const esPromocional = p.es_promocional && p.paquete_promocional;
        const borderColor = esPromocional ? '#28a745' : '#007bff';
        const borderStyle = esPromocional ? '3px solid' : '2px solid';
        
        html += `
            <tr style="background:#ffffff; border-radius:6px; box-shadow:0 1px 4px rgba(0,0,0,0.08); margin-bottom:8px; border:none; transition:all 0.3s ease; border-left: ${borderStyle} ${borderColor};">
                <td style="text-align:center; vertical-align:top; width:70px; padding:8px 6px;">
                    ${p.image ? `<img src="${p.image.startsWith('uploads/') ? '../' + p.image : '../uploads/products/' + p.image}" alt="${p.nombre}" style="width:65px; height:65px; object-fit:cover; border-radius:6px; border:none; box-shadow:0 1px 3px rgba(0,0,0,0.1);">` : '<div style="width:65px; height:65px; background:#f8f9fa; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#6c757d; font-weight:600; font-size:0.7rem; border:1px solid #dee2e6; text-align:center;">Sin<br>img</div>'}
                </td>
                <td style="font-weight:600; color:#495057; padding:8px 10px; min-width:250px; max-width:400px; vertical-align:top;">
                    <div style="display:flex; flex-direction:column; gap:6px; width:100%;">
                        <div style="display:flex; align-items:flex-start; gap:6px; width:100%;">
                            <textarea class="form-control nombre-producto-input" data-index="${i}" style="border:2px solid #e9ecef; background:#fff; padding:6px 8px; font-weight:600; font-size:0.95rem; color:#212529; line-height:1.3; flex:1; border-radius:4px; min-height:38px; max-height:80px; resize:none; overflow:hidden; width:100%;" onblur="actualizarNombreProducto(${i}, this.value)" placeholder="Nombre del producto">${p.nombre || ''}</textarea>
                            ${p.nombre ? `<a href="https://www.google.com/search?q=${nombreGoogle}" target="_blank" title="Buscar en Google" class="icon-buscar-google" style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; background:#007bff; color:white; border-radius:50%; text-decoration:none; font-size:0.6rem; flex-shrink:0; transition:all 0.2s ease; margin-top:4px;"><i class="bi bi-search"></i></a>` : ''}
                        </div>
                        <textarea class="form-control descripcion-producto-input" data-index="${i}" style="border:1px solid #dee2e6; background:#f8f9fa; padding:5px 8px; font-weight:400; font-size:0.85rem; color:#495057; line-height:1.3; width:100%; border-radius:4px; min-height:32px; max-height:60px; resize:none; overflow:hidden;" onblur="actualizarDescripcionProducto(${i}, this.value)" placeholder="Descripción opcional">${p.description || ''}</textarea>
                        ${p.sku ? `<small style="color:#6c757d; font-weight:500; font-size:0.75rem;">SKU: ${p.sku}</small>` : ''}
                        ${esPromocional ? `<small style="color:#28a745; font-weight:600; font-size:0.7rem;"><i class="bi bi-percent"></i> Paquete promocional: ${p.paquete_promocional.nombre}</small>` : ''}
                        ${esBobina && p._tipoVenta ? `<small style="color:${p._tipoVenta === 'bobina_completa' ? '#007bff' : '#ff6b35'}; font-weight:600; font-size:0.7rem;">
                            ${p._tipoVenta === 'bobina_completa' ? `<i class="bi bi-box-seam"></i> ${p._bobinasCompletas} bobina(s) completa(s)` : '<i class="bi bi-rulers"></i> Venta por metros'}
                        </small>` : ''}
                    </div>
                </td>
                <td style="text-align:center; vertical-align:top; padding:8px 10px;">
                    <div style="display:flex; flex-direction:column; align-items:center; gap:3px;">
                        ${p.paquete_id ? `
                            <div style="display:flex; flex-direction:column; align-items:center; gap:3px;">
                                <span class="badge" style="background:#17a2b8; color:white; font-size:0.7rem; padding:4px 6px; border-radius:4px; font-weight:600; display:flex; align-items:center; gap:3px;">
                                    <i class="bi bi-link-45deg"></i> Conectado
                                </span>
                                <label style="display:flex; align-items:center; gap:3px; font-size:0.7rem; color:#495057; cursor:pointer;">
                                    <input type="checkbox" class="sync-checkbox" data-index="${i}" ${p.sincronizado !== false ? 'checked' : ''} title="Sincronizar con principal" style="accent-color:#17a2b8; transform:scale(0.9);">
                                    Sync
                                </label>
                            </div>
                        ` : `<button type="button" class="btn btn-sm" onclick="conectarProductoAPaquete(${i})" title="Conectar a paquete inteligente" style="background:#17a2b8; color:white; border:none; border-radius:4px; padding:4px 8px; font-weight:600; transition:all 0.3s ease; font-size:0.75rem;"><i class="bi bi-link"></i> Conectar</button>`}
                        
                        ${esBobina ? `
                            <button type="button" class="btn btn-sm cambiar-modo-btn" data-index="${i}" title="Cambiar modo: ${modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA ? 'Bobinas → Metros' : 'Metros → Bobinas'}" style="background:${modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA ? '#28a745' : '#ff6b35'}; color:white; border:none; border-radius:4px; padding:3px 6px; font-weight:600; transition:all 0.3s ease; font-size:0.65rem; margin-top:2px;">
                                ${modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA ? '<i class="bi bi-box-seam"></i> Bobinas' : '<i class="bi bi-rulers"></i> Metros'}
                            </button>
                        ` : ''}
                    </div>
                </td>
                <td style="vertical-align:top; padding:8px 10px;">
                    <div style="display:flex; flex-direction:column; align-items:center; gap:3px;">
                        <input type="number" min="${min}" step="${step}" value="${cantidadMostrar}" class="form-control form-control-sm cantidad-input" data-index="${i}" data-paquete-id="${p.paquete_id || ''}" data-tipo-paquete="${p.tipo_paquete || ''}" data-modo-precio="${modoPrecio}" style="width:80px; background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px; text-align:center; font-weight:600; padding:6px; font-size:0.85rem;">
                        <small style="color:#6c757d; font-weight:500; font-size:0.7rem; line-height:1;">${unidad.trim()}</small>
                        ${esBobina && modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA ? `<small style="color:#007bff; font-weight:600; font-size:0.65rem; text-align:center; line-height:1;">(${cantidad.toFixed(0)}m total)</small>` : ''}
                    </div>
                </td>
                <td style="vertical-align:top; padding:8px 10px;">
                    <div class="input-group" style="width:110px;">
                        <span class="input-group-text" style="background:#28a745; color:white; border:none; border-radius:4px 0 0 4px; font-weight:600; font-size:0.8rem; padding:6px 8px;">$</span>
                        <input type="number" min="0" step="0.01" value="${p.precio || ''}" class="form-control form-control-sm precio-input" data-index="${i}" data-modo-precio="${modoPrecio}" style="background:#f8f9fa; border:1px solid #dee2e6; border-left:none; border-radius:0 4px 4px 0; text-align:center; font-weight:600; font-size:0.85rem; padding:6px;">
                    </div>
                </td>
                <td style="vertical-align:top; padding:8px 10px;">
                    <div style="display:flex; flex-direction:column; align-items:center; gap:2px;">
                        ${costoUnitario !== undefined ? `
                            <div class="input-group" style="width:110px;">
                                <span class="input-group-text" style="background:#6c757d; color:white; border:none; border-radius:4px 0 0 4px; font-weight:600; font-size:0.8rem; padding:6px 8px;">$</span>
                                <input type="number" min="0" step="0.01" value="${costoUnitario.toFixed(2)}" class="form-control form-control-sm costo-input" data-index="${i}" style="background:#f1f3f4; border:1px solid #adb5bd; border-left:none; border-radius:0 4px 4px 0; text-align:center; font-weight:600; font-size:0.9rem; padding:6px;" onblur="actualizarCostoProducto(${i}, this.value)">
                            </div>
                            <small style="color:#6c757d; font-weight:500; font-size:0.7rem; text-align:center;">
                                ${esBobina && p._modoPrecio === PRECIO_CONFIG.modosPrecio.POR_METRO ? 'por metro' : esBobina && p._modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA ? 'por bobina' : 'unitario'}
                            </small>
                        ` : `
                            <span style="color:#adb5bd; font-size:0.8rem; font-style:italic;">Sin costo</span>
                        `}
                    </div>
                </td>
                <td style="vertical-align:top; padding:8px 10px;">
                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:2px;">
                        <input type="number" min="-99" max="99" step="0.01" value="${margen}" class="form-control form-control-sm margen-producto-input" data-index="${i}" style="width:65px; text-align:center; font-weight:700; background:${margenNegativo ? '#f8d7da' : '#d4edda'}; color:${margenNegativo ? '#dc3545' : '#28a745'}; border:1px solid ${margenNegativo ? '#f5c6cb' : '#c3e6cb'}; border-radius:4px; font-size:0.8rem; padding:4px;" title="Editar margen (%)" ${costoUnitario === undefined ? 'disabled' : ''}>
                        <span style="font-size:0.7rem; color:#6c757d; font-weight:500;">
                            ${costoUnitario !== undefined && parseFloat(p.precio) > 0 ? `Utilidad: $${(parseFloat(p.precio) - costoUnitario).toFixed(2)}` : ''}
                        </span>
                    </div>
                </td>
                <td style="vertical-align:top; padding:8px 10px;">
                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:1px;">
                        <span style="font-size:1rem; font-weight:700; color:#007bff;">$${sub.toFixed(2)}</span>
                    </div>
                </td>
                <td style="width:50px; text-align:center; vertical-align:top; padding:6px;">
                    <div style="display:flex; justify-content:center; align-items:flex-start; height:100%; padding-top:4px;">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-producto" data-idx="${i}" style="width:30px; height:30px; display:flex; justify-content:center; align-items:center; padding:0; border-radius:6px; border:1px solid #dc3545; transition:all 0.3s ease;">
                            <i class="bi bi-trash" style="font-size:0.8rem;"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    $('#tablaProductosCotizacion tbody').html(html);
    $('#subtotal').val(`$${subtotal.toFixed(2)}`);
    recalcularTotales();
    
    // Eventos para productos - solo cálculos sin auto-selección
    $('.cantidad-input').off('input').on('input', function() {
        const idx = $(this).data('index');
        const cantidad = parseFloat($(this).val()) || 1;
        if (productosCotizacion[idx]) {
            productosCotizacion[idx].cantidad = cantidad;
            // Actualizar subtotal de la fila
            const precio = parseFloat(productosCotizacion[idx].precio) || 0;
            const prod = productosCotizacion[idx];
            const esBobina = prod.tipo_gestion === 'bobina';
            
            // 🎯 CÁLCULO CORRECTO DEL SUBTOTAL SEGÚN EL MODO
            let subtotal;
            if (esBobina && prod._modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
                // Para bobinas: permitir fracciones de bobinas × precio por bobina
                const bobinasCompletas = cantidad / PRECIO_CONFIG.metrosPorBobina;
                subtotal = bobinasCompletas * precio;
            } else {
                // Para metros o productos normales: cantidad × precio
                subtotal = cantidad * precio;
            }
            $(this).closest('tr').find('td').eq(6).find('span').first().text('$' + subtotal.toFixed(2));
            recalcularTotales();
            guardarBorrador();
        }
    });
    
    // Evento blur para sincronización de paquetes en productos
    $('.cantidad-input').off('blur').on('blur', function() {
        const idx = $(this).data('index');
        const producto = productosCotizacion[idx];
        if (producto && producto.paquete_id && producto.tipo_paquete === 'principal') {
            sincronizarCantidadesPaqueteV2(producto.paquete_id);
        }
    });

    $('.precio-input').off('input').on('input', function() {
        const idx = $(this).data('index');
        const precio = parseFloat($(this).val()) || 0;
        if (productosCotizacion[idx]) {
            productosCotizacion[idx].precio = precio;
            
            // 🎯 Recalcular margen automáticamente cuando cambia el precio
            const prod = productosCotizacion[idx];
            let costoUnitario = undefined;
            const esBobina = prod.tipo_gestion === 'bobina';
            
            // Calcular costo unitario (misma lógica que en el margen)
            if (esBobina && typeof prod.cost_price !== 'undefined' && prod.cost_price !== null && prod.cost_price !== '' && !isNaN(parseFloat(prod.cost_price))) {
                const cantidad = parseFloat(prod.cantidad) || 1;
                const costPrice = parseFloat(prod.cost_price);
                const metrosPorBobina = PRECIO_CONFIG.metrosPorBobina;
                const tolerancia = PRECIO_CONFIG.tolerancia;
                // Permitir fracciones de bobinas
                const bobinasCompletas = cantidad / metrosPorBobina;
                const bobinasRedondeadas = Math.round(bobinasCompletas);
                const metrosEsperados = bobinasRedondeadas * metrosPorBobina;
                const diferencia = Math.abs(cantidad - metrosEsperados);
                
                if (bobinasRedondeadas > 0 && diferencia <= tolerancia) {
                    costoUnitario = costPrice / cantidad;
                } else {
                    costoUnitario = costPrice / metrosPorBobina;
                }
            } else if (typeof prod.cost_price !== 'undefined' && prod.cost_price !== null && prod.cost_price !== '' && !isNaN(parseFloat(prod.cost_price))) {
                costoUnitario = parseFloat(prod.cost_price);
            }
            
            // Actualizar margen si hay costo
            if (costoUnitario !== undefined && precio > 0) {
                const margenCalc = (((precio - costoUnitario) / precio) * 100);
                prod.margen = margenCalc;
                
                // Actualizar campo de margen en la interfaz
                const $row = $(this).closest('tr');
                const $margenInput = $row.find('.margen-producto-input');
                $margenInput.val(margenCalc.toFixed(2));
            }
            
            // Actualizar subtotal de la fila
            const cantidad = parseFloat(productosCotizacion[idx].cantidad) || 1;
            // Reutilizar la variable esBobina ya declarada arriba
            
            // 🎯 CÁLCULO CORRECTO DEL SUBTOTAL SEGÚN EL MODO
            let subtotal;
            if (esBobina && prod._modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
                // Para bobinas: permitir fracciones de bobinas × precio por bobina
                const bobinasCompletas = cantidad / PRECIO_CONFIG.metrosPorBobina;
                subtotal = bobinasCompletas * precio;
            } else {
                // Para metros o productos normales: cantidad × precio
                subtotal = cantidad * precio;
            }
            $(this).closest('tr').find('td').eq(6).find('span').first().text('$' + subtotal.toFixed(2));
            recalcularTotales();
            guardarBorrador();
        }
    });
    
    // Evento para margen editable en productos (con debounce para mejor UX)
    let margenTimeout;
    $('.margen-producto-input').off('input').on('input', function() {
        const $input = $(this);
        clearTimeout(margenTimeout);
        
        // Debounce de 300ms para evitar cálculos excesivos
        margenTimeout = setTimeout(function() {
            const index = parseInt($input.data('index'));
            let porcentaje = parseFloat($input.val());
            const prod = productosCotizacion[index];
            let costoUnitario = undefined;
            const esBobina = prod.tipo_gestion === 'bobina';
        if (esBobina && typeof prod.cost_price !== 'undefined' && prod.cost_price !== null && prod.cost_price !== '' && !isNaN(parseFloat(prod.cost_price))) {
            // Aplicar la misma lógica inteligente para bobinas
            const cantidad = parseFloat(prod.cantidad) || 1;
            const costPrice = parseFloat(prod.cost_price);
            
            // Detectar si es venta de bobinas completas (305m, 610m, 915m, etc.)
            const metrosPorBobina = PRECIO_CONFIG.metrosPorBobina; // Metros estándar por bobina
            const tolerancia = PRECIO_CONFIG.tolerancia; // Tolerancia para considerar bobina completa
            
            // Verificar si la cantidad es cercana a múltiplos de bobinas completas
            // Permitir fracciones de bobinas
            const bobinasCompletas = cantidad / metrosPorBobina;
            const bobinasRedondeadas = Math.round(bobinasCompletas);
            const metrosEsperados = bobinasRedondeadas * metrosPorBobina;
            const diferencia = Math.abs(cantidad - metrosEsperados);
            
            if (bobinasRedondeadas > 0 && diferencia <= tolerancia) {
                // Es venta de bobinas completas - usar costo completo dividido por cantidad total
                costoUnitario = costPrice / cantidad;
            } else {
                // Es venta por metros sueltos - calcular costo por metro
                costoUnitario = costPrice / metrosPorBobina;
            }
        } else if (typeof prod.cost_price !== 'undefined' && prod.cost_price !== null && prod.cost_price !== '' && !isNaN(parseFloat(prod.cost_price))) {
            costoUnitario = parseFloat(prod.cost_price);
        }
        if (prod && costoUnitario !== undefined && !isNaN(porcentaje)) {
            // Calcular nuevo precio según margen sobre precio de venta
            // margen = ((precio - costoUnitario) / precio) * 100 => precio = costoUnitario / (1 - margen/100)
            let nuevoPrecio = 0;
            if (porcentaje < 100) {
                nuevoPrecio = costoUnitario / (1 - porcentaje / 100);
            } else {
                nuevoPrecio = costoUnitario * 10; // Evitar división por cero, poner un precio alto
            }
            prod.precio = parseFloat(nuevoPrecio.toFixed(4));
            prod.margen = porcentaje;
            
            // 🎯 Actualizar solo el precio específico sin re-renderizar toda la tabla
            const $row = $input.closest('tr');
            const $precioInput = $row.find('.precio-input');
            $precioInput.val(prod.precio.toFixed(4));
            
            // Actualizar subtotal de la fila
            const cantidad = parseFloat(prod.cantidad) || 1;
            const esBobina = prod.tipo_gestion === 'bobina';
            
            // 🎯 CÁLCULO CORRECTO DEL SUBTOTAL SEGÚN EL MODO
            let subtotal;
            if (esBobina && prod._modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
                // Para bobinas: permitir fracciones de bobinas × precio por bobina
                const bobinasCompletas = cantidad / PRECIO_CONFIG.metrosPorBobina;
                subtotal = bobinasCompletas * prod.precio;
            } else {
                // Para metros o productos normales: cantidad × precio
                subtotal = cantidad * prod.precio;
            }
            
            $row.find('td').eq(6).find('span').first().text('$' + subtotal.toFixed(2));
            
            recalcularTotales();
            guardarBorrador();
        }
        }, 300); // Debounce de 300ms
    });
}

// Función para actualizar el nombre/descripción del producto
function actualizarNombreProducto(index, nuevoNombre) {
    if (productosCotizacion[index]) {
        productosCotizacion[index].nombre = nuevoNombre.trim();
        guardarBorrador();
        mostrarNotificacion('Nombre del producto actualizado', 'success');
    }
}

// Función para actualizar la descripción del producto
function actualizarDescripcionProducto(index, nuevaDescripcion) {
    if (productosCotizacion[index]) {
        productosCotizacion[index].description = nuevaDescripcion.trim();
        guardarBorrador();
        mostrarNotificacion('Descripción del producto actualizada', 'success');
    }
}

// Función para actualizar el costo del producto
function actualizarCostoProducto(index, nuevoCosto) {
    if (productosCotizacion[index]) {
        const costo = parseFloat(nuevoCosto) || 0;
        productosCotizacion[index].cost_price = costo;
        
        // Recalcular margen automáticamente
        const prod = productosCotizacion[index];
        const esBobina = prod.tipo_gestion === 'bobina';
        const precio = parseFloat(prod.precio) || 0;
        
        if (precio > 0 && costo > 0) {
            let costoUnitario = costo;
            
            // Para bobinas, ajustar el costo según el modo
            if (esBobina && prod._modoPrecio === PRECIO_CONFIG.modosPrecio.POR_METRO) {
                costoUnitario = costo / PRECIO_CONFIG.metrosPorBobina; // Costo por metro
            }
            
            // Calcular margen sobre precio de venta
            const margen = ((precio - costoUnitario) / precio) * 100;
            prod.margen = margen;
        }
        
        // Re-renderizar tabla para mostrar cambios
        renderTablaProductos();
        guardarBorrador();
        mostrarNotificacion('Costo actualizado y margen recalculado', 'success');
    }
}

// Eventos para cantidad de productos - Mejorados para bobinas completas vs metros
$(document).on('input', '.cantidad-input', function() {
    const index = parseInt($(this).data('index'));
    let value = parseFloat($(this).val());
    const prod = productosCotizacion[index];
    const modoPrecio = $(this).data('modo-precio');
    
    if (!prod) return;
    
    let esBobina = prod.tipo_gestion === 'bobina';
    
    // Validación básica sin re-renderizar
    if (value && !isNaN(value) && value > 0) {
        if (esBobina) {
            // 🎯 LÓGICA PARA BOBINAS CON DIFERENTES MODOS
            if (modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
                // 🔄 MODO BOBINAS COMPLETAS: Permitir fracciones de bobinas (ej: 1.5)
                value = Math.max(0.1, parseFloat(value.toFixed(1))); // Permitir decimales con 1 decimal
                const metrosTotal = value * PRECIO_CONFIG.metrosPorBobina;
                
                // Guardar los metros reales en el producto (para cálculos internos)
                prod.cantidad = metrosTotal;
                
                console.log(`🔄 Modo bobinas: ${value} bobina(s) = ${metrosTotal}m total`);
                
            } else {
                // 📏 MODO POR METROS: El usuario ingresa metros directamente
                value = Math.max(0.01, parseFloat(value.toFixed(2)));
                prod.cantidad = value;
                
                console.log(`📏 Modo metros: ${value}m`);
            }
        } else {
            // Productos normales (no bobinas)
            value = Math.max(1, Math.round(value));
            prod.cantidad = value;
        }
        
        $(this).removeClass('is-invalid');
    } else {
        $(this).addClass('is-invalid');
    }
    
    recalcularTotales();
    
    // 🎯 Solo actualizar subtotal de la fila sin re-renderizar toda la tabla
    if (value && !isNaN(value) && value > 0) {
        const $row = $(this).closest('tr');
        const precio = parseFloat(prod.precio) || 0;
        
        // 🎯 CÁLCULO CORRECTO DEL SUBTOTAL SEGÚN EL MODO DETECTADO
        let subtotal;
        // Usar el modo guardado en el producto, no el del input que puede estar desactualizado
        const modoProducto = prod._modoPrecio || PRECIO_CONFIG.modosPrecio.POR_METRO;
        
        if (esBobina && modoProducto === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
            // Para bobinas en modo POR_BOBINA: fracciones de bobinas × precio por bobina
            const bobinasCompletas = prod.cantidad / PRECIO_CONFIG.metrosPorBobina;
            subtotal = bobinasCompletas * precio;
            console.log(`DEBUG Global cantidad event - Bobina POR_BOBINA: ${prod.cantidad}m ÷ ${PRECIO_CONFIG.metrosPorBobina}m = ${bobinasCompletas} bobinas × $${precio} = $${subtotal}`);
        } else {
            // Para metros o productos normales: cantidad × precio
            subtotal = prod.cantidad * precio;
            console.log(`DEBUG Global cantidad event - Normal/Metro: ${prod.cantidad} × $${precio} = $${subtotal}`);
        }
        
        $row.find('td').eq(6).find('span').first().text('$' + subtotal.toFixed(2));
        
        // Para bobinas, actualizar la etiqueta de unidad si cambió el modo
        if (esBobina) {
            const metrosPorBobina = PRECIO_CONFIG.metrosPorBobina;
            // Permitir fracciones de bobinas en la visualización
            const bobinasCompletas = prod.cantidad / metrosPorBobina;
            const bobinasConDecimales = Math.round(bobinasCompletas * 10) / 10;
            const esModoBobina = $(this).data('modo-precio') === PRECIO_CONFIG.modosPrecio.POR_BOBINA;
            
            if (esModoBobina) {
                const $unidadSpan = $row.find('.unidad-display');
                $unidadSpan.text(` bobina${bobinasConDecimales !== 1 ? 's' : ''}`);
            }
        }
    }
});

// Eventos para precio de productos - Simplificados para mejor UX
$(document).on('input', '.precio-input', function() {
    const index = parseInt($(this).data('index'));
    let value = parseFloat($(this).val());
    const prod = productosCotizacion[index];
    
    if (!prod) return;
    
    // Validación básica sin re-renderizar
    if (value && !isNaN(value) && value >= 0) {
        prod.precio = parseFloat(value.toFixed(4));
        
        // 🎯 Recalcular margen automáticamente cuando cambia el precio
        let costoUnitario = undefined;
        const esBobina = prod.tipo_gestion === 'bobina';
        
        // Calcular costo unitario (misma lógica que en el renderizado)
        if (esBobina && typeof prod.cost_price !== 'undefined' && prod.cost_price !== null && prod.cost_price !== '' && !isNaN(parseFloat(prod.cost_price))) {
            const costPrice = parseFloat(prod.cost_price);
            
            // 🎯 CÁLCULO CORRECTO DEL COSTO SEGÚN EL MODO DE VENTA
            if (prod._modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
                // 🔄 MODO BOBINA ENTERA: Usar costo total de la bobina
                costoUnitario = costPrice;
            } else {
                // 📏 MODO POR METROS: Usar costo por metro
                costoUnitario = costPrice / PRECIO_CONFIG.metrosPorBobina;
            }
        } else if (typeof prod.cost_price !== 'undefined' && prod.cost_price !== null && prod.cost_price !== '' && !isNaN(parseFloat(prod.cost_price))) {
            costoUnitario = parseFloat(prod.cost_price);
        }
        
        // Actualizar margen si hay costo
        if (costoUnitario !== undefined && value > 0) {
            let margenCalc;
            
            // Para bobinas en modo POR_BOBINA, calcular margen considerando las fracciones
            if (esBobina && prod._modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
                const cantidad = parseFloat(prod.cantidad) || 1;
                const bobinasCompletas = cantidad / PRECIO_CONFIG.metrosPorBobina;
                const precioTotal = bobinasCompletas * value; // Precio total
                const costoTotal = bobinasCompletas * costoUnitario; // Costo total
                margenCalc = ((precioTotal - costoTotal) / precioTotal) * 100;
            } else {
                // Para productos normales o bobinas en modo metros
                margenCalc = (((value - costoUnitario) / value) * 100);
            }
            
            prod.margen = margenCalc;
            
            // Actualizar campo de margen en la interfaz
            const $row = $(this).closest('tr');
            const $margenInput = $row.find('.margen-producto-input');
            if ($margenInput.length) {
                $margenInput.val(margenCalc.toFixed(2));
            }
        }
        
        // Actualizar subtotal de la fila
        const cantidad = parseFloat(prod.cantidad) || 1;
        const $row = $(this).closest('tr');
        
        // 🎯 CÁLCULO CORRECTO DEL SUBTOTAL SEGÚN EL MODO DETECTADO
        let subtotal;
        // Usar el modo guardado en el producto, no detectar desde el DOM
        const modoProducto = prod._modoPrecio || PRECIO_CONFIG.modosPrecio.POR_METRO;
        
        if (esBobina && modoProducto === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
            // Para bobinas en modo POR_BOBINA: fracciones de bobinas × precio por bobina
            const bobinasCompletas = cantidad / PRECIO_CONFIG.metrosPorBobina;
            subtotal = bobinasCompletas * value;
            console.log(`DEBUG Global precio event - Bobina POR_BOBINA: ${cantidad}m ÷ ${PRECIO_CONFIG.metrosPorBobina}m = ${bobinasCompletas} bobinas × $${value} = $${subtotal}`);
        } else {
            // Para metros o productos normales: cantidad × precio
            subtotal = cantidad * value;
            console.log(`DEBUG Global precio event - Normal/Metro: ${cantidad} × $${value} = $${subtotal}`);
        }
        
        $row.find('td').eq(6).find('span').first().text('$' + subtotal.toFixed(2));
        
        $(this).removeClass('is-invalid');
    } else {
        $(this).addClass('is-invalid');
    }
    
    recalcularTotales();
});

// Evento global para margen de productos
let margenGlobalTimeout;
$(document).on('input', '.margen-producto-input', function() {
    const $input = $(this);
    clearTimeout(margenGlobalTimeout);
    
    // Debounce de 300ms para evitar cálculos excesivos
    margenGlobalTimeout = setTimeout(function() {
        const index = parseInt($input.data('index'));
        let porcentaje = parseFloat($input.val());
        const prod = productosCotizacion[index];
        
        if (!prod || isNaN(porcentaje)) return;
        
        let costoUnitario = undefined;
        const esBobina = prod.tipo_gestion === 'bobina';
        
        // Calcular costo unitario (misma lógica que en renderizado)
        if (esBobina && typeof prod.cost_price !== 'undefined' && prod.cost_price !== null && prod.cost_price !== '' && !isNaN(parseFloat(prod.cost_price))) {
            const costPrice = parseFloat(prod.cost_price);
            
            // 🎯 CÁLCULO CORRECTO DEL COSTO SEGÚN EL MODO DE VENTA
            if (prod._modoPrecio === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
                // 🔄 MODO BOBINA ENTERA: Usar costo total de la bobina
                costoUnitario = costPrice;
            } else {
                // 📏 MODO POR METROS: Usar costo por metro
                costoUnitario = costPrice / PRECIO_CONFIG.metrosPorBobina;
            }
        } else if (typeof prod.cost_price !== 'undefined' && prod.cost_price !== null && prod.cost_price !== '' && !isNaN(parseFloat(prod.cost_price))) {
            costoUnitario = parseFloat(prod.cost_price);
        }
        
        if (costoUnitario !== undefined) {
            // Calcular nuevo precio según margen sobre precio de venta
            let nuevoPrecio = 0;
            if (porcentaje < 100) {
                nuevoPrecio = costoUnitario / (1 - porcentaje / 100);
            } else {
                nuevoPrecio = costoUnitario * 10; // Evitar división por cero
            }
            
            prod.precio = parseFloat(nuevoPrecio.toFixed(4));
            prod.margen = porcentaje;
            
            // Actualizar precio en la interfaz
            const $row = $input.closest('tr');
            const $precioInput = $row.find('.precio-input');
            $precioInput.val(prod.precio.toFixed(4));
            
            // Actualizar subtotal de la fila
            const cantidad = parseFloat(prod.cantidad) || 1;
            const esBobina = prod.tipo_gestion === 'bobina';
            
            // 🎯 CÁLCULO CORRECTO DEL SUBTOTAL SEGÚN EL MODO DETECTADO
            let subtotal;
            // Usar el modo guardado en el producto, no detectar desde el DOM
            const modoProducto = prod._modoPrecio || PRECIO_CONFIG.modosPrecio.POR_METRO;
            
            if (esBobina && modoProducto === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
                // Para bobinas en modo POR_BOBINA: fracciones de bobinas × precio por bobina
                const bobinasCompletas = cantidad / PRECIO_CONFIG.metrosPorBobina;
                subtotal = bobinasCompletas * prod.precio;
                console.log(`DEBUG Global margen event - Bobina POR_BOBINA: ${cantidad}m ÷ ${PRECIO_CONFIG.metrosPorBobina}m = ${bobinasCompletas} bobinas × $${prod.precio} = $${subtotal}`);
            } else {
                // Para metros o productos normales: cantidad × precio
                subtotal = cantidad * prod.precio;
                console.log(`DEBUG Global margen event - Normal/Metro: ${cantidad} × $${prod.precio} = $${subtotal}`);
            }
            $row.find('td').eq(6).find('span').first().text('$' + subtotal.toFixed(2));
            
            recalcularTotales();
            guardarBorrador();
        }
    }, 300);
});

// 🔄 Evento para cambiar modo de precio en bobinas (Bobinas vs Metros) - CON PRECIO BASE CORRECTO
$(document).on('click', '.cambiar-modo-btn', function() {
    const index = parseInt($(this).data('index'));
    const prod = productosCotizacion[index];
    
    if (!prod || prod.tipo_gestion !== 'bobina') return;
    
    // 🎯 DETECTAR MODO ACTUAL CORRECTAMENTE usando la propiedad guardada
    const modoActual = prod._modoPrecio || PRECIO_CONFIG.modosPrecio.POR_METRO;
    const nuevoModo = modoActual === PRECIO_CONFIG.modosPrecio.POR_BOBINA 
        ? PRECIO_CONFIG.modosPrecio.POR_METRO 
        : PRECIO_CONFIG.modosPrecio.POR_BOBINA;
    
    // Obtener precio base por metro
    const metrosPorBobina = PRECIO_CONFIG.metrosPorBobina;
    const cantidadActual = parseFloat(prod.cantidad) || metrosPorBobina;
    let precioBasePorMetro;
    
    // Determinar precio base por metro
    if (prod._precioBase) {
        precioBasePorMetro = prod._precioBase;
    } else {
        // Calcular precio base según modo actual
        const precioActual = parseFloat(prod.precio) || 0;
        if (modoActual === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
            precioBasePorMetro = precioActual / metrosPorBobina;
        } else {
            precioBasePorMetro = precioActual;
        }
        prod._precioBase = precioBasePorMetro;
    }
    
    console.log(`🔍 Modo actual: ${modoActual}, Nuevo modo: ${nuevoModo}, Precio base: $${precioBasePorMetro.toFixed(4)}/metro`);
    
    if (nuevoModo === PRECIO_CONFIG.modosPrecio.POR_BOBINA) {
        // 📦 Cambiar de METROS → BOBINAS COMPLETAS (permitir fracciones)
        const bobinasCompletas = Math.max(0.1, parseFloat((cantidadActual / metrosPorBobina).toFixed(1)));
        prod.cantidad = bobinasCompletas * metrosPorBobina;
        
        // 🎯 PRECIO INDEPENDIENTE: Si tiene precio original de bobina, usarlo; sino calcular
        if (prod._precioBobinaOriginal) {
            prod.precio = prod._precioBobinaOriginal; // Usar precio original de bobina
        } else {
            prod.precio = parseFloat((precioBasePorMetro * metrosPorBobina).toFixed(4)); // Calcular si no hay precio original
        }
        prod._modoPrecio = PRECIO_CONFIG.modosPrecio.POR_BOBINA;
        
        console.log(`📦 Conversión M→B: Bobina $${prod.precio} (precio independiente)`);
        mostrarNotificacion(`🔄 Modo bobinas: ${bobinasCompletas} bobina(s) × $${prod.precio.toFixed(2)} = $${(bobinasCompletas * prod.precio).toFixed(2)}`, 'info');
        
    } else {
        // 📏 Cambiar de BOBINAS → METROS  
        // Guardar precio de bobina antes de cambiar a metros
        if (!prod._precioBobinaOriginal) {
            prod._precioBobinaOriginal = parseFloat(prod.precio);
        }
        
        // Precio = precio base por metro (independiente del precio de bobina)
        prod.precio = parseFloat(precioBasePorMetro.toFixed(4));
        prod._modoPrecio = PRECIO_CONFIG.modosPrecio.POR_METRO;
        
        console.log(`📏 Conversión B→M: Metro $${precioBasePorMetro.toFixed(4)} (precio independiente)`);
        mostrarNotificacion(`📏 Modo metros: ${cantidadActual}m × $${prod.precio.toFixed(4)}/m = $${(cantidadActual * prod.precio).toFixed(2)}`, 'info');
    }
    
    console.log(`✅ Conversión completada: ${modoActual} → ${nuevoModo} | Precio base: $${precioBasePorMetro.toFixed(4)}/m`);
    
    // Re-renderizar tabla para actualizar interfaz
    renderTablaProductos();
    recalcularTotales();
    guardarBorrador();
});

// --- SERVICIOS ---
// Función para actualizar el nombre/descripción del servicio
function actualizarNombreServicio(index, nuevoNombre) {
    if (serviciosCotizacion[index]) {
        serviciosCotizacion[index].nombre = nuevoNombre.trim();
        guardarBorrador();
        mostrarNotificacion('Nombre del servicio actualizado', 'success');
    }
}

// Función para actualizar el costo del servicio
function actualizarCostoServicio(index, nuevoCosto) {
    if (serviciosCotizacion[index]) {
        const costo = parseFloat(nuevoCosto) || 0;
        serviciosCotizacion[index].costo = costo;
        
        // Recalcular margen si hay precio
        const servicio = serviciosCotizacion[index];
        const precio = parseFloat(servicio.precio) || 0;
        
        if (precio > 0 && costo > 0) {
            const margen = ((precio - costo) / precio) * 100;
            servicio.margen = margen;
        }
        
        guardarBorrador();
        mostrarNotificacion('Costo del servicio actualizado', 'success');
    }
}

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
    limpiarPaquetesPromocionales();
    renderTablaServicios();
    recalcularTotales();
    guardarBorrador();
});

function renderTablaServicios() {
    let html = '';
    serviciosCotizacion.forEach((s, i) => {
        const sub = (parseFloat(s.precio) || 0) * (parseFloat(s.cantidad) || 1);
        
        // Verificar si es parte de un paquete promocional
        const esPromocional = s.es_promocional && s.paquete_promocional;
        const borderColor = esPromocional ? '#28a745' : '#17a2b8';
        const borderStyle = esPromocional ? '3px solid' : '3px solid';
        
        html += `
            <tr style="background:#ffffff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:12px; border:none; transition:all 0.3s ease; border-left: ${borderStyle} ${borderColor};">
                <td style="text-align:center; vertical-align:middle; padding:16px 12px;">
                    ${s.imagen ? `<img src="../uploads/services/${s.imagen}" alt="${s.nombre}" style="width:60px; height:60px; object-fit:cover; border-radius:8px; border:none; box-shadow:0 2px 6px rgba(0,0,0,0.1);" onerror="this.style.display='none'">` : '<div style="width:60px; height:60px; background:#f8f9fa; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#6c757d; font-weight:600; font-size:0.8rem; border:1px solid #dee2e6;">Sin img</div>'}
                </td>
                <td style="font-weight:600; color:#495057; padding:16px 12px; min-width:180px; vertical-align:middle;">
                    <div style="display:flex; flex-direction:column; gap:4px;">
                        <input type="text" value="${s.nombre}" class="form-control form-control-sm nombre-servicio-input" data-index="${i}" style="border:1px solid transparent; background:transparent; padding:2px 4px; font-weight:600; font-size:1.1rem; color:#212529;" onblur="actualizarNombreServicio(${i}, this.value)" placeholder="Nombre del servicio">
                        ${s.tiempo_estimado ? `<small style="color:#6c757d; font-weight:500;">Tiempo: ${s.tiempo_estimado}h</small>` : ''}
                        ${esPromocional ? `<small style="color:#28a745; font-weight:600; font-size:0.7rem;"><i class="bi bi-percent"></i> Paquete promocional: ${s.paquete_promocional.nombre}</small>` : ''}
                    </div>
                </td>
                <td style="text-align:center; vertical-align:middle; padding:16px 12px;">
                    ${s.paquete_id ? `
                        <div style="display:flex; flex-direction:column; align-items:center; gap:6px;">
                            <span class="badge" style="background:#17a2b8; color:white; font-size:0.8rem; padding:6px 10px; border-radius:6px; font-weight:600; display:flex; align-items:center; gap:4px;">
                                <i class="bi bi-link-45deg"></i> Conectado
                            </span>
                            <label style="display:flex; align-items:center; gap:4px; font-size:0.8rem; color:#495057; cursor:pointer;">
                                <input type="checkbox" class="sync-checkbox-servicio" data-index="${i}" ${s.sincronizado !== false ? 'checked' : ''} title="Sincronizar con principal" style="accent-color:#17a2b8;">
                                Sync
                            </label>
                        </div>
                    ` : `<button type="button" class="btn btn-sm" onclick="conectarServicioAPaquete(${i})" title="Conectar a paquete inteligente" style="background:#17a2b8; color:white; border:none; border-radius:6px; padding:8px 12px; font-weight:600; transition:all 0.3s ease;"><i class="bi bi-link"></i> Conectar</button>`}
                </td>
                <td style="vertical-align:middle; padding:16px 12px;">
                    <input type="number" min="1" step="1" value="${Math.round(s.cantidad)}" class="form-control form-control-sm cantidad-servicio-input" data-index="${i}" data-paquete-id="${s.paquete_id || ''}" data-tipo-paquete="${s.tipo_paquete || ''}" style="width:100px; background:#f8f9fa; border:2px solid #dee2e6; border-radius:6px; text-align:center; font-weight:600; padding:8px;">
                    ${s.unidad_medida ? ` ${s.unidad_medida}` : ''}
                </td>
                <td style="vertical-align:middle; padding:16px 12px;">
                    <div class="input-group" style="width:130px;">
                        <span class="input-group-text" style="background:#28a745; color:white; border:none; border-radius:6px 0 0 6px; font-weight:600;">$</span>
                        <input type="number" min="0" step="0.01" value="${s.precio || ''}" class="form-control form-control-sm precio-servicio-input" data-index="${i}" style="background:#f8f9fa; border:2px solid #dee2e6; border-left:none; border-radius:0 6px 6px 0; text-align:center; font-weight:600;">
                    </div>
                </td>
                <td style="vertical-align:middle; padding:16px 12px;">
                    <div style="display:flex; flex-direction:column; align-items:center; gap:2px;">
                        ${s.costo !== undefined && s.costo !== null && s.costo !== '' ? `
                            <div class="input-group" style="width:130px;">
                                <span class="input-group-text" style="background:#6c757d; color:white; border:none; border-radius:6px 0 0 6px; font-weight:600; font-size:0.85rem; padding:8px;">$</span>
                                <input type="number" min="0" step="0.01" value="${parseFloat(s.costo).toFixed(2)}" class="form-control form-control-sm costo-servicio-input" data-index="${i}" style="background:#f1f3f4; border:2px solid #adb5bd; border-left:none; border-radius:0 6px 6px 0; text-align:center; font-weight:600; font-size:0.9rem; padding:8px;" onblur="actualizarCostoServicio(${i}, this.value)">
                            </div>
                            <small style="color:#6c757d; font-weight:500; font-size:0.75rem;">por servicio</small>
                        ` : `
                            <div class="input-group" style="width:130px;">
                                <span class="input-group-text" style="background:#6c757d; color:white; border:none; border-radius:6px 0 0 6px; font-weight:600; font-size:0.85rem; padding:8px;">$</span>
                                <input type="number" min="0" step="0.01" value="0" class="form-control form-control-sm costo-servicio-input" data-index="${i}" style="background:#f1f3f4; border:2px solid #adb5bd; border-left:none; border-radius:0 6px 6px 0; text-align:center; font-weight:600; font-size:0.9rem; padding:8px;" onblur="actualizarCostoServicio(${i}, this.value)" placeholder="Sin costo">
                            </div>
                            <small style="color:#adb5bd; font-size:0.75rem; font-style:italic;">Sin costo</small>
                        `}
                    </div>
                </td>
                <td style="font-weight:700; color:#007bff; padding:16px 12px; text-align:right; vertical-align:middle;">
                    <span style="font-size:1.2rem;">$${sub.toFixed(2)}</span>
                </td>
                <td style="text-align:center; vertical-align:middle; width:70px; padding:16px 12px;">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-servicio" data-idx="${i}" title="Eliminar servicio" style="width:36px; height:36px; display:flex; justify-content:center; align-items:center; padding:0; border-radius:6px; border:2px solid #dc3545; transition:all 0.3s ease;">
                        <i class="bi bi-trash" style="font-size:1rem;"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    $('#tablaServiciosCotizacion tbody').html(html);
    recalcularTotales();
    
    // Eventos para servicios - solo cálculos sin auto-selección
    $('.cantidad-servicio-input').off('input').on('input', function() {
        const idx = $(this).data('index');
        const cantidad = parseFloat($(this).val()) || 1;
        if (serviciosCotizacion[idx]) {
            serviciosCotizacion[idx].cantidad = cantidad;
            // Actualizar subtotal de la fila
            const precio = parseFloat(serviciosCotizacion[idx].precio) || 0;
            const subtotal = cantidad * precio;
            $(this).closest('tr').find('td').eq(5).find('span').first().text('$' + subtotal.toFixed(2));
            recalcularTotales();
            guardarBorrador();
        }
    });

    $('.precio-servicio-input').off('input').on('input', function() {
        const idx = $(this).data('index');
        const precio = parseFloat($(this).val()) || 0;
        if (serviciosCotizacion[idx]) {
            serviciosCotizacion[idx].precio = precio;
            // Actualizar subtotal de la fila
            const cantidad = parseFloat(serviciosCotizacion[idx].cantidad) || 1;
            const subtotal = cantidad * precio;
            $(this).closest('tr').find('td').eq(5).find('span').first().text('$' + subtotal.toFixed(2));
            recalcularTotales();
            guardarBorrador();
        }
    });
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
    }
    // NO re-renderizar tabla aquí para evitar perder foco
});

$(document).on('focus', '.cantidad-servicio-input', function() {
    // Permitir edición normal sin interferencias
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
    // NO re-renderizar tabla aquí para evitar perder foco
});

$(document).on('focus', '.precio-servicio-input', function() {
    // Permitir edición normal sin interferencias
});

// EVENTO SUBMIT CON AJAX
$(document).ready(function() {
    // Asegurarse de que los campos ocultos existan
    if ($('#subtotal_frontend').length === 0) {
        $('<input>').attr({
            type: 'hidden',
            id: 'subtotal_frontend',
            name: 'subtotal_frontend'
        }).appendTo('#formCrearCotizacion');
    }
    if ($('#descuento_porcentaje_frontend').length === 0) {
        $('<input>').attr({
            type: 'hidden',
            name: 'descuento_porcentaje_frontend',
            id: 'descuento_porcentaje_frontend'
        }).appendTo('#formCrearCotizacion');
    }
    if ($('#descuento_monto_frontend').length === 0) {
        $('<input>').attr({
            type: 'hidden',
            id: 'descuento_monto_frontend',
            name: 'descuento_monto_frontend'
        }).appendTo('#formCrearCotizacion');
    }
    if ($('#total_frontend').length === 0) {
        $('<input>').attr({
            type: 'hidden',
            id: 'total_frontend',
            name: 'total_frontend'
        }).appendTo('#formCrearCotizacion');
    }

    // Manejador de clic en el botón de guardar
    $('button[type="submit"]').on('click', function(e) {
        e.preventDefault();
        $('#formCrearCotizacion').trigger('submit');
    });

    // Manejador de envío del formulario
    $('#formCrearCotizacion').on('submit', function(e) {
        e.preventDefault();
        
        const $submitButton = $(this).find('button[type="submit"]');
        $submitButton.prop('disabled', true).html('<i class="bi bi-arrow-repeat"></i> Guardando...');
        
        let error = '';
        const nombre = $('#cliente_nombre').val().trim();
        const clienteId = $('#cliente_id').val(); // Get the client ID from the hidden input
        if ((!clienteId || clienteId === '') && !nombre) {
            error = 'Debes seleccionar un cliente existente o registrar uno nuevo con al menos el nombre.';
        }

        if (productosCotizacion.length === 0 && serviciosCotizacion.length === 0 && insumosCotizacion.length === 0) {
            error = 'Debes agregar al menos un producto, servicio o insumo a la cotización.';
        }

        // Asegurarse de que los totales estén actualizados
        recalcularTotales();

        if (error) {
            mostrarNotificacion(error, 'danger');
            $submitButton.prop('disabled', false).html('<i class="bi bi-check-circle"></i> Guardar Cotización');
            return;
        }

        // Crear FormData a partir del formulario
        const formData = new FormData(this);

        // Asegurarse de que los campos ocultos tengan los valores más recientes
        formData.set('subtotal_frontend', $('#subtotal_frontend').val());
        formData.set('descuento_porcentaje_frontend', $('#descuento_porcentaje_frontend').val());
        formData.set('descuento_monto_frontend', $('#descuento_monto_frontend').val());
        formData.set('total_frontend', $('#total_frontend').val());
        
        // Asegurarse de que el campo observaciones esté incluido
        formData.set('observaciones', $('#observaciones').val());

        // Añadir los datos de las tablas que están en arrays de JS
        formData.append('productos_json', JSON.stringify(productosCotizacion));
        formData.append('servicios_json', JSON.stringify(serviciosCotizacion));
        formData.append('insumos_json', JSON.stringify(insumosCotizacion));
        
        // Depuración: Mostrar el contenido de FormData
        console.log('Contenido de FormData:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
        
        fetch('crear.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            console.log("Respuesta del servidor recibida:", response);
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
            }
            return response.text();
        })
        .then(text => {
            console.log("Texto de la respuesta:", text);
            try {
                const json = JSON.parse(text);
                console.log("JSON parseado:", json);
                if (json.success && json.redirect_url) {
                    window.location.href = json.redirect_url;
                } else {
                    throw new Error(json.error || 'El servidor devolvió un error desconocido.');
                }
            } catch (e) {
                console.error("Error al parsear JSON:", e);
                throw new Error("La respuesta del servidor no es válida. Revise la consola para más detalles.");
            }
        })
        .catch(error => {
            console.error("Error en el proceso de guardado:", error);
            mostrarNotificacion('Error al guardar: ' + error.message, 'danger');
            $submitButton.prop('disabled', false).html('<i class="bi bi-check-circle"></i> Guardar Cotización');
        });
    });

    // Prevenir submit por Enter accidental
    $('#formCrearCotizacion').on('keydown', function(e) {
        if (e.key === 'Enter') {
            const isSubmitBtn = document.activeElement && document.activeElement.type === 'submit';
            if (!isSubmitBtn) {
                e.preventDefault();
                return false;
            }
        }
    });

    // Vincular eventos para recalcular totales
    $('#descuento_porcentaje, #condicion_iva').on('input', function() {
        recalcularTotales();
    });

    // Llamada inicial para establecer los campos ocultos
    recalcularTotales();
}); // Cerrar $(document).ready()

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
            // Calcular precio total o mostrar precio promocional
            let precioInfo = '';
            if (paq.es_promocional && paq.precio_personalizado) {
                precioInfo = `<div class="text-center mb-2">
                    <span class="badge bg-warning text-dark">
                        <i class="bi bi-percent"></i> PROMOCIONAL
                    </span>
                    <div class="fw-bold text-success">$${parseFloat(paq.precio_personalizado).toFixed(2)}</div>
                </div>`;
            } else {
                precioInfo = `<div class="text-center mb-2">
                    <span class="badge bg-info">
                        <i class="bi bi-calculator"></i> NORMAL
                    </span>
                    <div class="text-muted small">Precio calculado</div>
                </div>`;
            }
            
            html += `<div class="col-md-6 col-lg-4">
                <div class="card shadow-sm h-100 ${paq.es_promocional ? 'border-warning' : ''}">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0 flex-grow-1" style="font-size:1rem; line-height:1.3; overflow-wrap: break-word; word-break: break-word;" title="${paq.nombre}">${paq.nombre.length > 25 ? paq.nombre.substring(0, 25) + '...' : paq.nombre}</h5>
                            <span class="badge bg-primary ms-2 flex-shrink-0">${paq.items.length}</span>
                        </div>
                        ${precioInfo}
                        <div class="mb-2" style="max-height: 120px; overflow-y: auto;">
                            <ul class="list-group list-group-flush" style="font-size:0.85rem;">
                                ${paq.items.map(item => {
                                    const itemName = item.nombre || 'Sin nombre';
                                    const displayName = itemName.length > 20 ? itemName.substring(0, 20) + '...' : itemName;
                                    return `<li class="list-group-item py-1 px-2 d-flex justify-content-between align-items-center border-0" style="background: transparent;">
                                        <span class="flex-grow-1" title="${itemName}">
                                            <strong>${displayName}</strong> 
                                            <small class="text-muted">x${item.factor || 1}</small>
                                        </span>
                                        <span class="badge ${item.tipo === 'principal' ? 'bg-success' : 'bg-secondary'} ms-1 flex-shrink-0" style="font-size:0.7rem;">
                                            ${item.tipo === 'principal' ? 'P' : 'R'}
                                        </span>
                                    </li>`;
                                }).join('')}
                            </ul>
                        </div>
                        <div class="mt-auto d-flex gap-1 justify-content-end">
                            <button class="btn btn-sm btn-success" onclick="aplicarPaqueteCotizacion(${idx});" title="Aplicar paquete">
                                <i class="bi bi-play-fill"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="editarPaquete(${idx});" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="duplicarPaquete(${idx});" title="Duplicar">
                                <i class="bi bi-files"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarPaquete(${idx});" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
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
    // Mantener propiedades promocionales en la copia
    copia.es_promocional = paq.es_promocional || false;
    copia.precio_personalizado = paq.precio_personalizado || null;
    window.PaquetesCotizacion.addPaquete(copia);
    renderPaquetesPanel();
    mostrarNotificacion('Paquete duplicado correctamente.', 'success');
}

// --- PAQUETES INTELIGENTES ---
function aplicarPaqueteCotizacion(idx, event) {
    const paquetes = window.PaquetesCotizacion.getPaquetes();
    const paquete = paquetes[idx];
    if (!paquete) return;
    
    // Prevenir que se dispare el submit del formulario solo si hay un evento
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
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
                    description: prod.description || '',
                    sku: prod.sku,
                    categoria: prod.categoria,
                    proveedor: prod.proveedor,
                    stock: prod.tipo_gestion === 'bobina' ? (prod.stock_disponible || 0) : prod.stock_disponible,
                    cantidad: prod.tipo_gestion === 'bobina' ? (item.factor || 1.00) : (item.factor || 1),
                    precio: prod.price,
                    tipo_gestion: prod.tipo_gestion,
                    cost_price: prod.cost_price, // Añadir cost_price para cálculo de margen
                    image: prod.image, // Añadir imagen
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
                    costo: ins.costo, // Añadir costo para cálculo de margen
                    cost_price: ins.costo, // Alias por compatibilidad
                    imagen: ins.imagen, // Añadir imagen
                    paquete_id: paqueteId,
                    tipo_paquete: (item.tipo_item === 'insumo' && item.insumo_id == principalInsumo) ? 'principal' : 'relacionado',
                    factor_paquete: item.factor,
                    sincronizado: true
                });
            }
        }
    });
    // --- DEPURACIÓN ---
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
    
    // Si es un paquete promocional, configurar el precio especial
    if (paquete.es_promocional && paquete.precio_personalizado) {
        // Marcar todos los items del paquete como promocionales y guardar datos del paquete
        const paqueteInfo = {
            id: paqueteId,
            nombre: paquete.nombre,
            precio_promocional: parseFloat(paquete.precio_personalizado),
            precio_original: 0 // Se calculará después
        };
        
        // Calcular precio original del paquete (suma de todos los items)
        let precioOriginal = 0;
        productosCotizacion.filter(p => p.paquete_id === paqueteId).forEach(p => {
            precioOriginal += (parseFloat(p.precio) || 0) * (parseFloat(p.cantidad) || 1);
            p.es_promocional = true;
            p.paquete_promocional = paqueteInfo;
        });
        serviciosCotizacion.filter(s => s.paquete_id === paqueteId).forEach(s => {
            precioOriginal += (parseFloat(s.precio) || 0) * (parseFloat(s.cantidad) || 1);
            s.es_promocional = true;
            s.paquete_promocional = paqueteInfo;
        });
        insumosCotizacion.filter(i => i.paquete_id === paqueteId).forEach(i => {
            precioOriginal += (parseFloat(i.precio) || 0) * (parseFloat(i.cantidad) || 1);
            i.es_promocional = true;
            i.paquete_promocional = paqueteInfo;
        });
        
        paqueteInfo.precio_original = precioOriginal;
        
        // Guardar información del paquete promocional globalmente
        if (!window.paquetesPromocionales) window.paquetesPromocionales = [];
        window.paquetesPromocionales.push(paqueteInfo);
        
        mostrarNotificacion(`Aplicado paquete promocional "${paquete.nombre}": $${parseFloat(paquete.precio_personalizado).toFixed(2)} (Original: $${precioOriginal.toFixed(2)})`, 'info');
    }
    
    // Recalcular totales para aplicar precios promocionales
    recalcularTotales();
    
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
    
    if (!principal) {
        return;
    }
    
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
    recalcularTotales();
}
function eliminarPaquete(idx, event) {
    // Prevenir que se dispare el submit del formulario solo si hay un evento
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
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
    window._paqEdit.es_promocional = document.getElementById('paqEsPromocional').checked;
    const precioInput = document.getElementById('paqPrecioPersonalizado');
    window._paqEdit.precio_personalizado = precioInput && precioInput.value ? parseFloat(precioInput.value) : null;
    
    if (!window._paqEdit.nombre || window._paqEdit.items.length === 0) {
        mostrarNotificacion('Ponle nombre y al menos un producto al paquete.', 'warning');
        return;
    }
    
    // Validar que si es promocional, tenga precio personalizado
    if (window._paqEdit.es_promocional && (!window._paqEdit.precio_personalizado || window._paqEdit.precio_personalizado <= 0)) {
        mostrarNotificacion('Los paquetes promocionales deben tener un precio personalizado válido.', 'warning');
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
        condiciones_pago: '', // Campo eliminado
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
    
    // Normalizar descripciones en productos
    productosCotizacion = (datos.productos || []).map(prod => normalizarProducto(prod));
    
    serviciosCotizacion = datos.servicios || [];
    insumosCotizacion = datos.insumos || [];
    renderTablaProductos();
    renderTablaServicios();
    renderTablaInsumos();
    $('input[name="fecha_cotizacion"]').val(datos.fecha_cotizacion || '');
    $('input[name="validez_dias"]').val(datos.validez_dias || '');
    $('#estado_id').val(datos.estado_id || '');
    // Campo condiciones_pago eliminado del formulario
    $('textarea[name="observaciones"]').val(datos.observaciones || '');
    $('#descuento_porcentaje').val(datos.descuento_porcentaje || 0);
    recalcularTotales();
}

// Guardar borrador al cambiar datos relevantes (condiciones_pago eliminado)
$(document).on('input change', '#cliente_select, #cliente_nombre, #cliente_telefono, #cliente_ubicacion, #cliente_email, #estado_id, input[name="fecha_cotizacion"], input[name="validez_dias"], textarea[name="observaciones"], #descuento_porcentaje', function() {
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
// COMENTADO - Ya manejado en el evento submit principal
// $('#formCrearCotizacion').on('submit', function() {
//     // Asegurar que los campos ocultos estén actualizados antes del envío
//     recalcularTotales();
//     console.log('📝 Enviando formulario con totales:', {
//         subtotal: $('#subtotal_frontend').val(),
//         descuento: $('#descuento_monto_frontend').val(),
//         total: $('#total_frontend').val()
//     });
//     limpiarBorrador();
// });

// Función para actualizar el nombre/descripción del insumo
function actualizarNombreInsumo(index, nuevoNombre) {
    if (insumosCotizacion[index]) {
        insumosCotizacion[index].nombre = nuevoNombre.trim();
        guardarBorrador();
        mostrarNotificacion('Nombre del insumo actualizado', 'success');
    }
}

// Función para actualizar la descripción del insumo
function actualizarDescripcionInsumo(index, nuevaDescripcion) {
    if (insumosCotizacion[index]) {
        insumosCotizacion[index].descripcion = nuevaDescripcion.trim();
        guardarBorrador();
        mostrarNotificacion('Descripción del insumo actualizada', 'success');
    }
}

// Función para actualizar el costo del insumo
function actualizarCostoInsumo(index, nuevoCosto) {
    if (insumosCotizacion[index]) {
        const costo = parseFloat(nuevoCosto) || 0;
        insumosCotizacion[index].costo = costo;
        insumosCotizacion[index].cost_price = costo; // Mantener compatibilidad
        
        // Recalcular margen si hay precio
        const insumo = insumosCotizacion[index];
        const precio = parseFloat(insumo.precio) || 0;
        
        if (precio > 0 && costo > 0) {
            const margen = ((precio - costo) / precio) * 100;
            insumo.margen = margen;
        }
        
        // Re-renderizar tabla para mostrar cambios
        renderTablaInsumos();
        guardarBorrador();
        mostrarNotificacion('Costo del insumo actualizado y margen recalculado', 'success');
    }
}

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
                // Incluir data-costo y data-cost_price si existen en el objeto ins
                let dataCosto = '';
                if (typeof ins.costo !== 'undefined' && ins.costo !== null && ins.costo !== '' && !isNaN(parseFloat(ins.costo))) {
                    dataCosto = `data-costo='${ins.costo}'`;
                }
                let dataCostPrice = '';
                if (typeof ins.cost_price !== 'undefined' && ins.cost_price !== null && ins.cost_price !== '' && !isNaN(parseFloat(ins.cost_price))) {
                    dataCostPrice = `data-cost_price='${ins.cost_price}'`;
                }
                sugerencias += `<button type='button' class='list-group-item list-group-item-action' data-id='${ins.insumo_id}' data-nombre='${ins.nombre}' data-categoria='${ins.categoria_nombre||''}' data-proveedor='${ins.proveedor||''}' data-stock='${ins.cantidad}' data-precio='${ins.precio_unitario}' data-imagen='${ins.imagen||''}' ${dataCosto} ${dataCostPrice}>
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
        precioFinal = (precioBolsa / equivalencia).toFixed(2);
    } else {
        precioFinal = precioBolsa;
    }
    // Tomar costo/cost_price si existen en el objeto ins
    let costo = $(this).data('costo');
    if (typeof costo === 'undefined' || costo === null || costo === '' || isNaN(parseFloat(costo))) {
        costo = $(this).data('cost_price');
    }
    // Si equivalencia > 1 y costo es por bolsa, convertir a unitario
    let costoUnitario = (typeof costo !== 'undefined' && costo !== null && costo !== '' && !isNaN(parseFloat(costo))) ? parseFloat(costo) : undefined;
    if (costoUnitario !== undefined && equivalencia > 1) {
        costoUnitario = costoUnitario / equivalencia;
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
        unidad: unidad,
        costo: costoUnitario,
        imagen: $(this).data('imagen') || ''
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
        // IVA REMOVED
        const sub = (parseFloat(ins.precio) || 0) * (parseFloat(ins.cantidad) || 1);
        subtotal += sub;
        const nombreGoogle = encodeURIComponent(ins.nombre || '');
        
        // Margen sobre precio base (costo)
        let costoReal = undefined;
        if (ins.costo !== undefined && ins.costo !== null && ins.costo !== '' && !isNaN(parseFloat(ins.costo))) {
            costoReal = parseFloat(ins.costo);
        } else if (ins.cost_price !== undefined && ins.cost_price !== null && ins.cost_price !== '' && !isNaN(parseFloat(ins.cost_price))) {
            costoReal = parseFloat(ins.cost_price);
        }
        // Mostrar margen guardado si existe, si no calcular
        let margen = '';
        let margenNegativo = false;
        if (typeof ins.margen !== 'undefined' && ins.margen !== null && ins.margen !== '') {
            margenNegativo = parseFloat(ins.margen) < 0;
            margen = Math.abs(parseFloat(ins.margen)).toFixed(2);
        } else if (costoReal !== undefined && parseFloat(ins.precio) > 0) {
            let margenCalc = (((parseFloat(ins.precio) - costoReal) / costoReal) * 100);
            margenNegativo = margenCalc < 0;
            margen = isFinite(margenCalc) ? Math.abs(parseFloat(margenCalc)).toFixed(2) : '0.00';
        } else if (costoReal !== undefined && parseFloat(ins.precio) === 0) {
            margen = '0.00';
        }
        
        // Imagen
        const imagenPath = ins.imagen && ins.imagen.trim() !== '' ? 
            (ins.imagen.startsWith('uploads/') ? `../${ins.imagen}` : `../uploads/insumos/${ins.imagen}`) : '';
        const imagenHtml = imagenPath ? 
            `<img src="${imagenPath}" alt="${ins.nombre}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;" onerror="this.style.display='none'">` : 
            '<span style="color: #ccc; font-size: 12px;">Sin imagen</span>';

        // Verificar si es parte de un paquete promocional
        const esPromocional = ins.es_promocional && ins.paquete_promocional;
        const borderColor = esPromocional ? '#28a745' : '#17a2b8';
        const borderStyle = esPromocional ? '3px solid' : '2px solid';

        html += `
            <tr style="background:#ffffff; border-radius:6px; box-shadow:0 1px 4px rgba(0,0,0,0.08); margin-bottom:8px; border:none; transition:all 0.3s ease; border-left: ${borderStyle} ${borderColor};">
                <td style="text-align:center; vertical-align:top; padding:8px 6px; width:90px;">
                    ${imagenPath ? `<img src="${imagenPath}" alt="${ins.nombre}" style="width:65px; height:65px; object-fit:cover; border-radius:8px; border:2px solid #e9ecef; box-shadow:0 2px 4px rgba(0,0,0,0.1);">` : '<div style="width:65px; height:65px; background:#f8f9fa; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#6c757d; font-weight:600; font-size:0.7rem; border:2px solid #dee2e6; text-align:center; line-height:1.1;">Sin<br>imagen</div>'}
                </td>
                <td style="font-weight:600; color:#495057; padding:8px 10px; width:35%; min-width:280px; vertical-align:top;">
                    <div style="display:flex; flex-direction:column; gap:3px; width:100%;">
                        <div style="display:flex; align-items:flex-start; gap:6px; width:100%;">
                            <div style="flex:1; display:flex; flex-direction:column; gap:3px;">
                                <textarea class="form-control nombre-insumo-input" data-index="${i}" style="border:2px solid #e9ecef; background:#fff; padding:6px 10px; font-weight:600; font-size:0.95rem; color:#212529; line-height:1.3; border-radius:5px; min-height:38px; max-height:80px; resize:none; overflow:hidden; width:100%; word-wrap:break-word; white-space:pre-wrap;" onblur="actualizarNombreInsumo(${i}, this.value)" placeholder="Nombre del insumo">${ins.nombre}</textarea>
                                <textarea class="form-control descripcion-insumo-input" data-index="${i}" style="border:1px solid #dee2e6; background:#f8f9fa; padding:5px 8px; font-weight:400; font-size:0.85rem; color:#6c757d; line-height:1.2; border-radius:4px; min-height:32px; max-height:60px; resize:none; overflow:hidden; width:100%; word-wrap:break-word; white-space:pre-wrap;" onblur="actualizarDescripcionInsumo(${i}, this.value)" placeholder="Descripción opcional">${ins.descripcion || ''}</textarea>
                            </div>
                            ${ins.nombre ? `<a href="https://www.google.com/search?q=${nombreGoogle}" target="_blank" title="Buscar en Google" class="icon-buscar-google" style="display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; background:#007bff; color:white; border-radius:50%; text-decoration:none; font-size:0.65rem; flex-shrink:0; transition:all 0.2s ease; margin-top:6px;"><i class="bi bi-search"></i></a>` : ''}
                        </div>
                        ${esPromocional ? `<small style="color:#28a745; font-weight:600; font-size:0.7rem; margin-top:2px;"><i class="bi bi-percent"></i> Paquete promocional: ${ins.paquete_promocional.nombre}</small>` : ''}
                    </div>
                </td>
                <td style="text-align:center; vertical-align:top; padding:8px 10px; width:120px;">
                    ${ins.paquete_id ? `
                        <div style="display:flex; flex-direction:column; align-items:center; gap:3px;">
                            <span class="badge" style="background:#17a2b8; color:white; font-size:0.7rem; padding:4px 6px; border-radius:4px; font-weight:600; display:flex; align-items:center; gap:3px;">
                                <i class="bi bi-link-45deg"></i> Conectado
                            </span>
                            <label style="display:flex; align-items:center; gap:3px; font-size:0.7rem; color:#495057; cursor:pointer;">
                                <input type="checkbox" class="sync-checkbox-insumo" data-index="${i}" ${ins.sincronizado !== false ? 'checked' : ''} title="Sincronizar con principal" style="accent-color:#17a2b8; transform:scale(0.9);">
                                Sync
                            </label>
                        </div>
                    ` : `<button type="button" class="btn btn-sm" onclick="conectarInsumoAPaquete(${i})" title="Conectar a paquete inteligente" style="background:#17a2b8; color:white; border:none; border-radius:4px; padding:4px 8px; font-weight:600; transition:all 0.3s ease; font-size:0.75rem;"><i class="bi bi-link"></i> Conectar</button>`}
                </td>
                <td style="vertical-align:top; padding:8px 10px; width:90px;">
                    <input type="number" min="0.01" step="0.01" value="${ins.cantidad}" class="form-control form-control-sm cantidad-insumo-input" data-index="${i}" data-paquete-id="${ins.paquete_id || ''}" data-tipo-paquete="${ins.tipo_paquete || ''}" style="width:75px; background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px; text-align:center; font-weight:600; padding:6px; font-size:0.9rem;">
                </td>
                <td style="vertical-align:top; padding:8px 10px; width:130px;">
                    <div class="input-group" style="width:120px;">
                        <span class="input-group-text" style="background:#28a745; color:white; border:none; border-radius:4px 0 0 4px; font-weight:600; font-size:0.8rem; padding:6px 8px;">$</span>
                        <input type="number" min="0" step="0.01" value="${ins.precio || ''}" class="form-control form-control-sm precio-insumo-input" data-index="${i}" style="background:#f8f9fa; border:1px solid #dee2e6; border-left:none; border-radius:0 4px 4px 0; text-align:center; font-weight:600; font-size:0.9rem; padding:6px;">
                    </div>
                </td>
                <td style="vertical-align:top; padding:8px 10px; width:130px;">
                    <div style="display:flex; flex-direction:column; align-items:center; gap:2px;">
                        ${costoReal !== undefined ? `
                            <div class="input-group" style="width:120px;">
                                <span class="input-group-text" style="background:#6c757d; color:white; border:none; border-radius:4px 0 0 4px; font-weight:600; font-size:0.8rem; padding:6px 8px;">$</span>
                                <input type="number" min="0" step="0.01" value="${costoReal.toFixed(2)}" class="form-control form-control-sm costo-insumo-input" data-index="${i}" style="background:#f1f3f4; border:1px solid #adb5bd; border-left:none; border-radius:0 4px 4px 0; text-align:center; font-weight:600; font-size:0.9rem; padding:6px;" onblur="actualizarCostoInsumo(${i}, this.value)">
                            </div>
                            <small style="color:#6c757d; font-weight:500; font-size:0.7rem; text-align:center;">unitario</small>
                        ` : `
                            <div class="input-group" style="width:120px;">
                                <span class="input-group-text" style="background:#6c757d; color:white; border:none; border-radius:4px 0 0 4px; font-weight:600; font-size:0.8rem; padding:6px 8px;">$</span>
                                <input type="number" min="0" step="0.01" value="0" class="form-control form-control-sm costo-insumo-input" data-index="${i}" style="background:#f1f3f4; border:1px solid #adb5bd; border-left:none; border-radius:0 4px 4px 0; text-align:center; font-weight:600; font-size:0.9rem; padding:6px;" onblur="actualizarCostoInsumo(${i}, this.value)" placeholder="Sin costo">
                            </div>
                            <small style="color:#adb5bd; font-size:0.7rem; font-style:italic;">Sin costo</small>
                        `}
                    </div>
                </td>
                <td style="vertical-align:top; padding:8px 10px; width:85px;">
                    <div style="display:flex; flex-direction:column; align-items:center; gap:2px;">
                        <input type="number" min="0" max="99" step="0.01" value="${margen}" class="form-control form-control-sm margen-insumo-input" data-index="${i}" style="width:70px; text-align:center; font-weight:700; background:${margenNegativo ? '#f8d7da' : '#d4edda'}; color:${margenNegativo ? '#dc3545' : '#28a745'}; border:1px solid ${margenNegativo ? '#f5c6cb' : '#c3e6cb'}; border-radius:4px; font-size:0.85rem; padding:4px;" title="Editar margen (%)" ${costoReal === undefined ? 'disabled' : ''}>
                        ${(costoReal === undefined) ? '<span style="font-size:0.7rem; color:#dc3545; font-weight:500;">Sin costo</span>' : '<span style="font-size:0.7rem; color:#6c757d; font-weight:500;">%</span>'}
                    </div>
                </td>
                <td style="vertical-align:top; padding:8px 10px; width:120px;">
                    <div style="display:flex; flex-direction:column; align-items:center; gap:1px;">
                        <span style="font-size:1.1rem; font-weight:700; color:#007bff;">$${sub.toFixed(2)}</span>
                    </div>
                </td>
                <td style="width:60px; text-align:center; vertical-align:top; padding:6px;">
                    <div style="display:flex; justify-content:center; align-items:flex-start; height:100%; padding-top:4px;">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-insumo" data-idx="${i}" title="Eliminar insumo" style="width:32px; height:32px; display:flex; justify-content:center; align-items:center; padding:0; border-radius:6px; border:1px solid #dc3545; transition:all 0.3s ease;">
                            <i class="bi bi-trash" style="font-size:0.85rem;"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    $('#tablaInsumosCotizacion tbody').html(html);
    recalcularTotales();
    
    // Eventos para insumos - solo cálculos sin auto-selección
    $('.cantidad-insumo-input').off('input').on('input', function() {
        const idx = $(this).data('index');
        const cantidad = parseFloat($(this).val()) || 1;
        if (insumosCotizacion[idx]) {
            insumosCotizacion[idx].cantidad = cantidad;
            // Actualizar subtotal de la fila
            const precio = parseFloat(insumosCotizacion[idx].precio) || 0;
            const subtotal = cantidad * precio;
            $(this).closest('tr').find('td').eq(6).find('span').first().text('$' + subtotal.toFixed(2));
            recalcularTotales();
            guardarBorrador();
        }
    });
    
    // Evento blur para sincronización de paquetes en insumos
    $('.cantidad-insumo-input').off('blur').on('blur', function() {
        const idx = $(this).data('index');
        const insumo = insumosCotizacion[idx];
        if (insumo && insumo.paquete_id && insumo.tipo_paquete === 'principal') {
            sincronizarCantidadesPaqueteV2(insumo.paquete_id);
        }
    });

    $('.precio-insumo-input').off('input').on('input', function() {
        const idx = $(this).data('index');
        const precio = parseFloat($(this).val()) || 0;
        if (insumosCotizacion[idx]) {
            insumosCotizacion[idx].precio = precio;
            // Actualizar subtotal de la fila
            const cantidad = parseFloat(insumosCotizacion[idx].cantidad) || 1;
            const subtotal = cantidad * precio;
            $(this).closest('tr').find('td').eq(6).find('span').first().text('$' + subtotal.toFixed(2));
            recalcularTotales();
            guardarBorrador();
        }
    });
    
    // Evento para margen editable en insumos
    $('.margen-insumo-input').off('input').on('input', function() {
        const index = parseInt($(this).data('index'));
        let porcentaje = parseFloat($(this).val());
        // Forzar margen positivo
        if (!isNaN(porcentaje) && porcentaje < 0) {
            porcentaje = Math.abs(porcentaje);
            $(this).val(porcentaje.toFixed(2));
        }
        const ins = insumosCotizacion[index];
        // Usar costoReal para el cálculo
        let costoReal = undefined;
        if (ins.costo !== undefined && ins.costo !== null && ins.costo !== '' && !isNaN(parseFloat(ins.costo))) {
            costoReal = parseFloat(ins.costo);
        } else if (ins.cost_price !== undefined && ins.cost_price !== null && ins.cost_price !== '' && !isNaN(parseFloat(ins.cost_price))) {
            costoReal = parseFloat(ins.cost_price);
        }
        if (ins && costoReal !== undefined && !isNaN(porcentaje) && porcentaje >= 0) {
            // Calcular nuevo precio según margen sobre costo
            const nuevoPrecio = costoReal * (1 + porcentaje / 100);
            ins.precio = parseFloat(nuevoPrecio.toFixed(2));
            // Actualizar el margen en el objeto insumo
            ins.margen = porcentaje;
            // Re-renderizar la tabla para mostrar el margen actualizado
            renderTablaInsumos();
            guardarBorrador();
        }
    });
// Eventos de delegación para sincronización de paquetes inteligentes
$(document).on('blur', '.cantidad-input', function() {
    const idx = parseInt($(this).data('index'));
    const cantidad = parseFloat($(this).val()) || 1;
    
    // Actualizar la cantidad en el array
    if (productosCotizacion[idx]) {
        productosCotizacion[idx].cantidad = cantidad;
    }
    
    const producto = productosCotizacion[idx];
    if (producto && producto.paquete_id && producto.tipo_paquete === 'principal') {
        setTimeout(() => {
            sincronizarCantidadesPaqueteV2(producto.paquete_id);
        }, 100);
    }
});

$(document).on('blur', '.cantidad-insumo-input', function() {
    const idx = parseInt($(this).data('index'));
    const cantidad = parseFloat($(this).val()) || 1;
    
    // Actualizar la cantidad en el array
    if (insumosCotizacion[idx]) {
        insumosCotizacion[idx].cantidad = cantidad;
    }
    
    const insumo = insumosCotizacion[idx];
    if (insumo && insumo.paquete_id && insumo.tipo_paquete === 'principal') {
        setTimeout(() => {
            sincronizarCantidadesPaqueteV2(insumo.paquete_id);
        }, 100);
    }
});

$(document).on('blur', '.cantidad-servicio-input', function() {
    const idx = parseInt($(this).data('index'));
    const cantidad = parseFloat($(this).val()) || 1;
    
    // Actualizar la cantidad en el array
    if (serviciosCotizacion[idx]) {
        serviciosCotizacion[idx].cantidad = cantidad;
    }
    
    const servicio = serviciosCotizacion[idx];
    if (servicio && servicio.paquete_id && servicio.tipo_paquete === 'principal') {
        setTimeout(() => {
            sincronizarCantidadesPaqueteV2(servicio.paquete_id);
        }, 100);
    }
});

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
// Eventos para cantidad de insumos - Simplificados para mejor UX
$(document).on('input', '.cantidad-insumo-input', function() {
    const index = parseInt($(this).data('index'));
    let value = parseFloat($(this).val());
    const ins = insumosCotizacion[index];
    
    if (!ins) return;
    
    // Validación básica sin re-renderizar
    if (value && !isNaN(value) && value > 0) {
        value = Math.max(0.01, parseFloat(value.toFixed(2)));
        ins.cantidad = value;
        $(this).removeClass('is-invalid');
    } else {
        $(this).addClass('is-invalid');
    }
    
    recalcularTotales();
});

// Eventos para precio de insumos - Simplificados para mejor UX
$(document).on('input', '.precio-insumo-input', function() {
    const index = parseInt($(this).data('index'));
    let value = parseFloat($(this).val());
    const ins = insumosCotizacion[index];
    
    if (!ins) return;
    
    // Validación básica sin re-renderizar
    if (value && !isNaN(value) && value >= 0) {
        ins.precio = parseFloat(value.toFixed(2));
        $(this).removeClass('is-invalid');
    } else {
        $(this).addClass('is-invalid');
    }
    
    recalcularTotales();
});

// Hacer celdas clickeables para enfocar inputs (simplificado)
$(document).on('click', 'td:has(.cantidad-input), td:has(.precio-input), td:has(.cantidad-insumo-input), td:has(.precio-insumo-input)', function(e) {
    if (!$(e.target).is('input')) {
        $(this).find('input').focus();
    }
});

$(document).on('click', '.btn-eliminar-insumo', function() {
    const idx = $(this).data('idx');
    insumosCotizacion.splice(idx, 1);
    renderTablaInsumos();
    guardarBorrador();
});
// Función recalcularTotales() duplicada eliminada - se mantiene la versión unificada en líneas anteriores

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

// Función para conectar un insumo a un paquete inteligente
function conectarInsumoAPaquete(index) {
    const insumo = insumosCotizacion[index];
    if (!insumo) return;
    
    // Mostrar modal para seleccionar paquete
    const paquetes = window.PaquetesCotizacion ? window.PaquetesCotizacion.getPaquetes() : [];
    
    if (paquetes.length === 0) {
        mostrarNotificacion('No hay paquetes inteligentes creados. Primero crea un paquete desde "Gestionar paquetes inteligentes".', 'warning');
        return;
    }
    
    let html = `
    <div class="modal fade" id="modalConectarInsumo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-link"></i> Conectar insumo a paquete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Insumo:</strong> ${insumo.nombre}</p>
                    <div class="mb-3">
                        <label class="form-label">Seleccionar paquete:</label>
                        <select class="form-select" id="selectPaqueteInsumo">
                            <option value="">-- Selecciona un paquete --</option>`;
    
    paquetes.forEach((paq, idx) => {
        html += `<option value="${idx}">${paq.nombre}</option>`;
    });
    
    html += `
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de relación:</label>
                        <select class="form-select" id="tipoRelacionInsumo">
                            <option value="relacionado">Relacionado (se sincroniza con principal)</option>
                            <option value="principal">Principal (controla las cantidades)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Factor de multiplicación:</label>
                        <input type="number" class="form-control" id="factorInsumo" value="1" min="0.01" step="0.01">
                        <small class="text-muted">Cantidad que se usará por cada unidad del principal</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="confirmarConexionInsumo(${index})">Conectar</button>
                </div>
            </div>
        </div>
    </div>`;
    
    // Remover modal anterior si existe
    $('#modalConectarInsumo').remove();
    
    // Agregar modal al DOM
    $('body').append(html);
    
    // Mostrar modal
    $('#modalConectarInsumo').modal('show');
}

// Función para confirmar la conexión del insumo al paquete
function confirmarConexionInsumo(index) {
    const paqueteIndex = $('#selectPaqueteInsumo').val();
    const tipoRelacion = $('#tipoRelacionInsumo').val();
    const factor = parseFloat($('#factorInsumo').val()) || 1;
    
    if (!paqueteIndex) {
        mostrarNotificacion('Selecciona un paquete.', 'warning');
        return;
    }
    
    // Generar ID único para el paquete
    const paqueteId = 'paq_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    
    // Actualizar el insumo con la información del paquete
    insumosCotizacion[index].paquete_id = paqueteId;
    insumosCotizacion[index].tipo_paquete = tipoRelacion;
    insumosCotizacion[index].factor_paquete = factor;
    insumosCotizacion[index].sincronizado = true;
    
    // Cerrar modal
    $('#modalConectarInsumo').modal('hide');
    
    // Volver a renderizar la tabla
    renderTablaInsumos();
    
    mostrarNotificacion('Insumo conectado al paquete correctamente.', 'success');
}

// Función para conectar un producto a un paquete inteligente
function conectarProductoAPaquete(index) {
    const producto = productosCotizacion[index];
    if (!producto) return;
    
    // Mostrar modal para seleccionar paquete
    const paquetes = window.PaquetesCotizacion ? window.PaquetesCotizacion.getPaquetes() : [];
    
    if (paquetes.length === 0) {
        mostrarNotificacion('No hay paquetes inteligentes creados. Primero crea un paquete desde "Gestionar paquetes inteligentes".', 'warning');
        return;
    }
    
    let html = `
    <div class="modal fade" id="modalConectarProducto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-link"></i> Conectar producto a paquete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Producto:</strong> ${producto.nombre}</p>
                    <div class="mb-3">
                        <label class="form-label">Seleccionar paquete:</label>
                        <select class="form-select" id="selectPaqueteProducto">
                            <option value="">-- Selecciona un paquete --</option>`;
    
    paquetes.forEach((paq, idx) => {
        html += `<option value="${idx}">${paq.nombre}</option>`;
    });
    
    html += `
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de relación:</label>
                        <select class="form-select" id="tipoRelacionProducto">
                            <option value="relacionado">Relacionado (se sincroniza con principal)</option>
                            <option value="principal">Principal (controla las cantidades)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Factor de multiplicación:</label>
                        <input type="number" class="form-control" id="factorProducto" value="1" min="0.01" step="0.01">
                        <small class="text-muted">Cantidad que se usará por cada unidad del principal</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="confirmarConexionProducto(${index})">Conectar</button>
                </div>
            </div>
        </div>
    </div>`;
    
    // Remover modal anterior si existe
    $('#modalConectarProducto').remove();
    
    // Agregar modal al DOM
    $('body').append(html);
    
    // Mostrar modal
    $('#modalConectarProducto').modal('show');
}

// Función para confirmar la conexión del producto al paquete
function confirmarConexionProducto(index) {
    const paqueteIndex = $('#selectPaqueteProducto').val();
    const tipoRelacion = $('#tipoRelacionProducto').val();
    const factor = parseFloat($('#factorProducto').val()) || 1;
    
    if (!paqueteIndex) {
        mostrarNotificacion('Selecciona un paquete.', 'warning');
        return;
    }
    
    // Generar ID único para el paquete
    const paqueteId = 'paq_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    
    // Actualizar el producto con la información del paquete
    productosCotizacion[index].paquete_id = paqueteId;
    productosCotizacion[index].tipo_paquete = tipoRelacion;
    productosCotizacion[index].factor_paquete = factor;
    productosCotizacion[index].sincronizado = true;
    
    // Cerrar modal
    $('#modalConectarProducto').modal('hide');
    
    // Volver a renderizar la tabla
    renderTablaProductos();
    
    mostrarNotificacion('Producto conectado al paquete correctamente.', 'success');
}

// Función para conectar un servicio a un paquete inteligente
function conectarServicioAPaquete(index) {
    const servicio = serviciosCotizacion[index];
    if (!servicio) return;
    
    // Mostrar modal para seleccionar paquete
    const paquetes = window.PaquetesCotizacion ? window.PaquetesCotizacion.getPaquetes() : [];
    
    if (paquetes.length === 0) {
        mostrarNotificacion('No hay paquetes inteligentes creados. Primero crea un paquete desde "Gestionar paquetes inteligentes".', 'warning');
        return;
    }
    
    let html = `
    <div class="modal fade" id="modalConectarServicio" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-link"></i> Conectar servicio a paquete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Servicio:</strong> ${servicio.nombre}</p>
                    <div class="mb-3">
                        <label class="form-label">Seleccionar paquete:</label>
                        <select class="form-select" id="selectPaqueteServicio">
                            <option value="">-- Selecciona un paquete --</option>`;
    
    paquetes.forEach((paq, idx) => {
        html += `<option value="${idx}">${paq.nombre}</option>`;
    });
    
    html += `
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de relación:</label>
                        <select class="form-select" id="tipoRelacionServicio">
                            <option value="relacionado">Relacionado (se sincroniza con principal)</option>
                            <option value="principal">Principal (controla las cantidades)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Factor de multiplicación:</label>
                        <input type="number" class="form-control" id="factorServicio" value="1" min="0.01" step="0.01">
                        <small class="text-muted">Cantidad que se usará por cada unidad del principal</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="confirmarConexionServicio(${index})">Conectar</button>
                </div>
            </div>
        </div>
    </div>`;
    
    // Remover modal anterior si existe
    $('#modalConectarServicio').remove();
    
    // Agregar modal al DOM
    $('body').append(html);
    
    // Mostrar modal
    $('#modalConectarServicio').modal('show');
}

// Función para confirmar la conexión del servicio al paquete
function confirmarConexionServicio(index) {
    const paqueteIndex = $('#selectPaqueteServicio').val();
    const tipoRelacion = $('#tipoRelacionServicio').val();
    const factor = parseFloat($('#factorServicio').val()) || 1;
    
    if (!paqueteIndex) {
        mostrarNotificacion('Selecciona un paquete.', 'warning');
        return;
    }
    
    // Generar ID único para el paquete
    const paqueteId = 'paq_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    
    // Actualizar el servicio con la información del paquete
    serviciosCotizacion[index].paquete_id = paqueteId;
    serviciosCotizacion[index].tipo_paquete = tipoRelacion;
    serviciosCotizacion[index].factor_paquete = factor;
    serviciosCotizacion[index].sincronizado = true;
    
    // Cerrar modal
    $('#modalConectarServicio').modal('hide');
    
    // Volver a renderizar la tabla
    renderTablaServicios();
    
    mostrarNotificacion('Servicio conectado al paquete correctamente.', 'success');
}

// Inicializar el objeto PaquetesCotizacion si no existe
if (typeof window.PaquetesCotizacion === 'undefined') {
    window.PaquetesCotizacion = {
        getPaquetes: function() {
            return JSON.parse(localStorage.getItem('cotiz_paquetes') || '[]');
        },
        addPaquete: function(paquete) {
            const paquetes = this.getPaquetes();
            paquetes.push(paquete);
            localStorage.setItem('cotiz_paquetes', JSON.stringify(paquetes));
        },
        updatePaquete: function(index, paquete) {
            const paquetes = this.getPaquetes();
            paquetes[index] = paquete;
            localStorage.setItem('cotiz_paquetes', JSON.stringify(paquetes));
        },
        deletePaquete: function(index) {
            const paquetes = this.getPaquetes();
            paquetes.splice(index, 1);
            localStorage.setItem('cotiz_paquetes', JSON.stringify(paquetes));
        }
    };
}

// Evento delegado global para manejar el toggle promocional de paquetes
$(document).on('change', '#paqEsPromocional', function() {
    const precioContainer = document.getElementById('paqPrecioContainer');
    if (precioContainer) {
        precioContainer.style.display = this.checked ? 'block' : 'none';
        
        // Actualizar el objeto en edición
        if (window._paqEdit) {
            window._paqEdit.es_promocional = this.checked;
            if (!this.checked) {
                window._paqEdit.precio_personalizado = null;
                const precioInput = document.getElementById('paqPrecioPersonalizado');
                if (precioInput) precioInput.value = '';
            }
        }
    }
});

// MutationObserver moderno para detectar cuando se renderizan los paquetes
const paqueteObserver = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === 'childList') {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    // Buscar toggle promocional en el nodo agregado o sus hijos
                    const toggle = node.id === 'paqEsPromocional' ? node : node.querySelector && node.querySelector('#paqEsPromocional');
                    const container = node.id === 'paqPrecioContainer' ? node : node.querySelector && node.querySelector('#paqPrecioContainer');
                    
                    if (toggle && container) {
                        // Configurar estado inicial
                        container.style.display = toggle.checked ? 'block' : 'none';
                    } else if (toggle) {
                        // Si solo encontramos el toggle, buscar el container
                        setTimeout(() => {
                            const cont = document.getElementById('paqPrecioContainer');
                            if (cont) {
                                cont.style.display = toggle.checked ? 'block' : 'none';
                            }
                        }, 10);
                    }
                }
            });
        }
    });
});

// Observar cambios en el panel de paquetes
const paquetesPanel = document.getElementById('paquetesPanel');
if (paquetesPanel) {
    paqueteObserver.observe(paquetesPanel, {
        childList: true,
        subtree: true
    });
}

// Función para auto-redimensionar textareas
function autoResizeTextarea(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = Math.max(45, textarea.scrollHeight) + 'px';
}

// Event listeners para auto-redimensionar textareas de nombres y descripciones
$(document).on('input keyup paste', '.nombre-producto-input, .nombre-insumo-input, .descripcion-producto-input, .descripcion-insumo-input', function() {
    autoResizeTextarea(this);
});

// Auto-redimensionar al cargar contenido
$(document).on('focus', '.nombre-producto-input, .nombre-insumo-input, .descripcion-producto-input, .descripcion-insumo-input', function() {
    autoResizeTextarea(this);
});

// Inicializar auto-redimensionado después de renderizar tablas
function initAutoResize() {
    $('.nombre-producto-input, .nombre-insumo-input, .descripcion-producto-input, .descripcion-insumo-input').each(function() {
        autoResizeTextarea(this);
    });
}

// Ejecutar después de cada renderizado
const originalRenderTablaProductos = renderTablaProductos;
renderTablaProductos = function() {
    originalRenderTablaProductos.apply(this, arguments);
    setTimeout(initAutoResize, 100);
};

const originalRenderTablaInsumos = renderTablaInsumos;
renderTablaInsumos = function() {
    originalRenderTablaInsumos.apply(this, arguments);
    setTimeout(initAutoResize, 100);
};

// Función para limpiar inconsistencias existentes al cargar la página
$(document).ready(function() {
    // Normalizar productos existentes si los hay
    if (productosCotizacion && productosCotizacion.length > 0) {
        productosCotizacion = productosCotizacion.map(prod => normalizarProducto(prod));
        renderTablaProductos(); // Re-renderizar para mostrar las descripciones correctamente
    }
});

</script>
</body>
</html>