<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

// Set JSON header first
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($success, $message = '', $data = []) {
    $response = array_merge(['success' => $success], $message ? ['message' => $message] : [], $data);
    echo json_encode($response);
    exit;
}

// Debug logging (commented out for production)
// error_log("=== AJAX ADD SERVICIO DEBUG ===");
// error_log("POST data: " . print_r($_POST, true));
// error_log("FILES data: " . print_r($_FILES, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Método no permitido');
}

$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$precio = floatval($_POST['precio'] ?? 0);

// Validaciones
if (empty($nombre)) {
    sendJsonResponse(false, 'El nombre del servicio es obligatorio');
}

if ($precio < 0) {
    sendJsonResponse(false, 'El precio debe ser mayor o igual a 0');
}

// Verificar si ya existe un servicio con ese nombre
$stmt = $mysqli->prepare("SELECT servicio_id FROM servicios WHERE nombre = ? LIMIT 1");
$stmt->bind_param('s', $nombre);
$stmt->execute();
$stmt->bind_result($existing_id);
if ($stmt->fetch()) {
    $stmt->close();
    sendJsonResponse(false, 'Ya existe un servicio con ese nombre.');
}
$stmt->close();

// Crear carpeta de servicios si no existe
$upload_dir = '../uploads/services/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$imagen = '';

// Procesar subida de imagen si existe
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['imagen'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (in_array($file['type'], $allowed_types)) {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'servicio_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    sendJsonResponse(false, 'Error al subir la imagen');
}
$imagen = $filename;
    } else {
    sendJsonResponse(false, 'Tipo de archivo no permitido. Solo JPG, PNG, GIF, WEBP');
}
}

// Insertar servicio en la base de datos
// Primero intentar con is_active, si falla, intentar sin is_active
$stmt = $mysqli->prepare("INSERT INTO servicios (nombre, descripcion, categoria, precio, imagen, is_active) VALUES (?, ?, ?, ?, ?, ?)");
$is_active = 1; // Crear como activo (alta rápida)

if ($stmt) {
    $stmt->bind_param('sssdsi', $nombre, $descripcion, $categoria, $precio, $imagen, $is_active);
    
    if ($stmt->execute()) {
        $servicio_id = $stmt->insert_id;
        $stmt->close();
        
        sendJsonResponse(true, '', [
            'servicio_id' => $servicio_id,
            'imagen' => $imagen
        ]);
    } else {
        $stmt->close();
        // Si falla, intentar sin is_active (por si el campo no existe)
        $stmt2 = $mysqli->prepare("INSERT INTO servicios (nombre, descripcion, categoria, precio, imagen) VALUES (?, ?, ?, ?, ?)");
        if ($stmt2) {
            $stmt2->bind_param('sssds', $nombre, $descripcion, $categoria, $precio, $imagen);
            if ($stmt2->execute()) {
                $servicio_id = $stmt2->insert_id;
                $stmt2->close();
                
                // Intentar actualizar is_active después de la inserción
                $update_stmt = $mysqli->prepare("UPDATE servicios SET is_active = ? WHERE servicio_id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param('ii', $is_active, $servicio_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                
                sendJsonResponse(true, '', [
                    'servicio_id' => $servicio_id,
                    'imagen' => $imagen
                ]);
            } else {
                $error = $stmt2->error;
                $stmt2->close();
                sendJsonResponse(false, 'Error al crear servicio: ' . $error);
            }
        } else {
            sendJsonResponse(false, 'Error en la consulta SQL: ' . $mysqli->error);
        }
    }
} else {
    sendJsonResponse(false, 'Error preparando consulta: ' . $mysqli->error);
}
?>
