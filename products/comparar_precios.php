<?php
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$nombre = $input['nombre'] ?? '';
$descripcion = $input['descripcion'] ?? '';
// Simulación de resultados
$resultados = [
    [
        'tienda' => 'TVC.mx',
        'precio' => '1234.56',
        'enlace' => 'https://tvc.mx/buscar?q=' . urlencode($nombre)
    ],
    [
        'tienda' => 'Syscom',
        'precio' => '1200.00',
        'enlace' => 'https://www.syscom.mx/buscar.html?query=' . urlencode($nombre)
    ],
    [
        'tienda' => 'Tecnosinergia',
        'precio' => '1250.00',
        'enlace' => 'https://tecnosinergia.com/catalogsearch/result/?q=' . urlencode($nombre)
    ],
    [
        'tienda' => 'PCH',
        'precio' => '1199.99',
        'enlace' => 'https://shop.pchconnect.com/search?controller=search&s=' . urlencode($nombre)
    ]
];
echo json_encode($resultados); 