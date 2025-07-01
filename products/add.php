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
        
        $description = trim($_POST['description']);

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
            $stmt = $mysqli->prepare("INSERT INTO products (product_name, sku, price, quantity, category_id, supplier, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdiiss", $product_name, $sku, $price, $quantity, $category_id, $supplier, $description);
            if ($stmt->execute()) {
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
        .main-content {
            max-width: 440px;
            margin: 40px auto 0 auto;
        }
        .main-content h2 {
            margin-bottom: 22px;
        }
        form label {
            margin-top: 10px;
        }
        .form-group {
            margin-bottom: 14px;
        }
        .form-group input {
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
    <aside class="sidebar">
        <h1>Gestor de inventarios<br>Alarmas y Cámaras de seguridad del sureste</h1>
        <nav>
            <a href="../dashboard/index.php">
                <i class="bi bi-grid-3x3-gap-fill"></i>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="../products/list.php" class="active">
                <i class="bi bi-box-seam-fill"></i>
                <span class="nav-text">Productos</span>
            </a>
            <a href="../movements/index.php">
                <i class="bi bi-arrow-left-right"></i>
                <span class="nav-text">Movimientos</span>
            </a>
        </nav>
        <a href="../auth/logout.php" class="logout">
            <i class="bi bi-box-arrow-right"></i>
            <span class="nav-text">Cerrar sesión</span>
        </a>
    </aside>
    <main class="main-content">
        <h2>Agregar nuevo producto</h2>
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
        <form action="" method="POST" id="formAgregarProducto">
            <div class="mb-3">
                <label for="sku" class="form-label">SKU:</label>
                <input type="text" class="form-control" name="sku" id="sku">
                <div id="alertSkuRealtime" class="alert alert-info" style="display:none;margin-top:6px;">
                    <i class="bi bi-info-circle"></i>
                    Si dejas este campo vacío, el sistema generará un SKU automáticamente al guardar.
                </div>
            </div>
            <div class="mb-3">
                <label for="product_name" class="form-label">Nombre del producto:</label>
                <input type="text" class="form-control" name="product_name" id="product_name" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Descripción:</label>
                <textarea class="form-control" name="description" id="description"></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="price" class="form-label">Precio:</label>
                    <input type="number" step="0.01" class="form-control" name="price" id="price">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="quantity" class="form-label">Cantidad:</label>
                    <input type="number" class="form-control" name="quantity" id="quantity" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Comparador de precios</label>
                <div class="d-flex gap-2 mb-2">
                    <button type="button" class="btn btn-warning btn-sm" id="btnCompararPrecios">
                        <i class="bi bi-arrow-repeat"></i> Actualizar precios
                    </button>
                    <a href="configurar_tvc.php" class="btn btn-info btn-sm">
                        <i class="bi bi-gear"></i> Configurar TVC.mx
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle" id="tablaComparadorPrecios">
                        <thead>
                            <tr>
                                <th>Tienda</th>
                                <th>Precio</th>
                                <th>Enlace</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="3" class="text-center text-muted">Sin datos</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="category" class="form-label">Categoría:</label>
                    <select class="form-control" name="category" id="category">
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
                    <label for="supplier" class="form-label">Proveedor:</label>
                    <select class="form-control" name="supplier" id="supplier">
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
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-plus-circle"></i> Agregar producto
                </button>
                <a href="list.php" class="btn btn-secondary flex-fill">
                    <i class="bi bi-arrow-left"></i> Volver al listado
                </a>
            </div>
        </form>
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

        // Mostrar/ocultar botón de buscar en tiendas (SOLO SI EXISTEN LOS ELEMENTOS)
        const buscarCheck = document.getElementById('buscarTiendasCheck');
        const btnBuscar = document.getElementById('btnBuscarTiendas');
        const inputNombre = document.getElementById('product_name');
        const inputSKU = document.getElementById('sku');
        const mensajeBuscar = document.getElementById('mensajeBuscarTiendas');
        const resultadosBuscar = document.getElementById('resultadosBuscarTiendas');
        
        // Solo ejecutar si existen los elementos de búsqueda en tiendas
        if (buscarCheck && btnBuscar && inputNombre && inputSKU && mensajeBuscar && resultadosBuscar) {
            buscarCheck.addEventListener('change', function() {
                btnBuscar.style.display = this.checked ? 'inline-block' : 'none';
                mensajeBuscar.innerHTML = '';
                resultadosBuscar.innerHTML = '';
            });
            
            // Deshabilitar botón si el campo nombre y/o SKU están vacíos
            function toggleBtnBuscar() {
                btnBuscar.disabled = (inputNombre.value.trim() === '' && inputSKU.value.trim() === '');
            }
            inputNombre.addEventListener('input', toggleBtnBuscar);
            inputSKU.addEventListener('input', toggleBtnBuscar);
            toggleBtnBuscar();
            
            // Lógica de búsqueda en tiendas (real, varios resultados)
            btnBuscar.addEventListener('click', function() {
                btnBuscar.disabled = true;
                btnBuscar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Buscando...';
                mensajeBuscar.innerHTML = '';
                resultadosBuscar.innerHTML = '';
                const nombre = inputNombre.value;
                const sku = inputSKU.value;
                fetch('buscar_tiendas.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nombre, sku })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data || data.length === 0) {
                        mensajeBuscar.innerHTML = `<div class='alert alert-danger py-2 mb-0'>No se encontraron resultados en las tiendas. Puedes escribir los datos manualmente.</div>`;
                        btnBuscar.disabled = false;
                        btnBuscar.innerHTML = '<i class="bi bi-search"></i> Buscar y autollenar desde tiendas';
                        return;
                    }
                    let html = '';
                    data.forEach(tienda => {
                        html += `<div class='mb-2'><b>${tienda.tienda}</b><div class='table-responsive'><table class='table table-bordered table-sm align-middle mb-0'><thead><tr><th>Nombre</th><th>Precio</th><th>Enlace</th><th>Acción</th></tr></thead><tbody>`;
                        if (tienda.resultados.length > 0) {
                            tienda.resultados.forEach(res => {
                                html += `<tr><td>${res.nombre}</td><td>${res.precio ? '$' + res.precio : '-'}</td><td>${res.enlace ? `<a href='${res.enlace}' target='_blank'>Ver</a>` : '-'}</td><td><button type='button' class='btn btn-success btn-sm usar-resultado' data-nombre='${encodeURIComponent(res.nombre)}' data-desc='${encodeURIComponent(res.descripcion)}'>Usar este</button></td></tr>`;
                            });
                        } else {
                            html += `<tr><td colspan='4' class='text-center text-muted'>Sin resultados</td></tr>`;
                        }
                        html += '</tbody></table></div></div>';
                    });
                    resultadosBuscar.innerHTML = html;
                    // Asignar eventos a los botones "Usar este"
                    document.querySelectorAll('.usar-resultado').forEach(btn => {
                        btn.addEventListener('click', function() {
                            inputNombre.value = decodeURIComponent(this.getAttribute('data-nombre'));
                            document.getElementById('description').value = decodeURIComponent(this.getAttribute('data-desc'));
                            mensajeBuscar.innerHTML = `<div class='alert alert-success py-2 mb-0'>¡Producto autollenado!</div>`;
                            resultadosBuscar.innerHTML = '';
                            btnBuscar.disabled = false;
                            btnBuscar.innerHTML = '<i class="bi bi-search"></i> Buscar y autollenar desde tiendas';
                        });
                    });
                    btnBuscar.disabled = false;
                    btnBuscar.innerHTML = '<i class="bi bi-search"></i> Buscar y autollenar desde tiendas';
                })
                .catch(() => {
                    mensajeBuscar.innerHTML = `<div class='alert alert-danger py-2 mb-0'>No se encontró el producto en las tiendas. Puedes escribir los datos manualmente.</div>`;
                    btnBuscar.disabled = false;
                    btnBuscar.innerHTML = '<i class="bi bi-search"></i> Buscar y autollenar desde tiendas';
                });
            });
        }

        // Comparador de precios - CON SCRAPING REAL
        const btnComparar = document.getElementById('btnCompararPrecios');
        const tablaComparador = document.getElementById('tablaComparadorPrecios');
        
        // Solo ejecutar si existen los elementos del comparador
        if (btnComparar && tablaComparador) {
            const tablaComparadorTbody = tablaComparador.querySelector('tbody');
            
            btnComparar.addEventListener('click', function() {
                btnComparar.disabled = true;
                btnComparar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Buscando precios reales...';
                
                const nombre = document.getElementById('product_name').value;
                const sku = document.getElementById('sku').value;
                const descripcion = document.getElementById('description').value;
                
                fetch('comparar_precios.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nombre, sku, descripcion })
                })
                .then(res => res.json())
                .then(data => {
                    tablaComparadorTbody.innerHTML = '';
                    if (data && data.length > 0) {
                        data.forEach(tienda => {
                            let enlaceHtml = '-';
                            if (tienda.enlace && tienda.enlace !== '-') {
                                enlaceHtml = `<a href="${tienda.enlace}" target="_blank" class="btn btn-sm btn-outline-primary">Ver producto</a>`;
                            }
                            
                            let precioHtml = tienda.precio !== '-' ? '$' + tienda.precio : '<small class="text-muted">Haz clic en "Ver producto"</small>';
                            
                            let notaHtml = '';
                            if (tienda.nota) {
                                notaHtml = `<br><small class="text-info">${tienda.nota}</small>`;
                            }
                            
                            let nombreProductoHtml = '';
                            if (tienda.nombre_producto) {
                                nombreProductoHtml = `<br><small class="text-success">${tienda.nombre_producto.substring(0, 50)}${tienda.nombre_producto.length > 50 ? '...' : ''}</small>`;
                            }
                            
                            tablaComparadorTbody.innerHTML += `<tr>
                                <td><strong>${tienda.tienda}</strong>${notaHtml}${nombreProductoHtml}</td>
                                <td>${precioHtml}</td>
                                <td>${enlaceHtml}</td>
                            </tr>`;
                        });
                    } else {
                        tablaComparadorTbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Sin resultados</td></tr>';
                    }
                    btnComparar.disabled = false;
                    btnComparar.innerHTML = '<i class="bi bi-arrow-repeat"></i> Actualizar precios';
                })
                .catch(() => {
                    tablaComparadorTbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Error al buscar precios</td></tr>';
                    btnComparar.disabled = false;
                    btnComparar.innerHTML = '<i class="bi bi-arrow-repeat"></i> Actualizar precios';
                });
            });
        }
    </script>
</body>
</html>
