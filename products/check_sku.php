<?php
require_once '../connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sku'])) {
    $sku = trim($_POST['sku']);
    
    if (empty($sku)) {
        echo json_encode(['exists' => false]);
        exit;
    }
    
    $stmt = $mysqli->prepare("SELECT product_id, product_name FROM products WHERE sku = ?");
    $stmt->bind_param("s", $sku);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        echo json_encode([
            'exists' => true,
            'product_name' => $product['product_name']
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['error' => 'Solicitud inválida']);
}

$mysqli->close();
?> 