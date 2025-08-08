<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    // Filtro de estado activo/inactivo
    $activo_filtro = isset($_GET['activo']) ? $_GET['activo'] : '1'; // Por defecto mostrar solo activos

    // Construir consulta con filtro de estado activo (NULL se trata como activo)
    $where_clause = "WHERE " . ($activo_filtro === '' ? '1=1' : 
                    ($activo_filtro === '1' ? '(p.is_active = 1 OR p.is_active IS NULL)' : 
                    ($activo_filtro === '2' ? 'p.is_active = 2' : 'p.is_active = 0')));
    $params = [];
    $types = '';

    $query = "
        SELECT p.*, c.name as category_name, s.name as supplier_name,
               COALESCE(SUM(b.metros_actuales), 0) as metros_totales,
               COUNT(b.bobina_id) as total_bobinas
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
        LEFT JOIN bobinas b ON p.product_id = b.product_id AND b.is_active = 1
        $where_clause
        GROUP BY p.product_id, p.product_name, p.sku, p.price, p.quantity, p.category_id, p.supplier_id, p.description, p.barcode, p.image, p.tipo_gestion, p.cost_price, p.min_stock, p.max_stock, p.unit_measure, p.is_active, p.created_at, p.updated_at, c.name, s.name
        ORDER BY p.created_at DESC
    ";

    $stmt = $mysqli->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Obtener categorías únicas para el filtro
    $categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name");
    
    // Obtener proveedores únicos para el filtro
    $proveedores = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name");
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
        .barcode-col span {
            font-size: 0.65rem !important;
            font-weight: 400 !important;
            color: #888 !important;
            background: none !important;
            padding: 0 2px !important;
            border-radius: 0 !important;
            font-family: 'Fira Mono', 'Consolas', monospace;
            display: inline-block;
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
                        <option value="<?= htmlspecialchars($prov['supplier_id']) ?>"><?= htmlspecialchars($prov['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <select id="filtroActivo" class="form-select" onchange="filtrarPorEstado()">
                    <option value="1" <?= $activo_filtro === '1' ? 'selected' : '' ?>>Solo activos</option>
                    <option value="0" <?= $activo_filtro === '0' ? 'selected' : '' ?>>Solo inactivos</option>
                    <option value="2" <?= $activo_filtro === '2' ? 'selected' : '' ?>>Solo descontinuados</option>
                    <option value="" <?= $activo_filtro === '' ? 'selected' : '' ?>>Todos</option>
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
                        <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr data-product-id="<?= $row['product_id'] ?>" 
                                data-category="<?= htmlspecialchars($row['category_name'] ?? '') ?>"
                                data-supplier="<?= htmlspecialchars($row['supplier_name'] ?? '') ?>">
                                <td><?= $i++ ?></td>
                                <td class="img-col">
                                    <?php
                                    $img = $row['image'] ?? '';
                                    if ($img && strpos($img, 'uploads/products/') === false) {
                                        $img = 'uploads/products/' . $img;
                                    }
                                    ?>
                                    <?php if ($img): ?>
                                        <img src="../<?= htmlspecialchars($img) ?>" alt="Imagen del producto" style="width: 60px; height: 60px; object-fit: cover; border-radius: 10px;">
                                    <?php else: ?>
                                        <span class="text-muted"><i class="bi bi-image" style="font-size: 2rem;"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="img-nombre-wrap">
                                    <div>
                                            <div class="nombre-producto"><?= htmlspecialchars($row['product_name']) ?></div>
                                            <div class="sku-badge">SKU: <?= htmlspecialchars($row['sku']) ?></div>
                                        <?php if ($row['description']): ?>
                                                <div class="desc-producto"><?= htmlspecialchars($row['description']) ?></div>
                                        <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="barcode-col">
                                    <?php if ($row['barcode']): ?>
                                        <span><?= htmlspecialchars($row['barcode']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin código</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['category_name'] ?? 'Sin categoría') ?></td>
                                <td><?= htmlspecialchars($row['supplier_name'] ?? 'Sin proveedor') ?></td>
                                <td>$<?= number_format($row['price'], 2) ?></td>
                                <td>
                                    <?php if ($row['tipo_gestion'] === 'bobina'): ?>
                                        <?php if ($row['total_bobinas'] > 0): ?>
                                            <?= $row['total_bobinas'] ?> bobina<?= $row['total_bobinas'] != 1 ? 's' : '' ?> (<?= number_format($row['metros_totales'], 0) ?>m)
                                    <?php else: ?>
                                            0 bobinas
                                    <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge <?= $row['quantity'] > 10 ? 'bg-success' : ($row['quantity'] > 0 ? 'bg-warning' : 'bg-danger') ?>">
                                            <?= $row['quantity'] ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <div class="acciones">
                                        <a href="edit.php?id=<?= $row['product_id'] ?>" class="btn btn-outline-primary btn-sm" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($row['tipo_gestion'] === 'bobina'): ?>
                                            <a href="../bobinas/gestionar.php?product_id=<?= $row['product_id'] ?>" class="btn btn-outline-info btn-sm" title="Gestionar bobinas">
                                                <i class="bi bi-receipt"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php 
                                            $current_status = $row['is_active'] ?? 1; // Tratar NULL como activo
                                            $next_status = ($current_status == 1 || $current_status === null) ? 0 : ($current_status == 0 ? 2 : 1);
                                            $status_text = ($current_status == 1 || $current_status === null) ? 'Desactivar' : ($current_status == 0 ? 'Descontinuar' : 'Activar');
                                            $status_icon = ($current_status == 1 || $current_status === null) ? 'eye-slash' : ($current_status == 0 ? 'archive' : 'eye');
                                            $btn_color = ($current_status == 1 || $current_status === null) ? 'warning' : ($current_status == 0 ? 'secondary' : 'success');
                                        ?>
                                        <button class="btn btn-outline-<?= $btn_color ?> btn-sm" 
                                                title="<?= $status_text ?> producto"
                                                onclick="toggleProductStatus(<?= $row['product_id'] ?>, <?= $next_status ?>)">
                                            <i class="bi bi-<?= $status_icon ?>"></i>
                                        </button>
                                        <a href="delete.php?id=<?= $row['product_id'] ?>" class="btn btn-outline-danger btn-sm btn-delete" title="Eliminar" 
                                           onclick="return confirm('¿Estás seguro de que quieres eliminar este producto?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-box-seam" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">No hay productos</h4>
                <p class="text-muted">Agrega tu primer producto para comenzar</p>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Agregar Producto
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-productos').classList.add('active');
        
        // Filtro de búsqueda en tiempo real
        const busquedaInput = document.getElementById('busquedaProducto');
        const filtroCategoria = document.getElementById('filtroCategoria');
        const filtroProveedor = document.getElementById('filtroProveedor');
        const limpiarFiltros = document.getElementById('limpiarFiltros');
        const contadorProductos = document.getElementById('contadorProductos');
        const filas = document.querySelectorAll('tbody tr');
        
        function filtrarProductos() {
            const busqueda = busquedaInput.value.toLowerCase();
            const categoria = filtroCategoria.value;
            const proveedor = filtroProveedor.value;
            let visibleCount = 0;
            
            filas.forEach(fila => {
                const nombre = fila.querySelector('.nombre-producto').textContent.toLowerCase();
                const sku = fila.querySelector('.sku-badge').textContent.toLowerCase();
                const categoriaFila = fila.getAttribute('data-category').toLowerCase();
                const proveedorFila = fila.getAttribute('data-supplier').toLowerCase();
                
                const coincideBusqueda = nombre.includes(busqueda) || sku.includes(busqueda);
                const coincideCategoria = !categoria || categoriaFila.includes(categoria);
                const coincideProveedor = !proveedor || proveedorFila.includes(proveedor);
                
                if (coincideBusqueda && coincideCategoria && coincideProveedor) {
                    fila.style.display = '';
                    visibleCount++;
                } else {
                    fila.style.display = 'none';
                }
            });
            
            contadorProductos.textContent = visibleCount;
            
            // Mostrar/ocultar botón de limpiar filtros
            if (busqueda || categoria || proveedor) {
                limpiarFiltros.style.display = 'inline-block';
            } else {
                limpiarFiltros.style.display = 'none';
            }
        }
        
        busquedaInput.addEventListener('input', filtrarProductos);
        
        // Función para cambiar el estado activo/inactivo/descontinuado de un producto
        function toggleProductStatus(productId, newStatus) {
            let action = newStatus == 1 ? 'activar' : (newStatus == 2 ? 'marcar como descontinuado' : 'desactivar');
            if (confirm('¿Estás seguro de que quieres ' + action + ' este producto?')) {
                fetch('toggle_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        is_active: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Recargar la página para mostrar los cambios
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cambiar el estado del producto');
                });
            }
        }
        
        // Función para filtrar por estado activo/inactivo
        function filtrarPorEstado() {
            const activo = document.getElementById('filtroActivo').value;
            const url = new URL(window.location);
            if (activo !== '') {
                url.searchParams.set('activo', activo);
            } else {
                url.searchParams.delete('activo');
            }
            window.location.href = url.toString();
        }
        filtroCategoria.addEventListener('change', filtrarProductos);
        filtroProveedor.addEventListener('change', filtrarProductos);
        
        limpiarFiltros.addEventListener('click', function() {
            busquedaInput.value = '';
            filtroCategoria.value = '';
            filtroProveedor.value = '';
            filtrarProductos();
        });
    </script>
</body>
</html>
