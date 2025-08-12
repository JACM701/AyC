<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$cotizacion_id = intval($_GET['id']);

// Obtener cotización existente
$stmt = $mysqli->prepare("
    SELECT c.*, u.username as usuario_nombre, cl.nombre as cliente_nombre_real, cl.telefono as cliente_telefono_real, cl.ubicacion as cliente_direccion_real, cl.email as cliente_email_real, ec.nombre_estado
    FROM cotizaciones c
    LEFT JOIN users u ON c.user_id = u.user_id
    LEFT JOIN clientes cl ON c.cliente_id = cl.cliente_id
    LEFT JOIN est_cotizacion ec ON c.estado_id = ec.est_cot_id
    WHERE c.cotizacion_id = ?
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$cotizacion = $stmt->get_result()->fetch_assoc();

if (!$cotizacion) {
    header('Location: index.php');
    exit;
}

// Extraer descripciones personalizadas de las observaciones
$descripcionesPersonalizadas = [];
if (!empty($cotizacion['observaciones']) && preg_match('/\[DESCRIPCIONES:([^\]]+)\]/', $cotizacion['observaciones'], $match)) {
    $descripcionesData = base64_decode($match[1]);
    $descripcionesJson = json_decode($descripcionesData, true);
    if (is_array($descripcionesJson)) {
        $descripcionesPersonalizadas = $descripcionesJson;
    }
}

// Extraer descripciones personalizadas de insumos de las observaciones
$descripcionesPersonalizadasInsumos = [];
if (!empty($cotizacion['observaciones']) && preg_match('/\[DESCRIPCIONES_INSUMOS:([^\]]+)\]/', $cotizacion['observaciones'], $match)) {
    $descripcionesData = base64_decode($match[1]);
    $descripcionesJson = json_decode($descripcionesData, true);
    if (is_array($descripcionesJson)) {
        $descripcionesPersonalizadasInsumos = $descripcionesJson;
    }
}

// Obtener productos de la cotización
$stmt = $mysqli->prepare("
    SELECT cp.*, p.product_name, p.sku, p.image as product_image, c.name as categoria, s.name as proveedor, p.tipo_gestion
    FROM cotizaciones_productos cp
    LEFT JOIN products p ON cp.product_id = p.product_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
    WHERE cp.cotizacion_id = ?
    ORDER BY cp.cotizacion_producto_id
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$productos_cotizacion = $stmt->get_result();

// Preparar productos existentes para JavaScript
$productos_existentes = [];
while ($prod = $productos_cotizacion->fetch_assoc()) {
    $img = $prod['product_image'] ?? '';
    if ($img && strpos($img, 'uploads/products/') === false) {
        $img = 'uploads/products/' . $img;
    }
    
    // Obtener descripción personalizada si existe
    $descripcionPersonalizada = isset($descripcionesPersonalizadas[$prod['product_id']]) ? $descripcionesPersonalizadas[$prod['product_id']] : '';
    
    $productos_existentes[] = [
        'product_id' => $prod['product_id'],
        'nombre' => $prod['product_name'],
        'description' => $descripcionPersonalizada, // Agregar descripción personalizada
        'sku' => $prod['sku'],
        'cantidad' => $prod['cantidad'],
        'precio' => $prod['precio_unitario'],
        'imagen' => $img,
        'tipo_gestion' => $prod['tipo_gestion']
    ];
}

// Debug: agregar información para depuración
error_log("DEBUG editar.php - Cotización ID: $cotizacion_id");
error_log("DEBUG editar.php - Productos encontrados: " . count($productos_existentes));
if (count($productos_existentes) > 0) {
    error_log("DEBUG editar.php - Primer producto: " . json_encode($productos_existentes[0]));
}

// Obtener servicios de la cotización
$stmt = $mysqli->prepare("
    SELECT cs.*, s.nombre as servicio_nombre, s.descripcion as servicio_descripcion, s.categoria as servicio_categoria, s.imagen as servicio_imagen
    FROM cotizaciones_servicios cs
    LEFT JOIN servicios s ON cs.servicio_id = s.servicio_id
    WHERE cs.cotizacion_id = ?
    ORDER BY cs.cotizacion_servicio_id
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$servicios_cotizacion = $stmt->get_result();

// Preparar servicios existentes para JS
$servicios_existentes = [];
while ($serv = $servicios_cotizacion->fetch_assoc()) {
    $img = $serv['imagen'] ?? $serv['servicio_imagen'] ?? '';
    if ($img && strpos($img, 'uploads/services/') === false) {
        $img = 'uploads/services/' . $img;
    }
    $servicios_existentes[] = [
        'servicio_id' => $serv['servicio_id'],
        'nombre' => $serv['nombre_servicio'],
        'categoria' => $serv['servicio_categoria'],
        'descripcion' => $serv['descripcion'],
        'cantidad' => $serv['cantidad'],
        'precio' => $serv['precio_unitario'],
        'imagen' => $img
    ];
}
// Servicios disponibles para agregar
$servicios = $mysqli->query("SELECT servicio_id, nombre, categoria, descripcion, precio, imagen FROM servicios WHERE is_active = 1 ORDER BY categoria, nombre ASC");
$servicios_array = $servicios ? $servicios->fetch_all(MYSQLI_ASSOC) : [];

// Obtener insumos de la cotización
$stmt = $mysqli->prepare("
    SELECT ci.*, i.nombre as insumo_nombre, i.categoria as insumo_categoria, i.cantidad as insumo_stock, i.precio_unitario as insumo_precio, c.name as categoria_nombre, s.name as proveedor_nombre
    FROM cotizaciones_insumos ci
    LEFT JOIN insumos i ON ci.insumo_id = i.insumo_id
    LEFT JOIN categories c ON i.category_id = c.category_id
    LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
    WHERE ci.cotizacion_id = ?
    ORDER BY ci.cotizacion_insumo_id
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$insumos_cotizacion = $stmt->get_result();
$insumos_existentes = [];
while ($ins = $insumos_cotizacion->fetch_assoc()) {
    $insumo_id = $ins['insumo_id'];
    $descripcionPersonalizadaInsumo = isset($descripcionesPersonalizadasInsumos[$insumo_id]) ? $descripcionesPersonalizadasInsumos[$insumo_id] : '';
    
    $insumos_existentes[] = [
        'insumo_id' => $ins['insumo_id'],
        'nombre' => $ins['nombre_insumo'] ?? $ins['insumo_nombre'],
        'categoria' => $ins['categoria'] ?? $ins['categoria_nombre'],
        'proveedor' => $ins['proveedor'] ?? $ins['proveedor_nombre'],
        'stock' => $ins['stock_disponible'] ?? $ins['insumo_stock'],
        'cantidad' => $ins['cantidad'],
        'precio' => $ins['precio_unitario'],
        'descripcion' => $descripcionPersonalizadaInsumo,
    ];
}

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
    GROUP BY p.product_id, p.product_name, p.sku, p.price, p.tipo_gestion, p.image, c.name, s.name, p.quantity
    ORDER BY p.product_name ASC
");
$productos_array = $productos ? $productos->fetch_all(MYSQLI_ASSOC) : [];
$categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name ASC");
$categorias_array = $categorias ? $categorias->fetch_all(MYSQLI_ASSOC) : [];
$proveedores = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name ASC");
$proveedores_array = $proveedores ? $proveedores->fetch_all(MYSQLI_ASSOC) : [];

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

// Manejo AJAX para crear productos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'crear_producto') {
    header('Content-Type: application/json');
    
    $nombre = trim($_POST['nombre'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $costo = floatval($_POST['costo'] ?? 0);
    $cantidad = intval($_POST['cantidad'] ?? 1);
    $categoria = $_POST['categoria_id'] ?? null;
    $proveedor = $_POST['supplier_id'] ?? null;
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (!$nombre || !$precio || $cantidad === null || $cantidad < 0) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios o cantidad inválida']);
        exit;
    }

    // Verificar si ya existe producto con ese nombre o SKU
    $stmt = $mysqli->prepare("SELECT product_id FROM products WHERE product_name = ? OR sku = ? LIMIT 1");
    $stmt->bind_param('ss', $nombre, $sku);
    $stmt->execute();
    $stmt->bind_result($existing_id);
    if ($stmt->fetch()) {
        $stmt->close();
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe un producto con ese nombre o SKU.'
        ]);
        exit;
    }
    $stmt->close();

    // Generar SKU si no se proporcionó
    if (!$sku) {
        $sku = strtoupper(substr($nombre, 0, 3)) . '-' . rand(1000,9999);
    }

    $image_path = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $img_tmp = $_FILES['imagen']['tmp_name'];
        $img_name = basename($_FILES['imagen']['name']);
        $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($img_ext, $allowed)) {
            $dir = '../uploads/products/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $new_name = uniqid('prod_') . '.' . $img_ext;
            $dest = $dir . $new_name;
            if (move_uploaded_file($img_tmp, $dest)) {
                $image_path = 'uploads/products/' . $new_name;
            }
        }
    }

    $stmt = $mysqli->prepare("INSERT INTO products (product_name, sku, price, cost_price, quantity, category_id, supplier_id, description, image, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssdiiisssi', $nombre, $sku, $precio, $costo, $cantidad, $categoria, $proveedor, $descripcion, $image_path, 0);
    $stmt->execute();
    $product_id = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'product_id' => $product_id,
        'producto' => [
            'product_id' => $product_id,
            'nombre' => $nombre,
            'sku' => $sku,
            'categoria' => $categoria,
            'proveedor' => $proveedor,
            'stock' => $cantidad,
            'imagen' => $image_path
        ]
    ]);
    exit;
}

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productos_json = $_POST['productos_json'] ?? '';
    $productos = json_decode($productos_json, true);
    $servicios_json = $_POST['servicios_json'] ?? '';
    $servicios = json_decode($servicios_json, true);
    $insumos_json = $_POST['insumos_json'] ?? '';
    $insumos = json_decode($insumos_json, true);
    $cliente_id = $_POST['cliente_id'] ?? '';
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $cliente_telefono = trim($_POST['cliente_telefono'] ?? '');
    $cliente_ubicacion = trim($_POST['cliente_ubicacion'] ?? '');
    $cliente_email = trim($_POST['cliente_email'] ?? '');
    
    if ((!$productos || !is_array($productos) || count($productos) == 0) && (!$servicios || !is_array($servicios) || count($servicios) == 0) && (!$insumos || !is_array($insumos) || count($insumos) == 0)) {
        $error = 'Debes agregar al menos un producto, servicio o insumo a la cotización.';
    }
    if (!$cliente_id && !$cliente_nombre) {
        $error = 'Debes seleccionar o registrar un cliente.';
    }
    
    if (!$error) {
        // Cliente: alta si es nuevo
        if (!$cliente_id) {
            $stmt = $mysqli->prepare("SELECT cliente_id FROM clientes WHERE nombre = ? OR telefono = ? OR email = ? LIMIT 1");
            $stmt->bind_param('sss', $cliente_nombre, $cliente_telefono, $cliente_email);
            $stmt->execute();
            $stmt->bind_result($cliente_id_encontrado);
            if ($stmt->fetch()) {
                $cliente_id = $cliente_id_encontrado;
            }
            $stmt->close();
            if (!$cliente_id) {
                $stmt = $mysqli->prepare("INSERT INTO clientes (nombre, telefono, ubicacion, email) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('ssss', $cliente_nombre, $cliente_telefono, $cliente_ubicacion, $cliente_email);
                $stmt->execute();
                $cliente_id = $stmt->insert_id;
                $stmt->close();
            }
        }
        
        // Datos de la cotización
        $fecha_cotizacion = $_POST['fecha_cotizacion'];
        $validez_dias = intval($_POST['validez_dias']);
        $condiciones_pago = trim($_POST['condiciones_pago']);
        $observaciones = trim($_POST['observaciones']);
        $descuento_porcentaje = floatval($_POST['descuento_porcentaje']);
        $estado_id = intval($_POST['estado_id']);
        
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
            // Eliminar cualquier referencia anterior de descripciones
            $observaciones = preg_replace('/\[DESCRIPCIONES:[^\]]*\]/', '', $observaciones);
            // Agregar las nuevas descripciones
            $observaciones .= ' [DESCRIPCIONES:' . base64_encode(json_encode($descripcionesPersonalizadas)) . ']';
            $observaciones = trim($observaciones);
        } else {
            // Si no hay descripciones personalizadas, eliminar la referencia
            $observaciones = preg_replace('/\[DESCRIPCIONES:[^\]]*\]/', '', $observaciones);
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
            // Eliminar cualquier referencia anterior de descripciones de insumos
            $observaciones = preg_replace('/\[DESCRIPCIONES_INSUMOS:[^\]]*\]/', '', $observaciones);
            // Agregar las nuevas descripciones de insumos
            $observaciones .= ' [DESCRIPCIONES_INSUMOS:' . base64_encode(json_encode($descripcionesPersonalizadasInsumos)) . ']';
            $observaciones = trim($observaciones);
        } else {
            // Si no hay descripciones personalizadas de insumos, eliminar la referencia
            $observaciones = preg_replace('/\[DESCRIPCIONES_INSUMOS:[^\]]*\]/', '', $observaciones);
            $observaciones = trim($observaciones);
        }
        
        // 🎯 USAR TOTALES CALCULADOS DEL FRONTEND (igual que crear.php)
        if (isset($_POST['subtotal_frontend']) && isset($_POST['descuento_monto_frontend']) && isset($_POST['total_frontend'])) {
            // Usar valores calculados por el frontend (CORRECTO)
            $subtotal = floatval($_POST['subtotal_frontend']);
            $descuento_monto = floatval($_POST['descuento_monto_frontend']);
            $total = floatval($_POST['total_frontend']);
        } else {
            // Fallback: calcular en backend (solo si no hay valores del frontend)
            $subtotal = 0;
            foreach ($productos as $prod) {
                // El frontend ya calcula correctamente cantidad * precio para todos los productos
                // incluyendo bobinas, así que usamos esos valores directamente
                $subtotal += floatval($prod['precio']) * floatval($prod['cantidad']);
            }
            foreach ($servicios as $serv) {
                $subtotal += floatval($serv['precio']) * floatval($serv['cantidad']);
            }
            if ($insumos && is_array($insumos)) {
                foreach ($insumos as $ins) {
                    $cantidad = floatval($ins['cantidad'] ?? 1);
                    $precio_unitario = floatval($ins['precio'] ?? 0);
                    $subtotal += $cantidad * $precio_unitario;
                }
            }
            $descuento_monto = $subtotal * $descuento_porcentaje / 100;
            $total = $subtotal - $descuento_monto;
        }
        
        // Actualizar cotización
        $stmt = $mysqli->prepare("UPDATE cotizaciones SET cliente_id = ?, fecha_cotizacion = ?, validez_dias = ?, subtotal = ?, descuento_porcentaje = ?, descuento_monto = ?, total = ?, condiciones_pago = ?, observaciones = ?, estado_id = ? WHERE cotizacion_id = ?");
        $stmt->bind_param('isidddssiii', $cliente_id, $fecha_cotizacion, $validez_dias, $subtotal, $descuento_porcentaje, $descuento_monto, $total, $condiciones_pago, $observaciones, $estado_id, $cotizacion_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Registrar acción en el historial
            require_once 'helpers.php';
            inicializarAccionesCotizacion($mysqli);
            registrarAccionCotizacion(
                $cotizacion_id, 
                'Modificada', 
                "Cotización modificada con " . count($productos) . " productos por un total de $" . number_format($total, 2),
                $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null,
                $mysqli
            );
            
            // Eliminar productos anteriores
            $stmt = $mysqli->prepare("DELETE FROM cotizaciones_productos WHERE cotizacion_id = ?");
            $stmt->bind_param('i', $cotizacion_id);
            $stmt->execute();
            $stmt->close();
            
            // Insertar nuevos productos
            foreach ($productos as $prod) {
                $product_id = $prod['product_id'] ?? null;
                if (!$product_id) {
                    $stmt_prod = $mysqli->prepare("INSERT INTO products (product_name, sku, price, quantity, category_id, supplier_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $cat_id = $prod['category_id'] ?? null;
                    $prov_id = $prod['supplier_id'] ?? null;
                    $stmt_prod->bind_param('ssdiis', $prod['nombre'], $prod['sku'], $prod['precio'], $prod['cantidad'], $cat_id, $prov_id);
                    $stmt_prod->execute();
                    $product_id = $stmt_prod->insert_id;
                    $stmt_prod->close();
                }
                
                $stmt_cp = $mysqli->prepare("INSERT INTO cotizaciones_productos (cotizacion_id, product_id, cantidad, precio_unitario, precio_total) VALUES (?, ?, ?, ?, ?)");
                
                $cantidad = floatval($prod['cantidad']);
                $precio_unitario = floatval($prod['precio']);
                $precio_total = $cantidad * $precio_unitario;
                
                $stmt_cp->bind_param('iiddd', $cotizacion_id, $product_id, $cantidad, $precio_unitario, $precio_total);
                $stmt_cp->execute();
                $stmt_cp->close();
            }

            // Eliminar servicios anteriores
            $stmt = $mysqli->prepare("DELETE FROM cotizaciones_servicios WHERE cotizacion_id = ?");
            $stmt->bind_param('i', $cotizacion_id);
            $stmt->execute();
            $stmt->close();
            // Insertar nuevos servicios
            foreach ($servicios as $serv) {
                $servicio_id = $serv['servicio_id'] ?? null;
                $nombre_servicio = $serv['nombre'];
                $descripcion = $serv['descripcion'] ?? '';
                $cantidad = intval($serv['cantidad']);
                $precio_unitario = floatval($serv['precio']);
                $precio_total = $cantidad * $precio_unitario;
                $imagen = $serv['imagen'] ?? null;
                $categoria = $serv['categoria'] ?? '';
                $stmt_cs = $mysqli->prepare("INSERT INTO cotizaciones_servicios (cotizacion_id, servicio_id, nombre_servicio, descripcion, cantidad, precio_unitario, precio_total, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_cs->bind_param('iissddds', $cotizacion_id, $servicio_id, $nombre_servicio, $descripcion, $cantidad, $precio_unitario, $precio_total, $imagen);
                $stmt_cs->execute();
                $stmt_cs->close();
            }

            // Eliminar insumos anteriores
            $stmt = $mysqli->prepare("DELETE FROM cotizaciones_insumos WHERE cotizacion_id = ?");
            $stmt->bind_param('i', $cotizacion_id);
            $stmt->execute();
            $stmt->close();
            // Insertar nuevos insumos
            foreach ($insumos as $ins) {
                $insumo_id = $ins['insumo_id'] ?? null;
                $nombre_insumo = $ins['nombre'];
                $categoria = $ins['categoria'] ?? '';
                $proveedor = $ins['proveedor'] ?? '';
                $cantidad = intval($ins['cantidad']);
                $precio_unitario = floatval($ins['precio']);
                $precio_total = $cantidad * $precio_unitario;
                $imagen = $ins['imagen'] ?? null;
                $stock_disponible = $ins['stock'] ?? 0; // Assuming 'stock' is the correct key for stock_disponible
                $stmt_ci = $mysqli->prepare("INSERT INTO cotizaciones_insumos (cotizacion_id, insumo_id, nombre_insumo, categoria, proveedor, cantidad, precio_unitario, precio_total, stock_disponible, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_ci->bind_param('iissddddds', $cotizacion_id, $insumo_id, $nombre_insumo, $categoria, $proveedor, $cantidad, $precio_unitario, $precio_total, $stock_disponible, $imagen);
                $stmt_ci->execute();
                $stmt_ci->close();
            }
            
            header("Location: ver.php?id=$cotizacion_id");
            exit;
        } else {
            $error = 'Error al actualizar la cotización: ' . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cotización <?= $cotizacion['numero_cotizacion'] ?></title>
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
        .select2-container--default .select2-selection--single { 
            height: 38px; 
        }
        .select2-selection__rendered { 
            line-height: 38px !important; 
        }
        .select2-selection__arrow { 
            height: 38px !important; 
        }
        .table thead th { 
            background: #121866; 
            color: #fff; 
            font-size: 0.9rem;
            padding: 12px 8px;
        }
        .table tbody td {
            padding: 8px;
            vertical-align: middle;
        }
        .badge-stock { 
            font-size: 0.85rem; 
        }
        .btn-remove-product { 
            color: #dc3545; 
            cursor: pointer; 
        }
        .btn-remove-product:hover { 
            color: #c82333; 
        }
        .form-control-sm {
            font-size: 0.875rem;
        }
        .cantidad-input, .precio-input {
            text-align: center;
        }
        .total-fila {
            font-weight: 600;
            color: #121866;
        }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil-square"></i> Editar Cotización <?= htmlspecialchars($cotizacion['numero_cotizacion']) ?></h2>
        <a href="ver.php?id=<?= $cotizacion_id ?>" class="btn btn-secondary">
            <i class="bi bi-eye"></i> Ver Cotización
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

    <form method="POST" id="formEditarCotizacion" autocomplete="off">
        <!-- Sección Cliente -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-person"></i> Cliente</div>
            <div class="mb-3">
                <label for="cliente_select" class="form-label">Seleccionar cliente</label>
                <select class="form-select" id="cliente_select" name="cliente_id">
                    <option value="">-- Nuevo cliente --</option>
                    <?php foreach ($clientes_array as $cl): ?>
                        <option value="<?= $cl['cliente_id'] ?>" 
                                data-nombre="<?= htmlspecialchars($cl['nombre']) ?>" 
                                data-telefono="<?= htmlspecialchars($cl['telefono']) ?>" 
                                data-ubicacion="<?= htmlspecialchars($cl['ubicacion']) ?>" 
                                data-email="<?= htmlspecialchars($cl['email']) ?>"
                                <?= ($cl['cliente_id'] == $cotizacion['cliente_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cl['nombre']) ?><?= $cl['telefono'] ? ' (' . htmlspecialchars($cl['telefono']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="camposNuevoCliente">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="cliente_nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="cliente_nombre" id="cliente_nombre" 
                               value="<?= htmlspecialchars($cotizacion['cliente_nombre_real'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="cliente_telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="cliente_telefono" id="cliente_telefono" 
                               value="<?= htmlspecialchars($cotizacion['cliente_telefono_real'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="cliente_ubicacion" class="form-label">Ubicación</label>
                        <input type="text" class="form-control" name="cliente_ubicacion" id="cliente_ubicacion" 
                               value="<?= htmlspecialchars($cotizacion['cliente_direccion_real'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="cliente_email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="cliente_email" id="cliente_email" 
                               value="<?= htmlspecialchars($cotizacion['cliente_email_real'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección Productos -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-box-seam"></i> Productos</div>
            <div class="mb-3">
                <label for="producto_select" class="form-label">Agregar producto</label>
                <select class="form-select" id="producto_select">
                    <option value="">Seleccionar producto...</option>
                    <?php foreach ($productos_array as $prod): ?>
                        <option value="<?= $prod['product_id'] ?>" 
                                data-nombre="<?= htmlspecialchars($prod['product_name']) ?>" 
                                data-sku="<?= htmlspecialchars($prod['sku']) ?>" 
                                data-precio="<?= $prod['price'] ?>" 
                                data-stock="<?= $prod['stock_disponible'] ?>"
                                data-categoria="<?= htmlspecialchars($prod['categoria'] ?? '') ?>"
                                data-proveedor="<?= htmlspecialchars($prod['proveedor'] ?? '') ?>"
                                data-imagen="<?= htmlspecialchars($prod['image'] ?? '') ?>">
                            <?= htmlspecialchars($prod['product_name']) ?> - <?= htmlspecialchars($prod['sku']) ?> ($<?= number_format($prod['price'], 2) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped" id="tablaProductos">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los productos se cargarán dinámicamente con JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Alta rápida de productos -->
            <div class="mt-4 p-3 border rounded" style="background-color: #f8f9fa;">
                <h6 class="mb-3"><i class="bi bi-plus-circle text-success"></i> Alta rápida de producto</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small">Nombre *</label>
                        <input type="text" class="form-control form-control-sm" id="nuevo_nombre_producto" placeholder="Nombre del producto">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Descripción</label>
                        <input type="text" class="form-control form-control-sm" id="nuevo_descripcion_producto" placeholder="Descripción">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">SKU</label>
                        <input type="text" class="form-control form-control-sm" id="nuevo_sku_producto" placeholder="SKU">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Precio *</label>
                        <input type="number" class="form-control form-control-sm" id="nuevo_precio_producto" placeholder="0.00" step="0.01" min="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Costo</label>
                        <input type="number" class="form-control form-control-sm" id="nuevo_costo_producto" placeholder="0.00" step="0.01" min="0">
                    </div>
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small">Cantidad</label>
                        <input type="number" class="form-control form-control-sm" id="nuevo_cantidad_producto" value="1" min="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Categoría</label>
                        <select class="form-select form-select-sm" id="nuevo_categoria_producto">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($categorias_array as $categoria): ?>
                                <option value="<?= $categoria['category_id'] ?>"><?= htmlspecialchars($categoria['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Proveedor</label>
                        <select class="form-select form-select-sm" id="nuevo_proveedor_producto">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($proveedores_array as $proveedor): ?>
                                <option value="<?= $proveedor['supplier_id'] ?>"><?= htmlspecialchars($proveedor['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Imagen</label>
                        <input type="file" class="form-control form-control-sm" id="nuevo_imagen_producto" accept="image/*">
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="nuevo_agregar_cotizacion">
                            <label class="form-check-label small" for="nuevo_agregar_cotizacion">
                                Agregar a cotización
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-success btn-sm w-100" id="btn_crear_producto">
                            <i class="bi bi-plus"></i> Crear Producto
                        </button>
                    </div>
                </div>
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

        <!-- Sección Servicios -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-tools"></i> Servicios</div>
            <div class="mb-3">
                <label for="servicio_select" class="form-label">Agregar servicio</label>
                <select class="form-select" id="servicio_select">
                    <option value="">Seleccionar servicio...</option>
                    <?php foreach ($servicios_array as $serv): ?>
                        <option value="<?= $serv['servicio_id'] ?>"
                                data-nombre="<?= htmlspecialchars($serv['nombre']) ?>"
                                data-categoria="<?= htmlspecialchars($serv['categoria']) ?>"
                                data-descripcion="<?= htmlspecialchars($serv['descripcion']) ?>"
                                data-precio="<?= $serv['precio'] ?>"
                                data-imagen="<?= htmlspecialchars($serv['imagen']) ?>">
                            <?= htmlspecialchars($serv['nombre']) ?> (<?= htmlspecialchars($serv['categoria']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="table-responsive">
                <table class="table table-striped" id="tablaServicios">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Categoría</th>
                            <th>Descripción</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los servicios se cargarán dinámicamente con JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sección Detalles -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-gear"></i> Detalles de la Cotización</div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="fecha_cotizacion" class="form-label">Fecha de cotización</label>
                    <input type="date" class="form-control" name="fecha_cotizacion" id="fecha_cotizacion" 
                           value="<?= $cotizacion['fecha_cotizacion'] ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="validez_dias" class="form-label">Validez (días)</label>
                    <input type="number" class="form-control" name="validez_dias" id="validez_dias" 
                           value="<?= $cotizacion['validez_dias'] ?>" min="1" required>
                </div>
                <div class="col-md-3">
                    <label for="estado_id" class="form-label">Estado</label>
                    <select class="form-select" name="estado_id" id="estado_id" required>
                        <?php foreach ($estados_array as $est): ?>
                            <option value="<?= $est['est_cot_id'] ?>" <?= ($est['est_cot_id'] == $cotizacion['estado_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($est['nombre_estado']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="descuento_porcentaje" class="form-label">Descuento (%)</label>
                    <input type="number" class="form-control" name="descuento_porcentaje" id="descuento_porcentaje" 
                           value="<?= $cotizacion['descuento_porcentaje'] ?>" min="0" max="100" step="0.01">
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label for="condiciones_pago" class="form-label">Condiciones de pago</label>
                    <textarea class="form-control" name="condiciones_pago" id="condiciones_pago" rows="3"><?= htmlspecialchars($cotizacion['condiciones_pago'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="observaciones" class="form-label">Observaciones</label>
                    <textarea class="form-control" name="observaciones" id="observaciones" rows="3"><?= htmlspecialchars($cotizacion['observaciones'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Resumen -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-calculator"></i> Resumen</div>
            <div class="row">
                <div class="col-md-6 offset-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Subtotal:</strong></td>
                            <td class="text-end" id="subtotal">$<?= number_format($cotizacion['subtotal'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Descuento:</strong></td>
                            <td class="text-end" id="descuento">$<?= number_format($cotizacion['descuento_monto'], 2) ?></td>
                        </tr>
                        <tr class="table-active">
                            <td><strong>Total:</strong></td>
                            <td class="text-end" id="total">$<?= number_format($cotizacion['total'], 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <input type="hidden" name="productos_json" id="productos_json" value="">
        <input type="hidden" name="servicios_json" id="servicios_json" value="">
        <input type="hidden" name="insumos_json" id="insumos_json" value="">
        
        <div class="d-flex justify-content-between">
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> Guardar Cambios
            </button>
        </div>
    </form>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="../assets/js/script.js"></script>
<script>
    document.querySelector('.sidebar-cotizaciones').classList.add('active');
    

    
    // Productos existentes de PHP
    const productosExistentes = <?= json_encode($productos_existentes) ?>;
    const serviciosExistentes = <?= json_encode($servicios_existentes) ?>;
    const insumosExistentes = <?= json_encode($insumos_existentes) ?>;
    
    // Variables globales para el sistema de precios
    let productos = [];
    let productosCotizacion = [];
    let serviciosCotizacion = [];
    let insumosCotizacion = [];
    
    // Inicializar Select2
    $(document).ready(function() {
        $('#cliente_select, #producto_select').select2();
        
        console.log('Productos existentes desde PHP:', productosExistentes);
        console.log('Servicios existentes desde PHP:', serviciosExistentes);
        console.log('Insumos existentes desde PHP:', insumosExistentes);
        
        // Convertir productos existentes al formato moderno
        productosExistentes.forEach(producto => {
            let productoModerno = {
                product_id: producto.product_id,
                nombre: producto.nombre,
                sku: producto.sku,
                cantidad: parseFloat(producto.cantidad) || 1,
                precio: parseFloat(producto.precio) || 0,
                imagen: producto.imagen,
                tipo_gestion: producto.tipo_gestion || 'normal'
            };
            
            productosCotizacion.push(productoModerno);
        });
        
        // Renderizar productos con sistema moderno
        renderTablaProductos();
        
        // Cargar servicios existentes
        serviciosExistentes.forEach(servicio => {
            serviciosCotizacion.push({
                servicio_id: servicio.servicio_id,
                nombre: servicio.nombre,
                categoria: servicio.categoria,
                descripcion: servicio.descripcion,
                cantidad: parseFloat(servicio.cantidad) || 1,
                precio: parseFloat(servicio.precio) || 0,
                imagen: servicio.imagen
            });
        });
        
        // Cargar insumos existentes
        insumosExistentes.forEach(insumo => {
            insumosCotizacion.push({
                insumo_id: insumo.insumo_id,
                nombre: insumo.nombre,
                categoria: insumo.categoria,
                proveedor: insumo.proveedor,
                stock: parseFloat(insumo.stock) || 0,
                cantidad: parseFloat(insumo.cantidad) || 1,
                precio: parseFloat(insumo.precio) || 0
            });
        });
        
        // Renderizar tablas
        renderTablaServicios();
        renderTablaInsumos();
        
        // Actualizar totales iniciales
        recalcularTotales();
    });

    // Manejo del cliente con Select2
    $('#cliente_select').on('change', function() {
        if (this.value) {
            // Obtener la opción seleccionada
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('cliente_nombre').value = selectedOption.dataset.nombre || '';
            document.getElementById('cliente_telefono').value = selectedOption.dataset.telefono || '';
            document.getElementById('cliente_ubicacion').value = selectedOption.dataset.ubicacion || '';
            document.getElementById('cliente_email').value = selectedOption.dataset.email || '';
        }
    });

    // Manejo de productos con Select2
    $('#producto_select').on('change', function() {
        if (this.value) {
            // Obtener la opción seleccionada
            const selectedOption = this.options[this.selectedIndex];
            console.log('selectedOption.dataset:', selectedOption.dataset);
            
            // Buscar información completa del producto
            const productData = <?= json_encode($productos_array) ?>.find(p => p.product_id == this.value);
            console.log('productData encontrado:', productData);
            const tipoGestion = productData ? productData.tipo_gestion : '';
            const precio = parseFloat(selectedOption.dataset.precio) || 0;
            
            const nuevoProducto = {
                product_id: this.value,
                nombre: selectedOption.dataset.nombre,
                sku: selectedOption.dataset.sku,
                precio: precio,
                cantidad: 1,
                tipo_gestion: tipoGestion,
                imagen: selectedOption.dataset.imagen || ''
            };
            
            console.log('Agregando producto:', nuevoProducto);
            productosCotizacion.push(nuevoProducto);
            renderTablaProductos();
            recalcularTotales();
            
            // Limpiar selección
            $(this).val('').trigger('change');
        }
    });

    // Manejo del selector de servicios con Select2
    $('#servicio_select').on('change', function() {
        if (this.value) {
            // Obtener la opción seleccionada
            const selectedOption = this.options[this.selectedIndex];
            
            const nuevoServicio = {
                servicio_id: this.value,
                nombre: selectedOption.dataset.nombre,
                categoria: selectedOption.dataset.categoria,
                descripcion: selectedOption.dataset.descripcion,
                precio: parseFloat(selectedOption.dataset.precio) || 0,
                cantidad: 1,
                imagen: selectedOption.dataset.imagen
            };
            
            serviciosCotizacion.push(nuevoServicio);
            renderTablaServicios();
            recalcularTotales();
            
            // Limpiar selección
            $(this).val('').trigger('change');
        }
    });

    // Función moderna para renderizar tabla de productos
    function renderTablaProductos() {
        console.log('Renderizando productos:', productosCotizacion);
        let html = '';
        let subtotal = 0;
        
        productosCotizacion.forEach((p, i) => {
            const cantidad = parseFloat(p.cantidad) || 1;
            const precio = parseFloat(p.precio) || 0;
            const sub = cantidad * precio;
            
            subtotal += sub;
            
            html += `
                <tr>
                    <td>
                        ${p.imagen ? `<img src="${p.imagen.startsWith('uploads/') ? '../' + p.imagen : '../uploads/products/' + p.imagen}" alt="${p.nombre}" style="height:32px;max-width:40px;margin-right:6px;vertical-align:middle;object-fit:cover;border-radius:4px;">` : '<div style="width:32px;height:32px;background:#f8f9fa;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#6c757d;font-size:0.6rem;margin-right:6px;border:1px solid #dee2e6;">Sin<br>img</div>'}
                        ${p.nombre}
                    </td>
                    <td>${p.sku || ''}</td>
                    <td>
                        <span class="badge bg-secondary">Normal</span>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm cantidad-input text-center" 
                               value="${cantidad}" min="1" step="1" 
                               data-index="${i}" style="width: 80px;">
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm precio-input text-center" 
                               value="${precio.toFixed(2)}" min="0" step="0.01" 
                               data-index="${i}" style="width: 100px;">
                    </td>
                    <td class="total-fila text-center fw-bold" style="color: #121866;">
                        $${sub.toFixed(2)}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-product" data-index="${i}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        document.querySelector('#tablaProductos tbody').innerHTML = html;
        return subtotal;
    }

    // Función para renderizar tabla de servicios
    function renderTablaServicios() {
        let html = '';
        serviciosCotizacion.forEach((s, i) => {
            const cantidad = parseFloat(s.cantidad) || 1;
            const precio = parseFloat(s.precio) || 0;
            const total = cantidad * precio;
            
            html += `
                <tr>
                    <td>
                        ${s.imagen ? `<img src="../uploads/services/${s.imagen}" alt="Imagen" style="height:32px;max-width:40px;margin-right:6px;vertical-align:middle;">` : ''}
                        ${s.nombre}
                    </td>
                    <td>${s.categoria || ''}</td>
                    <td>${s.descripcion || ''}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm cantidad-servicio-input text-center" 
                               value="${cantidad}" min="1" step="1" data-index="${i}" style="width: 80px;">
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm precio-servicio-input text-center" 
                               value="${precio.toFixed(2)}" min="0" step="0.01" data-index="${i}" style="width: 100px;">
                    </td>
                    <td class="total-fila-servicio text-center fw-bold" style="color: #121866;">
                        $${total.toFixed(2)}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-servicio" data-index="${i}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        document.querySelector('#tablaServicios tbody').innerHTML = html;
    }

    // Función para renderizar tabla de insumos
    function renderTablaInsumos() {
        let html = '';
        insumosCotizacion.forEach((ins, i) => {
            const cantidad = parseFloat(ins.cantidad) || 1;
            const precio = parseFloat(ins.precio) || 0;
            const total = cantidad * precio;
            const stock = parseFloat(ins.stock) || 0;
            
            html += `
                <tr>
                    <td>${ins.nombre}</td>
                    <td>${ins.categoria || ''}</td>
                    <td>${ins.proveedor || ''}</td>
                    <td>
                        <span class="badge ${stock > cantidad ? 'bg-success' : 'bg-warning'}">${stock}</span>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm cantidad-insumo-input text-center" 
                               value="${cantidad}" min="1" step="1" data-index="${i}" style="width: 80px;">
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm precio-insumo-input text-center" 
                               value="${precio.toFixed(2)}" min="0" step="0.01" data-index="${i}" style="width: 100px;">
                    </td>
                    <td class="total-fila-insumo text-center fw-bold" style="color: #121866;">
                        $${total.toFixed(2)}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-insumo" data-index="${i}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        document.querySelector('#tablaInsumosCotizacion tbody').innerHTML = html;
    }

    // Eventos modernos para cantidad y precio
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('cantidad-input')) {
            const index = parseInt(e.target.dataset.index);
            const prod = productosCotizacion[index];
            if (!prod) return;
            
            let value = parseFloat(e.target.value) || 0;
            value = Math.max(1, Math.round(value));
            prod.cantidad = value;
            
            e.target.value = value;
            recalcularTotales();
        }
        
        if (e.target.classList.contains('precio-input')) {
            const index = parseInt(e.target.dataset.index);
            const prod = productosCotizacion[index];
            if (!prod) return;
            
            let value = parseFloat(e.target.value) || 0;
            prod.precio = Math.max(0, value);
            e.target.value = value.toFixed(2);
            recalcularTotales();
        }
    });

    // Eventos para servicios e insumos
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('cantidad-servicio-input') || e.target.classList.contains('precio-servicio-input')) {
            const index = parseInt(e.target.dataset.index);
            const servicio = serviciosCotizacion[index];
            if (!servicio) return;
            
            if (e.target.classList.contains('cantidad-servicio-input')) {
                servicio.cantidad = Math.max(1, Math.round(parseFloat(e.target.value) || 1));
                e.target.value = servicio.cantidad;
            } else {
                servicio.precio = Math.max(0, parseFloat(e.target.value) || 0);
                e.target.value = servicio.precio.toFixed(2);
            }
            renderTablaServicios();
            recalcularTotales();
        }
        
        if (e.target.classList.contains('cantidad-insumo-input') || e.target.classList.contains('precio-insumo-input')) {
            const index = parseInt(e.target.dataset.index);
            const insumo = insumosCotizacion[index];
            if (!insumo) return;
            
            if (e.target.classList.contains('cantidad-insumo-input')) {
                insumo.cantidad = Math.max(1, Math.round(parseFloat(e.target.value) || 1));
                e.target.value = insumo.cantidad;
            } else {
                insumo.precio = Math.max(0, parseFloat(e.target.value) || 0);
                e.target.value = insumo.precio.toFixed(2);
            }
            renderTablaInsumos();
            recalcularTotales();
        }
    });

    // Eliminar servicios e insumos
    document.addEventListener('click', function(e) {
        // Eliminar productos
        if (e.target.closest('.btn-remove-product')) {
            const index = parseInt(e.target.closest('.btn-remove-product').dataset.index);
            productosCotizacion.splice(index, 1);
            renderTablaProductos();
            recalcularTotales();
        }
        
        if (e.target.closest('.btn-remove-servicio')) {
            const index = parseInt(e.target.closest('.btn-remove-servicio').dataset.index);
            serviciosCotizacion.splice(index, 1);
            renderTablaServicios();
            recalcularTotales();
        }
        
        if (e.target.closest('.btn-remove-insumo')) {
            const index = parseInt(e.target.closest('.btn-remove-insumo').dataset.index);
            insumosCotizacion.splice(index, 1);
            renderTablaInsumos();
            recalcularTotales();
        }
    });

    // Función moderna para actualizar totales
    function actualizarTotales() {
        let subtotal = 0;
        
        // Calcular subtotal desde el array de productos modernos
        productosCotizacion.forEach(prod => {
            const cantidad = parseFloat(prod.cantidad) || 0;
            const precio = parseFloat(prod.precio) || 0;
            subtotal += cantidad * precio;
        });
        
        // Agregar servicios
        serviciosCotizacion.forEach(serv => {
            const cantidad = parseFloat(serv.cantidad) || 0;
            const precio = parseFloat(serv.precio) || 0;
            subtotal += cantidad * precio;
        });
        
        // Agregar insumos
        insumosCotizacion.forEach(ins => {
            const cantidad = parseFloat(ins.cantidad) || 0;
            const precio = parseFloat(ins.precio) || 0;
            subtotal += cantidad * precio;
        });
        
        const descuentoPorcentaje = parseFloat(document.getElementById('descuento_porcentaje').value) || 0;
        const descuento = subtotal * descuentoPorcentaje / 100;
        const total = subtotal - descuento;
        
        document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('descuento').textContent = '$' + descuento.toFixed(2);
        document.getElementById('total').textContent = '$' + total.toFixed(2);
    }

    // Función alias para compatibilidad
    function recalcularTotales() {
        actualizarTotales();
    }

    // Función para cargar productos existentes al formato moderno
    function cargarProductosCotizacion() {
        // Ya convertimos los productos PHP al array moderno al cargar la página
        // Solo necesitamos renderizar
        renderTablaProductos();
        recalcularTotales();
    }

    // Ejecutar al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        cargarProductosCotizacion();
    });

    // Preparar datos modernos para envío
    document.getElementById('formEditarCotizacion').addEventListener('submit', function(e) {
        const productos = [];
        const servicios = [];
        const insumos = [];
        let error = '';
        
        // Obtener productos del array moderno
        productosCotizacion.forEach(prod => {
            const productId = prod.product_id;
            const tipoGestion = prod.tipo_gestion || 'tradicional';
            const esBobina = tipoGestion === 'bobina';
            
            let cantidad = parseFloat(prod.cantidad) || 0;
            let precio = parseFloat(prod.precio) || 0;
            
            if (productId && cantidad > 0 && precio > 0) {
                productos.push({
                    product_id: productId,
                    cantidad: cantidad,
                    precio: precio,
                    tipo_gestion: tipoGestion,
                    _modoPrecio: prod._modoPrecio // Preservar el modo de precio para el cálculo correcto
                });
            }
        });
        
        // Obtener servicios del array moderno
        serviciosCotizacion.forEach(serv => {
            const servicioId = serv.servicio_id;
            const cantidad = parseFloat(serv.cantidad) || 0;
            const precio = parseFloat(serv.precio) || 0;
            
            if (cantidad > 0 && precio > 0) {
                servicios.push({
                    servicio_id: servicioId,
                    nombre: serv.nombre,
                    categoria: serv.categoria,
                    descripcion: serv.descripcion,
                    cantidad: cantidad,
                    precio: precio,
                    imagen: serv.imagen
                });
            }
        });
        
        // Obtener insumos del array moderno
        insumosCotizacion.forEach(ins => {
            const insumoId = ins.insumo_id;
            const cantidad = parseFloat(ins.cantidad) || 0;
            const precio = parseFloat(ins.precio) || 0;
            
            if (cantidad > 0 && precio > 0) {
                insumos.push({
                    insumo_id: insumoId,
                    nombre: ins.nombre,
                    categoria: ins.categoria,
                    proveedor: ins.proveedor,
                    cantidad: cantidad,
                    precio: precio,
                    stock: ins.stock
                });
            }
        });
        
        // También revisar tabla tradicional por compatibilidad
        document.querySelectorAll('#tablaProductos tbody tr').forEach(row => {
            const productId = row.dataset.productId;
            const tipoGestion = row.dataset.tipoGestion || 'tradicional';
            const esBobina = tipoGestion === 'bobina';
            
            let cantidad = parseFloat(row.querySelector('.cantidad-input')?.value) || 0;
            if (esBobina) {
                cantidad = Math.max(0.01, cantidad);
            } else {
                cantidad = Math.max(1, Math.round(cantidad));
            }
            
            let precio = parseFloat(row.querySelector('.precio-input')?.value) || 0;
            
            // Para bobinas, convertir precio por metro a precio total de bobina para almacenamiento
            if (esBobina && row._precioBase) {
                // El precio actual es por metro, pero necesitamos almacenar el precio de bobina completa
                const precioParaAlmacenar = row._precioBobinaOriginal || (precio * PRECIO_CONFIG.metrosPorBobina);
                precio = precioParaAlmacenar;
            }
            
            if (productId && cantidad > 0 && precio > 0) {
                productos.push({
                    product_id: productId,
                    cantidad: cantidad,
                    precio: precio,
                    tipo_gestion: tipoGestion
                });
            }
        });
        
        if (productos.length === 0 && servicios.length === 0 && insumos.length === 0) {
            e.preventDefault();
            alert('Debes agregar al menos un producto, servicio o insumo a la cotización.');
            return false;
        }
        
        document.getElementById('productos_json').value = JSON.stringify(productos);
        document.getElementById('servicios_json').value = JSON.stringify(servicios);
        document.getElementById('insumos_json').value = JSON.stringify(insumos);
        
        // ENVIAR TOTALES CALCULADOS DEL FRONTEND AL BACKEND (igual que crear.php)
        const totalesCalculados = calcularTotalesCompletos();
        
        // Crear campos hidden para enviar totales del frontend
        let inputSubtotal = document.getElementById('subtotal_frontend');
        if (!inputSubtotal) {
            inputSubtotal = document.createElement('input');
            inputSubtotal.type = 'hidden';
            inputSubtotal.name = 'subtotal_frontend';
            inputSubtotal.id = 'subtotal_frontend';
            document.getElementById('formEditarCotizacion').appendChild(inputSubtotal);
        }
        inputSubtotal.value = totalesCalculados.subtotal;
        
        let inputDescuento = document.getElementById('descuento_monto_frontend');
        if (!inputDescuento) {
            inputDescuento = document.createElement('input');
            inputDescuento.type = 'hidden';
            inputDescuento.name = 'descuento_monto_frontend';
            inputDescuento.id = 'descuento_monto_frontend';
            document.getElementById('formEditarCotizacion').appendChild(inputDescuento);
        }
        inputDescuento.value = totalesCalculados.descuento_monto;
        
        let inputTotal = document.getElementById('total_frontend');
        if (!inputTotal) {
            inputTotal = document.createElement('input');
            inputTotal.type = 'hidden';
            inputTotal.name = 'total_frontend';
            inputTotal.id = 'total_frontend';
            document.getElementById('formEditarCotizacion').appendChild(inputTotal);
        }
        inputTotal.value = totalesCalculados.total;
    });

    // Alta rápida de productos
    const btnCrearProducto = document.getElementById('btn_crear_producto');
    if (btnCrearProducto) {
        btnCrearProducto.addEventListener('click', function() {
        const nombre = document.getElementById('nuevo_nombre_producto').value.trim();
        const descripcion = document.getElementById('nuevo_descripcion_producto').value.trim();
        const sku = document.getElementById('nuevo_sku_producto').value.trim();
        const precio = parseFloat(document.getElementById('nuevo_precio_producto').value) || 0;
        const costo = parseFloat(document.getElementById('nuevo_costo_producto').value) || 0;
        const cantidad = parseInt(document.getElementById('nuevo_cantidad_producto').value) || 1;
        const categoria_id = document.getElementById('nuevo_categoria_producto').value;
        const supplier_id = document.getElementById('nuevo_proveedor_producto').value;
        const agregar_cotizacion = document.getElementById('nuevo_agregar_cotizacion').checked;
        const imagen = document.getElementById('nuevo_imagen_producto').files[0];

        if (!nombre || !precio) {
            alert('El nombre y precio son obligatorios');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'crear_producto');
        formData.append('nombre', nombre);
        formData.append('descripcion', descripcion);
        formData.append('sku', sku);
        formData.append('precio', precio);
        formData.append('costo', costo);
        formData.append('cantidad', cantidad);
        formData.append('categoria_id', categoria_id);
        formData.append('supplier_id', supplier_id);
        if (imagen) {
            formData.append('imagen', imagen);
        }
        formData.append('ajax_action', 'crear_producto');

        fetch('editar.php?id=<?= $cotizacion_id ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Producto creado exitosamente');
                
                if (agregar_cotizacion) {
                    const producto = {
                        product_id: data.product_id,
                        nombre: nombre,
                        sku: sku,
                        precio: precio,
                        cantidad: cantidad,
                        tipo_gestion: 'normal'
                    };
                    productosCotizacion.push(producto);
                    renderTablaProductos();
                    recalcularTotales();
                }
                
                // Limpiar campos
                document.getElementById('nuevo_nombre_producto').value = '';
                document.getElementById('nuevo_descripcion_producto').value = '';
                document.getElementById('nuevo_sku_producto').value = '';
                document.getElementById('nuevo_precio_producto').value = '';
                document.getElementById('nuevo_costo_producto').value = '';
                document.getElementById('nuevo_cantidad_producto').value = '1';
                document.getElementById('nuevo_categoria_producto').value = '';
                document.getElementById('nuevo_proveedor_producto').value = '';
                document.getElementById('nuevo_imagen_producto').value = '';
                document.getElementById('nuevo_agregar_cotizacion').checked = false;
            } else {
                alert('Error al crear producto: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al crear producto');
        });
    });
    }

    // SISTEMA MODERNO DE SERVICIOS - Se maneja con las funciones ya definidas arriba

    // SISTEMA MODERNO DE INSUMOS - Se maneja con las funciones ya definidas arriba
    
    // Buscador de insumos
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
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Error en búsqueda de insumos:', textStatus, errorThrown);
            $('#sugerencias_insumos').html('<div class="list-group-item text-danger">Error al buscar insumos</div>').show();
        });
    });
    
    $('#sugerencias_insumos').on('click', 'button', function() {
        const nuevoInsumo = {
            insumo_id: $(this).data('id'),
            nombre: $(this).data('nombre'),
            categoria: $(this).data('categoria'),
            proveedor: $(this).data('proveedor'),
            stock: $(this).data('stock'),
            cantidad: 1,
            precio: $(this).data('precio')
        };
        
        // Evitar duplicados
        if (insumosCotizacion.some(i => i.insumo_id == nuevoInsumo.insumo_id)) {
            alert('Este insumo ya está agregado a la cotización');
            return;
        }
        
        insumosCotizacion.push(nuevoInsumo);
        renderTablaInsumos();
        recalcularTotales();
        $('#buscador_insumo').val('');
        $('#sugerencias_insumos').hide();
    });

    // Función para calcular totales completos
    function calcularTotalesCompletos() {
        let subtotal = 0;
        
        // Sumar productos
        productosCotizacion.forEach(prod => {
            const cantidad = parseFloat(prod.cantidad) || 1;
            const precio = parseFloat(prod.precio) || 0;
            subtotal += cantidad * precio;
        });
        
        // Sumar servicios
        serviciosCotizacion.forEach(serv => {
            const cantidad = parseFloat(serv.cantidad) || 0;
            const precio = parseFloat(serv.precio) || 0;
            subtotal += cantidad * precio;
        });
        
        // Sumar insumos
        insumosCotizacion.forEach(ins => {
            const cantidad = parseFloat(ins.cantidad) || 0;
            const precio = parseFloat(ins.precio) || 0;
            subtotal += cantidad * precio;
        });
        
        const descuentoPorcentaje = parseFloat(document.getElementById('descuento_porcentaje').value) || 0;
        const descuentoMonto = subtotal * descuentoPorcentaje / 100;
        const total = subtotal - descuentoMonto;
        
        return {
            subtotal: subtotal.toFixed(2),
            descuento_monto: descuentoMonto.toFixed(2),
            total: total.toFixed(2)
        };
    }

    // Evento de descuento
    document.getElementById('descuento_porcentaje').addEventListener('input', actualizarTotales);
    
</script>
</body>
</html> 