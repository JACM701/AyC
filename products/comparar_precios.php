<?php
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$nombre = $input['nombre'] ?? '';
$descripcion = $input['descripcion'] ?? '';

// --- Configuración de la API de Syscom ---
$syscom_key = 'oC5GPC7PRju3Ht1fIBngCbRBmspMMHx1';
$syscom_secret = 'A2mV7CE1mE2Qme4Rij3msB7P3o791oBZ0cjixP9K';

// --- Función para consultar la API de Syscom ---
function buscarSyscom($nombre, $key, $secret) {
    $url = 'https://www.syscom.mx/api/v1/items?search=' . urlencode($nombre) . "&api_key=$key&api_secret=$secret";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    $response = curl_exec($ch);
    curl_close($ch);
    if ($response) {
        $json = json_decode($response, true);
        if (isset($json['items'][0])) {
            $item = $json['items'][0];
            return [
                'tienda' => 'Syscom',
                'precio' => $item['precio'] ?? '-',
                'enlace' => isset($item['clave']) ? 'https://www.syscom.mx/producto/' . $item['clave'] . '.html' : '-',
            ];
        }
    }
    return [ 'tienda' => 'Syscom', 'precio' => '-', 'enlace' => '-' ];
}

// --- Función de scraping simple ---
function buscarTVC($nombre) {
    $url = 'https://tvc.mx/buscar?q=' . urlencode($nombre);
    $html = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 6]]));
    if ($html && preg_match('/<a[^>]*href="([^"]*producto[^"]*)"[^>]*>(.*?)<\/a>/i', $html, $m)) {
        $enlace = 'https://tvc.mx' . $m[1];
        if (preg_match('/\$([0-9\.,]+)/', $html, $pm)) {
            $precio = str_replace(',', '', $pm[1]);
        } else {
            $precio = '-';
        }
        return [ 'tienda' => 'TVC.mx', 'precio' => $precio, 'enlace' => $enlace ];
    }
    return [ 'tienda' => 'TVC.mx', 'precio' => '-', 'enlace' => '-' ];
}

function buscarTecnosinergia($nombre) {
    $url = 'https://tecnosinergia.com/catalogsearch/result/?q=' . urlencode($nombre);
    $html = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 6]]));
    if ($html && preg_match('/<a[^>]*href="([^"]*\/producto\/[^"]*)"[^>]*>(.*?)<\/a>/i', $html, $m)) {
        $enlace = 'https://tecnosinergia.com' . $m[1];
        if (preg_match('/\$([0-9\.,]+)/', $html, $pm)) {
            $precio = str_replace(',', '', $pm[1]);
        } else {
            $precio = '-';
        }
        return [ 'tienda' => 'Tecnosinergia', 'precio' => $precio, 'enlace' => $enlace ];
    }
    return [ 'tienda' => 'Tecnosinergia', 'precio' => '-', 'enlace' => '-' ];
}

function buscarPCH($nombre) {
    $url = 'https://shop.pchconnect.com/search?controller=search&s=' . urlencode($nombre);
    $html = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 6]]));
    if ($html && preg_match('/<a[^>]*href="([^"]*\/producto\/[^"]*)"[^>]*>(.*?)<\/a>/i', $html, $m)) {
        $enlace = 'https://shop.pchconnect.com' . $m[1];
        if (preg_match('/\$([0-9\.,]+)/', $html, $pm)) {
            $precio = str_replace(',', '', $pm[1]);
        } else {
            $precio = '-';
        }
        return [ 'tienda' => 'PCH', 'precio' => $precio, 'enlace' => $enlace ];
    }
    return [ 'tienda' => 'PCH', 'precio' => '-', 'enlace' => '-' ];
}

// --- Ejecutar todas las búsquedas con timeout global ---
$start = microtime(true);
$resultados = [];

// Syscom API
$resultados[] = buscarSyscom($nombre, $syscom_key, $syscom_secret);
if ((microtime(true) - $start) > 10) goto fin;
// TVC
$resultados[] = buscarTVC($nombre);
if ((microtime(true) - $start) > 10) goto fin;
// Tecnosinergia
$resultados[] = buscarTecnosinergia($nombre);
if ((microtime(true) - $start) > 10) goto fin;
// PCH
$resultados[] = buscarPCH($nombre);

fin:
echo json_encode($resultados); 