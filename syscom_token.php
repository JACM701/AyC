<?php
$client_id = 'oC5GPC7PRju3Ht1fIBngCbRBmspMMHx1';
$client_secret = 'A2mV7CE1mE2Qme4Rij3msB7P3o791oBZ0cjixP9K';

$token_file = __DIR__ . '/syscom_access_token.json';

// ¿Ya hay un token guardado y sigue vigente?
if (file_exists($token_file)) {
    $data = json_decode(file_get_contents($token_file), true);
    if ($data && isset($data['access_token'], $data['expires_at']) && $data['expires_at'] > time()) {
        // Token válido, úsalo
        $access_token = $data['access_token'];
    } else {
        $access_token = null;
    }
} else {
    $access_token = null;
}

// Si no hay token válido, pide uno nuevo
if (!$access_token) {
    $url = "https://developers.syscom.mx/oauth/token";
    $post_data = [
        "grant_type" => "client_credentials",
        "client_id" => $client_id,
        "client_secret" => $client_secret
    ];

    $options = [
        "http" => [
            "header"  => "Content-type: application/x-www-form-urlencoded\r\n",
            "method"  => "POST",
            "content" => http_build_query($post_data),
            "ignore_errors" => true
        ],
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
        die('Error al obtener el token');
    }

    $res = json_decode($result, true);
    if (!isset($res['access_token'])) {
        die('Respuesta inválida de la API de Syscom');
    }
    $access_token = $res['access_token'];
    $expires_in = $res['expires_in'] ?? 3600;
    $expires_at = time() + $expires_in - 60; // 1 minuto de margen

    // Guarda el token y su expiración
    file_put_contents($token_file, json_encode([
        'access_token' => $access_token,
        'expires_at' => $expires_at
    ]));
}

// Ahora $access_token tiene el token válido para usar en tus peticiones
// Puedes incluir este archivo donde lo necesites y usar $access_token
?>