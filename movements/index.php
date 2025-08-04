<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    $success = $error = '';

    // Obtener parámetros de orden
    $orden_col = $_GET['orden'] ?? 'm.movement_date';
    $orden_dir = $_GET['dir'] ?? 'DESC';
    $ordenes_validos = [
        'm.movement_id' => 'ID',
        'p.product_name' => 'Producto',
        'mt.name' => 'Tipo',
        'm.quantity' => 'Cantidad',
        'u.username' => 'Usuario',
        'm.movement_date' => 'Fecha'
    ];
    if (!array_key_exists($orden_col, $ordenes_validos)) $orden_col = 'm.movement_date';
    if (!in_array(strtoupper($orden_dir), ['ASC','DESC'])) $orden_dir = 'DESC';

    // Filtros
    $filtro_producto = isset($_GET['producto']) ? trim($_GET['producto']) : '';
    $filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
    $filtro_usuario = isset($_GET['usuario']) ? $_GET['usuario'] : '';
    $filtro_mes = isset($_GET['mes']) ? $_GET['mes'] : '';
    $filtro_bobina = isset($_GET['solo_bobinas']) && $_GET['solo_bobinas'] == '1';

    // Validar filtro de mes (entre 2020-01 y 2035-12)
    if ($filtro_mes) {
        $min_fecha = '2020-01';
        $max_fecha = '2035-12';
        if ($filtro_mes < $min_fecha || $filtro_mes > $max_fecha) {
            $filtro_mes = ''; // Resetear si está fuera del rango válido
        }
    }

    // Paginación
    $movimientos_por_pagina = 20;
    $pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
    $offset = ($pagina_actual - 1) * $movimientos_por_pagina;

    // Si no hay filtro de mes específico, mostrar solo movimientos del mes actual
    $mostrar_solo_mes_actual = false;
    $filtro_fecha_desde = '';
    $filtro_fecha_hasta = '';

    if ($filtro_mes) {
        // Solo aplicar filtro de mes cuando se especifique explícitamente
        $filtro_fecha_desde = $filtro_mes . '-01'; // Primer día del mes
        $filtro_fecha_hasta = date('Y-m-t', strtotime($filtro_fecha_desde)); // Último día del mes
    } else if (!$filtro_producto && !$filtro_tipo && !$filtro_usuario && !$filtro_bobina) {
        // Solo mostrar mes actual si no hay otros filtros aplicados
        $mostrar_solo_mes_actual = true;
        $filtro_mes = date('Y-m'); // Mes actual en formato YYYY-MM
        $filtro_fecha_desde = $filtro_mes . '-01';
        $filtro_fecha_hasta = date('Y-m-t', strtotime($filtro_fecha_desde));
    }

    // Obtener datos para filtros
    $productos_filtro = $mysqli->query("SELECT DISTINCT product_name FROM products ORDER BY product_name");
    $tipos_filtro = $mysqli->query("SELECT movement_type_id, name FROM movement_types ORDER BY name");
    $usuarios_filtro = $mysqli->query("SELECT user_id, username FROM users ORDER BY username");
    
    // Obtener arrays para usar múltiples veces
    $tipos_array = $tipos_filtro ? $tipos_filtro->fetch_all(MYSQLI_ASSOC) : [];
    $usuarios_array = $usuarios_filtro ? $usuarios_filtro->fetch_all(MYSQLI_ASSOC) : [];

    // Construir consulta con filtros
    $where_conditions = [];
    $params = [];
    $param_types = '';

    if ($filtro_producto) {
        $where_conditions[] = "p.product_name LIKE ?";
        $params[] = "%$filtro_producto%";
        $param_types .= 's';
    }
    if ($filtro_tipo) {
        $where_conditions[] = "m.movement_type_id = ?";
        $params[] = $filtro_tipo;
        $param_types .= 'i';
    }
    if ($filtro_usuario) {
        $where_conditions[] = "m.user_id = ?";
        $params[] = $filtro_usuario;
        $param_types .= 'i';
    }
    if ($filtro_fecha_desde) {
        $where_conditions[] = "DATE(m.movement_date) >= ?";
        $params[] = $filtro_fecha_desde;
        $param_types .= 's';
    }
    if ($filtro_fecha_hasta) {
        $where_conditions[] = "DATE(m.movement_date) <= ?";
        $params[] = $filtro_fecha_hasta;
        $param_types .= 's';
    }
    if ($filtro_bobina) {
        $where_conditions[] = "p.tipo_gestion = 'bobina'";
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Consulta para contar total de registros
    $count_query = "
        SELECT COUNT(DISTINCT m.movement_id) as total
        FROM movements m
        JOIN products p ON m.product_id = p.product_id
        LEFT JOIN movement_types mt ON m.movement_type_id = mt.movement_type_id
        LEFT JOIN bobinas b ON m.bobina_id = b.bobina_id
        LEFT JOIN users u ON m.user_id = u.user_id
        LEFT JOIN tecnicos t ON m.tecnico_id = t.tecnico_id
        $where_clause
    ";

    $count_stmt = $mysqli->prepare($count_query);
    if (!empty($params)) {
        $count_stmt->bind_param($param_types, ...$params);
    }
    $count_stmt->execute();
    $total_registros = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_paginas = ceil($total_registros / $movimientos_por_pagina);

    // Consulta principal con paginación
    $query = "
        SELECT 
            m.*, 
            p.product_name,
            p.tipo_gestion,
            mt.name AS movement_type_nombre,
            b.identificador AS bobina_identificador,
            b.metros_actuales AS bobina_metros_actuales,
            u.username AS usuario_nombre,
            t.nombre AS tecnico_nombre
        FROM 
            movements m
        JOIN 
            products p ON m.product_id = p.product_id
        LEFT JOIN
            movement_types mt ON m.movement_type_id = mt.movement_type_id
        LEFT JOIN
            bobinas b ON m.bobina_id = b.bobina_id
        LEFT JOIN
            users u ON m.user_id = u.user_id
        LEFT JOIN
            tecnicos t ON m.tecnico_id = t.tecnico_id
        $where_clause
        ORDER BY $orden_col $orden_dir
        LIMIT $movimientos_por_pagina OFFSET $offset
    ";

    $stmt = $mysqli->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Debug temporal - remover después
    // echo "<!-- Debug: Total registros: $total_registros, Filas en result: " . $result->num_rows . " -->";

    // Estadísticas mensuales - usar el mes filtrado o el actual
    $mes_stats_inicio = '';
    $mes_stats_fin = '';
    $mes_mostrado = '';

    if ($filtro_mes) {
        // Si hay filtro de mes, usar ese mes para las estadísticas
        $mes_stats_inicio = $filtro_mes . '-01';
        $mes_stats_fin = date('Y-m-t', strtotime($mes_stats_inicio));
        
        // Traducir mes para mostrar
        $meses_esp = [
            'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
            'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
            'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
            'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
        ];
        $fecha_temp = DateTime::createFromFormat('Y-m', $filtro_mes);
        $mes_eng = $fecha_temp->format('F Y');
        $mes_mostrado = str_replace(array_keys($meses_esp), array_values($meses_esp), $mes_eng);
    } else {
        // Si no hay filtro, usar todas las fechas (no restringir a mes actual)
        $mes_stats_inicio = '2020-01-01';
        $mes_stats_fin = '2035-12-31';
        $mes_mostrado = 'histórico';
    }

    // Estadísticas
    $stats_query = "
        SELECT 
            COUNT(*) as total_movimientos,
            COUNT(DISTINCT m.product_id) as productos_afectados,
            COUNT(DISTINCT m.user_id) as usuarios_activos,
            SUM(CASE WHEN m.quantity > 0 THEN 1 ELSE 0 END) as entradas,
            SUM(CASE WHEN m.quantity < 0 THEN 1 ELSE 0 END) as salidas
        FROM movements m
        JOIN products p ON m.product_id = p.product_id
        WHERE DATE(m.movement_date) BETWEEN '$mes_stats_inicio' AND '$mes_stats_fin'
    ";
    $stats_result = $mysqli->query($stats_query);
    $stats = $stats_result ? $stats_result->fetch_assoc() : [];

    // --- RESPUESTA AJAX SOLO LISTA ---
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        // Recalcular estadísticas para el período filtrado
        $stats_ajax_query = "
            SELECT 
                COUNT(*) as total_movimientos,
                COUNT(DISTINCT m.product_id) as productos_afectados,
                COUNT(DISTINCT m.user_id) as usuarios_activos,
                SUM(CASE WHEN m.quantity > 0 THEN 1 ELSE 0 END) as entradas,
                SUM(CASE WHEN m.quantity < 0 THEN 1 ELSE 0 END) as salidas
            FROM movements m
            JOIN products p ON m.product_id = p.product_id
            WHERE DATE(m.movement_date) BETWEEN '$mes_stats_inicio' AND '$mes_stats_fin'
        ";
        $stats_ajax_result = $mysqli->query($stats_ajax_query);
        $stats_ajax = $stats_ajax_result ? $stats_ajax_result->fetch_assoc() : [];

        ob_start();
        include __DIR__ . '/partials/lista.php';
        $lista_html = ob_get_clean();
        
        // Generar HTML de estadísticas
        $estadisticas_html = '<div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number">' . ($stats_ajax['total_movimientos'] ?? 0) . '</span>
                <span class="stat-label">Total Movimientos ' . $mes_mostrado . '</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">' . ($stats_ajax['productos_afectados'] ?? 0) . '</span>
                <span class="stat-label">Productos Afectados ' . $mes_mostrado . '</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">' . ($stats_ajax['usuarios_activos'] ?? 0) . '</span>
                <span class="stat-label">Usuarios Activos ' . $mes_mostrado . '</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">' . ($stats_ajax['entradas'] ?? 0) . '</span>
                <span class="stat-label">Entradas ' . $mes_mostrado . '</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">' . ($stats_ajax['salidas'] ?? 0) . '</span>
                <span class="stat-label">Salidas ' . $mes_mostrado . '</span>
            </div>
        </div>';
        
        // Generar HTML de paginación
        $paginacion_html = '';
        if ($total_paginas > 1) {
            $paginacion_html = '<nav aria-label="Paginación de movimientos" class="mt-4">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Mostrando ' . ($offset + 1) . ' - ' . min($offset + $movimientos_por_pagina, $total_registros) . ' de ' . $total_registros . ' movimientos
                    </small>
                    <ul class="pagination mb-0" id="paginacionMovimientos">';
            
            if ($pagina_actual > 1) {
                $paginacion_html .= '<li class="page-item">
                    <a class="page-link" href="#" data-pagina="' . ($pagina_actual - 1) . '">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </a>
                </li>';
            }
            
            $inicio = max(1, $pagina_actual - 2);
            $fin = min($total_paginas, $pagina_actual + 2);
            
            for ($i = $inicio; $i <= $fin; $i++) {
                $active = $i == $pagina_actual ? 'active' : '';
                $paginacion_html .= '<li class="page-item ' . $active . '">
                    <a class="page-link" href="#" data-pagina="' . $i . '">' . $i . '</a>
                </li>';
            }
            
            if ($pagina_actual < $total_paginas) {
                $paginacion_html .= '<li class="page-item">
                    <a class="page-link" href="#" data-pagina="' . ($pagina_actual + 1) . '">
                        Siguiente <i class="bi bi-chevron-right"></i>
                    </a>
                </li>';
            }
            
            $paginacion_html .= '</ul></div></nav>';
        }
        
        // Devolver JSON con lista, paginación y estadísticas
        echo json_encode([
            'lista' => $lista_html,
            'paginacion' => $paginacion_html,
            'estadisticas' => $estadisticas_html,
            'pagina_actual' => $pagina_actual,
            'total_paginas' => $total_paginas
        ]);
        exit;
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimientos | Gestor de inventarios</title>
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
        .movements-header {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .movements-title {
            font-size: 2.2rem;
            color: #121866;
            font-weight: 800;
            margin: 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-card {
            background: #232a7c;
            color: #fff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(35,42,124,0.07);
            border: 1.5px solid #e3e6f0;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .stat-card:hover {
            box-shadow: 0 6px 24px rgba(35,42,124,0.13);
            transform: translateY(-2px) scale(1.03);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            display: block;
            margin-bottom: 8px;
            color: #fff;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.93;
            color: #e3e6fa;
            font-weight: 500;
        }
        .filters-section {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .filters-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #121866;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .movements-container {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .movement-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 12px rgba(35,42,124,0.07);
            border: 1.5px solid #e3e6f0;
            transition: box-shadow 0.2s, transform 0.2s;
            position: relative;
            overflow: hidden;
        }
        .movement-card:hover {
            box-shadow: 0 8px 32px rgba(35,42,124,0.13);
            transform: translateY(-2px) scale(1.01);
            border-color: #232a7c;
        }
        .movement-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #232a7c;
        }
        .movement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .movement-id {
            font-size: 0.9rem;
            color: #666;
            font-weight: 600;
        }
        .movement-date {
            font-size: 0.85rem;
            color: #888;
        }
        .product-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #121866;
            margin: 8px 0;
        }
        .movement-type {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .type-entrada {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .type-salida {
            background: #ffebee;
            color: #c62828;
        }
        .type-ajuste {
            background: #fff3e0;
            color: #f57c00;
        }
        .movement-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        .detail-item {
            text-align: center;
            padding: 12px;
            background: #f7f9fc;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
        }
        .detail-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .detail-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #121866;
        }
        .quantity-entrada {
            color: #2e7d32;
        }
        .quantity-salida {
            color: #c62828;
        }
        .bobina-info {
            background: #e3f2fd;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #1565c0;
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        .table-header {
            background: #121866;
            color: #fff;
            padding: 16px 20px;
            border-radius: 12px 12px 0 0;
            margin-bottom: 0;
        }
        .table-header h5 {
            margin: 0;
            font-weight: 700;
        }
        .table-responsive {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
        }
        .table {
            margin: 0;
            font-size: 0.95rem;
        }
        .table th {
            background: #f8f9fa;
            color: #121866;
            font-weight: 700;
            border: none;
            padding: 16px 12px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .table th:hover {
            background: #e9ecef;
        }
        .table td {
            padding: 16px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #e3e6f0;
        }
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        .sort-icon {
            margin-left: 4px;
            opacity: 0.7;
        }
        .sort-active {
            opacity: 1;
        }
        
        /* Estilos para paginación */
        .pagination .page-link {
            color: #232a7c;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            margin: 0 2px;
            padding: 8px 12px;
            transition: all 0.2s ease;
        }
        .pagination .page-link:hover {
            background-color: #232a7c;
            color: white;
            border-color: #232a7c;
        }
        .pagination .page-item.active .page-link {
            background-color: #232a7c;
            border-color: #232a7c;
            color: white;
        }
        .alert-info {
            background-color: #e3f2fd;
            border-color: #90caf9;
            color: #0d47a1;
        }
        
        /* Mejorar el campo de mes */
        input[type="month"] {
            cursor: pointer;
        }
        input[type="month"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            filter: invert(0.3) sepia(1) saturate(3) hue-rotate(225deg);
        }
        
        #loaderMovimientos { display: none; margin: 0 auto; }
        
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .movement-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <!-- Header con estadísticas -->
        <div class="movements-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="movements-title">
                        <i class="bi bi-arrow-left-right"></i> Movimientos de Inventario
                    </h1>
                    <p class="text-muted mb-0">Registro detallado de entradas, salidas y ajustes de stock</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="new.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Registrar Movimiento
                    </a>
                    <a href="manage_types.php" class="btn btn-outline-info">
                        <i class="bi bi-gear"></i> Gestionar Tipos
                    </a>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div id="estadisticasContainer">
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total_movimientos'] ?? 0 ?></span>
                    <span class="stat-label">Total Movimientos <?= $mes_mostrado ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['productos_afectados'] ?? 0 ?></span>
                    <span class="stat-label">Productos Afectados <?= $mes_mostrado ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['usuarios_activos'] ?? 0 ?></span>
                    <span class="stat-label">Usuarios Activos <?= $mes_mostrado ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['entradas'] ?? 0 ?></span>
                    <span class="stat-label">Entradas <?= $mes_mostrado ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['salidas'] ?? 0 ?></span>
                    <span class="stat-label">Salidas <?= $mes_mostrado ?></span>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <div class="filters-title">
                <i class="bi bi-funnel"></i> Filtros de Búsqueda
            </div>
            <form method="GET" class="row g-3" id="formFiltrosMovimientos" autocomplete="off">
                <input type="hidden" name="pagina" id="inputPagina" value="<?= $pagina_actual ?>">
                <div class="col-md-3">
                    <label class="form-label">Producto</label>
                    <input type="text" name="producto" id="filtroProducto" class="form-control" placeholder="Nombre de producto" value="<?= htmlspecialchars($filtro_producto) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo de movimiento</label>
                    <select name="tipo" id="filtroTipo" class="form-select">
                        <option value="">Todos los tipos</option>
                        <?php foreach ($tipos_array as $t): ?>
                            <option value="<?= $t['movement_type_id'] ?>" <?= $filtro_tipo == $t['movement_type_id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Usuario</label>
                    <select name="usuario" id="filtroUsuario" class="form-select">
                        <option value="">Todos los usuarios</option>
                        <?php foreach ($usuarios_array as $u): ?>
                            <option value="<?= $u['user_id'] ?>" <?= $filtro_usuario == $u['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mes</label>
                    <input type="month" name="mes" id="filtroMes" class="form-control" 
                           value="<?= htmlspecialchars($filtro_mes) ?>" 
                           min="2020-01" max="2035-12">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="solo_bobinas" value="1" id="soloBobinas" <?= $filtro_bobina ? 'checked' : '' ?>>
                        <label class="form-check-label" for="soloBobinas">Solo bobinas</label>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <div id="loaderMovimientos" style="display: none; text-align: center; margin: 20px 0;">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
        </div>
        
        <!-- Información de filtro activo -->
        <?php if ($mostrar_solo_mes_actual): 
            // Función para traducir meses al español
            $meses_esp = [
                'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
                'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
                'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
                'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
            ];
            $mes_actual_eng = date('F Y');
            $mes_actual_esp = str_replace(array_keys($meses_esp), array_values($meses_esp), $mes_actual_eng);
        ?>
        <div class="alert alert-info d-flex align-items-center mb-3">
            <i class="bi bi-info-circle me-2"></i>
            <span>Mostrando movimientos de <strong><?= $mes_actual_esp ?></strong>. Usa el filtro de mes para ver movimientos de otros períodos.</span>
        </div>
        <?php endif; ?>

        <!-- Lista de movimientos -->
        <div class="movements-container">
            <div id="movimientosLista">
                <?php include __DIR__ . '/partials/lista.php'; ?>
            </div>
        </div>
        
        <!-- Contenedor de paginación -->
        <div id="paginacionContainer">
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Paginación de movimientos" class="mt-4">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Mostrando <?= ($offset + 1) ?> - <?= min($offset + $movimientos_por_pagina, $total_registros) ?> de <?= $total_registros ?> movimientos
                    </small>
                    <ul class="pagination mb-0" id="paginacionMovimientos">
                        <?php if ($pagina_actual > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="#" data-pagina="<?= ($pagina_actual - 1) ?>">
                                <i class="bi bi-chevron-left"></i> Anterior
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php 
                        $inicio = max(1, $pagina_actual - 2);
                        $fin = min($total_paginas, $pagina_actual + 2);
                        for ($i = $inicio; $i <= $fin; $i++): 
                            $active = $i == $pagina_actual ? 'active' : '';
                        ?>
                        <li class="page-item <?= $active ?>">
                            <a class="page-link" href="#" data-pagina="<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagina_actual < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" href="#" data-pagina="<?= ($pagina_actual + 1) ?>">
                                Siguiente <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
            <?php endif; ?>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Resalta el menú activo
        document.querySelector('.sidebar-movimientos').classList.add('active');

        const formFiltros = document.getElementById('formFiltrosMovimientos');
        const inputProducto = document.getElementById('filtroProducto');
        const inputMes = document.getElementById('filtroMes');
        const loader = document.getElementById('loaderMovimientos');
        const lista = document.getElementById('movimientosLista');
        const paginacionContainer = document.getElementById('paginacionContainer');
        const estadisticasContainer = document.getElementById('estadisticasContainer');
        let filtroTimeout = null;

        function buscarMovimientosAJAX(pagina = 1) {
            const formData = new FormData(formFiltros);
            formData.append('pagina', pagina);
            const params = new URLSearchParams(formData).toString();
            loader.style.display = 'block';
            lista.style.opacity = '0.5';
            paginacionContainer.style.opacity = '0.5';
            estadisticasContainer.style.opacity = '0.5';
            
            fetch('index.php?' + params + '&ajax=1')
                .then(res => res.json())
                .then(data => {
                    lista.innerHTML = data.lista;
                    paginacionContainer.innerHTML = data.paginacion;
                    estadisticasContainer.innerHTML = data.estadisticas;
                    loader.style.display = 'none';
                    lista.style.opacity = '1';
                    paginacionContainer.style.opacity = '1';
                    estadisticasContainer.style.opacity = '1';
                    actualizarURL(data.pagina_actual);
                })
                .catch(error => {
                    console.error('Error:', error);
                    loader.style.display = 'none';
                    lista.style.opacity = '1';
                    paginacionContainer.style.opacity = '1';
                    estadisticasContainer.style.opacity = '1';
                });
        }
        
        function actualizarURL(paginaActual) {
            if (paginaActual > 1) {
                const url = new URL(window.location);
                url.searchParams.set('pagina', paginaActual);
                window.history.replaceState({}, '', url);
            } else {
                const url = new URL(window.location);
                url.searchParams.delete('pagina');
                window.history.replaceState({}, '', url);
            }
        }

        inputProducto.addEventListener('input', function() {
            clearTimeout(filtroTimeout);
            filtroTimeout = setTimeout(() => buscarMovimientosAJAX(1), 400);
        });
        
        inputMes.addEventListener('change', function() {
            buscarMovimientosAJAX(1);
        });

        formFiltros.addEventListener('submit', function(e) {
            e.preventDefault();
            buscarMovimientosAJAX(1);
        });

        document.getElementById('filtroTipo').addEventListener('change', () => buscarMovimientosAJAX(1));
        document.getElementById('filtroUsuario').addEventListener('change', () => buscarMovimientosAJAX(1));
        document.getElementById('soloBobinas').addEventListener('change', () => buscarMovimientosAJAX(1));
        
        // Manejar clics en paginación
        document.addEventListener('click', function(e) {
            if (e.target.closest('#paginacionContainer a')) {
                e.preventDefault();
                const pagina = e.target.closest('a').dataset.pagina;
                if (pagina) {
                    buscarMovimientosAJAX(parseInt(pagina));
                }
            }
            
            // Manejar clics en ordenamiento
            if (e.target.closest('#movimientosLista th[data-col]')) {
                e.preventDefault();
                const th = e.target.closest('th[data-col]');
                const col = th.dataset.col;
                const dir = th.dataset.dir;
                
                // Agregar parámetros de ordenamiento al formulario
                const hiddenOrden = document.createElement('input');
                hiddenOrden.type = 'hidden';
                hiddenOrden.name = 'orden';
                hiddenOrden.value = col;
                
                const hiddenDir = document.createElement('input');
                hiddenDir.type = 'hidden';
                hiddenDir.name = 'dir';
                hiddenDir.value = dir;
                
                // Remover inputs de ordenamiento previos
                const prevOrden = formFiltros.querySelector('input[name="orden"]');
                const prevDir = formFiltros.querySelector('input[name="dir"]');
                if (prevOrden) prevOrden.remove();
                if (prevDir) prevDir.remove();
                
                // Agregar nuevos
                formFiltros.appendChild(hiddenOrden);
                formFiltros.appendChild(hiddenDir);
                
                buscarMovimientosAJAX(1);
            }
        });
    </script>
</body>
</html>