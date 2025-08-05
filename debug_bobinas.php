<?php
require_once 'connection.php';

echo "=== ESTRUCTURA TABLA BOBINAS ===\n";
$result = $mysqli->query('DESCRIBE bobinas');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== DATOS BOBINAS DEL CABLE ===\n";
$bobinas_query = "SELECT * FROM bobinas WHERE product_id = (SELECT product_id FROM products WHERE sku = 'SAXXON OUTP5ECCAEXT') AND is_active = 1";
$bobinas_result = $mysqli->query($bobinas_query);

while ($bobina = $bobinas_result->fetch_assoc()) {
    echo "Bobina ID: " . $bobina['bobina_id'] . "\n";
    echo "  Metros actuales: " . $bobina['metros_actuales'] . "\n";
    echo "  Metros iniciales: " . ($bobina['metros_iniciales'] ?? 'N/A') . "\n";
    echo "  Precio compra: $" . ($bobina['precio_compra'] ?? 'N/A') . "\n";
    echo "  Fecha: " . ($bobina['fecha_ingreso'] ?? 'N/A') . "\n";
    echo "  Estado: " . ($bobina['is_active'] ? 'Activa' : 'Inactiva') . "\n";
    echo "---\n";
}

echo "\n=== PRODUCTO EN TABLA PRODUCTS ===\n";
$product_query = "SELECT * FROM products WHERE sku = 'SAXXON OUTP5ECCAEXT'";
$product_result = $mysqli->query($product_query);
$product = $product_result->fetch_assoc();

echo "Product ID: " . $product['product_id'] . "\n";
echo "Precio en products: $" . $product['price'] . "\n";
echo "Cost price: $" . ($product['cost_price'] ?? 'N/A') . "\n";
echo "Quantity: " . $product['quantity'] . "\n";
echo "Tipo gestión: " . $product['tipo_gestion'] . "\n";
?>
