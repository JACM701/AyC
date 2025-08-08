-- Script para agregar campo precio_venta_metro a la tabla products
-- Este campo almacenará el precio de venta por metro para productos tipo bobina

ALTER TABLE products 
ADD COLUMN precio_venta_metro DECIMAL(10,4) DEFAULT NULL 
COMMENT 'Precio de venta por metro para productos tipo bobina (cables)';

-- Actualizar cables existentes con precios por metro basados en patrones
-- Cat 5E
UPDATE products 
SET precio_venta_metro = 7.00 
WHERE tipo_gestion = 'bobina' 
AND (product_name LIKE '%cat 5%' OR product_name LIKE '%categoria 5%' OR product_name LIKE '%category 5%');

-- Cat 6
UPDATE products 
SET precio_venta_metro = 8.50 
WHERE tipo_gestion = 'bobina' 
AND (product_name LIKE '%cat 6%' OR product_name LIKE '%categoria 6%' OR product_name LIKE '%category 6%');

-- Cables coaxiales
UPDATE products 
SET precio_venta_metro = 4.50 
WHERE tipo_gestion = 'bobina' 
AND (product_name LIKE '%coaxial%' OR product_name LIKE '%coax%');

-- Cables de alarma
UPDATE products 
SET precio_venta_metro = 3.00 
WHERE tipo_gestion = 'bobina' 
AND (product_name LIKE '%alarma%' OR product_name LIKE '%alarm%');

-- Cables de poder
UPDATE products 
SET precio_venta_metro = 4.50 
WHERE tipo_gestion = 'bobina' 
AND (product_name LIKE '%poder%' OR product_name LIKE '%power%');

-- Fibra óptica
UPDATE products 
SET precio_venta_metro = 12.00 
WHERE tipo_gestion = 'bobina' 
AND (product_name LIKE '%fibra%' OR product_name LIKE '%fiber%');

-- Para cables que no coincidan con ningún patrón, usar precio por defecto
UPDATE products 
SET precio_venta_metro = 7.00 
WHERE tipo_gestion = 'bobina' 
AND precio_venta_metro IS NULL
AND (product_name LIKE '%cable%' OR product_name LIKE '%utp%');

-- Mostrar resultados
SELECT product_name, tipo_gestion, precio_venta_metro 
FROM products 
WHERE tipo_gestion = 'bobina' 
ORDER BY product_name;
