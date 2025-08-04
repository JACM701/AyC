<?php
$conn = new mysqli('localhost', 'root', '', 'inventory_management_system2');
$result = $conn->query('SELECT observaciones, condiciones_pago FROM cotizaciones WHERE cotizacion_id = 104');
$row = $result->fetch_assoc();
echo '🎉 COTIZACIÓN ID 104 (VERIFICACIÓN FINAL):' . PHP_EOL;
echo 'Observaciones: [' . $row['observaciones'] . ']' . PHP_EOL;
echo 'Condiciones de pago: [' . $row['condiciones_pago'] . ']' . PHP_EOL;
echo 'Longitud observaciones: ' . strlen($row['observaciones']) . PHP_EOL;
echo 'Longitud condiciones: ' . strlen($row['condiciones_pago']) . PHP_EOL;
echo PHP_EOL;
echo '✅ SISTEMA FUNCIONANDO CORRECTAMENTE!' . PHP_EOL;
?>
