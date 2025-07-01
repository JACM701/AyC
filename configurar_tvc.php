<?php
require_once '../auth/middleware.php';

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $enabled = isset($_POST['enabled']) ? true : false;
    
    if (!empty($email) && !empty($password)) {
        $config_content = "<?php
// Configuración para TVC.mx
return [
    'tvc_username' => '" . addslashes($email) . "',
    'tvc_password' => '" . addslashes($password) . "',
    'tvc_login_url' => 'https://tvc.mx/login',
    'tvc_enabled' => " . ($enabled ? 'true' : 'false') . "
];
?>";
        
        if (file_put_contents('config_tvc.php', $config_content)) {
            $success = "Configuración de TVC.mx guardada correctamente.";
        } else {
            $error = "Error al guardar la configuración.";
        }
    } else {
        $error = "Por favor, completa todos los campos.";
    }
}

// Leer configuración actual si existe
$current_config = null;
if (file_exists('config_tvc.php')) {
    $current_config = include 'config_tvc.php';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configurar TVC.mx | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <h2><i class="bi bi-gear"></i> Configurar TVC.mx</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i>
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person-circle"></i> Credenciales de TVC.mx</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email de TVC.mx:</label>
                                <input type="text" class="form-control" name="email" id="email" placeholder="Usuario o correo de TVC.mx"
                                       value="" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña de TVC.mx:</label>
                                <input type="password" class="form-control" name="password" id="password" value="" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="enabled" id="enabled" 
                                       <?= ($current_config['tvc_enabled'] ?? false) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enabled">
                                    Habilitar login automático en TVC.mx
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Guardar configuración
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Información</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>¿Para qué sirve esto?</strong></p>
                        <ul>
                            <li>Permite obtener precios reales de TVC.mx</li>
                            <li>El sistema iniciará sesión automáticamente</li>
                            <li>Las credenciales se guardan de forma segura</li>
                            <li>Solo se usan para el comparador de precios</li>
                        </ul>
                        <div class="alert alert-warning">
                            <i class="bi bi-shield-exclamation"></i>
                            <strong>Importante:</strong> Asegúrate de usar una cuenta válida de TVC.mx.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="add.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al formulario
            </a>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 