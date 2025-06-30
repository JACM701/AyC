<?php
// Archivo donde se guardan las credenciales (puedes cambiarlo por base de datos si quieres)
$cred_file = __DIR__ . '/credenciales_tecnosinergia.json';

// Guardar credenciales si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');
    file_put_contents($cred_file, json_encode(['usuario' => $usuario, 'password' => $password]));
    $msg = 'Credenciales guardadas correctamente.';
}
// Leer credenciales actuales
$credenciales = file_exists($cred_file) ? json_decode(file_get_contents($cred_file), true) : ['usuario' => '', 'password' => ''];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configurar credenciales Tecnosinergia</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-container { max-width: 400px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(18,24,102,0.07); padding: 24px; }
        label { font-weight: 600; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Credenciales Tecnosinergia</h2>
        <?php if (!empty($msg)): ?>
            <div class="alert alert-success"> <?= htmlspecialchars($msg) ?> </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="usuario">Usuario:</label>
                <input type="text" name="usuario" id="usuario" class="form-control" value="<?= htmlspecialchars($credenciales['usuario']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="password">Contraseña:</label>
                <input type="password" name="password" id="password" class="form-control" value="<?= htmlspecialchars($credenciales['password']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</body>
</html> 