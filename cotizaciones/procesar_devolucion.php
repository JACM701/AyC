<?php
require_once '../auth/middleware.php';
require_once '../connection.php';
require_once 'helpers.php'; // Asegúrate de tener esto arriba
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
            // 1. Obtener cantidad vendida en la cotización
            $stmt = $mysqli->prepare("SELECT cantidad FROM cotizaciones_productos WHERE cotizacion_id = ? AND product_id = ?");
            $stmt->bind_param('ii', $cotizacion_id, $product_id);
            $stmt->execute();
            $stmt->bind_result($cantidad_vendida);
            $stmt->fetch();
            $stmt->close();
            if (!$cantidad_vendida) $cantidad_vendida = 0;

            // 2. Obtener cantidad ya devuelta para este producto y cotización
            $stmt = $mysqli->prepare("SELECT SUM(quantity) FROM movements WHERE product_id = ? AND reference = ? AND movement_type_id = 7");
            $referencia = "Devolución por cotización #$cotizacion_id";
            $stmt->bind_param('is', $product_id, $referencia);
            $stmt->execute();
            $stmt->bind_result($cantidad_devuelta);
            $stmt->fetch();
            $stmt->close();
            if (!$cantidad_devuelta) $cantidad_devuelta = 0;

            // 3. Calcular máximo a devolver
            $max_devolver = $cantidad_vendida - $cantidad_devuelta;
            if ($cantidad > $max_devolver) {
                $cantidad = $max_devolver;
            }
            if ($cantidad <= 0) continue; // No devolver más de lo permitido

            // 4. Sumar al stock
            $stmt = $mysqli->prepare("UPDATE products SET quantity = quantity + ? WHERE product_id = ?");
            $stmt->bind_param('di', $cantidad, $product_id);
            $stmt->execute();
            $stmt->close();

            // 5. Registrar movimiento de devolución
            $stmt = $mysqli->prepare("
                INSERT INTO movements (product_id, movement_type_id, quantity, movement_date, reference, notes, user_id)
                VALUES (?, 7, ?, NOW(), ?, ?, ?)
            ");
            $notas = "Devolución de producto tras venta";
            $stmt->bind_param('idssi', $product_id, $cantidad, $referencia, $notas, $user_id);
            $stmt->execute();
            $stmt->close();

            registrarAccionCotizacion(
                $cotizacion_id,
                'Devolución',
                "Se devolvieron $cantidad unidades del producto ID $product_id",
                $user_id,
                $mysqli
            );
        }
    }
    $mysqli->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al procesar la devolución: ' . $e->getMessage()]);
}