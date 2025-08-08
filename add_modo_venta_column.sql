-- Agregar columna para diferenciar modo de venta
ALTER TABLE cotizaciones_productos 
ADD COLUMN modo_venta ENUM('metro', 'bobina', 'unidad') DEFAULT 'unidad'
COMMENT 'Modo de venta: metro (por metro), bobina (por bobina completa), unidad (productos normales)';

-- Actualizar cotizaciones existentes de cables para detectar el modo
UPDATE cotizaciones_productos cp
INNER JOIN products p ON cp.product_id = p.product_id
SET cp.modo_venta = CASE
    WHEN p.tipo_gestion = 'bobina' AND (cp.cantidad % 305 = 0) THEN 'bobina'
    WHEN p.tipo_gestion = 'bobina' AND (cp.cantidad % 305 != 0) THEN 'metro'
    ELSE 'unidad'
END
WHERE p.tipo_gestion = 'bobina';

-- Verificar resultados
SELECT 
    cp.cotizacion_producto_id,
    p.product_name,
    cp.cantidad,
    cp.precio_unitario,
    cp.modo_venta,
    CASE 
        WHEN cp.modo_venta = 'metro' THEN CONCAT(cp.cantidad, ' metros')
        WHEN cp.modo_venta = 'bobina' THEN CONCAT(cp.cantidad/305, ' bobinas')
        ELSE CONCAT(cp.cantidad, ' unidades')
    END as display_cantidad
FROM cotizaciones_productos cp
INNER JOIN products p ON cp.product_id = p.product_id
WHERE p.tipo_gestion = 'bobina'
ORDER BY cp.cotizacion_producto_id;
