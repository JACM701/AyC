<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Obtener el producto
if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
    die('Producto no especificado.');
}
$product_id = intval($_GET['product_id']);
$stmt = $mysqli->prepare('SELECT product_name FROM products WHERE product_id = ?');
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->bind_result($product_name);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metros_iniciales = floatval($_POST['metros_iniciales']);
    $identificador = trim($_POST['identificador']);
    if ($metros_iniciales > 0) {
        $stmt = $mysqli->prepare('INSERT INTO bobinas (product_id, metros_iniciales, metros_actuales, identificador) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('idds', $product_id, $metros_iniciales, $metros_iniciales, $identificador);
        if ($stmt->execute()) {
            $success = 'Bobina registrada correctamente.';
        } else {
            $error = 'Error al registrar la bobina: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = 'Los metros iniciales deben ser mayores a 0.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar bobina | <?= htmlspecialchars($product_name) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content { max-width: 440px; margin: 40px auto 0 auto; }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h2>Registrar bobina para:<br><small><?= htmlspecialchars($product_name) ?></small></h2>
    <?php if ($success): ?>
        <div class="alert alert-success d-flex flex-column align-items-start gap-2">
            <div><i class="bi bi-check-circle"></i> <?= $success ?></div>
            <div class="d-flex gap-2 mt-2">
                <a href="add.php?product_id=<?= $product_id ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Agregar otra bobina</a>
                <a href="../products/edit.php?id=<?= $product_id ?>" class="btn btn-success btn-sm"><i class="bi bi-arrow-left"></i> Volver al producto</a>
            </div>
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= $error ?></div>
    <?php endif; ?>
    <form action="" method="POST" class="mb-3">
        <div class="mb-3">
            <label for="metros_iniciales" class="form-label">Metros iniciales de la bobina</label>
            <input type="number" step="0.01" min="0.01" class="form-control" name="metros_iniciales" id="metros_iniciales" required>
        </div>
        <div class="mb-3">
            <label for="identificador" class="form-label">Identificador (opcional)</label>
            <input type="text" class="form-control" name="identificador" id="identificador" placeholder="Ej: Bobina #1, Lote 2024, etc.">
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Registrar bobina</button>
    </form>
</main>
<script src="../assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 