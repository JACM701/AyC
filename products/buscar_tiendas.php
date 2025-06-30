<?php
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$nombre = $input['nombre'] ?? '';
$sku = $input['sku'] ?? '';

// --- Configuración de la API de Syscom ---
$syscom_key = 'oC5GPC7PRju3Ht1fIBngCbRBmspMMHx1';
$syscom_secret = 'A2mV7CE1mE2Qme4Rij3msB7P3o791oBZ0cjixP9K';

function buscarSyscom($nombre, $sku, $key, $secret) {
    $resultados = [];
    $query = $sku !== '' ? $sku : $nombre;
    $url = 'https://www.syscom.mx/api/v1/items?search=' . urlencode($query) . "&api_key=$key&api_secret=$secret";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    $response = curl_exec($ch);
    curl_close($ch);
    if ($response) {
        $json = json_decode($response, true);
        if (isset($json['items']) && is_array($json['items'])) {
            foreach (array_slice($json['items'], 0, 3) as $item) {
                $resultados[] = [
                    'nombre' => $item['descripcion'] ?? $item['clave'] ?? $query,
                    'precio' => $item['precio'] ?? '-',
                    'enlace' => isset($item['clave']) ? 'https://www.syscom.mx/producto/' . $item['clave'] . '.html' : '-',
                    'descripcion' => $item['descripcion_larga'] ?? $item['descripcion'] ?? 'Sin descripción',
                ];
            }
        }
    }
    return $resultados;
}

function buscarTVC($nombre, $sku) {
    $resultados = [];
    $query = $sku !== '' ? $sku : $nombre;
    $url = 'https://tvc.mx/buscar?q=' . urlencode($query);
    $html = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 6]]));
    if ($html) {
        // Buscar hasta 3 productos
        if (preg_match_all('/<a[^>]*href="([^"]*producto[^"]*)"[^>]*>(.*?)<\/a>/i', $html, $matches, PREG_SET_ORDER)) {
            $count = 0;
            foreach ($matches as $m) {
                if ($count++ >= 3) break;
                $nombreProd = strip_tags($m[2]);
                $enlace = 'https://tvc.mx' . $m[1];
                if (preg_match('/\$([0-9\.,]+)/', $html, $pm)) {
                    $precio = str_replace(',', '', $pm[1]);
                } else {
                    $precio = '-';
                }
                $resultados[] = [
                    'nombre' => $nombreProd,
                    'precio' => $precio,
                    'enlace' => $enlace,
                    'descripcion' => $nombreProd
                ];
            }
        }
    }
    return $resultados;
}

function buscarTecnosinergia($nombre, $sku) {
    $resultados = [];
    $query = $sku !== '' ? $sku : $nombre;
    $url = 'https://tecnosinergia.com/catalogsearch/result/?q=' . urlencode($query);
    $html = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 6]]));
    if ($html) {
        if (preg_match_all('/<a[^>]*href="([^"]*\/producto\/[^"]*)"[^>]*>(.*?)<\/a>/i', $html, $matches, PREG_SET_ORDER)) {
            $count = 0;
            foreach ($matches as $m) {
                if ($count++ >= 3) break;
                $nombreProd = strip_tags($m[2]);
                $enlace = 'https://tecnosinergia.com' . $m[1];
                if (preg_match('/\$([0-9\.,]+)/', $html, $pm)) {
                    $precio = str_replace(',', '', $pm[1]);
                } else {
                    $precio = '-';
                }
                $resultados[] = [
                    'nombre' => $nombreProd,
                    'precio' => $precio,
                    'enlace' => $enlace,
                    'descripcion' => $nombreProd
                ];
            }
        }
    }
    return $resultados;
}

function buscarPCH($nombre, $sku) {
    $resultados = [];
    $query = $sku !== '' ? $sku : $nombre;
    $url = 'https://shop.pchconnect.com/search?controller=search&s=' . urlencode($query);
    $html = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 6]]));
    if ($html) {
        if (preg_match_all('/<a[^>]*href="([^"]*\/producto\/[^"]*)"[^>]*>(.*?)<\/a>/i', $html, $matches, PREG_SET_ORDER)) {
            $count = 0;
            foreach ($matches as $m) {
                if ($count++ >= 3) break;
                $nombreProd = strip_tags($m[2]);
                $enlace = 'https://shop.pchconnect.com' . $m[1];
                if (preg_match('/\$([0-9\.,]+)/', $html, $pm)) {
                    $precio = str_replace(',', '', $pm[1]);
                } else {
                    $precio = '-';
                }
                $resultados[] = [
                    'nombre' => $nombreProd,
                    'precio' => $precio,
                    'enlace' => $enlace,
                    'descripcion' => $nombreProd
                ];
            }
        }
    }
    return $resultados;
}

$resultados = [
    [
        'tienda' => 'Syscom',
        'resultados' => buscarSyscom($nombre, $sku, $syscom_key, $syscom_secret)
    ],
    [
        'tienda' => 'TVC.mx',
        'resultados' => buscarTVC($nombre, $sku)
    ],
    [
        'tienda' => 'Tecnosinergia',
        'resultados' => buscarTecnosinergia($nombre, $sku)
    ],
    [
        'tienda' => 'PCH',
        'resultados' => buscarPCH($nombre, $sku)
    ]
];
echo json_encode($resultados); 