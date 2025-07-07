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

    // ID de la categoría 'Cables y Conectores'
    $bobina_category_id = 13;

    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_name = trim($_POST['product_name']);
        $sku = trim($_POST['sku']);
        $price = isset($_POST['price']) && $_POST['price'] !== '' ? floatval($_POST['price']) : 0.00;
        $quantity = intval($_POST['quantity']);
        
        // Nuevo: tipo de gestión
        $tipo_gestion = isset($_POST['tipo_gestion']) ? $_POST['tipo_gestion'] : 'normal';
        $allowed_tipos = ['normal','bobina','bolsa','par'];
        if (!in_array($tipo_gestion, $allowed_tipos)) $tipo_gestion = 'normal';
        
        // Manejar categoría (existente o nueva)
        $category_id = isset($_POST['category']) && $_POST['category'] !== '' ? intval($_POST['category']) : null;
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
        $supplier = isset($_POST['supplier']) ? trim($_POST['supplier']) : null;
        if ($supplier === '') $supplier = null;
        $new_supplier = trim($_POST['new_supplier'] ?? '');
        if (!empty($new_supplier)) {
            $supplier = $new_supplier;
        }
        
        $description = isset($_POST['description']) ? trim($_POST['description']) : null;
        if ($description === '') $description = null;

        // Procesar código de barras
        $barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : null;
        if ($barcode === '') $barcode = null;

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
        if ($product_name && $price >= 0 && $quantity >= 0 && $quantity > 0) {
            $stmt = $mysqli->prepare("INSERT INTO products (product_name, sku, price, quantity, category_id, supplier, description, barcode, image, tipo_gestion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdiisssss", $product_name, $sku, $price, $quantity, $category_id, $supplier, $description, $barcode, $image_path, $tipo_gestion);
            if ($stmt->execute()) {
                $new_product_id = $stmt->insert_id;
                // Si el tipo de gestión es bobina, mostrar opción de registrar bobinas
                if ($tipo_gestion === 'bobina') {
                    $success = "Producto tipo bobina agregado correctamente. Ahora puedes registrar las bobinas individuales.";
                } else {
                    $success = "Producto agregado correctamente.";
                }
            } else {
                $error = "Error en la base de datos: " . $stmt->error;
            }
            $stmt->close();
        } else {
            if (!$product_name) {
                $error = "El nombre del producto es obligatorio.";
            } elseif ($price < 0) {
                $error = "El precio no puede ser negativo.";
            } elseif ($quantity <= 0) {
                $error = "La cantidad inicial debe ser mayor a 0.";
            } else {
                $error = "Por favor, completa todos los campos correctamente.";
            }
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
        .form-wrapper {
            max-width: 520px;
            margin: 40px auto 0 auto;
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
        .form-check {
            border: 1.5px solid #e3e6f0;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
            background: #f7f9fc;
        }
        .form-check:hover {
            border-color: #121866;
            background: #e3e6fa;
        }
        .form-check-input:checked + .form-check-label {
            color: #121866;
            font-weight: 600;
        }
        .form-check-label {
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 4px;
        }
        .form-check-label i {
            font-size: 1.2rem;
            margin-right: 6px;
            vertical-align: middle;
            color: #232a7c;
        }
        .form-check-input:checked ~ .form-check-label i {
            color: #121866;
        }
        .form-switch {
            background: none !important;
            border: none !important;
            padding: 0 !important;
        }
        @media (max-width: 700px) {
            .form-wrapper { max-width: 98vw; padding: 0 2vw; }
            .card-form { padding: 18px 6px; }
            .form-check { padding: 12px; }
        }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="form-wrapper">
            <div class="card-form">
                <h2><i class="bi bi-plus-circle"></i> Agregar producto</h2>
                <?php if ($sku_auto_generado): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="bi bi-info-circle"></i>
                        El SKU se generó automáticamente: <b><?= htmlspecialchars($sku) ?></b>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <div id="alertSkuRealtime" class="alert alert-info" style="display:none;margin-bottom:18px;">
                    <i class="bi bi-info-circle"></i>
                    Si dejas este campo vacío, el sistema generará un SKU automáticamente al guardar.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex flex-column align-items-start gap-2" role="alert">
                        <div>
                            <i class="bi bi-check-circle"></i>
                            <?= $success ?>
                        </div>
                        <?php if (strpos($success, 'bobina') !== false): ?>
                            <div class="d-flex gap-2 mt-2">
                                <a href="../bobinas/add.php?product_id=<?= $new_product_id ?? '' ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> Registrar bobinas
                                </a>
                                <a href="list.php" class="btn btn-success btn-sm">
                                    <i class="bi bi-list"></i> Ver todos los productos
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="d-flex gap-2 mt-2">
                                <a href="add.php" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> Agregar otro producto
                                </a>
                                <a href="list.php" class="btn btn-success btn-sm">
                                    <i class="bi bi-list"></i> Ver todos los productos
                                </a>
                            </div>
                        <?php endif; ?>
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
                    
                    <!-- SECCIÓN 1: INFORMACIÓN BÁSICA -->
                    <div class="form-section">
                        <h6 class="form-label mb-3"><i class="bi bi-box"></i> Información Básica</h6>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="product_name" class="form-label">Nombre del producto *</label>
                                    <input type="text" class="form-control" name="product_name" id="product_name" required placeholder="Ej: Cámara Bullet 5MP">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="sku" class="form-label">SKU</label>
                                    <input type="text" class="form-control" name="sku" id="sku" placeholder="Dejar vacío para auto-generar">
                                    <small class="text-muted">Se genera automáticamente si está vacío</small>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" id="description" rows="2" placeholder="Descripción breve del producto"></textarea>
                        </div>
                    </div>

                    <!-- SECCIÓN 2: PRECIOS Y CANTIDAD -->
                    <div class="form-section">
                        <h6 class="form-label mb-3"><i class="bi bi-currency-dollar"></i> Precios y Cantidad</h6>
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="price" class="form-label">Precio *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                                        <input type="number" step="0.01" class="form-control" name="price" id="price" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Cantidad inicial *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-123"></i></span>
                                        <input type="number" class="form-control" name="quantity" id="quantity" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <input class="form-check-input" type="checkbox" id="toggleBarcode">
                                        <label class="form-check-label mb-0" for="toggleBarcode">Agregar código de barras</label>
                                    </div>
                                    <div id="barcodeField" style="display:none;">
                                        <input type="text" class="form-control" name="barcode" id="barcode" placeholder="Código de barras">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 3: CATEGORÍA Y PROVEEDOR -->
                    <div class="form-section">
                        <h6 class="form-label mb-3"><i class="bi bi-tags"></i> Categoría y Proveedor</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Categoría</label>
                                    <select class="form-select" name="category" id="category">
                                        <option value="">Selecciona una categoría</option>
                                        <?php $categorias->data_seek(0); while ($row = $categorias->fetch_assoc()): ?>
                                            <option value="<?= htmlspecialchars($row['category_id']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <small class="text-muted">O escribe una nueva: <input type="text" class="form-control mt-1" name="new_category" id="new_category" placeholder="Nueva categoría"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="supplier" class="form-label">Proveedor</label>
                                    <select class="form-select" name="supplier" id="supplier">
                                        <option value="">Selecciona un proveedor</option>
                                        <?php $proveedores->data_seek(0); while ($row = $proveedores->fetch_assoc()): ?>
                                            <option value="<?= htmlspecialchars($row['supplier']) ?>"><?= htmlspecialchars($row['supplier']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <small class="text-muted">O escribe uno nuevo: <input type="text" class="form-control mt-1" name="new_supplier" id="new_supplier" placeholder="Nuevo proveedor"></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 4: TIPO DE GESTIÓN -->
                    <div class="form-section">
                        <h6 class="form-label mb-3"><i class="bi bi-gear"></i> Tipo de Gestión</h6>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_gestion" id="tipo_normal" value="normal" checked>
                                <label class="form-check-label" for="tipo_normal"><i class="bi bi-box"></i> Normal (por unidades)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_gestion" id="tipo_bobina" value="bobina">
                                <label class="form-check-label" for="tipo_bobina"><i class="bi bi-receipt"></i> Bobina (por metros)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_gestion" id="tipo_bolsa" value="bolsa">
                                <label class="form-check-label" for="tipo_bolsa"><i class="bi bi-bag"></i> Bolsa</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_gestion" id="tipo_par" value="par">
                                <label class="form-check-label" for="tipo_par"><i class="bi bi-2-circle"></i> Par</label>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 5: CONFIGURACIÓN DE BOBINA (oculta por defecto) -->
                    <div class="form-section" id="bobinaSection" style="display:none;">
                        <h6 class="form-label mb-3"><i class="bi bi-receipt"></i> Configuración de Bobina</h6>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Producto tipo bobina:</strong> Después de crear el producto, podrás registrar las bobinas individuales con sus metros.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="metros_iniciales" class="form-label">Metros sugeridos por bobina</label>
                                    <input type="number" step="0.01" min="0.01" class="form-control" name="metros_iniciales" id="metros_iniciales" placeholder="Ej: 100.00">
                                    <small class="text-muted">Solo para referencia</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="identificador" class="form-label">Formato de identificador</label>
                                    <input type="text" class="form-control" name="identificador" id="identificador" placeholder="Ej: Bobina #1">
                                    <small class="text-muted">Solo para referencia</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 6: IMAGEN -->
                    <div class="form-section">
                        <h6 class="form-label mb-3"><i class="bi bi-image"></i> Imagen del Producto</h6>
                        <div class="mb-3">
                            <input type="file" class="form-control" name="image" id="image" accept="image/*">
                            <small class="text-muted">Opcional - Formatos: JPG, PNG, GIF</small>
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
                alertSku.style.display = 'block';
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

        // Mostrar/ocultar campos de bobina según tipo_gestion
        const tipoGestionRadios = document.querySelectorAll('input[name="tipo_gestion"]');
        const bobinaSection = document.getElementById('bobinaSection');
        
        tipoGestionRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'bobina') {
                    bobinaSection.style.display = '';
                } else {
                    bobinaSection.style.display = 'none';
                    document.getElementById('metros_iniciales').value = '';
                    document.getElementById('identificador').value = '';
                }
            });
        });
        
        // Al cargar, mostrar si corresponde
        const checkedRadio = document.querySelector('input[name="tipo_gestion"]:checked');
        if (checkedRadio && checkedRadio.value === 'bobina') {
            bobinaSection.style.display = '';
        }
    </script>
</body>
</html>
