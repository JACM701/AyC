<?php
require_once 'connection.php';

echo "=== ANÁLISIS DETALLADO DEL CABLE SAXXON ===\n";

// Obtener información del producto
$product_query = "SELECT * FROM products WHERE sku = 'SAXXON OUTP5ECCAEXT'";
$product = $mysqli->query($product_query)->fetch_assoc();

echo "Producto: " . $product['product_name'] . "\n";
echo "SKU: " . $product['sku'] . "\n";
echo "Precio unitario: $" . $product['price'] . "\n\n";

// Obtener todas las bobinas de este producto
$bobinas_query = "SELECT bobina_id, metros_actuales, metros_iniciales, is_active, fecha_ingreso 
                  FROM bobinas 
                  WHERE product_id = " . $product['product_id'] . "
                  ORDER BY bobina_id";
$bobinas_result = $mysqli->query($bobinas_query);

echo "=== BOBINAS REGISTRADAS ===\n";
$total_metros_activos = 0;
$total_bobinas_activas = 0;
$total_metros_todos = 0;
$total_bobinas_todas = 0;

while ($bobina = $bobinas_result->fetch_assoc()) {
    $estado = $bobina['is_active'] ? 'ACTIVA' : 'INACTIVA';
    echo "Bobina ID: " . $bobina['bobina_id'] . "\n";
    echo "  Estado: " . $estado . "\n";
    echo "  Metros actuales: " . $bobina['metros_actuales'] . "\n";
    echo "  Metros iniciales: " . ($bobina['metros_iniciales'] ?? 'N/A') . "\n";
    echo "  Fecha ingreso: " . ($bobina['fecha_ingreso'] ?? 'N/A') . "\n";
    
    if ($bobina['is_active']) {
        $total_metros_activos += $bobina['metros_actuales'];
        $total_bobinas_activas++;
    }
    
    $total_metros_todos += $bobina['metros_actuales'];
    $total_bobinas_todas++;
    echo "  ---\n";
}

echo "\n=== RESUMEN ===\n";
echo "Total bobinas activas: $total_bobinas_activas\n";
echo "Total metros activos: $total_metros_activos\n";
echo "Total bobinas (todas): $total_bobinas_todas\n";
echo "Total metros (todos): $total_metros_todos\n";

echo "\n=== CÁLCULOS ===\n";
if ($total_metros_activos > 0) {
    $bobinas_equivalentes = $total_metros_activos / 305;
    echo "Bobinas equivalentes (metros/305): " . round($bobinas_equivalentes, 2) . "\n";
}

echo "\n=== VALORES CALCULADOS ===\n";
echo "Valor por bobinas físicas: $total_bobinas_activas × $" . $product['price'] . " = $" . ($total_bobinas_activas * $product['price']) . "\n";
echo "Valor por metros: $total_metros_activos × $" . $product['price'] . " = $" . ($total_metros_activos * $product['price']) . "\n";

if ($total_metros_activos > 0) {
    $precio_por_metro_real = ($total_bobinas_activas * $product['price']) / $total_metros_activos;
    echo "Precio real por metro: $" . round($precio_por_metro_real, 2) . "\n";
}

echo "\n=== RECOMENDACIÓN ===\n";
if ($total_metros_activos / 305 > $total_bobinas_activas) {
    echo "⚠️  PROBLEMA DETECTADO: Los metros totales (" . $total_metros_activos . ") indican más bobinas equivalentes (" . round($total_metros_activos/305, 2) . ") que las bobinas físicas registradas (" . $total_bobinas_activas . ").\n";
    echo "Esto sugiere que:\n";
    echo "1. Faltan bobinas por registrar en la base de datos\n";
    echo "2. O hay bobinas parciales que no se están contando correctamente\n";
    echo "3. O el precio unitario debería ser por metro, no por bobina\n";
} else {
    echo "✅ Los datos están consistentes\n";
}
?>
