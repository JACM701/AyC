<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

// Función para obtener productos del inventario
function getProductosInventario() {
    global $mysqli;
    $productos = [];
    
    $query = "SELECT p.product_id as id, p.product_name as nombre, p.quantity as stock, 
                     p.price as precio, p.tipo_gestion, c.name as categoria, s.name as proveedor
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.category_id 
              LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
              WHERE p.quantity > 0 
              ORDER BY p.product_name";
    
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    
    return $productos;
}

// Filtros
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$proveedor_filtro = isset($_GET['proveedor']) ? $_GET['proveedor'] : '';
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : '';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

// Construir consulta base para insumos
$query = "SELECT i.*, p.product_name as producto_origen, s.name as proveedor 
          FROM insumos i
          LEFT JOIN products p ON i.product_id = p.product_id
          LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
          WHERE i.is_active = 1";
$params = [];
$types = '';

if ($categoria_filtro) {
    $query .= " AND p.category_id = ?";
    $params[] = $categoria_filtro;
    $types .= 'i';
}
if ($proveedor_filtro) {
    $query .= " AND s.supplier_id = ?";
    $params[] = $proveedor_filtro;
    $types .= 'i';
}
if ($estado_filtro) {
    $query .= " AND i.estado = ?";
    $params[] = $estado_filtro;
    $types .= 's';
}
if ($busqueda) {
    $query .= " AND (i.nombre LIKE ? OR p.product_name LIKE ? OR s.name LIKE ?)";
    $like = "%$busqueda%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
$query .= " ORDER BY i.nombre ASC";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$insumos = $stmt->get_result();

// Obtener categorías únicas
$categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name");
// Obtener proveedores únicos
$proveedores = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name");

// Calcular estadísticas
$stats_query = "SELECT COUNT(*) as total_insumos,
    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
    SUM(CASE WHEN estado = 'bajo_stock' THEN 1 ELSE 0 END) as bajo_stock,
    SUM(CASE WHEN estado = 'agotado' THEN 1 ELSE 0 END) as agotados,
    SUM(cantidad * precio_unitario) as valor_total,
    SUM(consumo_semanal) as consumo_semanal_total
    FROM insumos WHERE is_active = 1";
$stats = $mysqli->query($stats_query)->fetch_assoc();
$total_insumos = $stats['total_insumos'] ?? 0;
$disponibles = $stats['disponibles'] ?? 0;
$bajo_stock = $stats['bajo_stock'] ?? 0;
$agotados = $stats['agotados'] ?? 0;
$valor_total = $stats['valor_total'] ?? 0;
$consumo_semanal_total = $stats['consumo_semanal_total'] ?? 0;

// --- Endpoint para alta real de insumos (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'agregar_insumo') {
    header('Content-Type: application/json');
    $product_id = intval($_POST['product_id'] ?? 0);
    $cantidad = floatval($_POST['cantidad'] ?? 0);
    $minimo = floatval($_POST['minimo'] ?? 0);
    $unidad = trim($_POST['unidad'] ?? 'pieza');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
    
    if (!$product_id || $cantidad <= 0 || $minimo < 0) {
        echo json_encode(['success'=>false,'message'=>'Datos incompletos o inválidos.']);
        exit;
    }
    
    // Obtener producto de origen
    $stmt = $mysqli->prepare("SELECT p.product_id, p.product_name, p.quantity, p.price, p.category_id, p.supplier_id, p.tipo_gestion, c.name as categoria_nombre FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = ?");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$producto) {
        echo json_encode(['success'=>false,'message'=>'Producto de origen no encontrado.']);
        exit;
    }
    
    if ($producto['quantity'] < $cantidad) {
        echo json_encode(['success'=>false,'message'=>'No hay suficiente stock disponible en el producto de origen. Stock actual: ' . $producto['quantity']]);
        exit;
    }
    
    // Calcular precio unitario basado en el tipo de gestión
    $precio_unitario = 0;
    if ($producto['tipo_gestion'] === 'bobina') {
        $precio_unitario = floatval($producto['price']) / 305; // 305 metros por bobina
    } else {
        $precio_unitario = floatval($producto['price']);
    }
    
    // Determinar estado inicial
    $estado = 'disponible';
    if ($cantidad <= $minimo) {
        $estado = 'bajo_stock';
    }
    if ($cantidad == 0) {
        $estado = 'agotado';
    }
    
    // Insertar insumo
    $stmt = $mysqli->prepare("INSERT INTO insumos (product_id, nombre, categoria, unidad, cantidad, minimo, precio_unitario, ubicacion, estado, consumo_semanal, ultima_actualizacion, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), 1)");
    $nombre_insumo = $producto['product_name'];
    $categoria = $producto['categoria_nombre'] ?? '';
    
    $stmt->bind_param('isssddsss', $product_id, $nombre_insumo, $categoria, $unidad, $cantidad, $minimo, $precio_unitario, $ubicacion, $estado);
    
    if ($stmt->execute()) {
        // Descontar stock del producto de origen
        $stmt2 = $mysqli->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
        $stmt2->bind_param('di', $cantidad, $product_id);
        $stmt2->execute();
        $stmt2->close();
        
        // Registrar movimiento en la tabla movements
        $movement_type_id = 2; // Salida
        $stmt3 = $mysqli->prepare("INSERT INTO movements (product_id, movement_type_id, quantity, notes, user_id, movement_date) VALUES (?, ?, ?, ?, ?, NOW())");
        $notes = "Creación de insumo: " . $nombre_insumo;
        $stmt3->bind_param('iissi', $product_id, $movement_type_id, $cantidad, $notes, $user_id);
        $stmt3->execute();
        $stmt3->close();
        
        echo json_encode(['success'=>true,'message'=>'Insumo registrado correctamente y stock actualizado.']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Error al registrar el insumo: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// --- Endpoint para edición real de insumos (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar_insumo') {
    header('Content-Type: application/json');
    $insumo_id = intval($_POST['insumo_id'] ?? 0);
    $minimo = floatval($_POST['minimo'] ?? 0);
    $unidad = trim($_POST['unidad'] ?? 'pieza');
    
    if (!$insumo_id || $minimo < 0) {
        echo json_encode(['success'=>false,'message'=>'Datos incompletos o inválidos.']);
        exit;
    }
    
    // Obtener insumo actual
    $stmt = $mysqli->prepare("SELECT * FROM insumos WHERE insumo_id = ?");
    $stmt->bind_param('i', $insumo_id);
    $stmt->execute();
    $insumo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$insumo) {
        echo json_encode(['success'=>false,'message'=>'Insumo no encontrado.']);
        exit;
    }
    
    // Determinar estado basado en la cantidad actual
    $estado = 'disponible';
    if ($insumo['cantidad'] <= $minimo) {
        $estado = 'bajo_stock';
    }
    if ($insumo['cantidad'] == 0) {
        $estado = 'agotado';
    }
    
    // Actualizar insumo (solo minimo, unidad y estado)
    $stmt = $mysqli->prepare("UPDATE insumos SET minimo = ?, unidad = ?, estado = ?, ultima_actualizacion = NOW() WHERE insumo_id = ?");
    $stmt->bind_param('dssi', $minimo, $unidad, $estado, $insumo_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'message'=>'Insumo actualizado correctamente.']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Error al actualizar el insumo: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// --- Endpoint para registrar movimiento de insumo (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'registrar_movimiento') {
    header('Content-Type: application/json');
    $insumo_id = intval($_POST['insumo_id'] ?? 0);
    $tipo_movimiento = $_POST['tipo_movimiento'] ?? '';
    $cantidad = floatval($_POST['cantidad'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 1; // Default a 1 si no hay usuario
    
    if (!$insumo_id || !in_array($tipo_movimiento, ['entrada', 'salida']) || $cantidad <= 0) {
        echo json_encode(['success'=>false,'message'=>'Datos incompletos o inválidos.']);
        exit;
    }
    
    // Obtener insumo actual y producto origen
    $stmt = $mysqli->prepare("SELECT i.*, p.product_id as product_id, p.quantity as product_stock, p.tipo_gestion 
                              FROM insumos i 
                              LEFT JOIN products p ON i.product_id = p.product_id 
                              WHERE i.insumo_id = ?");
    $stmt->bind_param('i', $insumo_id);
    $stmt->execute();
    $insumo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$insumo) {
        echo json_encode(['success'=>false,'message'=>'Insumo no encontrado.']);
        exit;
    }
    
    // Debug: Mostrar información del insumo
    error_log("Insumo ID: " . $insumo_id);
    error_log("Tipo movimiento: " . $tipo_movimiento);
    error_log("Cantidad: " . $cantidad);
    error_log("Stock actual insumo: " . $insumo['cantidad']);
    error_log("Stock producto origen: " . $insumo['product_stock']);
    
    // Verificar stock según el tipo de movimiento
    if ($tipo_movimiento === 'entrada') {
        // Para entradas, verificar que hay stock disponible en el producto origen
        if (!$insumo['product_id']) {
            echo json_encode(['success'=>false,'message'=>'Este insumo no tiene un producto origen válido.']);
            exit;
        }
        
        if ($insumo['tipo_gestion'] === 'bobina') {
            // --- Lógica especial para bobinas ---
            // Obtener bobinas con stock, ordenadas por fecha (FIFO)
            $stmt_bobinas = $mysqli->prepare("SELECT bobina_id, metros_actuales FROM bobinas WHERE product_id = ? AND is_active = 1 AND metros_actuales > 0 ORDER BY created_at ASC, bobina_id ASC");
            $stmt_bobinas->bind_param('i', $insumo['product_id']);
            $stmt_bobinas->execute();
            $bobinas = $stmt_bobinas->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_bobinas->close();
            
            $metros_restantes = $cantidad;
            $bobinas_a_descontar = [];
            foreach ($bobinas as $bobina) {
                if ($metros_restantes <= 0) break;
                $descontar = min($bobina['metros_actuales'], $metros_restantes);
                $bobinas_a_descontar[] = [
                    'bobina_id' => $bobina['bobina_id'],
                    'descontar' => $descontar
                ];
                $metros_restantes -= $descontar;
            }
            if ($metros_restantes > 0) {
                echo json_encode(['success'=>false,'message'=>'No hay suficientes metros disponibles en las bobinas para realizar la entrada.']);
                exit;
            }
        }
        else {
            // Producto normal: verificar stock en products
            if ($insumo['product_stock'] < $cantidad) {
                echo json_encode(['success'=>false,'message'=>'No hay suficiente stock en el inventario. Stock disponible: ' . $insumo['product_stock'] . ' ' . $insumo['unidad']]);
                exit;
            }
        }
    } else {
        // Para salidas, verificar stock del insumo
        if ($insumo['cantidad'] < $cantidad) {
            echo json_encode(['success'=>false,'message'=>'No hay suficiente stock en el insumo. Stock actual: ' . $insumo['cantidad'] . ' ' . $insumo['unidad']]);
            exit;
        }
    }
    
    // Calcular nueva cantidad del insumo
    $nueva_cantidad = $insumo['cantidad'];
    if ($tipo_movimiento === 'entrada') {
        $nueva_cantidad += $cantidad;
    } else {
        $nueva_cantidad -= $cantidad;
    }
    
    // Determinar nuevo estado del insumo
    $estado = 'disponible';
    if ($nueva_cantidad <= $insumo['minimo']) {
        $estado = 'bajo_stock';
    }
    if ($nueva_cantidad == 0) {
        $estado = 'agotado';
    }
    
    // Usar transacción para asegurar consistencia
    $mysqli->begin_transaction();
    
    try {
        // Registrar movimiento (insert y bind_param en orden correcto)
        $stmt = $mysqli->prepare("INSERT INTO insumos_movements (insumo_id, user_id, tipo_movimiento, motivo, cantidad, fecha_movimiento) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('iissd', $insumo_id, $user_id, $tipo_movimiento, $motivo, $cantidad);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al registrar el movimiento: ' . $stmt->error);
        }
        $stmt->close();
        
        // Actualizar stock del insumo
        $stmt2 = $mysqli->prepare("UPDATE insumos SET cantidad = ?, estado = ?, ultima_actualizacion = NOW() WHERE insumo_id = ?");
        $stmt2->bind_param('dsi', $nueva_cantidad, $estado, $insumo_id);
        
        if (!$stmt2->execute()) {
            throw new Exception('Error al actualizar el insumo: ' . $stmt2->error);
        }
        $stmt2->close();
        
        // Si es entrada, descontar del producto origen solo si la cantidad es mayor a 0
        if ($tipo_movimiento === 'entrada' && $cantidad > 0) {
            if ($insumo['tipo_gestion'] === 'bobina') {
                // Descontar metros de las bobinas seleccionadas
                foreach ($bobinas_a_descontar as $b) {
                    $stmt3 = $mysqli->prepare("UPDATE bobinas SET metros_actuales = metros_actuales - ? WHERE bobina_id = ?");
                    $stmt3->bind_param('di', $b['descontar'], $b['bobina_id']);
                    if (!$stmt3->execute()) {
                        throw new Exception('Error al descontar metros de la bobina: ' . $stmt3->error);
                    }
                    $stmt3->close();

                    // Registrar movimiento en tabla movements (Salida de bobina)
                    $movement_type_id = 2; // Salida
                    $cantidad_negativa = -1 * $b['descontar'];
                    $notes = "Descuento por creación de insumo ID $insumo_id: $motivo";
                    $stmt_mov = $mysqli->prepare("INSERT INTO movements (product_id, bobina_id, movement_type_id, quantity, notes, user_id, movement_date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt_mov->bind_param('iiidsi', $insumo['product_id'], $b['bobina_id'], $movement_type_id, $cantidad_negativa, $notes, $user_id);
                    if (!$stmt_mov->execute()) {
                        throw new Exception('Error al registrar movimiento de bobina: ' . $stmt_mov->error);
                    }
                    $stmt_mov->close();
                }
                // Actualizar el stock total del producto (suma de todas las bobinas)
                $stmt4 = $mysqli->prepare("UPDATE products SET quantity = (SELECT COALESCE(SUM(metros_actuales),0) FROM bobinas WHERE product_id = ?) WHERE product_id = ?");
                $stmt4->bind_param('ii', $insumo['product_id'], $insumo['product_id']);
                if (!$stmt4->execute()) {
                    throw new Exception('Error al actualizar el stock total del producto bobina: ' . $stmt4->error);
                }
                $stmt4->close();
            } else {
                // Producto normal: descontar del campo quantity
                $stmt3 = $mysqli->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
                $stmt3->bind_param('di', $cantidad, $insumo['product_id']);
                if (!$stmt3->execute()) {
                    throw new Exception('Error al actualizar el producto origen: ' . $stmt3->error);
                }
                $stmt3->close();
            }
        }
        
        $mysqli->commit();
        echo json_encode(['success'=>true,'message'=>'Movimiento registrado correctamente. Nuevo stock del insumo: ' . $nueva_cantidad . ' ' . $insumo['unidad']]);
        
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("Error en movimiento de insumo: " . $e->getMessage());
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    
    exit;
}

// --- Endpoint para obtener reporte de insumo (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'obtener_reporte') {
    header('Content-Type: application/json');
    $insumo_id = intval($_POST['insumo_id'] ?? 0);
    
    if (!$insumo_id) {
        echo json_encode(['success'=>false,'message'=>'ID de insumo requerido.']);
        exit;
    }
    
    // Obtener datos del insumo
    $stmt = $mysqli->prepare("SELECT i.*, p.product_name as producto_origen FROM insumos i 
                              LEFT JOIN products p ON i.product_id = p.product_id 
                              WHERE i.insumo_id = ?");
    $stmt->bind_param('i', $insumo_id);
    $stmt->execute();
    $insumo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$insumo) {
        echo json_encode(['success'=>false,'message'=>'Insumo no encontrado.']);
        exit;
    }
    
    // Obtener todos los movimientos (sin filtro de fecha para debug)
    $stmt = $mysqli->prepare("SELECT m.insumo_movement_id, m.tipo_movimiento, m.cantidad, m.motivo, m.fecha_movimiento, u.username as usuario 
                              FROM insumos_movements m 
                              LEFT JOIN users u ON m.user_id = u.user_id 
                              WHERE m.insumo_id = ? 
                              ORDER BY m.fecha_movimiento DESC");
    $stmt->bind_param('i', $insumo_id);
    $stmt->execute();
    $movimientos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Debug: Mostrar información de movimientos
    error_log("Insumo ID para reporte: " . $insumo_id);
    error_log("Número de movimientos encontrados: " . count($movimientos));
    
    // Calcular estadísticas
    $total_entradas = 0;
    $total_salidas = 0;
    $consumo_semanal = 0;
    
    foreach ($movimientos as $mov) {
        if ($mov['tipo_movimiento'] === 'entrada') {
            $total_entradas += $mov['cantidad'];
        } else {
            $total_salidas += $mov['cantidad'];
        }
    }
    
    // Calcular consumo semanal promedio
    if (count($movimientos) > 0) {
        $consumo_semanal = $total_salidas / 4; // Promedio de 4 semanas
    }
    
    $reporte = [
        'insumo' => $insumo,
        'movimientos' => $movimientos,
        'estadisticas' => [
            'total_entradas' => $total_entradas,
            'total_salidas' => $total_salidas,
            'consumo_semanal' => $consumo_semanal,
            'stock_actual' => $insumo['cantidad'],
            'stock_minimo' => $insumo['minimo']
        ]
    ];
    
    echo json_encode(['success'=>true,'data'=>$reporte]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Insumos y Materiales | Gestor de inventarios</title>
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
        .insumos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
        }
        .insumos-title {
            font-size: 2.2rem;
            color: #121866;
            font-weight: 800;
            margin: 0;
        }
        .insumos-stats {
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
        .insumos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        .insumo-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .insumo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .insumo-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #121866, #232a7c);
        }
        .insumo-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .insumo-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
            line-height: 1.3;
        }
        .insumo-category {
            display: inline-block;
            padding: 4px 12px;
            background: #e3f2fd;
            color: #1565c0;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
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
        .insumo-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .detail-item {
            text-align: center;
            padding: 12px 8px;
            background: #f7f9fc;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
        }
        .detail-number {
            font-size: 1.4rem;
            font-weight: 700;
            color: #121866;
            display: block;
            margin-bottom: 4px;
        }
        .detail-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
        }
        .insumo-actions {
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
        .btn-report {
            background: #fff3e0;
            color: #f57c00;
        }
        .btn-report:hover {
            background: #f57c00;
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
        .alert-section {
            margin-bottom: 24px;
        }
        .alert-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            border-left: 4px solid #e53935;
            box-shadow: 0 2px 8px rgba(18,24,102,0.07);
        }
        .alert-card.warning {
            border-left-color: #ffc107;
        }
        .alert-card.info {
            border-left-color: #00bcd4;
        }
        .producto-origen {
            font-size: 0.85rem;
            color: #666;
            font-style: italic;
            margin-bottom: 8px;
        }
        .consumo-semanal {
            display: inline-block;
            padding: 2px 8px;
            background: #e8f5e8;
            color: #2e7d32;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .insumos-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            .insumos-stats {
                flex-wrap: wrap;
                justify-content: center;
            }
            .insumos-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="insumos-header">
            <div>
                <h1 class="insumos-title">
                    <i class="bi bi-box2"></i> Insumos y Materiales
                </h1>
                <p class="text-muted mb-0">Materiales de trabajo derivados de productos del catálogo</p>
            </div>
            <div class="insumos-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $total_insumos ?></span>
                    <span class="stat-label">Insumos</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $disponibles ?></span>
                    <span class="stat-label">Disponibles</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $bajo_stock ?></span>
                    <span class="stat-label">Bajo Stock</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $agotados ?></span>
                    <span class="stat-label">Agotados</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">$<?= number_format($valor_total, 0) ?></span>
                    <span class="stat-label">Valor Total</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $consumo_semanal_total ?></span>
                    <span class="stat-label">Consumo Sem.</span>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        <div class="alert-section">
            <?php 
            $agotados_insumos = $mysqli->query("SELECT i.*, i.categoria as categoria, s.name as proveedor FROM insumos i
                LEFT JOIN products p ON i.product_id = p.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id WHERE i.estado = 'agotado' AND i.is_active = 1 ORDER BY i.nombre ASC");
            $bajo_stock_insumos = $mysqli->query("SELECT i.*, i.categoria as categoria, s.name as proveedor FROM insumos i
                LEFT JOIN products p ON i.product_id = p.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id WHERE i.estado = 'bajo_stock' AND i.is_active = 1 ORDER BY i.nombre ASC");
            ?>
            
            <?php if ($agotados_insumos->num_rows > 0): ?>
                <div class="alert-card">
                    <h6><i class="bi bi-exclamation-triangle"></i> Insumos Agotados</h6>
                    <p class="mb-0">Los siguientes insumos están agotados y requieren reabastecimiento:</p>
                    <ul class="mb-0 mt-2">
                        <?php while ($insumo = $agotados_insumos->fetch_assoc()): ?>
                            <li><strong><?= htmlspecialchars($insumo['nombre']) ?></strong> - <?= htmlspecialchars($insumo['proveedor']) ?></li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($bajo_stock_insumos->num_rows > 0): ?>
                <div class="alert-card warning">
                    <h6><i class="bi bi-exclamation-circle"></i> Stock Bajo</h6>
                    <p class="mb-0">Los siguientes insumos tienen stock bajo:</p>
                    <ul class="mb-0 mt-2">
                        <?php while ($insumo = $bajo_stock_insumos->fetch_assoc()): ?>
                            <li><strong><?= htmlspecialchars($insumo['nombre']) ?></strong> - <?= $insumo['cantidad'] ?> <?= $insumo['unidad'] ?> (mínimo: <?= $insumo['minimo'] ?>)</li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="filters-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="busqueda" class="form-label">Buscar insumo</label>
                    <input type="text" class="form-control" id="busqueda" name="busqueda" 
                           value="<?= htmlspecialchars($busqueda) ?>" 
                           placeholder="Nombre, categoría, proveedor...">
                </div>
                <div class="col-md-2">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">Todas</option>
                        <?php while ($cat = $categorias->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['category_id']) ?>" 
                                    <?= $categoria_filtro == $cat['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="proveedor" class="form-label">Proveedor</label>
                    <select class="form-select" id="proveedor" name="proveedor">
                        <option value="">Todos</option>
                        <?php while ($prov = $proveedores->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($prov['supplier_id']) ?>" 
                                    <?= $proveedor_filtro == $prov['supplier_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prov['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="disponible" <?= $estado_filtro === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                        <option value="bajo_stock" <?= $estado_filtro === 'bajo_stock' ? 'selected' : '' ?>>Bajo Stock</option>
                        <option value="agotado" <?= $estado_filtro === 'agotado' ? 'selected' : '' ?>>Agotado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="insumos.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                        <button type="button" class="btn btn-success" onclick="agregarInsumo()">
                            <i class="bi bi-plus-circle"></i> Agregar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($insumos->num_rows === 0): ?>
            <div class="empty-state">
                <i class="bi bi-box2"></i>
                <h5>No se encontraron insumos</h5>
                <p>Intenta ajustar los filtros de búsqueda o agrega nuevos insumos.</p>
                <button type="button" class="btn btn-primary" onclick="agregarInsumo()">
                    <i class="bi bi-plus-circle"></i> Agregar Insumo
                </button>
            </div>
        <?php else: ?>
            <?php 
            // Guardar los datos de insumos para JavaScript antes del bucle
            $insumos_data = $insumos->fetch_all(MYSQLI_ASSOC);
            ?>
            <div class="insumos-grid">
                <?php foreach ($insumos_data as $insumo): ?>
                    <div class="insumo-card">
                        <div class="insumo-header">
                            <div>
                                <h5 class="insumo-title"><?= htmlspecialchars($insumo['nombre']) ?></h5>
                                <div class="insumo-category"><?= htmlspecialchars($insumo['categoria']) ?></div>
                                <div class="producto-origen">
                                    <i class="bi bi-arrow-right"></i> Derivado de: <?= htmlspecialchars($insumo['producto_origen'] ?? 'Producto no encontrado') ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stock-status">
                            <span class="status-badge status-<?= $insumo['estado'] ?>">
                                <?php
                                switch($insumo['estado']) {
                                    case 'disponible': echo '<i class="bi bi-check-circle"></i> Disponible'; break;
                                    case 'bajo_stock': echo '<i class="bi bi-exclamation-triangle"></i> Bajo Stock'; break;
                                    case 'agotado': echo '<i class="bi bi-x-circle"></i> Agotado'; break;
                                }
                                ?>
                            </span>
                            <span class="consumo-semanal">
                                <i class="bi bi-calendar-week"></i> <?= $insumo['consumo_semanal'] ?> <?= $insumo['unidad'] ?>/sem
                            </span>
                        </div>
                        
                        <div class="insumo-details">
                            <div class="detail-item">
                                <span class="detail-number"><?= $insumo['cantidad'] ?></span>
                                <span class="detail-label">Stock (<?= $insumo['unidad'] ?>)</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-number"><?= $insumo['minimo'] ?></span>
                                <span class="detail-label">Mínimo</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-number">$<?= number_format($insumo['precio_unitario'], 2) ?></span>
                                <span class="detail-label">Precio Unit.</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-number">$<?= number_format($insumo['cantidad'] * $insumo['precio_unitario'], 2) ?></span>
                                <span class="detail-label">Valor Total</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <strong>Proveedor:</strong> <?= htmlspecialchars($insumo['proveedor']) ?> | 
                                <strong>Ubicación:</strong> <?= htmlspecialchars($insumo['ubicacion']) ?> | 
                                <strong>Última actualización:</strong> <?= date('d/m/Y', strtotime($insumo['ultima_actualizacion'])) ?>
                            </small>
                        </div>
                        
                        <div class="insumo-actions">
                            <button class="btn-action btn-edit" onclick="editarInsumo(<?= $insumo['insumo_id'] ?>)">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                            <button class="btn-action btn-movement" onclick="registrarMovimiento(<?= $insumo['insumo_id'] ?>)">
                                <i class="bi bi-arrow-left-right"></i> Movimiento
                            </button>
                            <button class="btn-action btn-report" onclick="verReporteSemanal(<?= $insumo['insumo_id'] ?>)">
                                <i class="bi bi-graph-up"></i> Reporte Sem.
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        window.insumosData = <?= json_encode($insumos_data ?? []) ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        
        function agregarInsumo() {
            // Obtener productos del catálogo desde PHP
            const productos = <?= json_encode(getProductosInventario()) ?>;
            
            // Generar opciones del select dinámicamente
            let opcionesProductos = '<option value="">-- Selecciona un producto --</option>';
            productos.forEach(producto => {
                const precioUnitario = producto.tipo_gestion === 'bobina' ? 
                    (producto.precio / 305).toFixed(2) : 
                    producto.precio;
                const unidad = producto.tipo_gestion === 'bobina' ? 'metros' : 'piezas';
                opcionesProductos += `<option value="${producto.id}" data-stock="${producto.stock}" data-tipo="${producto.tipo_gestion}" data-precio="${precioUnitario}">${producto.nombre} - $${precioUnitario}/${unidad} (Stock: ${producto.stock})</option>`;
            });
            
            // Mostrar modal para seleccionar producto del catálogo
            const modal = `
                <div class="modal fade" id="modalAgregarInsumo" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Agregar Insumo</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Seleccionar producto del catálogo:</label>
                                    <select class="form-select" id="productoOrigen" onchange="actualizarInfoProducto()">
                                        ${opcionesProductos}
                                    </select>
                                </div>
                                <div id="infoProducto" class="alert alert-info" style="display:none;">
                                    <i class="bi bi-info-circle"></i>
                                    <span id="infoProductoText"></span>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Cantidad a extraer:</label>
                                    <input type="number" class="form-control" id="cantidadExtraer" min="1" placeholder="Ej: 50 metros, 10 piezas">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Stock mínimo para alertas:</label>
                                    <input type="number" class="form-control" id="stockMinimo" min="0" placeholder="Ej: 20">
                                </div>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Nota:</strong> Los insumos se crean extrayendo cantidades de productos del catálogo. 
                                    Esto permite rastrear el consumo de materiales por proyecto y mantener control del stock.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="confirmarAgregarInsumo()">
                                    <i class="bi bi-check-circle"></i> Crear Insumo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Agregar modal al DOM
            document.body.insertAdjacentHTML('beforeend', modal);
            
            // Mostrar modal
            const modalElement = document.getElementById('modalAgregarInsumo');
            const bootstrapModal = new bootstrap.Modal(modalElement);
            bootstrapModal.show();
            
            // Limpiar modal al cerrar
            modalElement.addEventListener('hidden.bs.modal', function() {
                modalElement.remove();
            });
        }
        
        function actualizarInfoProducto() {
            const select = document.getElementById('productoOrigen');
            const infoDiv = document.getElementById('infoProducto');
            const infoText = document.getElementById('infoProductoText');
            
            if (select.value) {
                const option = select.options[select.selectedIndex];
                const stock = option.dataset.stock;
                const tipo = option.dataset.tipo;
                const precio = option.dataset.precio;
                const unidad = tipo === 'bobina' ? 'metros' : 'piezas';
                
                infoText.innerHTML = `<strong>Stock disponible:</strong> ${stock} ${unidad} | <strong>Precio unitario:</strong> $${precio}/${unidad} | <strong>Tipo:</strong> ${tipo}`;
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        }
        
        function confirmarAgregarInsumo() {
            const producto = document.getElementById('productoOrigen').value;
            const cantidad = document.getElementById('cantidadExtraer').value;
            const minimo = document.getElementById('stockMinimo').value;
            const unidad = document.getElementById('productoOrigen').selectedOptions[0]?.dataset.tipo === 'bobina' ? 'metros' : 'piezas';
            const ubicacion = '';
            
            if (!producto || !cantidad || !minimo) {
                Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Por favor completa todos los campos.' });
                return;
            }
            
            const select = document.getElementById('productoOrigen');
            const option = select.options[select.selectedIndex];
            const stockDisponible = parseFloat(option.dataset.stock);
            const cantidadExtraer = parseFloat(cantidad);
            
            if (cantidadExtraer > stockDisponible) {
                Swal.fire({ icon: 'error', title: 'Stock insuficiente', text: 'No hay suficiente stock disponible. Stock actual: ' + stockDisponible });
                return;
            }
            
            // Enviar datos al backend vía AJAX
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'agregar_insumo',
                    product_id: producto,
                    cantidad: cantidad,
                    minimo: minimo,
                    unidad: unidad,
                    ubicacion: ubicacion
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: '¡Insumo registrado!', text: data.message, timer: 1800, showConfirmButton: false });
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalAgregarInsumo'));
            modal.hide();
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo registrar el insumo. Intenta de nuevo.' });
            });
        }
        
        function editarInsumo(id) {
            // Buscar insumo real en la lista global
            const insumos = window.insumosData || [];
            const insumo = insumos.find(i => i.insumo_id == id);
            if (!insumo) {
                Swal.fire({ icon: 'error', title: 'Insumo no encontrado', text: 'No se pudo cargar la información del insumo.' });
                return;
            }
            // Modal de edición real
            const modal = `
                <div class="modal fade" id="modalEditarInsumo" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Insumo</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" value="${insumo.nombre}" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Cantidad (Solo visual)</label>
                                    <input type="number" class="form-control" value="${insumo.cantidad}" readonly style="background-color: #f8f9fa;">
                                    <small class="text-muted">La cantidad solo se modifica a través de movimientos</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Stock mínimo</label>
                                    <input type="number" class="form-control" id="editMinimo" value="${insumo.minimo}" min="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Unidad</label>
                                    <input type="text" class="form-control" id="editUnidad" value="${insumo.unidad}">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="guardarEdicionInsumo(${id})">
                                    <i class="bi bi-check-circle"></i> Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modal);
            const modalElement = document.getElementById('modalEditarInsumo');
            const bootstrapModal = new bootstrap.Modal(modalElement);
            bootstrapModal.show();
            modalElement.addEventListener('hidden.bs.modal', function() {
                modalElement.remove();
            });
        }
        
        function guardarEdicionInsumo(id) {
            const minimo = document.getElementById('editMinimo').value;
            const unidad = document.getElementById('editUnidad').value;
            if (minimo === '' || unidad === '') {
                Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Por favor completa todos los campos obligatorios.' });
                return;
            }
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'editar_insumo',
                    insumo_id: id,
                    minimo: minimo,
                    unidad: unidad
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: '¡Insumo actualizado correctamente!', text: 'Los cambios han sido guardados en la base de datos.', timer: 1800, showConfirmButton: false });
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarInsumo'));
                    modal.hide();
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo actualizar el insumo. Intenta de nuevo.' });
            });
        }
        
        function registrarMovimiento(id) {
            // Buscar insumo en la lista global
            const insumos = window.insumosData || [];
            const insumo = insumos.find(i => i.insumo_id == id);
            if (!insumo) {
                Swal.fire({ icon: 'error', title: 'Insumo no encontrado', text: 'No se pudo cargar la información del insumo.' });
                return;
            }
            
            // Modal para registrar movimiento
            const modal = `
                <div class="modal fade" id="modalRegistrarMovimiento" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-arrow-left-right"></i> Registrar Movimiento - ${insumo.nombre}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Stock actual del insumo:</strong> ${insumo.cantidad} ${insumo.unidad}
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tipo de movimiento</label>
                                    <select class="form-select" id="tipoMovimiento" onchange="actualizarInfoMovimiento()">
                                        <option value="entrada">Entrada (Desde inventario)</option>
                                        <option value="salida">Salida (Uso/Consumo)</option>
                                    </select>
                                </div>
                                <div id="infoStock" class="alert alert-warning" style="display:none;">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span id="infoStockText"></span>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Cantidad</label>
                                    <input type="number" class="form-control" id="cantidadMovimiento" min="0.01" step="0.01" placeholder="Ej: 10">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Motivo</label>
                                    <textarea class="form-control" id="motivoMovimiento" rows="3" placeholder="Ej: Uso en proyecto, Reabastecimiento, etc."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="confirmarMovimiento(${id})">
                                    <i class="bi bi-check-circle"></i> Registrar Movimiento
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modal);
            const modalElement = document.getElementById('modalRegistrarMovimiento');
            const bootstrapModal = new bootstrap.Modal(modalElement);
            bootstrapModal.show();
            modalElement.addEventListener('hidden.bs.modal', function() {
                modalElement.remove();
            });
        }
        
        function actualizarInfoMovimiento() {
            const tipo = document.getElementById('tipoMovimiento').value;
            const infoDiv = document.getElementById('infoStock');
            const infoText = document.getElementById('infoStockText');
            
            if (tipo === 'entrada') {
                infoText.innerHTML = '<strong>Nota:</strong> Las entradas se toman del stock del producto origen en el inventario principal.';
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        }
        
        function confirmarMovimiento(id) {
            const tipo = document.getElementById('tipoMovimiento').value;
            const cantidad = parseFloat(document.getElementById('cantidadMovimiento').value);
            const motivo = document.getElementById('motivoMovimiento').value;
            
            if (!cantidad || cantidad <= 0) {
                Swal.fire({ icon: 'warning', title: 'Cantidad inválida', text: 'Por favor ingresa una cantidad válida.' });
                return;
            }
            
            if (!motivo.trim()) {
                Swal.fire({ icon: 'warning', title: 'Motivo requerido', text: 'Por favor ingresa un motivo para el movimiento.' });
                return;
            }
            
            // Enviar datos al backend
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'registrar_movimiento',
                    insumo_id: id,
                    tipo_movimiento: tipo,
                    cantidad: cantidad,
                    motivo: motivo
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: '¡Movimiento registrado!', text: data.message, timer: 1800, showConfirmButton: false });
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalRegistrarMovimiento'));
                    modal.hide();
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo registrar el movimiento. Intenta de nuevo.' });
            });
        }
        
        function verReporteSemanal(id) {
            // Mostrar loading
            Swal.fire({
                title: 'Cargando reporte...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Obtener datos del reporte desde el backend
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'obtener_reporte',
                    insumo_id: id
                })
            })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                
                if (!data.success) {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    return;
                }
                
                const reporte = data.data;
                const insumo = reporte.insumo;
                const estadisticas = reporte.estadisticas;
                const movimientos = reporte.movimientos;
                
                // Generar tabla de movimientos
                let tablaMovimientos = '';
                if (movimientos.length > 0) {
                    movimientos.forEach(mov => {
                        const fecha = new Date(mov.fecha_movimiento).toLocaleDateString('es-ES');
                        const tipo = mov.tipo_movimiento === 'entrada' ? 'Entrada' : 'Salida';
                        const clase = mov.tipo_movimiento === 'entrada' ? 'success' : 'danger';
                        const motivo = mov.motivo || 'Sin motivo especificado';
                        tablaMovimientos += `
                            <tr>
                                <td>${fecha}</td>
                                <td><span class="badge bg-${clase}">${tipo}</span></td>
                                <td>${mov.cantidad} ${insumo.unidad}</td>
                                <td>${motivo}</td>
                                <td>${mov.usuario || 'Sistema'}</td>
                            </tr>
                        `;
                    });
                } else {
                    tablaMovimientos = '<tr><td colspan="5" class="text-center">No hay movimientos registrados</td></tr>';
                }
                
                // Generar reporte dinámico
                const modal = `
                    <div class="modal fade" id="modalReporteSemanal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="bi bi-graph-up"></i> Reporte de Movimientos - ${insumo.nombre}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <h3 class="text-primary">${estadisticas.stock_actual}</h3>
                                                    <p class="text-muted">Stock Actual</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <h3 class="text-success">${estadisticas.total_entradas}</h3>
                                                    <p class="text-muted">Total Entradas</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <h3 class="text-danger">${estadisticas.total_salidas}</h3>
                                                    <p class="text-muted">Total Salidas</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <h3 class="text-warning">${estadisticas.consumo_semanal.toFixed(1)}</h3>
                                                    <p class="text-muted">Consumo Sem.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h6>Historial de Movimientos (Últimas 4 semanas)</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha</th>
                                                        <th>Tipo</th>
                                                        <th>Cantidad</th>
                                                        <th>Motivo</th>
                                                        <th>Usuario</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${tablaMovimientos}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle"></i>
                                            <strong>Información del insumo:</strong><br>
                                            <strong>Producto origen:</strong> ${insumo.producto_origen || 'N/A'}<br>
                                            <strong>Stock mínimo:</strong> ${insumo.minimo} ${insumo.unidad}<br>
                                            <strong>Precio unitario:</strong> $${insumo.precio_unitario}/${insumo.unidad}<br>
                                            <strong>Estado:</strong> <span class="badge bg-${insumo.estado === 'disponible' ? 'success' : insumo.estado === 'bajo_stock' ? 'warning' : 'danger'}">${insumo.estado}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <button type="button" class="btn btn-primary">
                                        <i class="bi bi-download"></i> Exportar PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Agregar modal al DOM
                document.body.insertAdjacentHTML('beforeend', modal);
                
                // Mostrar modal
                const modalElement = document.getElementById('modalReporteSemanal');
                const bootstrapModal = new bootstrap.Modal(modalElement);
                bootstrapModal.show();
                
                // Limpiar modal al cerrar
                modalElement.addEventListener('hidden.bs.modal', function() {
                    modalElement.remove();
                });
            })
            .catch(() => {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el reporte. Intenta de nuevo.' });
            });
        }
    </script>
</body>
</html> 