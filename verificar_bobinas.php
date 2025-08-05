<?php
require_once 'connection.php';

echo "=== ESTRUCTURA DE LA TABLA BOBINAS ===\n";
$result = $mysqli->query('DESCRIBE bobinas');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== DATOS DEL CABLE SAXXON ===\n";
// Obtener todas las bobinas de este producto
$bobinas_query = "SELECT * FROM bobinas WHERE product_id = (SELECT product_id FROM products WHERE sku = 'SAXXON OUTP5ECCAEXT') ORDER BY bobina_id";
$bobinas_result = $mysqli->query($bobinas_query);

$total_metros = 0;
$total_bobinas_activas = 0;

while ($bobina = $bobinas_result->fetch_assoc()) {
    echo "Bobina ID: " . $bobina['bobina_id'] . "\n";
    foreach($bobina as $campo => $valor) {
        echo "  $campo: $valor\n";
    }
    
    if ($bobina['is_active']) {
        $total_metros += $bobina['metros_actuales'];
        $total_bobinas_activas++;
    }
    echo "  ---\n";
}

echo "\n=== RESUMEN ===\n";
echo "Total bobinas activas: $total_bobinas_activas\n";
echo "Total metros: $total_metros\n";
echo "Bobinas equivalentes (metros/305): " . round($total_metros/305, 2) . "\n";

echo "\n=== EL PROBLEMA ===\n";
echo "Tienes razón: $total_metros metros ÷ 305 metros/bobina = " . round($total_metros/305, 2) . " bobinas equivalentes\n";
echo "Pero el sistema cuenta $total_bobinas_activas bobinas físicas\n";
echo "Esto significa que el precio debería calcularse como:\n";
echo "- Opción 1: " . round($total_metros/305, 2) . " bobinas equivalentes × \$983.68 = \$" . round(($total_metros/305) * 983.68, 2) . "\n";
echo "- Opción 2: $total_metros metros × precio_por_metro\n";
?>
