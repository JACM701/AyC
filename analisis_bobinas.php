<?php
require_once 'connection.php';

echo "=== ANÁLISIS DEL PROBLEMA DE BOBINAS ===\n";

// Verificar el cable SAXXON
$query = "SELECT p.*, 
          COALESCE(SUM(b.metros_actuales), 0) as metros_totales,
          COUNT(b.bobina_id) as total_bobinas
          FROM products p
          LEFT JOIN bobinas b ON p.product_id = b.product_id AND b.is_active = 1
          WHERE p.sku = 'SAXXON OUTP5ECCAEXT'
          GROUP BY p.product_id";
          
$result = $mysqli->query($query);
$producto = $result->fetch_assoc();

echo "PRODUCTO: " . $producto['product_name'] . "\n";
echo "Tipo gestión: " . $producto['tipo_gestion'] . "\n";
echo "Precio en products: $" . $producto['price'] . "\n";
echo "Total bobinas: " . $producto['total_bobinas'] . "\n";
echo "Metros totales: " . $producto['metros_totales'] . "\n";

echo "\n=== CÁLCULOS ACTUALES (INCORRECTOS) ===\n";
echo "Metros × Precio/metro: " . $producto['metros_totales'] . " × $" . $producto['price'] . " = $" . ($producto['metros_totales'] * $producto['price']) . "\n";

echo "\n=== CÁLCULOS CORRECTOS (POR BOBINAS) ===\n";
echo "Bobinas × Precio/bobina: " . $producto['total_bobinas'] . " × $" . $producto['price'] . " = $" . ($producto['total_bobinas'] * $producto['price']) . "\n";

echo "\n=== BOBINAS INDIVIDUALES ===\n";
$bobinas_query = "SELECT bobina_id, metros_actuales, metros_iniciales 
                  FROM bobinas 
                  WHERE product_id = " . $producto['product_id'] . " 
                  AND is_active = 1 
                  ORDER BY bobina_id";
$bobinas_result = $mysqli->query($bobinas_query);

$total_metros_verificacion = 0;
while ($bobina = $bobinas_result->fetch_assoc()) {
    echo "Bobina " . $bobina['bobina_id'] . ": " . $bobina['metros_actuales'] . " metros actuales";
    if (isset($bobina['metros_iniciales'])) {
        echo " (de " . $bobina['metros_iniciales'] . " iniciales)";
    }
    echo "\n";
    $total_metros_verificacion += $bobina['metros_actuales'];
}

echo "\nVerificación metros: $total_metros_verificacion = $" . $producto['metros_totales'] . " ✓\n";

echo "\n=== RECOMENDACIÓN ===\n";
echo "El precio debe interpretarse como:\n";
echo "- Para inventario: Total bobinas × Precio por bobina\n";
echo "- Para ventas: Se puede vender por metros desde las bobinas\n";
echo "- El valor total del inventario debe ser: " . $producto['total_bobinas'] . " bobinas × $" . $producto['price'] . " = $" . ($producto['total_bobinas'] * $producto['price']) . "\n";
?>
