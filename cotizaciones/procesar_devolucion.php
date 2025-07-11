<?php
require_once '../auth/middleware.php';
require_once '../connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$cotizacion_id = intval($_POST['cotizacion_id'] ?? 0);
$devolver = $_POST['devolver'] ?? [];
$user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

if (!$cotizacion_id || !$devolver || !is_array($devolver)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}

$mysqli->begin_transaction();
try {
    foreach ($devolver as $product_id => $cantidad) {
        $product_id = intval($product_id);
        $cantidad = floatval($cantidad);
        if ($product_id && $cantidad > 0) {
            // Sumar al stock
            $stmt = $mysqli->prepare("UPDATE products SET quantity = quantity + ? WHERE product_id = ?");
            $stmt->bind_param('di', $cantidad, $product_id);
            $stmt->execute();
            $stmt->close();
            // Registrar movimiento de devolución
            $stmt = $mysqli->prepare("
                INSERT INTO movements (product_id, movement_type_id, quantity, movement_date, reference, notes, user_id)
                VALUES (?, 7, ?, NOW(), ?, ?, ?)
            ");
            $referencia = "Devolución por cotización #$cotizacion_id";
            $notas = "Devolución de producto tras venta";
            $stmt->bind_param('idssi', $product_id, $cantidad, $referencia, $notas, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    $mysqli->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al procesar la devolución: ' . $e->getMessage()]);
} 