<?php
require_once 'connection.php';

echo "<h2>Configurando tabla de proveedores...</h2>";

// Verificar si existe la tabla suppliers
$result = $mysqli->query("SHOW TABLES LIKE 'suppliers'");
if ($result->num_rows == 0) {
    // Crear tabla suppliers simplificada
    $sql = "CREATE TABLE suppliers (
        supplier_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        website VARCHAR(500) NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($mysqli->query($sql)) {
        echo "✅ Tabla suppliers creada correctamente<br>";
        echo "- supplier_id (INT, AUTO_INCREMENT, PRIMARY KEY)<br>";
        echo "- name (VARCHAR(255), NOT NULL)<br>";
        echo "- website (VARCHAR(500), NULL)<br>";
        echo "- notes (TEXT, NULL)<br>";
        echo "- created_at (TIMESTAMP)<br>";
        echo "- updated_at (TIMESTAMP)<br>";
    } else {
        echo "❌ Error al crear tabla suppliers: " . $mysqli->error . "<br>";
    }
} else {
    echo "ℹ️ La tabla suppliers ya existe<br>";
    
    // Verificar si tiene las columnas necesarias
    $columns = $mysqli->query("SHOW COLUMNS FROM suppliers");
    $existing_columns = [];
    while ($column = $columns->fetch_assoc()) {
        $existing_columns[] = $column['Field'];
    }
    
    // Verificar columnas faltantes
    $required_columns = ['name', 'website', 'notes'];
    foreach ($required_columns as $column) {
        if (!in_array($column, $existing_columns)) {
            $sql = "ALTER TABLE suppliers ADD COLUMN $column ";
            if ($column === 'name') {
                $sql .= "VARCHAR(255) NOT NULL";
            } elseif ($column === 'website') {
                $sql .= "VARCHAR(500) NULL";
            } elseif ($column === 'notes') {
                $sql .= "TEXT NULL";
            }
            
            if ($mysqli->query($sql)) {
                echo "✅ Columna $column agregada a suppliers<br>";
            } else {
                echo "❌ Error al agregar columna $column: " . $mysqli->error . "<br>";
            }
        }
    }
}

// Insertar algunos proveedores de ejemplo
$example_suppliers = [
    ['name' => 'Syscom', 'website' => 'https://www.syscom.mx', 'notes' => 'Proveedor principal de electrónicos'],
    ['name' => 'PCH', 'website' => 'https://www.pch.com.mx', 'notes' => 'Proveedor de componentes'],
    ['name' => 'Amazon México', 'website' => 'https://www.amazon.com.mx', 'notes' => 'Tienda online general']
];

foreach ($example_suppliers as $supplier) {
    $stmt = $mysqli->prepare("INSERT IGNORE INTO suppliers (name, website, notes) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $supplier['name'], $supplier['website'], $supplier['notes']);
    if ($stmt->execute()) {
        echo "✅ Proveedor de ejemplo agregado: " . $supplier['name'] . "<br>";
    }
    $stmt->close();
}

echo "<h3>Configuración completada.</h3>";
echo "<a href='proveedores/index.php'>Ir a Proveedores</a>";
?> 