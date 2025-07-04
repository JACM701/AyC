<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Obtener productos para el select
$products = $mysqli->query("SELECT product_id, product_name FROM products ORDER BY product_name");

// Obtener tipos de movimiento para el select
$movement_types = $mysqli->query("SELECT Id_tipo, nombre FROM movement_type ORDER BY nombre");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $movement_type_id = isset($_POST['movement_type_id']) ? intval($_POST['movement_type_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

    if ($product_id && $movement_type_id && $quantity > 0) {
        $stmt = $mysqli->prepare("INSERT INTO movements (product_id, movement_type_id, quantity, movement_date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iii", $product_id, $movement_type_id, $quantity);
        $stmt->execute();
        if ($stmt->error) {
            $error = "Error al registrar movimiento: " . $stmt->error;
        } else {
            $success = "Movimiento registrado correctamente.";
        }
        $stmt->close();
    } else {
        $error = "Por favor, completa todos los campos correctamente.";
    }
    // Recargar selects tras el POST
    $products = $mysqli->query("SELECT product_id, product_name FROM products ORDER BY product_name");
    $movement_types = $mysqli->query("SELECT Id_tipo, nombre FROM movement_type ORDER BY nombre");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar movimiento | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content { max-width: 440px; margin: 40px auto 0 auto; }
        .main-content h2 { margin-bottom: 22px; }
        .form-group { margin-bottom: 14px; }
        .form-group input, .form-group select { margin-top: 4px; }
        .form-actions { display: flex; gap: 10px; margin-top: 18px; }
        .form-actions button { flex: 1; }
        @media (max-width: 900px) { .main-content { max-width: 98vw; padding: 0 2vw; } }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h2>Registrar movimiento de inventario</h2>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <form action="" method="POST" autocomplete="off">
        <div class="mb-3">
            <label for="product_id" class="form-label">Producto:</label>
            <select name="product_id" id="product_id" class="form-select" required>
                <option value="">-- Selecciona un producto --</option>
                <?php while ($row = $products->fetch_assoc()): ?>
                    <option value="<?= $row['product_id'] ?>" <?= (isset($_POST['product_id']) && $_POST['product_id'] == $row['product_id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['product_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="movement_type_id" class="form-label">Tipo de movimiento:</label>
            <select name="movement_type_id" id="movement_type_id" class="form-select" required>
                <option value="">-- Selecciona un tipo --</option>
                <?php while ($mt = $movement_types->fetch_assoc()): ?>
                    <option value="<?= $mt['Id_tipo'] ?>" <?= (isset($_POST['movement_type_id']) && $_POST['movement_type_id'] == $mt['Id_tipo']) ? 'selected' : '' ?>><?= htmlspecialchars($mt['nombre']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="quantity" class="form-label">Cantidad:</label>
            <input type="number" name="quantity" id="quantity" class="form-control" min="1" step="1" required value="<?= isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '' ?>">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="bi bi-plus-circle"></i> Registrar movimiento
            </button>
            <a href="index.php" class="btn btn-secondary flex-fill">
                <i class="bi bi-arrow-left"></i> Volver al listado
            </a>
        </div>
    </form>
</main>
<script src="../assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>