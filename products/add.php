<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    $success = $error = "";
    $sku_auto_generado = false;

    // Obtener productos para el select
    $products = $mysqli->query("SELECT product_id, product_name FROM products ORDER BY product_name");

    // Obtener categorías existentes para el select
    $categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name");
    
    // Obtener proveedores existentes para el select
    $proveedores = $mysqli->query("SELECT DISTINCT supplier FROM products WHERE supplier IS NOT NULL AND supplier != '' ORDER BY supplier");

    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_name = trim($_POST['product_name']);
        $sku = trim($_POST['sku']);
        $price = isset($_POST['price']) && $_POST['price'] !== '' ? floatval($_POST['price']) : 0.00;
        $quantity = intval($_POST['quantity']);
        
        // Manejar categoría (existente o nueva)
        $category_id = intval($_POST['category']);
        $new_category = trim($_POST['new_category'] ?? '');
        
        if (!empty($new_category)) {
            // Insertar nueva categoría
            $stmt = $mysqli->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $new_category);
            if ($stmt->execute()) {
                $category_id = $stmt->insert_id;
            }
            $stmt->close();
        }
        
        // Manejar proveedor (existente o nuevo)
        $supplier = trim($_POST['supplier']);
        $new_supplier = trim($_POST['new_supplier'] ?? '');
        if (!empty($new_supplier)) {
            $supplier = $new_supplier;
        }
        
        $description = isset($_POST['description']) ? trim($_POST['description']) : null;
        if ($description === '') $description = null;

        // Subida de imagen
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $img_tmp = $_FILES['image']['tmp_name'];
            $img_name = basename($_FILES['image']['name']);
            $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($img_ext, $allowed)) {
                $dir = '../uploads/products/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $new_name = uniqid('prod_') . '.' . $img_ext;
                $dest = $dir . $new_name;
                if (move_uploaded_file($img_tmp, $dest)) {
                    $image_path = 'uploads/products/' . $new_name;
                }
            }
        }

        // Si el SKU está vacío, generar uno automáticamente
        if ($sku === '') {
            // Buscar el último SKU AUTO generado
            $result = $mysqli->query("SELECT sku FROM products WHERE sku LIKE 'AUTO-%' ORDER BY product_id DESC LIMIT 1");
            $last_auto = $result && $result->num_rows > 0 ? $result->fetch_assoc()['sku'] : null;
            if ($last_auto && preg_match('/AUTO-(\\d+)/', $last_auto, $m)) {
                $next_num = intval($m[1]) + 1;
            } else {
                $next_num = 1;
            }
            $sku = 'AUTO-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
            $sku_auto_generado = true;
        }

        // Validación básica
        if ($product_name && $price >= 0 && $quantity >= 0) {
            $stmt = $mysqli->prepare("INSERT INTO products (product_name, sku, price, quantity, category_id, supplier, description, barcode, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdiissss", $product_name, $sku, $price, $quantity, $category_id, $supplier, $description, $barcode, $image_path);
            if ($stmt->execute()) {
                $new_product_id = $stmt->insert_id;
                // Si la categoría es 'Cables y Conectores', redirigir a registrar bobinas
                $cat_name = '';
                $cat_stmt = $mysqli->prepare("SELECT name FROM categories WHERE category_id = ?");
                $cat_stmt->bind_param("i", $category_id);
                $cat_stmt->execute();
                $cat_stmt->bind_result($cat_name);
                $cat_stmt->fetch();
                $cat_stmt->close();
                if (strtolower($cat_name) === 'cables y conectores') {
                    header("Location: ../bobinas/add.php?product_id=" . $new_product_id);
                    exit;
                }
                $success = "Producto agregado correctamente.";
            } else {
                $error = "Error en la base de datos: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Por favor, completa todos los campos correctamente.";
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar producto | Gestor de inventarios Alarmas y Cámaras de seguridad del sureste</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6fb;
        }
        .main-content {
            max-width: 520px;
            margin: 40px auto 0 auto;
            padding: 0;
        }
        .card-form {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 32px rgba(18,24,102,0.10);
            padding: 36px 32px 32px 32px;
            margin-bottom: 30px;
        }
        .card-form h2 {
            text-align: center;
            margin-bottom: 28px;
            color: #121866;
            font-size: 2.1rem;
            font-weight: 700;
        }
        .form-section {
            margin-bottom: 22px;
            padding-bottom: 18px;
            border-bottom: 1.5px solid #f0f0f0;
        }
        .form-section:last-child {
            border-bottom: none;
        }
        .form-label {
            font-weight: 600;
            color: #232a7c;
        }
        .input-group-text {
            background: #f4f6fb;
            border: none;
            color: #232a7c;
            font-size: 1.2rem;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid #cfd8dc;
            background: #f7f9fc;
            font-size: 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #121866;
            box-shadow: 0 0 0 2px #e3e6fa;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }
        .form-actions button {
            flex: 1;
        }
        .alert-auto-sku {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #e3f2fd;
            color: #1565c0;
            border: 1.5px solid #90caf9;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 18px;
            font-size: 1.05rem;
            font-weight: 500;
        }
        .alert-auto-sku svg {
            width: 26px;
            height: 26px;
            fill: #1565c0;
            flex-shrink: 0;
        }
        @media (max-width: 700px) {
            .main-content { max-width: 98vw; padding: 0 2vw; }
            .card-form { padding: 18px 6px; }
        }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="card-form">
            <h2><i class="bi bi-plus-circle"></i> Agregar producto</h2>
            <?php if ($sku_auto_generado): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle"></i>
                    El SKU se generó automáticamente: <b><?= htmlspecialchars($sku) ?></b>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
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
            <form action="" method="POST" id="formAgregarProducto" enctype="multipart/form-data">
                <div class="form-section">
                    <div class="mb-3">
                        <label for="product_name" class="form-label">Nombre del producto</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-box"></i></span>
                            <input type="text" class="form-control" name="product_name" id="product_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="sku" class="form-label">SKU</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                            <input type="text" class="form-control" name="sku" id="sku">
                        </div>
                        <div id="alertSkuRealtime" class="alert alert-info" style="display:none;margin-top:6px;">
                            <i class="bi bi-info-circle"></i>
                            Si dejas este campo vacío, el sistema generará un SKU automáticamente al guardar.
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="toggleBarcode">
                            <label class="form-check-label" for="toggleBarcode">Añadir código de barras</label>
                        </div>
                        <div id="barcodeField" style="display:none;">
                            <label for="barcode" class="form-label">Código de barras <span class="text-muted" style="font-weight:400;">(opcional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-upc"></i></span>
                                <input type="text" class="form-control" name="barcode" id="barcode" placeholder="Escanea o escribe el código de barras">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Imagen del producto <span class="text-muted" style="font-weight:400;">(opcional)</span></label>
                        <input type="file" class="form-control" name="image" id="image" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control" name="description" id="description" rows="2" placeholder="Descripción breve del producto"></textarea>
                    </div>
                </div>
                <div class="form-section">
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Precio</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                                <input type="number" step="0.01" class="form-control" name="price" id="price">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label">Cantidad</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-123"></i></span>
                                <input type="number" class="form-control" name="quantity" id="quantity" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-section">
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Categoría</label>
                            <select class="form-select" name="category" id="category">
                                <option value="">Selecciona una categoría</option>
                                <?php $categorias->data_seek(0); while ($row = $categorias->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($row['category_id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="mt-2">
                                <small class="text-muted">O escribe una nueva categoría:</small>
                                <input type="text" class="form-control mt-1" name="new_category" id="new_category" placeholder="Nueva categoría (opcional)">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="supplier" class="form-label">Proveedor</label>
                            <select class="form-select" name="supplier" id="supplier">
                                <option value="">Selecciona un proveedor</option>
                                <?php $proveedores->data_seek(0); while ($row = $proveedores->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($row['supplier']) ?>"><?= htmlspecialchars($row['supplier']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="mt-2">
                                <small class="text-muted">O escribe un nuevo proveedor:</small>
                                <input type="text" class="form-control mt-1" name="new_supplier" id="new_supplier" placeholder="Nuevo proveedor (opcional)">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-plus-circle"></i> Agregar producto
                    </button>
                    <a href="list.php" class="btn btn-secondary flex-fill">
                        <i class="bi bi-arrow-left"></i> Volver al listado
                    </a>
                </div>
            </form>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Alerta visual en tiempo real para el SKU
        const skuInput = document.getElementById('sku');
        const alertSku = document.getElementById('alertSkuRealtime');
        function checkSkuAlert() {
            if (skuInput.value.trim() === '') {
                alertSku.style.display = 'flex';
            } else {
                alertSku.style.display = 'none';
            }
        }
        skuInput.addEventListener('input', checkSkuAlert);
        checkSkuAlert(); // Mostrar alerta al cargar si está vacío

        // Mostrar/ocultar campo de código de barras
        const toggleBarcode = document.getElementById('toggleBarcode');
        const barcodeField = document.getElementById('barcodeField');
        toggleBarcode.addEventListener('change', function() {
            barcodeField.style.display = this.checked ? '' : 'none';
            if (!this.checked) {
                document.getElementById('barcode').value = '';
            }
        });

        // Manejar campos de nueva categoría y proveedor
        const categorySelect = document.getElementById('category');
        const newCategoryInput = document.getElementById('new_category');
        const supplierSelect = document.getElementById('supplier');
        const newSupplierInput = document.getElementById('new_supplier');

        // Cuando se selecciona una categoría existente, limpiar el campo de nueva categoría
        categorySelect.addEventListener('change', function() {
            if (this.value !== '') {
                newCategoryInput.value = '';
                newCategoryInput.disabled = true;
            } else {
                newCategoryInput.disabled = false;
            }
        });

        // Cuando se escribe en nueva categoría, limpiar el select
        newCategoryInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                categorySelect.value = '';
            }
        });

        // Cuando se selecciona un proveedor existente, limpiar el campo de nuevo proveedor
        supplierSelect.addEventListener('change', function() {
            if (this.value !== '') {
                newSupplierInput.value = '';
                newSupplierInput.disabled = true;
            } else {
                newSupplierInput.disabled = false;
            }
        });

        // Cuando se escribe en nuevo proveedor, limpiar el select
        newSupplierInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                supplierSelect.value = '';
            }
        });
    </script>
</body>
</html>
