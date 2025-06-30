<?php
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$nombre = $input['nombre'] ?? '';
// Simulación de búsqueda en tiendas
$response = [
    'nombre' => $nombre !== '' ? $nombre . ' (desde tiendas)' : 'Producto ejemplo desde tiendas',
    'descripcion' => 'Descripción autollenada desde tiendas para ' . ($nombre !== '' ? $nombre : 'producto ejemplo')
];
echo json_encode($response); 