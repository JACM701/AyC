<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['product_id']) || !isset($input['is_active'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros faltantes']);
    exit;
}

$product_id = intval($input['product_id']);
$is_active = intval($input['is_active']);

// Validar que is_active sea 0, 1 o 2 (activo, inactivo, descontinuado)
if (!in_array($is_active, [0, 1, 2])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valor de estado inválido']);
    exit;
}

try {
    // Verificar que el producto existe
    $stmt = $mysqli->prepare("SELECT product_id FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit;
    }
    $stmt->close();
    
    // Actualizar el estado del producto
    $stmt = $mysqli->prepare("UPDATE products SET is_active = ?, updated_at = NOW() WHERE product_id = ?");
    $stmt->bind_param("ii", $is_active, $product_id);
    
    if ($stmt->execute()) {
        $action = $is_active == 1 ? 'activado' : ($is_active == 2 ? 'marcado como descontinuado' : 'desactivado');
        echo json_encode([
            'success' => true, 
            'message' => "Producto $action exitosamente",
            'is_active' => $is_active
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el producto']);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error en toggle_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
