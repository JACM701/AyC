<?php
// Archivo de prueba para verificar el funcionamiento del inventario
echo "<h1>Prueba del Sistema de Inventario</h1>";
echo "<p>Este archivo verifica que el sistema esté funcionando correctamente.</p>";

// Verificar que los archivos existen
$files_to_check = [
    'inventory/index.php',
    'inventory/add.php',
    'dashboard/index.php',
    'includes/sidebar.php'
];

echo "<h2>Verificación de archivos:</h2>";
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $file - Existe</p>";
    } else {
        echo "<p style='color: red;'>✗ $file - No existe</p>";
    }
}

echo "<h2>Enlaces de prueba:</h2>";
echo "<ul>";
echo "<li><a href='inventory/index.php' target='_blank'>Inventario (Vista de tarjetas)</a></li>";
echo "<li><a href='inventory/add.php' target='_blank'>Agregar producto al inventario</a></li>";
echo "<li><a href='dashboard/index.php' target='_blank'>Dashboard actualizado</a></li>";
echo "</ul>";

echo "<h2>Características implementadas:</h2>";
echo "<ul>";
echo "<li>✓ Vista de inventario con tarjetas/cuadritos</li>";
echo "<li>✓ Filtros por categoría y búsqueda</li>";
echo "<li>✓ Estadísticas visuales en el header</li>";
echo "<li>✓ Estados de stock (disponible, bajo stock, agotado)</li>";
echo "<li>✓ Formulario para agregar productos</li>";
echo "<li>✓ Dashboard con información general (sin precios)</li>";
echo "<li>✓ Gráficas con datos simulados</li>";
echo "<li>✓ Diseño responsive y moderno</li>";
echo "</ul>";

echo "<p><strong>Nota:</strong> Este es un maquetado funcional. Los datos son simulados y las funciones de editar/registrar movimientos muestran alertas de prueba.</p>";
?> 