<?php
/**
 * Script de depuración para verificar el funcionamiento del dashboard con bobinas
 */

require_once 'connection.php';

echo "=== DEPURACIÓN DEL DASHBOARD ===\n\n";

// 1. Verificar estructura de la tabla products
echo "1. Verificando estructura de la tabla products...\n";
$result = $mysqli->query("DESCRIBE products");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
    if ($row['Field'] === 'tipo_gestion') {
        echo "   ✓ Campo 'tipo_gestion' encontrado\n";
    }
}

if (!in_array('tipo_gestion', $columns)) {
    echo "   ❌ Campo 'tipo_gestion' NO encontrado\n";
    echo "   Agregando campo tipo_gestion...\n";
    $mysqli->query("ALTER TABLE products ADD COLUMN tipo_gestion VARCHAR(20) DEFAULT 'unidad'");
    echo "   ✓ Campo agregado\n";
}

// 2. Verificar productos con tipo_gestion
echo "\n2. Verificando productos con tipo_gestion...\n";
$result = $mysqli->query("SELECT tipo_gestion, COUNT(*) as total FROM products GROUP BY tipo_gestion");
while ($row = $result->fetch_assoc()) {
    echo "   Tipo: " . ($row['tipo_gestion'] ?? 'NULL') . " - Total: {$row['total']}\n";
}

// 3. Probar consulta de estadísticas
echo "\n3. Probando consulta de estadísticas...\n";
$stats_query = "
    SELECT 
        COUNT(*) as total_products,
        SUM(CASE 
            WHEN tipo_gestion = 'bobina' THEN 
                CASE WHEN quantity > 100 THEN 1 ELSE 0 END
            ELSE 
                CASE WHEN quantity > 10 THEN 1 ELSE 0 END
        END) as disponibles,
        SUM(CASE 
            WHEN tipo_gestion = 'bobina' THEN 
                CASE WHEN quantity > 0 AND quantity <= 100 THEN 1 ELSE 0 END
            ELSE 
                CASE WHEN quantity > 0 AND quantity <= 10 THEN 1 ELSE 0 END
        END) as bajo_stock,
        SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as agotados
    FROM products
";

$result = $mysqli->query($stats_query);
if ($result) {
    $stats = $result->fetch_assoc();
    echo "   ✓ Consulta exitosa:\n";
    echo "     Total productos: {$stats['total_products']}\n";
    echo "     Disponibles: {$stats['disponibles']}\n";
    echo "     Bajo stock: {$stats['bajo_stock']}\n";
    echo "     Agotados: {$stats['agotados']}\n";
} else {
    echo "   ❌ Error en consulta: " . $mysqli->error . "\n";
}

// 4. Probar consulta de menor stock
echo "\n4. Probando consulta de menor stock...\n";
$min_stock_query = "
    SELECT 
        product_name, 
        quantity, 
        tipo_gestion,
        CASE 
            WHEN tipo_gestion = 'bobina' THEN CONCAT(ROUND(quantity, 1), ' metros')
            ELSE CONCAT(quantity, ' unidades')
        END as stock_display
    FROM products p
    WHERE quantity > 0
    ORDER BY 
        CASE 
            WHEN tipo_gestion = 'bobina' THEN quantity / 100
            ELSE quantity
        END ASC
    LIMIT 1
";

$result = $mysqli->query($min_stock_query);
if ($result) {
    $min_stock = $result->fetch_assoc();
    if ($min_stock) {
        echo "   ✓ Producto con menor stock:\n";
        echo "     Nombre: {$min_stock['product_name']}\n";
        echo "     Cantidad: {$min_stock['quantity']}\n";
        echo "     Tipo: " . ($min_stock['tipo_gestion'] ?? 'NULL') . "\n";
        echo "     Display: {$min_stock['stock_display']}\n";
    } else {
        echo "   ⚠ No se encontraron productos con stock\n";
    }
} else {
    echo "   ❌ Error en consulta: " . $mysqli->error . "\n";
}

// 5. Verificar productos tipo bobina específicos
echo "\n5. Listando productos tipo bobina...\n";
$result = $mysqli->query("SELECT product_id, product_name, quantity, tipo_gestion FROM products WHERE tipo_gestion = 'bobina' LIMIT 5");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "   ID: {$row['product_id']} | {$row['product_name']} | {$row['quantity']} | {$row['tipo_gestion']}\n";
    }
} else {
    echo "   ⚠ No se encontraron productos tipo bobina\n";
}

// 6. Buscar productos que deberían ser bobinas
echo "\n6. Productos que podrían ser bobinas...\n";
$result = $mysqli->query("
    SELECT product_id, product_name, quantity, tipo_gestion 
    FROM products 
    WHERE (product_name LIKE '%cable%' OR product_name LIKE '%bobina%') 
    LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    echo "   ID: {$row['product_id']} | {$row['product_name']} | Tipo: " . ($row['tipo_gestion'] ?? 'NULL') . "\n";
}

echo "\n=== FIN DE LA DEPURACIÓN ===\n";
?>
