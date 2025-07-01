<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    $success = $error = "";

    // Obtener productos para el select
    $products = $mysqli->query("SELECT product_id, product_name FROM products ORDER BY product_name");

    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_id = intval($_POST['product_id']);
        $movement_type = $_POST['movement_type'];
        $quantity = intval($_POST['quantity']);

        if ($product_id && in_array($movement_type, ['in', 'out']) && $quantity > 0) {
            // Consultar cantidad actual
            $stmt = $mysqli->prepare("SELECT quantity FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $stmt->bind_result($current_quantity);
            $stmt->fetch();
            $stmt->close();

            $new_quantity = $movement_type === 'in'
                ? $current_quantity + $quantity
                : $current_quantity - $quantity;

            if ($new_quantity < 0) {
                $error = "Stock insuficiente para este movimiento.";
            } else {
                // Insertar movimiento
                $stmt = $mysqli->prepare("INSERT INTO movements (product_id, movement_type, quantity, movement_date) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("isi", $product_id, $movement_type, $quantity);
                $stmt->execute();
                $stmt->close();

                // Actualizar cantidad de producto
                $stmt = $mysqli->prepare("UPDATE products SET quantity = ? WHERE product_id = ?");
                $stmt->bind_param("ii", $new_quantity, $product_id);
                $stmt->execute();
                $stmt->close();

                $success = "Movimiento registrado y cantidad actualizada.";
            }
        } else {
            $error = "Por favor, selecciona un producto, tipo y cantidad válidos.";
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar movimiento | Gestor de inventarios Alarmas y Cámaras de seguridad del sureste</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content {
            max-width: 440px;
            margin: 40px auto 0 auto;
        }
        .main-content h2 {
            margin-bottom: 22px;
        }
        .form-group {
            margin-bottom: 14px;
        }
        .form-group input, .form-group select {
            margin-top: 4px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }
        .form-actions button {
            flex: 1;
        }
        @media (max-width: 900px) {
            .main-content { max-width: 98vw; padding: 0 2vw; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <h2>Registrar movimiento de inventario</h2>
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
        <form action="" method="POST" id="formMovimiento">
            <div class="mb-3">
                <label for="barcode" class="form-label">Código de barras (escanea aquí):</label>
                <input type="text" class="form-control" id="barcode" placeholder="Escanea el código de barras aquí">
                <div id="barcodePreview" class="form-text text-success"></div>
            </div>
            <div class="mb-3">
                <label for="product_id" class="form-label">Producto:</label>
                <select name="product_id" id="product_id" class="form-select" required>
                    <option value="">-- Selecciona un producto --</option>
                    <?php while ($row = $products->fetch_assoc()): ?>
                        <option value="<?= $row['product_id'] ?>"><?= htmlspecialchars($row['product_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="movement_type" class="form-label">Tipo de movimiento:</label>
                <select name="movement_type" id="movement_type" class="form-select" required>
                    <option value="in">Entrada</option>
                    <option value="out">Salida</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="quantity" class="form-label">Cantidad:</label>
                <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
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
    <script>
        // Solo enfocar el campo de código de barras al cargar la página
        const barcodeInput = document.getElementById('barcode');
        const barcodePreview = document.getElementById('barcodePreview');
        barcodeInput.focus();
        // Mostrar el código de barras capturado en tiempo real
        barcodeInput.addEventListener('input', function() {
            if (barcodeInput.value.trim() !== '') {
                barcodePreview.textContent = 'Código capturado: ' + barcodeInput.value;
            } else {
                barcodePreview.textContent = '';
            }
        });
        // Limpiar el campo al presionar Enter (usualmente el escáner lo envía al final)
        barcodeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                barcodePreview.textContent = 'Código capturado: ' + barcodeInput.value;
                barcodeInput.value = '';
                setTimeout(() => { barcodePreview.textContent = ''; }, 2000);
            }
        });
    </script>
</body>
</html>