<?php
require_once 'connection.php';

echo "🔧 Creando procedimiento almacenado para actualización de stock...\n";

// Eliminar el procedimiento si existe
$drop_sp = "DROP PROCEDURE IF EXISTS sp_update_product_stock";
$mysqli->query($drop_sp);

// Crear el procedimiento almacenado
$create_sp = "
CREATE PROCEDURE sp_update_product_stock(IN p_product_id INT)
BEGIN
    DECLARE total_stock DECIMAL(10,2) DEFAULT 0;
    
    -- Calcular stock total basado en movimientos
    SELECT COALESCE(SUM(quantity), 0) INTO total_stock
    FROM movements 
    WHERE product_id = p_product_id;
    
    -- Actualizar el stock del producto
    UPDATE products 
    SET quantity = total_stock 
    WHERE product_id = p_product_id;
    
END
";

if ($mysqli->query($create_sp)) {
    echo "✅ Procedimiento almacenado sp_update_product_stock creado correctamente\n";
    echo "📝 El procedimiento calcula el stock total basado en los movimientos de inventario\n";
} else {
    echo "❌ Error al crear procedimiento almacenado: " . $mysqli->error . "\n";
}

$mysqli->close();
?> 