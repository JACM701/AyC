<?php
require_once 'connection.php';

echo "=== VERIFICANDO PRODUCTOS ===\n";

$stmt = $mysqli->prepare("
    SELECT product_id, product_name, description, tipo_gestion 
    FROM products 
    WHERE product_name LIKE '%cable%' 
       OR description LIKE '%cable%'
       OR product_name LIKE '%utp%' 
       OR description LIKE '%utp%'
       OR product_name LIKE '%saxxon%' 
       OR description LIKE '%saxxon%'
    LIMIT 20
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Nueva lógica corregida
    $tipo_gestion = $row['tipo_gestion'] ?? '';
    $es_cable_nuevo = ($tipo_gestion === 'bobina') || 
                     (stripos($row['product_name'] ?? '', 'bobina') !== false) ||
                     (stripos($row['product_name'] ?? '', 'cable utp') !== false) ||
                     (stripos($row['product_name'] ?? '', 'saxxon out') !== false);
    
    // Lógica anterior (problemática)
    $es_cable_anterior = stripos($row['product_name'] ?? '', 'cable') !== false || 
                        stripos($row['product_name'] ?? '', 'utp') !== false ||
                        stripos($row['product_name'] ?? '', 'saxxon') !== false ||
                        stripos($row['description'] ?? '', 'cable') !== false;
    
    echo "ID: " . $row['product_id'] . "\n";
    echo "Nombre: " . $row['product_name'] . "\n";
    echo "Descripción: " . ($row['description'] ?? 'NULL') . "\n";
    echo "Tipo gestión: " . ($row['tipo_gestion'] ?? 'NULL') . "\n";
    echo "Detectado como cable (ANTERIOR): " . ($es_cable_anterior ? 'SÍ' : 'NO') . "\n";
    echo "Detectado como cable (NUEVO): " . ($es_cable_nuevo ? 'SÍ' : 'NO') . "\n";
    echo "---\n";
}

echo "\n=== PRODUCTOS CON TIPO_GESTION = 'bobina' ===\n";
$stmt2 = $mysqli->prepare("SELECT product_id, product_name, description, tipo_gestion FROM products WHERE tipo_gestion = 'bobina' LIMIT 10");
$stmt2->execute();
$result2 = $stmt2->get_result();

while ($row = $result2->fetch_assoc()) {
    echo "ID: " . $row['product_id'] . " | Nombre: " . $row['product_name'] . " | Tipo: " . $row['tipo_gestion'] . "\n";
}
?>
