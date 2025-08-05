<?php
require_once 'connection.php';

$query = "SELECT p.*, 
          COALESCE(SUM(b.metros_actuales), 0) as metros_totales,
          COUNT(b.bobina_id) as total_bobinas
          FROM products p
          LEFT JOIN bobinas b ON p.product_id = b.product_id AND b.is_active = 1
          WHERE p.sku = 'SAXXON OUTP5ECCAEXT'
          GROUP BY p.product_id";
          
$result = $mysqli->query($query);

if ($producto = $result->fetch_assoc()) {
    echo "=== DATOS DEL CABLE SAXXON ===\n";
    echo "SKU: " . $producto['sku'] . "\n";
    echo "Nombre: " . $producto['product_name'] . "\n";
    echo "Tipo gestión: " . $producto['tipo_gestion'] . "\n";
    echo "Precio: $" . $producto['price'] . "\n";
    echo "Quantity: " . $producto['quantity'] . "\n";
    echo "Metros totales: " . $producto['metros_totales'] . "\n";
    echo "Total bobinas: " . $producto['total_bobinas'] . "\n";
    echo "Valor calculado actual: $" . ($producto['metros_totales'] * $producto['price']) . "\n";
    echo "\n=== ANÁLISIS ===\n";
    echo "El problema está en que:\n";
    echo "- Metros totales: " . $producto['metros_totales'] . "\n";
    echo "- Precio por metro: $" . $producto['price'] . "\n";
    echo "- Si el precio es correcto, el valor total debería ser razonable\n";
    echo "- Si el precio está mal, necesita corrección\n";
    
    // Verificar bobinas individuales
    echo "\n=== BOBINAS INDIVIDUALES ===\n";
    $bobinas_query = "SELECT * FROM bobinas WHERE product_id = " . $producto['product_id'] . " AND is_active = 1";
    $bobinas_result = $mysqli->query($bobinas_query);
    
    while ($bobina = $bobinas_result->fetch_assoc()) {
        echo "Bobina ID: " . $bobina['bobina_id'] . 
             " - Metros: " . $bobina['metros_actuales'] . 
             " / " . $bobina['metros_totales'] . 
             " - Precio unitario: $" . $bobina['precio_unitario'] . "\n";
    }
    
} else {
    echo "Producto no encontrado\n";
}
?>
