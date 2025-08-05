<?php
/**
 * Script rápido para agregar la columna tipo_gestion a la tabla products
 */

require_once 'connection.php';

echo "Verificando y agregando columna tipo_gestion...\n";

// Verificar si la columna existe
$result = $mysqli->query("SHOW COLUMNS FROM products LIKE 'tipo_gestion'");

if ($result->num_rows == 0) {
    echo "Columna tipo_gestion no existe. Agregando...\n";
    
    // Agregar la columna
    $add_column = $mysqli->query("ALTER TABLE products ADD COLUMN tipo_gestion VARCHAR(20) DEFAULT 'unidad' AFTER quantity");
    
    if ($add_column) {
        echo "✓ Columna tipo_gestion agregada exitosamente.\n";
        
        // Actualizar productos que son cables a tipo bobina
        $update_cables = $mysqli->query("
            UPDATE products 
            SET tipo_gestion = 'bobina' 
            WHERE product_name LIKE '%cable%' 
               OR product_name LIKE '%bobina%' 
               OR description LIKE '%cable%'
               OR description LIKE '%bobina%'
        ");
        
        if ($update_cables) {
            $affected = $mysqli->affected_rows;
            echo "✓ Se actualizaron $affected productos a tipo 'bobina'.\n";
        }
        
    } else {
        echo "❌ Error al agregar columna: " . $mysqli->error . "\n";
    }
} else {
    echo "✓ La columna tipo_gestion ya existe.\n";
}

echo "Proceso completado.\n";
?>
