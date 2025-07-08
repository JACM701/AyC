<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    $success = $error = "";
    $product = null;
    $sku_auto_generado = false;

    // Get product ID from URL
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header("Location: list.php");
        exit;
    }

    $product_id = intval($_GET['id']);

    // Fetch existing product
    $stmt = $mysqli->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if (!$product) {
        header("Location: list.php");
        exit;
    }

    // Obtener bobinas asociadas a este producto
    $bobinas = $mysqli->query("SELECT * FROM bobinas WHERE product_id = $product_id ORDER BY fecha_ingreso ASC");

    // Obtener categorías existentes para el select
    $categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name");
    
    // Obtener proveedores existentes para el select
    $proveedores = $mysqli->query("SELECT DISTINCT supplier FROM products WHERE supplier IS NOT NULL AND supplier != '' ORDER BY supplier");

    // Obtener tipo de gestión actual
    $tipo_gestion_actual = isset($product['tipo_gestion']) ? $product['tipo_gestion'] : 'normal';
    $allowed_tipos = ['normal','bobina','bolsa','par','kit'];
    if (!in_array($tipo_gestion_actual, $allowed_tipos)) $tipo_gestion_actual = 'normal';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_name = trim($_POST['product_name']);
        $sku = trim($_POST['sku']);
        $barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : null;
        $price = isset($_POST['price']) && $_POST['price'] !== '' ? floatval($_POST['price']) : 0.00;
        $quantity = intval($_POST['quantity']);
        
        // Manejar categoría (existente o nueva)
        $category_id = intval($_POST['category']);
        $new_category = trim($_POST['new_category']);
        
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
        $new_supplier = trim($_POST['new_supplier']);
        if (!empty($new_supplier)) {
            $supplier = $new_supplier;
        }
        
        $description = isset($_POST['description']) ? trim($_POST['description']) : null;
        if ($description === '') $description = null;

        // Subida de imagen
        $image_path = $product['image'] ?? null;
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

        // Nuevo: tipo de gestión
        $tipo_gestion = isset($_POST['tipo_gestion']) ? $_POST['tipo_gestion'] : 'normal';
        if (!in_array($tipo_gestion, $allowed_tipos)) $tipo_gestion = 'normal';

        // Si el SKU está vacío, generar uno automáticamente
        if ($sku === '') {
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

        if ($product_name && $price >= 0 && $quantity >= 0) {
            $stmt = $mysqli->prepare("UPDATE products SET product_name = ?, sku = ?, price = ?, quantity = ?, category_id = ?, supplier = ?, description = ?, barcode = ?, image = ?, tipo_gestion = ? WHERE product_id = ?");
            $stmt->bind_param("ssdiisssssi", $product_name, $sku, $price, $quantity, $category_id, $supplier, $description, $barcode, $image_path, $tipo_gestion, $product_id);

            if ($stmt->execute()) {
                $success = "Producto actualizado correctamente.";
                // Refresh product data
                $product['product_name'] = $product_name;
                $product['sku'] = $sku;
                $product['price'] = $price;
                $product['quantity'] = $quantity;
                $product['category_id'] = $category_id;
                $product['supplier'] = $supplier;
                $product['description'] = $description;
                $product['barcode'] = $barcode;
                $product['image'] = $image_path;
                $product['tipo_gestion'] = $tipo_gestion;
                // Si el tipo de gestión cambió de bobina a otro y hay bobinas asociadas, eliminarlas si el usuario lo confirmó
                if ($tipo_gestion_actual === 'bobina' && $tipo_gestion !== 'bobina') {
                    // Si el usuario confirmó la eliminación
                    if (isset($_POST['confirmar_eliminar_bobinas']) && $_POST['confirmar_eliminar_bobinas'] === '1') {
                        $mysqli->query("DELETE FROM bobinas WHERE product_id = $product_id");
                    }
                }
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
            <title>Editar producto</title>
            <link rel="stylesheet" href="../assets/css/style.css">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                .form-container {
                    max-width: 440px;
                    margin: 60px auto;
                    padding: 36px 32px 32px 32px;
                    background: #fff;
                    border-radius: 14px;
                    box-shadow: 0 8px 32px rgba(18,24,102,0.10);
                }
                h2 {
                    text-align: center;
                    margin-bottom: 24px;
                    color: #121866;
                    font-size: 2rem;
                    font-weight: 700;
                }
                form label {
                    display: block;
                    margin: 12px 0 6px;
                    font-weight: 600;
                    color: #121866;
                }
                form input {
                    width: 100%;
                    padding: 11px;
                    border: 1.5px solid #cfd8dc;
                    border-radius: 7px;
                    font-size: 15px;
                    margin-bottom: 4px;
                    background: #f7f9fc;
                    transition: border 0.2s;
                }
                form input:focus {
                    border-color: #121866;
                    outline: none;
                }
                button {
                    width: 100%;
                    margin-top: 22px;
                    padding: 12px;
                    background-color: #121866;
                    color: #fff;
                    border: none;
                    border-radius: 7px;
                    font-size: 17px;
                    font-weight: 600;
                    cursor: pointer;
                    box-shadow: 0 2px 8px rgba(18,24,102,0.08);
                    transition: background-color 0.2s, box-shadow 0.2s;
                }
                button:hover {
                    background-color: #232a7c;
                    box-shadow: 0 4px 16px rgba(18,24,102,0.13);
                }
                .acciones-form {
                    display: flex;
                    gap: 10px;
                    margin-top: 18px;
                }
                .acciones-form button {
                    flex: 1;
                }
                .success, .error {
                    margin-bottom: 18px;
                }
                @media (max-width: 700px) {
                    .form-container { max-width: 98vw; padding: 18px 6px; }
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
            </style>
        </head>
        <body>
            <div class="form-container">
                <h2>Editar producto</h2>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex flex-column align-items-start gap-2" role="alert">
                        <div>
                            <i class="bi bi-check-circle"></i>
                            <?= $success ?>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <a href="list.php" class="btn btn-success btn-sm"><i class="bi bi-list"></i> Volver al listado</a>
                            <a href="edit.php?id=<?= $product_id ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i> Seguir editando</a>
                        </div>
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($sku_auto_generado): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="bi bi-info-circle"></i>
                        El SKU se generó automáticamente: <b><?= htmlspecialchars($sku) ?></b>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="product_name" class="form-label">Nombre del producto:</label>
                        <input type="text" class="form-control" name="product_name" id="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="sku" class="form-label">SKU:</label>
                        <input type="text" class="form-control" name="sku" id="sku" value="<?= htmlspecialchars($product['sku']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="barcode" class="form-label">Código de barras <span class="text-muted" style="font-weight:400;">(opcional)</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-upc"></i></span>
                            <input type="text" class="form-control" name="barcode" id="barcode" value="<?= isset($product['barcode']) ? htmlspecialchars($product['barcode']) : '' ?>" placeholder="Escanea o escribe el código de barras">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">Imagen del producto <span class="text-muted" style="font-weight:400;">(opcional)</span></label>
                        <?php if (isset($product['image']) && $product['image']): ?>
                            <div class="mb-2">
                                <img src="<?= htmlspecialchars($product['image']) ?>" alt="Imagen actual" style="width:48px;height:48px;object-fit:cover;border-radius:6px;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                                <span class="text-muted ms-2">Imagen actual</span>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="image" id="image" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label for="price" class="form-label">Precio:</label>
                        <input type="number" step="0.01" class="form-control" name="price" id="price" value="<?= $product['price'] ?>">
                    </div>

                    <div class="mb-3">
                        <label for="quantity" class="form-label">Cantidad:</label>
                        <input type="number" class="form-control" name="quantity" id="quantity" value="<?= $product['quantity'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Categoría:</label>
                        <select class="form-select" name="category" id="category">
                            <option value="">Selecciona una categoría</option>
                            <?php 
                            // Reset the result set
                            $categorias->data_seek(0);
                            while ($categoria = $categorias->fetch_assoc()): 
                            ?>
                                <option value="<?= htmlspecialchars($categoria['category_id']) ?>" <?= htmlspecialchars($product['category_id']) === htmlspecialchars($categoria['category_id']) ? 'selected' : '' ?>><?= htmlspecialchars($categoria['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="mt-2">
                            <small class="text-muted">O escribe una nueva categoría:</small>
                            <input type="text" class="form-control mt-1" name="new_category" id="new_category" placeholder="Nueva categoría (opcional)">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="supplier" class="form-label">Proveedor:</label>
                        <select class="form-select" name="supplier" id="supplier">
                            <option value="">Selecciona un proveedor</option>
                            <?php 
                            // Reset the result set
                            $proveedores->data_seek(0);
                            while ($proveedor = $proveedores->fetch_assoc()): 
                            ?>
                                <option value="<?= htmlspecialchars($proveedor['supplier']) ?>" <?= htmlspecialchars($product['supplier']) === htmlspecialchars($proveedor['supplier']) ? 'selected' : '' ?>><?= htmlspecialchars($proveedor['supplier']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="mt-2">
                            <small class="text-muted">O escribe un nuevo proveedor:</small>
                            <input type="text" class="form-control mt-1" name="new_supplier" id="new_supplier" placeholder="Nuevo proveedor (opcional)">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción:</label>
                        <textarea class="form-control" name="description" id="description" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="tipo_gestion" class="form-label">Tipo de gestión de inventario</label>
                        <select class="form-select" name="tipo_gestion" id="tipo_gestion" required>
                            <option value="normal" <?= $tipo_gestion_actual === 'normal' ? 'selected' : '' ?>>Normal (por unidades)</option>
                            <option value="bobina" <?= $tipo_gestion_actual === 'bobina' ? 'selected' : '' ?>>Bobina (metros)</option>
                            <option value="bolsa" <?= $tipo_gestion_actual === 'bolsa' ? 'selected' : '' ?>>Bolsa</option>
                            <option value="par" <?= $tipo_gestion_actual === 'par' ? 'selected' : '' ?>>Par</option>
                            <option value="kit" <?= $tipo_gestion_actual === 'kit' ? 'selected' : '' ?>>Kit</option>
                        </select>
                    </div>
                    <div class="form-section" id="bobinaSection" style="display:none;">
                        <div class="mb-3">
                            <label for="metros_iniciales" class="form-label">Metros iniciales de la bobina</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="metros_iniciales" id="metros_iniciales">
                        </div>
                        <div class="mb-3">
                            <label for="identificador" class="form-label">Identificador de la bobina (opcional)</label>
                            <input type="text" class="form-control" name="identificador" id="identificador" placeholder="Ej: Bobina #1, Lote 2024, etc.">
                        </div>
                    </div>
                   <div id="advertenciaBobinas" class="alert alert-warning mt-2" style="display:none;"></div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-check-circle"></i> Actualizar producto
                        </button>
                        <a href="list.php" class="btn btn-secondary flex-fill">
                            <i class="bi bi-arrow-left"></i> Volver al listado
                        </a>
                    </div>
                </form>
            </div>
            <?php if ($bobinas && $bobinas->num_rows > 0): ?>
                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4><i class="bi bi-receipt"></i> Bobinas asociadas</h4>
                        <div class="d-flex gap-2">
                            <a href="../bobinas/gestionar.php?product_id=<?= $product_id ?>" class="btn btn-info btn-sm">
                                <i class="bi bi-gear"></i> Gestionar bobinas
                            </a>
                            <a href="../bobinas/add.php?product_id=<?= $product_id ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle"></i> Agregar bobina
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Identificador</th>
                                    <th>Metros iniciales</th>
                                    <th>Metros actuales</th>
                                    <th>Fecha ingreso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i=1; while ($b = $bobinas->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($b['identificador']) ?: '<span class="text-muted">-</span>' ?></td>
                                        <td><?= number_format($b['metros_iniciales'], 2) ?></td>
                                        <td><?= number_format($b['metros_actuales'], 2) ?></td>
                                        <td><?= date('d/m/Y', strtotime($b['fecha_ingreso'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($tipo_gestion_actual === 'bobina'): ?>
                <div class="mt-4">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Producto tipo bobina:</strong> Este producto está configurado para gestionarse por metros en bobinas, pero aún no tiene bobinas registradas.
                    </div>
                    <a href="../bobinas/add.php?product_id=<?= $product_id ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Registrar primera bobina
                    </a>
                </div>
            <?php endif; ?>
            <script src="../assets/js/script.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
            <script>
            // Mostrar/ocultar campos de bobina según tipo_gestion
            const tipoGestionSelect = document.getElementById('tipo_gestion');
            const bobinaSection = document.getElementById('bobinaSection');
            const advertenciaBobinas = document.getElementById('advertenciaBobinas');
            let bobinasAsociadas = <?= isset($bobinas) && $bobinas->num_rows > 0 ? 'true' : 'false' ?>;
            let tipoGestionOriginal = '<?= $tipo_gestion_actual ?>';
            tipoGestionSelect.addEventListener('change', function() {
                if (this.value === 'bobina') {
                    bobinaSection.style.display = '';
                    advertenciaBobinas.style.display = 'none';
                } else {
                    bobinaSection.style.display = 'none';
                    document.getElementById('metros_iniciales').value = '';
                    document.getElementById('identificador').value = '';
                    // Si el producto era bobina y hay bobinas asociadas, advertir
                    if (tipoGestionOriginal === 'bobina' && bobinasAsociadas) {
                        advertenciaBobinas.innerHTML = 'Este producto tiene bobinas asociadas. Si cambias el tipo de gestión perderás todas las bobinas. <br><label><input type="checkbox" name="confirmar_eliminar_bobinas" value="1" required> Confirmo que deseo eliminar todas las bobinas asociadas.</label>';
                        advertenciaBobinas.style.display = 'block';
                    } else {
                        advertenciaBobinas.style.display = 'none';
                    }
                }
            });
            // Al cargar, mostrar si corresponde
            if (tipoGestionSelect.value === 'bobina') {
                bobinaSection.style.display = '';
            }
            </script>
        </body>
    </html>
