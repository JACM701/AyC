<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    // Obtener todos los productos con información de categoría
    $result = $mysqli->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        ORDER BY p.created_at DESC
    ");

    // Obtener categorías únicas para el filtro
    $categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name");
    
    // Obtener proveedores únicos para el filtro
    $proveedores = $mysqli->query("SELECT DISTINCT supplier FROM products WHERE supplier IS NOT NULL AND supplier != '' ORDER BY supplier");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos | Gestor de inventarios Alarmas y Cámaras de seguridad del sureste</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6fb;
        }
        .main-content {
            margin-top: 40px;
            margin-left: 250px;
            padding: 24px;
            width: calc(100vw - 250px);
            box-sizing: border-box;
        }
        .sidebar.collapsed ~ .main-content {
            margin-left: 70px !important;
            width: calc(100vw - 70px) !important;
            transition: margin-left 0.25s cubic-bezier(.4,2,.6,1), width 0.25s;
        }
         /* Sobrescribe el fondo del thead de las tablas de productos */
        .table thead,
        .table thead th,
        thead.table-dark th {
            background: #121866 !important;
            color: #fff !important;
        }
        .titulo-lista {
            font-size: 2rem;
            color: #121866;
            font-weight: 700;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .busqueda-bar {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-bottom: 18px;
            flex-wrap: wrap;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
            padding: 18px 18px 10px 18px;
        }
        #busquedaProducto {
            flex: 1 1 220px;
            padding: 10px 14px;
            border: 1.5px solid #cfd8dc;
            border-radius: 7px;
            font-size: 1rem;
            background: #f7f9fc;
            transition: border 0.2s;
            outline: none;
        }
        #busquedaProducto:focus {
            border-color: #121866;
        }
        #filtroCategoria, #filtroProveedor {
            min-width: 150px;
            max-width: 200px;
        }
        .filtros-container {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .add-btn, .btn-outline-info {
            display: inline-block;
            width: auto;
            padding: 8px 18px;
            font-size: 0.98rem;
            border-radius: 6px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(18,24,102,0.08);
            transition: background 0.18s;
            margin-bottom: 0;
        }
        .add-btn {
            background: #121866;
            color: #fff;
        }
        .add-btn:hover {
            background: #232a7c;
        }
        .btn-outline-info {
            border: 1.5px solid #00bcd4;
            color: #00bcd4;
            background: #f4f6fb;
        }
        .btn-outline-info:hover {
            background: #00bcd4;
            color: #fff;
        }
        .acciones {
            display: flex;
            gap: 6px;
            justify-content: center;
        }
        .acciones a {
            padding: 6px 10px;
            border-radius: 7px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        @media (max-width: 900px) {
            .main-content { width: calc(100vw - 70px); margin-left: 70px; max-width: 100vw; padding: 12px 2vw; box-sizing: border-box; }
            .titulo-lista { font-size: 1.2rem; }
            .busqueda-bar { flex-direction: column; gap: 10px; align-items: stretch; padding: 12px 6px 6px 6px; }
            .btn, .add-btn, .btn-outline-info { display: block; width: 100%; }
            .acciones { flex-direction: row; gap: 4px; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="titulo-lista"><i class="bi bi-box-seam"></i> Listado de productos</div>
        <div class="busqueda-bar">
            <input type="text" id="busquedaProducto" placeholder="Buscar por nombre, SKU, categoría, proveedor...">
            <div class="filtros-container">
                <select id="filtroCategoria" class="form-select">
                    <option value="">Todas las categorías</option>
                    <?php $categorias->data_seek(0); while ($cat = $categorias->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($cat['category_id']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <select id="filtroProveedor" class="form-select">
                    <option value="">Todos los proveedores</option>
                    <?php $proveedores->data_seek(0); while ($prov = $proveedores->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($prov['supplier']) ?>"><?= htmlspecialchars($prov['supplier']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <a href="add.php"><button class="add-btn"><i class="bi bi-plus-circle"></i> Agregar producto</button></a>
            <a href="categories.php"><button class="btn-outline-info"><i class="bi bi-tags"></i> Gestionar Categorías</button></a>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <small class="text-muted">
                Mostrando <span id="contadorProductos"><?= $result ? $result->num_rows : 0 ?></span> productos
            </small>
            <button id="limpiarFiltros" class="btn btn-outline-secondary btn-sm" style="display: none;">
                <i class="bi bi-x-circle"></i> Limpiar filtros
            </button>
        </div>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'stock'): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                No puedes eliminar un producto que aún tiene stock. Debes de dar de baja el inventario primero.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-productos">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col" class="img-col">Imagen</th>
                            <th scope="col">Producto</th>
                            <th scope="col" class="barcode-col">Código de barras</th>
                            <th scope="col">Categoría</th>
                            <th scope="col">Proveedor</th>
                            <th scope="col">Precio Unitario</th>
                            <th scope="col">Cantidad</th>
                            <th scope="col">Fecha de alta</th>
                            <th scope="col" style="text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['product_id']) ?></td>
                                <td class="img-col">
                                    <?php 
                                    $img_path = isset($row['image']) && $row['image'] ? $row['image'] : '';
                                    $img_src = $img_path && file_exists(__DIR__ . '/../' . $img_path) ? '../' . $img_path : '';
                                    ?>
                                    <?php if ($img_src): ?>
                                        <img src="<?= htmlspecialchars($img_src) ?>" alt="Imagen" class="img-col-img">
                                    <?php else: ?>
                                        <span class="img-placeholder"><i class="bi bi-box"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <span class="nombre-producto"><?= htmlspecialchars($row['product_name']) ?></span>
                                        <?php if ($row['sku']): ?>
                                            <br><span class="sku-badge" title="Código interno SKU">SKU: <?= htmlspecialchars($row['sku']) ?></span>
                                        <?php endif; ?>
                                        <?php if ($row['description']): ?>
                                            <br><span class="desc-producto"><?= htmlspecialchars(substr($row['description'], 0, 50)) ?><?= strlen($row['description']) > 50 ? '...' : '' ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="barcode-col">
                                    <?php if (isset($row['barcode']) && $row['barcode']): ?>
                                        <?= htmlspecialchars($row['barcode']) ?>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['category_name']): ?>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($row['category_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin categoría</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['supplier']): ?>
                                        <small><?= htmlspecialchars($row['supplier']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Sin proveedor</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong>$<?= number_format($row['price'], 2, ',', '.') ?></strong></td>
                                <td>
                                    <?php if ($row['quantity'] <= 5): ?>
                                        <span class="badge bg-danger"><?= htmlspecialchars($row['quantity']) ?></span>
                                    <?php elseif ($row['quantity'] <= 15): ?>
                                        <span class="badge bg-warning text-dark"><?= htmlspecialchars($row['quantity']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?= htmlspecialchars($row['quantity']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <div class="acciones">
                                        <a href="../proveedores/buscar_producto.php?id=<?= $row['product_id'] ?>" class="btn btn-outline-info btn-sm" title="Buscar precios">
                                            <i class="bi bi-search"></i>
                                        </a>
                                        <a href="edit.php?id=<?= $row['product_id'] ?>" class="btn btn-outline-primary btn-sm" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="delete.php?id=<?= $row['product_id'] ?>" class="btn btn-outline-danger btn-sm btn-delete" title="Eliminar">
                                            <i class="bi bi-trash3"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No hay productos registrados.</p>
        <?php endif; ?>
    </main>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Resalta el menú activo
        document.querySelector('.sidebar-productos').classList.add('active');
        
        // Función para filtrar productos
        function filtrarProductos() {
            const busqueda = document.getElementById('busquedaProducto').value.toLowerCase();
            const categoriaId = document.getElementById('filtroCategoria').value;
            const proveedor = document.getElementById('filtroProveedor').value.toLowerCase();
            let productosVisibles = 0;
            document.querySelectorAll('table tbody tr').forEach(row => {
                const nombreCell = row.cells[2]; // Ahora la columna 2 es Producto
                const nombreDiv = nombreCell.querySelector('.nombre-producto');
                const nombre = nombreDiv ? nombreDiv.textContent.toLowerCase() : '';
                const skuBadge = nombreCell.querySelector('.sku-badge');
                const sku = skuBadge ? skuBadge.textContent.toLowerCase() : '';
                // Extraer texto de categoría (puede estar en badge o texto normal)
                let categoriaFila = '';
                const categoriaCell = row.cells[4]; // Ahora la columna 4 es Categoría
                const categoriaBadge = categoriaCell.querySelector('.badge');
                if (categoriaBadge) {
                    categoriaFila = categoriaBadge.textContent.toLowerCase().trim();
                } else {
                    categoriaFila = categoriaCell.textContent.toLowerCase().trim();
                }
                // Extraer texto de proveedor
                let proveedorFila = '';
                const proveedorCell = row.cells[5]; // Ahora la columna 5 es Proveedor
                const proveedorSmall = proveedorCell.querySelector('small');
                if (proveedorSmall) {
                    proveedorFila = proveedorSmall.textContent.toLowerCase().trim();
                } else {
                    proveedorFila = proveedorCell.textContent.toLowerCase().trim();
                }
                const coincideBusqueda = nombre.includes(busqueda) || 
                                       sku.includes(busqueda) || 
                                       categoriaFila.includes(busqueda) || 
                                       proveedorFila.includes(busqueda);
                // Para categorías, comparamos el texto del badge con el texto del option seleccionado
                const categoriaSelect = document.getElementById('filtroCategoria');
                const categoriaSeleccionada = categoriaSelect.options[categoriaSelect.selectedIndex].text.toLowerCase();
                const coincideCategoria = categoriaId === '' || categoriaFila === categoriaSeleccionada;
                const coincideProveedor = proveedor === '' || proveedorFila === proveedor;
                const coincide = coincideBusqueda && coincideCategoria && coincideProveedor;
                row.style.display = coincide ? '' : 'none';
                if (coincide) productosVisibles++;
            });
            // Actualizar contador
            document.getElementById('contadorProductos').textContent = productosVisibles;
            // Mostrar/ocultar botón de limpiar filtros
            const hayFiltros = busqueda !== '' || categoriaId !== '' || proveedor !== '';
            document.getElementById('limpiarFiltros').style.display = hayFiltros ? 'block' : 'none';
            // Debug: mostrar en consola para verificar
            console.log('Filtros aplicados:', {
                busqueda: busqueda,
                categoriaId: categoriaId,
                proveedor: proveedor,
                productosVisibles: productosVisibles
            });
        }
        
        // Función para limpiar filtros
        function limpiarFiltros() {
            document.getElementById('busquedaProducto').value = '';
            document.getElementById('filtroCategoria').value = '';
            document.getElementById('filtroProveedor').value = '';
            filtrarProductos();
        }
        
        // Event listeners para filtros
        document.getElementById('busquedaProducto').addEventListener('input', filtrarProductos);
        document.getElementById('filtroCategoria').addEventListener('change', filtrarProductos);
        document.getElementById('filtroProveedor').addEventListener('change', filtrarProductos);
        document.getElementById('limpiarFiltros').addEventListener('click', limpiarFiltros);
    </script>
</body>
</html>
