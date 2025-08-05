<?php
/**
 * Script para actualizar estadísticas de productos tipo bobina
 * Este script corrige el conteo de inventario para productos que se manejan por bobinas
 */

require_once 'connection.php';

echo "=== ACTUALIZANDO ESTADÍSTICAS DE PRODUCTOS TIPO BOBINA ===\n\n";

// 1. Verificar productos que son cables pero no están marcados como bobina
echo "1. Verificando productos que deberían ser tipo bobina...\n";

$check_cables_query = "
    SELECT product_id, product_name, tipo_gestion 
    FROM products 
    WHERE (
        product_name LIKE '%cable%' OR 
        product_name LIKE '%bobina%' OR
        description LIKE '%cable%' OR
        description LIKE '%bobina%'
    ) AND (tipo_gestion != 'bobina' OR tipo_gestion IS NULL)
";

$result = $mysqli->query($check_cables_query);
$productos_a_actualizar = [];

while ($row = $result->fetch_assoc()) {
    $productos_a_actualizar[] = $row;
    echo "   - {$row['product_name']} (ID: {$row['product_id']}) - Tipo actual: " . ($row['tipo_gestion'] ?? 'NULL') . "\n";
}

if (count($productos_a_actualizar) > 0) {
    echo "\n¿Desea marcar estos productos como tipo 'bobina'? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($confirmation) === 'y') {
        echo "Actualizando productos a tipo bobina...\n";
        foreach ($productos_a_actualizar as $producto) {
            $stmt = $mysqli->prepare("UPDATE products SET tipo_gestion = 'bobina' WHERE product_id = ?");
            $stmt->bind_param("i", $producto['product_id']);
            $stmt->execute();
            echo "   ✓ {$producto['product_name']} actualizado\n";
        }
    }
}

// 2. Actualizar stock de productos tipo bobina basado en bobinas registradas
echo "\n2. Actualizando stock de productos tipo bobina...\n";

$bobina_products_query = "
    SELECT DISTINCT p.product_id, p.product_name 
    FROM products p 
    WHERE p.tipo_gestion = 'bobina'
";

$result = $mysqli->query($bobina_products_query);
$productos_bobina = [];

while ($row = $result->fetch_assoc()) {
    $productos_bobina[] = $row;
}

foreach ($productos_bobina as $producto) {
    // Verificar si tiene bobinas registradas
    $bobinas_query = "
        SELECT COUNT(*) as total_bobinas, 
               COALESCE(SUM(metros_actuales), 0) as metros_totales
        FROM bobinas 
        WHERE product_id = ? AND is_active = 1
    ";
    
    $stmt = $mysqli->prepare($bobinas_query);
    $stmt->bind_param("i", $producto['product_id']);
    $stmt->execute();
    $bobina_result = $stmt->get_result();
    $bobina_data = $bobina_result->fetch_assoc();
    
    if ($bobina_data['total_bobinas'] > 0) {
        // Actualizar stock con metros totales
        $update_stock = "UPDATE products SET quantity = ? WHERE product_id = ?";
        $stmt = $mysqli->prepare($update_stock);
        $stmt->bind_param("di", $bobina_data['metros_totales'], $producto['product_id']);
        $stmt->execute();
        
        echo "   ✓ {$producto['product_name']}: {$bobina_data['metros_totales']} metros ({$bobina_data['total_bobinas']} bobinas)\n";
    } else {
        echo "   ⚠ {$producto['product_name']}: Sin bobinas registradas\n";
    }
}

// 3. Resumen final
echo "\n3. Generando resumen de productos tipo bobina...\n";

$summary_query = "
    SELECT 
        COUNT(*) as total_productos_bobina,
        SUM(CASE WHEN quantity > 100 THEN 1 ELSE 0 END) as con_stock_alto,
        SUM(CASE WHEN quantity > 0 AND quantity <= 100 THEN 1 ELSE 0 END) as con_stock_bajo,
        SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as sin_stock,
        ROUND(SUM(quantity), 2) as metros_totales
    FROM products 
    WHERE tipo_gestion = 'bobina'
";

$result = $mysqli->query($summary_query);
$summary = $result->fetch_assoc();

echo "\n=== RESUMEN ===\n";
echo "Total productos tipo bobina: {$summary['total_productos_bobina']}\n";
echo "Con stock alto (>100m): {$summary['con_stock_alto']}\n";
echo "Con stock bajo (≤100m): {$summary['con_stock_bajo']}\n";
echo "Sin stock: {$summary['sin_stock']}\n";
echo "Metros totales en inventario: {$summary['metros_totales']} m\n";

echo "\n✅ Actualización completada!\n";
?>
