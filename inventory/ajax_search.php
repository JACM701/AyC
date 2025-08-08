<?php
require_once '../connection.php';

$categoria_filtro = $_GET['categoria'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';
$estado_filtro = $_GET['estado'] ?? '';
$activo_filtro = $_GET['activo'] ?? '1';

$query = "SELECT p.*, c.name as categoria, s.name as proveedor,
          COALESCE(SUM(b.metros_actuales), 0) as metros_totales,
          COUNT(b.bobina_id) as total_bobinas
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
          LEFT JOIN bobinas b ON p.product_id = b.product_id AND b.is_active = 1
          WHERE ";
$query .= ($activo_filtro === '' ? '1=1' : ($activo_filtro === '1' ? 'p.is_active = 1 OR p.is_active IS NULL' : ($activo_filtro === '2' ? 'p.is_active = 2' : 'p.is_active = 0')));
if ($categoria_filtro) {
    $query .= " AND c.category_id = '" . intval($categoria_filtro) . "'";
}
if ($busqueda) {
    $busqueda = $mysqli->real_escape_string($busqueda);
    $query .= " AND (p.product_name LIKE '%$busqueda%' OR p.sku LIKE '%$busqueda%' OR p.description LIKE '%$busqueda%')";
}
if ($estado_filtro === 'disponible') {
    $query .= " AND p.quantity > 10";
} elseif ($estado_filtro === 'bajo_stock') {
    $query .= " AND p.quantity > 0 AND p.quantity <= 10";
} elseif ($estado_filtro === 'agotado') {
    $query .= " AND p.quantity = 0";
}
$query .= " GROUP BY p.product_id ORDER BY p.product_name ASC LIMIT 50";

$result = $mysqli->query($query);
$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}
header('Content-Type: application/json');
echo json_encode($productos);
