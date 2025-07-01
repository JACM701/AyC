<?php
echo "=== PRUEBA DE EJECUCIÓN DEL SCRIPT ===\n";

$script_path = __DIR__ . '/scrape_tiendas.js';
$command = 'node "' . $script_path . '" "cámara" 2>&1';

echo "Comando a ejecutar: " . $command . "\n\n";

$output = shell_exec($command);

echo "Salida del script:\n";
echo $output . "\n";

if ($output) {
    $json_result = json_decode($output, true);
    if ($json_result && is_array($json_result)) {
        echo "✅ JSON válido - " . count($json_result) . " tiendas encontradas\n";
        foreach ($json_result as $tienda) {
            echo "- " . $tienda['tienda'] . ": " . count($tienda['resultados']) . " productos\n";
        }
    } else {
        echo "❌ Error: JSON inválido\n";
    }
} else {
    echo "❌ Error: No se obtuvo salida del script\n";
}
?> 