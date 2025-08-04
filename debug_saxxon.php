<?php
require_once 'connection.php';

// Buscar el producto SAXXON específico
$query = "SELECT p.product_id, p.product_name, p.cost_price, p.tipo_gestion, 
                 cp.cantidad, cp.precio_unitario, cp.precio_total, cp.cotizacion_id
          FROM products p 
          LEFT JOIN cotizaciones_productos cp ON p.product_id = cp.product_id
          WHERE p.product_name LIKE '%SAXXON%' AND p.product_name LIKE '%OUTP5ECCAEXT%'
          ORDER BY cp.cotizacion_id DESC
          LIMIT 3";

$result = $mysqli->query($query);

echo "<h3>Producto SAXXON OUTP5ECCAEXT:</h3>";
while($row = $result->fetch_assoc()) {
    echo "<strong>Cotización ID:</strong> " . ($row['cotizacion_id'] ?? 'N/A') . "<br>";
    echo "<strong>Producto:</strong> " . $row['product_name'] . "<br>";
    echo "<strong>Cost Price en BD:</strong> $" . number_format($row['cost_price'], 2) . "<br>";
    echo "<strong>Tipo gestión:</strong> " . ($row['tipo_gestion'] ?? 'N/A') . "<br>";
    if ($row['cotizacion_id']) {
        echo "<strong>Cantidad en cotización:</strong> " . $row['cantidad'] . "<br>";
        echo "<strong>Precio unitario:</strong> $" . number_format($row['precio_unitario'], 2) . "<br>";
        echo "<strong>Precio total:</strong> $" . number_format($row['precio_total'], 2) . "<br>";
        
        // Calcular como lo está haciendo el código actual
        $costo_calculado = $row['cost_price'] * $row['cantidad'];
        echo "<strong>Costo calculado actual:</strong> $" . number_format($costo_calculado, 2) . "<br>";
        
        // ¿Cuál debería ser el cost_price correcto?
        $cost_price_correcto = 983.63 / ($row['cantidad'] / 305); // 915 metros = 3 bobinas
        echo "<strong>Cost price que debería ser:</strong> $" . number_format($cost_price_correcto, 2) . " por metro<br>";
    }
    echo "<hr>";
}

// También ver todos los productos de cable para comparar
echo "<h3>Otros productos de cable:</h3>";
$query2 = "SELECT product_name, cost_price, tipo_gestion FROM products 
           WHERE product_name LIKE '%cable%' OR product_name LIKE '%utp%' 
           ORDER BY cost_price DESC LIMIT 5";
$result2 = $mysqli->query($query2);

while($row = $result2->fetch_assoc()) {
    echo $row['product_name'] . " - Cost: $" . number_format($row['cost_price'], 2) . " - Tipo: " . ($row['tipo_gestion'] ?? 'N/A') . "<br>";
}
?>
