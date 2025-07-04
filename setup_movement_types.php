<?php
require_once 'connection.php';

echo "<h2>Configurando tipos de movimiento...</h2>";

// Verificar si ya existen tipos de movimiento
$result = $mysqli->query("SELECT COUNT(*) as count FROM movement_type");
$count = $result->fetch_assoc()['count'];

if ($count == 0) {
    // Agregar tipos de movimiento básicos
    $sql = "INSERT INTO movement_type (Id_tipo, nombre) VALUES 
            (1, 'Entrada'),
            (2, 'Salida')";
    
    if ($mysqli->query($sql)) {
        echo "✅ Tipos de movimiento agregados correctamente<br>";
        echo "- Entrada (ID: 1)<br>";
        echo "- Salida (ID: 2)<br>";
    } else {
        echo "❌ Error al agregar tipos de movimiento: " . $mysqli->error . "<br>";
    }
} else {
    echo "ℹ️ Los tipos de movimiento ya existen<br>";
    
    // Mostrar tipos existentes
    $result = $mysqli->query("SELECT * FROM movement_type ORDER BY Id_tipo");
    echo "<h3>Tipos de movimiento existentes:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Id_tipo'] . "</td>";
        echo "<td>" . $row['nombre'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Configuración completada.</h3>";
echo "<a href='dashboard/index.php'>Ir al Dashboard</a>";
?> 