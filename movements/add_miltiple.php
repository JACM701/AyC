<?php
require_once '../auth/middleware.php';
require_once '../connection.php';
require_once '../includes/bobina_helpers.php';
header('Content-Type: application/json');

// Leer el JSON enviado
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

$errores = [];
$exitos = 0;
$detalles = [];

$mysqli->begin_transaction();
try {
    foreach ($data as $idx => $mov) {
        $product_id = isset($mov['producto']) ? intval($mov['producto']) : 0;
        $movement_type_id = isset($mov['tipo']) ? intval($mov['tipo']) : 0;
        $cantidad = isset($mov['cantidad']) ? floatval($mov['cantidad']) : 0;
        $bobina_id = isset($mov['bobina']) && $mov['bobina'] !== '' ? intval($mov['bobina']) : null;

        if (!$product_id || !$movement_type_id || $cantidad <= 0) {
            $errores[] = "Fila ".($idx+1).": Datos incompletos.";
            continue;
        }

        // Obtener tipo de gestión del producto
        $stmt = $mysqli->prepare("SELECT tipo_gestion FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $prod = $res->fetch_assoc();
        $stmt->close();
        if (!$prod) {
            $errores[] = "Fila ".($idx+1).": Producto no encontrado.";
            continue;
        }
        $tipo_gestion = $prod['tipo_gestion'];

        // Obtener tipo de movimiento (entrada/salida)
        $stmt = $mysqli->prepare("SELECT is_entry, name FROM movement_types WHERE movement_type_id = ?");
        $stmt->bind_param("i", $movement_type_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $movtype = $res->fetch_assoc();
        $stmt->close();
        if (!$movtype) {
            $errores[] = "Fila ".($idx+1).": Tipo de movimiento no válido.";
            continue;
        }
        $is_entrada = $movtype['is_entry'] == 1;

        if ($tipo_gestion === 'bobina') {
            if (!$bobina_id) {
                $errores[] = "Fila ".($idx+1).": Selecciona una bobina.";
                continue;
            }
            // Verificar metros disponibles
            $stmt = $mysqli->prepare("SELECT metros_actuales FROM bobinas WHERE bobina_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $bobina_id, $product_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $bobina = $res->fetch_assoc();
            $stmt->close();
            if (!$bobina) {
                $errores[] = "Fila ".($idx+1).": Bobina no encontrada.";
                continue;
            }
            if (!$is_entrada && $bobina['metros_actuales'] < $cantidad) {
                $errores[] = "Fila ".($idx+1).": No hay suficientes metros en la bobina (disponible: {$bobina['metros_actuales']}m).";
                continue;
            }
            // Registrar movimiento y actualizar bobina
            $movement_quantity = $is_entrada ? $cantidad : -$cantidad;
            $stmt = $mysqli->prepare("INSERT INTO movements (product_id, movement_type_id, quantity, movement_date, bobina_id) VALUES (?, ?, ?, NOW(), ?)");
            $stmt->bind_param("iidi", $product_id, $movement_type_id, $movement_quantity, $bobina_id);
            $stmt->execute();
            $stmt->close();
            // Actualizar metros en la bobina
            if ($is_entrada) {
                $stmt = $mysqli->prepare("UPDATE bobinas SET metros_actuales = metros_actuales + ? WHERE bobina_id = ?");
            } else {
                $stmt = $mysqli->prepare("UPDATE bobinas SET metros_actuales = metros_actuales - ? WHERE bobina_id = ?");
            }
            $stmt->bind_param("di", $cantidad, $bobina_id);
            $stmt->execute();
            $stmt->close();
            // Actualizar stock del producto (suma de todas las bobinas)
            actualizarStockBobina($mysqli, $product_id);
            $exitos++;
        } else {
            // Producto normal
            $movement_quantity = $is_entrada ? $cantidad : -$cantidad;
            $stmt = $mysqli->prepare("INSERT INTO movements (product_id, movement_type_id, quantity, movement_date) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iid", $product_id, $movement_type_id, $movement_quantity);
            $stmt->execute();
            $stmt->close();
            // Actualizar stock del producto
            $stock_change = $is_entrada ? $cantidad : -$cantidad;
            $stmt = $mysqli->prepare("UPDATE products SET quantity = quantity + ? WHERE product_id = ?");
            $stmt->bind_param("di", $stock_change, $product_id);
            $stmt->execute();
            $stmt->close();
            $exitos++;
        }
    }
    if (count($errores) > 0 && $exitos === 0) {
        $mysqli->rollback();
        echo json_encode(['error' => 'No se registró ningún movimiento.', 'detalles' => $errores]);
        exit;
    } else {
        $mysqli->commit();
        $msg = $exitos . ' movimiento(s) registrado(s) correctamente.';
        if (count($errores) > 0) {
            echo json_encode(['success' => $msg, 'detalles' => $errores]);
        } else {
            echo json_encode(['success' => $msg]);
        }
        exit;
    }
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    exit;
}