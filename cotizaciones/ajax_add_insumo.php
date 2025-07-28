<?php
// filepath: c:\xampp\htdocs\inventory-management-system-main\cotizaciones\ajax_add_insumo.php
require_once '../auth/middleware.php';
require_once '../connection.php';

$nombre = trim($_POST['nombre'] ?? '');
$unidad = trim($_POST['unidad'] ?? '');
$proveedor = $_POST['proveedor'] ?? null;
$cantidad = intval($_POST['cantidad'] ?? 1);
$precio = floatval($_POST['precio'] ?? 0);

if (!$nombre || !$unidad || !$cantidad || !$precio) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
    exit;
}

$stmt = $mysqli->prepare("INSERT INTO insumos (nombre, unidad, proveedor, cantidad, precio_unitario) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('sssdd', $nombre, $unidad, $proveedor, $cantidad, $precio);
$stmt->execute();
$insumo_id = $stmt->insert_id;
$stmt->close();

echo json_encode([
    'success' => true,
    'insumo' => [
        'insumo_id' => $insumo_id,
        'nombre' => $nombre,
        'unidad' => $unidad,
        'proveedor' => $proveedor,
        'cantidad' => $cantidad,
        'precio' => $precio
    ]
]);