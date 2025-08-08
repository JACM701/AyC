<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    // Obtener nombre de usuario
    $username = $_SESSION['username'] ?? 'Usuario';
    $user_role = $_SESSION['user_role'] ?? 'Usuario';

    // Obtener estadísticas reales de la base de datos
    // Primero verificar si existe la columna tipo_gestion
    $check_column = $mysqli->query("SHOW COLUMNS FROM products LIKE 'tipo_gestion'");
    $has_tipo_gestion = $check_column->num_rows > 0;
    
    if (!$has_tipo_gestion) {
        // Si no existe la columna, usar consulta simple
        $stats_query = "
            SELECT 
                COUNT(*) as total_products,
                SUM(CASE WHEN quantity > 10 THEN 1 ELSE 0 END) as disponibles,
                SUM(CASE WHEN quantity > 0 AND quantity <= 10 THEN 1 ELSE 0 END) as bajo_stock,
                SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as agotados,
                SUM(quantity * price) as valor_total
            FROM products
        ";
    } else {
        // Si existe la columna, usar consulta avanzada
        $stats_query = "
            SELECT 
                COUNT(*) as total_products,
                SUM(CASE 
                    WHEN COALESCE(tipo_gestion, 'unidad') = 'bobina' THEN 
                        CASE WHEN quantity > 100 THEN 1 ELSE 0 END
                    ELSE 
                        CASE WHEN quantity > 10 THEN 1 ELSE 0 END
                END) as disponibles,
                SUM(CASE 
                    WHEN COALESCE(tipo_gestion, 'unidad') = 'bobina' THEN 
                        CASE WHEN quantity > 0 AND quantity <= 100 THEN 1 ELSE 0 END
                    ELSE 
                        CASE WHEN quantity > 0 AND quantity <= 10 THEN 1 ELSE 0 END
                END) as bajo_stock,
                SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as agotados,
                SUM(quantity * price) as valor_total
            FROM products
        ";
    }
    
    $stats_result = $mysqli->query($stats_query);
    $stats = $stats_result ? $stats_result->fetch_assoc() : [
        'total_products' => 0,
        'disponibles' => 0,
        'bajo_stock' => 0,
        'agotados' => 0,
        'valor_total' => 0
    ];

    // Obtener producto con menor stock (considerando tipo de gestión)
    if (!$has_tipo_gestion) {
        $min_stock_query = "
            SELECT product_name, quantity, c.name as category_name,
                   CONCAT(quantity, ' unidades') as stock_display
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE quantity > 0
            ORDER BY quantity ASC
            LIMIT 1
        ";
    } else {
        $min_stock_query = "
            SELECT 
                product_name, 
                quantity, 
                c.name as category_name,
                COALESCE(tipo_gestion, 'unidad') as tipo_gestion,
                CASE 
                    WHEN COALESCE(tipo_gestion, 'unidad') = 'bobina' THEN CONCAT(ROUND(quantity, 1), ' metros')
                    ELSE CONCAT(quantity, ' unidades')
                END as stock_display
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE quantity > 0
            ORDER BY 
                CASE 
                    WHEN COALESCE(tipo_gestion, 'unidad') = 'bobina' THEN quantity / 100
                    ELSE quantity
                END ASC
            LIMIT 1
        ";
    }
    $min_stock_result = $mysqli->query($min_stock_query);
    $min_stock = $min_stock_result ? $min_stock_result->fetch_assoc() : null;

    // Obtener producto con mayor stock (considerando tipo de gestión)
    if (!$has_tipo_gestion) {
        $max_stock_query = "
            SELECT product_name, quantity, c.name as category_name,
                   CONCAT(quantity, ' unidades') as stock_display
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            ORDER BY quantity DESC
            LIMIT 1
        ";
    } else {
        $max_stock_query = "
            SELECT 
                product_name, 
                quantity, 
                c.name as category_name,
                COALESCE(tipo_gestion, 'unidad') as tipo_gestion,
                CASE 
                    WHEN COALESCE(tipo_gestion, 'unidad') = 'bobina' THEN CONCAT(ROUND(quantity, 1), ' metros')
                    ELSE CONCAT(quantity, ' unidades')
                END as stock_display
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            ORDER BY 
                CASE 
                    WHEN COALESCE(tipo_gestion, 'unidad') = 'bobina' THEN quantity / 100
                    ELSE quantity
                END DESC
            LIMIT 1
        ";
    }
    $max_stock_result = $mysqli->query($max_stock_query);
    $max_stock = $max_stock_result ? $max_stock_result->fetch_assoc() : null;

    // Obtener último producto agregado
    $last_product_query = "
        SELECT product_name, created_at
        FROM products
        ORDER BY created_at DESC
        LIMIT 1
    ";
    $last_product_result = $mysqli->query($last_product_query);
    $last_product = $last_product_result ? $last_product_result->fetch_assoc() : null;

    // Obtener movimientos de hoy
    $movimientos_hoy_query = "
        SELECT COUNT(*) as total
        FROM movements
        WHERE DATE(movement_date) = CURDATE()
    ";
    $movimientos_hoy_result = $mysqli->query($movimientos_hoy_query);
    $movimientos_hoy = $movimientos_hoy_result ? $movimientos_hoy_result->fetch_assoc()['total'] : 0;

    // Obtener producto más movido
    $most_moved_query = "
        SELECT p.product_name, COUNT(*) as total_movs
        FROM movements m
        JOIN products p ON m.product_id = p.product_id
        GROUP BY m.product_id
        ORDER BY total_movs DESC
        LIMIT 1
    ";
    $most_moved_result = $mysqli->query($most_moved_query);
    $most_moved = $most_moved_result ? $most_moved_result->fetch_assoc() : null;

    // Obtener categoría más popular
    $top_category_query = "
        SELECT c.name as category_name, COUNT(*) as total
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        GROUP BY p.category_id
        ORDER BY total DESC
        LIMIT 1
    ";
    $top_category_result = $mysqli->query($top_category_query);
    $top_category = $top_category_result ? $top_category_result->fetch_assoc() : null;

    // Obtener proveedor más popular
    $top_supplier_query = "
        SELECT s.name as supplier_name, COUNT(*) as total
        FROM products p
        LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
        GROUP BY p.supplier_id
        ORDER BY total DESC
        LIMIT 1
    ";
    $top_supplier_result = $mysqli->query($top_supplier_query);
    $top_supplier = $top_supplier_result ? $top_supplier_result->fetch_assoc() : null;

    // Obtener datos para gráficas (distinguiendo bobinas de productos normales)
    if (!$has_tipo_gestion) {
        $stock_data_query = "
            SELECT 
                CONCAT(product_name, ' (unidades)') as product_display,
                quantity as quantity_display
            FROM products p
            WHERE quantity > 0
            ORDER BY quantity DESC
            LIMIT 6
        ";
    } else {
        $stock_data_query = "
            SELECT 
                CASE 
                    WHEN COALESCE(tipo_gestion, 'unidad') = 'bobina' THEN CONCAT(product_name, ' (metros)')
                    ELSE CONCAT(product_name, ' (unidades)')
                END as product_display,
                CASE 
                    WHEN COALESCE(tipo_gestion, 'unidad') = 'bobina' THEN ROUND(quantity, 1)
                    ELSE quantity
                END as quantity_display
            FROM products p
            WHERE quantity > 0
            ORDER BY 
                CASE 
                    WHEN COALESCE(tipo_gestion, 'unidad') = 'bobina' THEN quantity / 100
                    ELSE quantity
                END DESC
            LIMIT 6
        ";
    }
    $stock_data_result = $mysqli->query($stock_data_query);
    $labels_stock = [];
    $data_stock = [];
    if ($stock_data_result) {
        while ($row = $stock_data_result->fetch_assoc()) {
            $labels_stock[] = $row['product_display'];
            $data_stock[] = $row['quantity_display'];
        }
    }

    // Obtener movimientos de la semana
    $movements_week_query = "
        SELECT 
            DATE(movement_date) as fecha,
            SUM(CASE WHEN quantity > 0 THEN 1 ELSE 0 END) as entradas,
            SUM(CASE WHEN quantity < 0 THEN 1 ELSE 0 END) as salidas
        FROM movements
        WHERE movement_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(movement_date)
        ORDER BY fecha
    ";
    $movements_week_result = $mysqli->query($movements_week_query);
    $labels_movs = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    $data_entradas = array_fill(0, 7, 0);
    $data_salidas = array_fill(0, 7, 0);
    
    $day_index = 0;
    while ($row = $movements_week_result->fetch_assoc()) {
        $data_entradas[$day_index] = $row['entradas'];
        $data_salidas[$day_index] = $row['salidas'];
        $day_index++;
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Gestor de inventarios Alarmas y Cámaras de seguridad del sureste</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --bg-card: #ffffff;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            
            --border-color: #e2e8f0;
            --border-radius: 16px;
            --border-radius-lg: 24px;
            
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-primary);
            line-height: 1.6;
        }

        .main-content {
            margin-left: 270px;
            padding: 2rem;
            min-height: 100vh;
            background: var(--bg-primary);
        }

        /* Header profesional */
        .dashboard-header {
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--bg-gradient);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-title h1 {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
        }

        .header-title .icon {
            width: 3rem;
            height: 3rem;
            background: var(--bg-gradient);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .header-subtitle {
            color: var(--text-secondary);
            font-size: 1.125rem;
            font-weight: 500;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: var(--bg-secondary);
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
        }

        .user-avatar {
            width: 3rem;
            height: 3rem;
            background: var(--bg-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .user-details h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .user-details p {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .live-time {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.125rem;
        }

        /* Cards estadísticas profesionales */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-accent);
        }

        .stat-card.primary::before { background: var(--primary-color); }
        .stat-card.success::before { background: var(--success-color); }
        .stat-card.warning::before { background: var(--warning-color); }
        .stat-card.danger::before { background: var(--danger-color); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .stat-icon.primary { background: var(--primary-color); }
        .stat-icon.success { background: var(--success-color); }
        .stat-icon.warning { background: var(--warning-color); }
        .stat-icon.danger { background: var(--danger-color); }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
        }

        .stat-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .stat-change {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .stat-change.positive { color: var(--success-color); }
        .stat-change.negative { color: var(--danger-color); }

        /* Sección de información detallada */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .info-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .info-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: var(--bg-secondary);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 1.125rem;
        }

        .info-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .info-content {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-main {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .info-detail {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* Sección de gráficas profesional */
        .charts-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .chart-card {
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
        }

        .chart-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--bg-secondary);
        }

        .chart-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .chart-title h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .chart-title .icon {
            width: 2.5rem;
            height: 2.5rem;
            background: var(--bg-gradient);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.125rem;
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin-top: 1rem;
        }

        /* Responsive design mejorado */
        @media (max-width: 1200px) {
            .main-content {
                margin-left: 250px;
            }
            
            .charts-section {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .header-title h1 {
                font-size: 1.875rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
        }

        /* Animaciones suaves */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card, .info-card, .chart-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <!-- Header Profesional -->
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-title">
                    <div class="icon">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div>
                        <h1>Dashboard Ejecutivo</h1>
                        <p class="header-subtitle">Panel de control del sistema de gestión de inventarios</p>
                    </div>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($username, 0, 2)) ?>
                    </div>
                    <div class="user-details">
                        <h3><?= htmlspecialchars($username) ?></h3>
                        <p><?= htmlspecialchars($user_role) ?></p>
                    </div>
                    <div class="live-time" id="horaActual"></div>
                </div>
            </div>
        </header>

        <!-- Estadísticas Principales -->
        <section class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-header">
                    <div>
                        <div class="stat-label">Total Productos</div>
                        <div class="stat-value"><?= number_format($stats['total_products'] ?? 0) ?></div>
                    </div>
                    <div class="stat-icon primary">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span>Inventario completo</span>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-label">Disponibles</div>
                        <div class="stat-value"><?= number_format($stats['disponibles'] ?? 0) ?></div>
                    </div>
                    <div class="stat-icon success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span><?= round((($stats['disponibles'] ?? 0) / max($stats['total_products'] ?? 1, 1)) * 100, 1) ?>% del total</span>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-label">Bajo Stock</div>
                        <div class="stat-value"><?= number_format($stats['bajo_stock'] ?? 0) ?></div>
                    </div>
                    <div class="stat-icon warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                </div>
                <div class="stat-change negative">
                    <i class="bi bi-arrow-down"></i>
                    <span>Requiere atención</span>
                </div>
            </div>

            <div class="stat-card danger">
                <div class="stat-header">
                    <div>
                        <div class="stat-label">Agotados</div>
                        <div class="stat-value"><?= number_format($stats['agotados'] ?? 0) ?></div>
                    </div>
                    <div class="stat-icon danger">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                </div>
                <div class="stat-change negative">
                    <i class="bi bi-exclamation-circle"></i>
                    <span>Crítico</span>
                </div>
            </div>
        </section>

        <!-- Información Detallada -->
        <section class="info-grid">
            <div class="info-card">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="bi bi-graph-down-arrow"></i>
                    </div>
                    <div class="info-title">Producto con Menor Stock</div>
                </div>
                <div class="info-content">
                    <div class="info-main"><?= $min_stock ? htmlspecialchars($min_stock['product_name']) : 'Sin datos' ?></div>
                    <div class="info-detail"><?= $min_stock ? $min_stock['stock_display'] : 'No hay productos disponibles' ?></div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <div class="info-title">Movimientos Hoy</div>
                </div>
                <div class="info-content">
                    <div class="info-main"><?= number_format($movimientos_hoy) ?> movimientos</div>
                    <div class="info-detail">Actividad del día actual</div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="info-title">Último Producto Agregado</div>
                </div>
                <div class="info-content">
                    <div class="info-main"><?= $last_product ? htmlspecialchars($last_product['product_name']) : 'Sin datos' ?></div>
                    <div class="info-detail"><?= $last_product ? date('d/m/Y H:i', strtotime($last_product['created_at'])) : 'No disponible' ?></div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="bi bi-tags-fill"></i>
                    </div>
                    <div class="info-title">Categoría Principal</div>
                </div>
                <div class="info-content">
                    <div class="info-main"><?= $top_category ? htmlspecialchars($top_category['category_name']) : 'Sin datos' ?></div>
                    <div class="info-detail"><?= $top_category ? number_format($top_category['total']) . ' productos' : 'No disponible' ?></div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="info-title">Proveedor Principal</div>
                </div>
                <div class="info-content">
                    <div class="info-main"><?= $top_supplier ? htmlspecialchars($top_supplier['supplier_name']) : 'Sin datos' ?></div>
                    <div class="info-detail"><?= $top_supplier ? number_format($top_supplier['total']) . ' productos' : 'No disponible' ?></div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div class="info-title">Producto con Mayor Stock</div>
                </div>
                <div class="info-content">
                    <div class="info-main"><?= $max_stock ? htmlspecialchars($max_stock['product_name']) : 'Sin datos' ?></div>
                    <div class="info-detail"><?= $max_stock ? $max_stock['stock_display'] : 'No disponible' ?></div>
                </div>
            </div>
        </section>

        <!-- Gráficas -->
        <section class="charts-section">
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <div class="icon">
                            <i class="bi bi-pie-chart-fill"></i>
                        </div>
                        <h3>Distribución de Stock</h3>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="stockChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <div class="icon">
                            <i class="bi bi-bar-chart-fill"></i>
                        </div>
                        <h3>Movimientos Semanales</h3>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="movementsChart"></canvas>
                </div>
            </div>
        </section>
    </main>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Actualizar hora en tiempo real
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('horaActual').textContent = timeString;
        }
        
        updateTime();
        setInterval(updateTime, 1000);

        // Gráfica de stock
        const stockCtx = document.getElementById('stockChart').getContext('2d');
        new Chart(stockCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels_stock) ?>,
                datasets: [{
                    data: <?= json_encode($data_stock) ?>,
                    backgroundColor: [
                        '#667eea', '#764ba2', '#f093fb', '#f5576c', 
                        '#4facfe', '#00f2fe', '#fa709a', '#fee140'
                    ],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverBorderWidth: 4,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 25,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#667eea',
                        borderWidth: 1,
                        cornerRadius: 10,
                        displayColors: true
                    }
                },
                elements: {
                    arc: {
                        borderRadius: 5
                    }
                }
            }
        });

        // Gráfica de movimientos
        const movementsCtx = document.getElementById('movementsChart').getContext('2d');
        new Chart(movementsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_movs) ?>,
                datasets: [{
                    label: 'Entradas',
                    data: <?= json_encode($data_entradas) ?>,
                    backgroundColor: 'rgba(79, 172, 254, 0.8)',
                    borderColor: '#4facfe',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                    hoverBackgroundColor: '#4facfe',
                    hoverBorderColor: '#00f2fe'
                }, {
                    label: 'Salidas',
                    data: <?= json_encode($data_salidas) ?>,
                    backgroundColor: 'rgba(245, 87, 108, 0.8)',
                    borderColor: '#f5576c',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                    hoverBackgroundColor: '#f5576c',
                    hoverBorderColor: '#f093fb'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                weight: '500'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            stepSize: 1,
                            font: {
                                weight: '500'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#667eea',
                        borderWidth: 1,
                        cornerRadius: 10
                    }
                }
            }
        });
    </script>
</body>
</html>