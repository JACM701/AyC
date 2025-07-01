<?php
header('Content-Type: application/json');

// Debug: Log que el archivo se está ejecutando
error_log("comparar_precios.php ejecutándose");

$input = json_decode(file_get_contents('php://input'), true);
$nombre = $input['nombre'] ?? '';
$sku = $input['sku'] ?? '';
$descripcion = $input['descripcion'] ?? '';

// Debug: Log los datos recibidos
error_log("Datos recibidos: nombre=$nombre, sku=$sku, descripcion=$descripcion");

// Usar el primer campo que tenga contenido
$termino_busqueda = '';
if (!empty($sku)) {
    $termino_busqueda = $sku;
} elseif (!empty($nombre)) {
    $termino_busqueda = $nombre;
} elseif (!empty($descripcion)) {
    $termino_busqueda = $descripcion;
}

// Debug: Log el término de búsqueda
error_log("Término de búsqueda: $termino_busqueda");

$resultados = [];

if (!empty($termino_busqueda)) {
    try {
        // Ejecutar el script de Node.js con la ruta completa
        $script_path = __DIR__ . '/../scrape_tiendas.js';
        $command = 'node "' . $script_path . '" "' . escapeshellarg($termino_busqueda) . '" 2>&1';
        
        // Debug: Log el comando
        error_log("Ejecutando comando: " . $command);
        
        $output = shell_exec($command);
        
        // Debug: Log la salida
        error_log("Salida del script: " . $output);
        
        if ($output) {
            $json_result = json_decode($output, true);
            if ($json_result && is_array($json_result)) {
                foreach ($json_result as $tienda) {
                    if (isset($tienda['tienda']) && isset($tienda['resultados']) && is_array($tienda['resultados'])) {
                        if (count($tienda['resultados']) > 0) {
                            // Encontrar el resultado con mejor precio
                            $mejor_resultado = null;
                            $mejor_precio = PHP_FLOAT_MAX;
                            
                            foreach ($tienda['resultados'] as $resultado) {
                                if (isset($resultado['precio']) && !empty($resultado['precio'])) {
                                    $precio_float = floatval(str_replace(['$', ','], '', $resultado['precio']));
                                    if ($precio_float > 0 && $precio_float < $mejor_precio) {
                                        $mejor_precio = $precio_float;
                                        $mejor_resultado = $resultado;
                                    }
                                }
                            }
                            
                            // Si no hay precio válido, usar el primer resultado
                            if (!$mejor_resultado) {
                                $mejor_resultado = $tienda['resultados'][0];
                            }
                            
                            $precio_mostrado = '-';
                            if ($tienda['tienda'] === 'PCH' && isset($mejor_resultado['precio']) && $mejor_resultado['precio'] !== '') {
                                $precio_usd = floatval(str_replace([',', '$'], '', $mejor_resultado['precio']));
                                $precio_mxn = $precio_usd * 17.5;
                                $precio_mostrado = '$' . number_format($precio_mxn, 2) . ' MXN';
                            } elseif (isset($mejor_resultado['precio']) && $mejor_resultado['precio'] !== '') {
                                $precio_mostrado = '$' . $mejor_resultado['precio'];
                            }
                            
                            $resultados[] = [
                                'tienda' => $tienda['tienda'],
                                'precio' => $precio_mostrado,
                                'enlace' => isset($mejor_resultado['enlace']) && !empty($mejor_resultado['enlace']) ? $mejor_resultado['enlace'] : '-',
                                'nombre_producto' => isset($mejor_resultado['nombre']) ? $mejor_resultado['nombre'] : ''
                            ];
                        } else {
                            // Si no hay resultados, agregar la tienda con enlace de búsqueda
                            $enlaces_busqueda = [
                                'Syscom' => 'https://www.syscom.mx/buscar.html?query=' . urlencode($termino_busqueda),
                                'TVC.mx' => 'https://tvc.mx/buscar?q=' . urlencode($termino_busqueda),
                                'Tecnosinergia' => 'https://tecnosinergia.com/catalogsearch/result/?q=' . urlencode($termino_busqueda),
                                'PCH' => 'https://shop.pchconnect.com/productos?search=' . urlencode($termino_busqueda) . '&orderBy=stock'
                            ];
                            
                            $resultados[] = [
                                'tienda' => $tienda['tienda'],
                                'precio' => '-',
                                'enlace' => $enlaces_busqueda[$tienda['tienda']] ?? '-',
                                'nota' => 'Haz clic para buscar manualmente'
                            ];
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error ejecutando script: " . $e->getMessage());
    }
}

// Si no hay resultados, generar enlaces directos como fallback
if (empty($resultados)) {
    $resultados = [
        [
            'tienda' => 'Syscom',
            'precio' => '-',
            'enlace' => 'https://www.syscom.mx/buscar.html?query=' . urlencode($termino_busqueda),
            'nota' => 'Haz clic para buscar y ver precios'
        ],
        [
            'tienda' => 'TVC.mx',
            'precio' => '-',
            'enlace' => 'https://tvc.mx/buscar?q=' . urlencode($termino_busqueda),
            'nota' => 'Haz clic para buscar y ver precios'
        ],
        [
            'tienda' => 'Tecnosinergia',
            'precio' => '-',
            'enlace' => 'https://tecnosinergia.com/catalogsearch/result/?q=' . urlencode($termino_busqueda),
            'nota' => 'Haz clic para buscar y ver precios'
        ],
        [
            'tienda' => 'PCH',
            'precio' => '-',
            'enlace' => 'https://shop.pchconnect.com/productos?search=' . urlencode($termino_busqueda) . '&orderBy=stock',
            'nota' => 'Haz clic para buscar y ver precios'
        ]
    ];
}

// Debug: Log los resultados
error_log("Resultados generados: " . json_encode($resultados));

echo json_encode($resultados); 