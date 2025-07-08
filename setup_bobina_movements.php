<?php
require_once 'connection.php';

echo "<h2>Configurando integración de bobinas con movimientos...</h2>";

// Verificar si existe la columna bobina_id en movements
$result = $mysqli->query("SHOW COLUMNS FROM movements LIKE 'bobina_id'");
if ($result->num_rows == 0) {
    // Agregar columna bobina_id a la tabla movements
    $sql = "ALTER TABLE movements ADD COLUMN bobina_id INT NULL AFTER quantity";
    
    if ($mysqli->query($sql)) {
        echo "✅ Columna bobina_id agregada a la tabla movements<br>";
    } else {
        echo "❌ Error al agregar columna bobina_id: " . $mysqli->error . "<br>";
    }
} else {
    echo "ℹ️ La columna bobina_id ya existe en movements<br>";
}

// Verificar si existe la tabla bobinas
$result = $mysqli->query("SHOW TABLES LIKE 'bobinas'");
if ($result->num_rows == 0) {
    // Crear tabla bobinas
    $sql = "CREATE TABLE bobinas (
        bobina_id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        metros_iniciales DECIMAL(10,2) NOT NULL,
        metros_actuales DECIMAL(10,2) NOT NULL,
        identificador VARCHAR(100) NULL,
        fecha_ingreso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
    )";
    
    if ($mysqli->query($sql)) {
        echo "✅ Tabla bobinas creada correctamente<br>";
    } else {
        echo "❌ Error al crear tabla bobinas: " . $mysqli->error . "<br>";
    }
} else {
    echo "ℹ️ La tabla bobinas ya existe<br>";
}

// Verificar si existe la columna tipo_gestion en products
$result = $mysqli->query("SHOW COLUMNS FROM products LIKE 'tipo_gestion'");
if ($result->num_rows == 0) {
    // Agregar columna tipo_gestion a la tabla products
    $sql = "ALTER TABLE products ADD COLUMN tipo_gestion ENUM('normal', 'bobina', 'bolsa', 'par', 'kit') DEFAULT 'normal' AFTER barcode";
    
    if ($mysqli->query($sql)) {
        echo "✅ Columna tipo_gestion agregada a la tabla products<br>";
    } else {
        echo "❌ Error al agregar columna tipo_gestion: " . $mysqli->error . "<br>";
    }
} else {
    echo "ℹ️ La columna tipo_gestion ya existe en products<br>";
}

// Mostrar resumen de la configuración
echo "<h3>Resumen de la configuración:</h3>";
echo "<ul>";
echo "<li>✅ Tabla bobinas: " . ($mysqli->query("SHOW TABLES LIKE 'bobinas'")->num_rows > 0 ? "Creada" : "No existe") . "</li>";
echo "<li>✅ Columna bobina_id en movements: " . ($mysqli->query("SHOW COLUMNS FROM movements LIKE 'bobina_id'")->num_rows > 0 ? "Agregada" : "No existe") . "</li>";
echo "<li>✅ Columna tipo_gestion en products: " . ($mysqli->query("SHOW COLUMNS FROM products LIKE 'tipo_gestion'")->num_rows > 0 ? "Agregada" : "No existe") . "</li>";
echo "</ul>";

echo "<h3>Configuración completada.</h3>";
echo "<a href='dashboard/index.php'>Ir al Dashboard</a>";
?> 