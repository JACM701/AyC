<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    // Obtener nombre de usuario
    $username = '';
    if (isset($_SESSION['admin_id'])) {
        $admin_id = $_SESSION['admin_id'];
        $stmt = $mysqli->prepare("SELECT username FROM admins WHERE admin_id = ?");
        $stmt->bind_param('i', $admin_id);
        $stmt->execute();
        $stmt->bind_result($username);
        $stmt->fetch();
        $stmt->close();
    } elseif (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $stmt = $mysqli->prepare("SELECT username FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($username);
        $stmt->fetch();
        $stmt->close();
    }

    // Estadísticas principales
    $result = $mysqli->query("SELECT COUNT(*) AS total_products FROM products");
    $total_products = $result->fetch_assoc()['total_products'];

    $result = $mysqli->query("SELECT SUM(quantity) AS total_quantity FROM products");
    $total_quantity = $result->fetch_assoc()['total_quantity'] ?? 0;

    $result = $mysqli->query("SELECT SUM(quantity) AS total_in FROM movements WHERE movement_type = 'in'");
    $total_in = $result->fetch_assoc()['total_in'] ?? 0;

    $result = $mysqli->query("SELECT SUM(quantity) AS total_out FROM movements WHERE movement_type = 'out'");
    $total_out = $result->fetch_assoc()['total_out'] ?? 0;

    // Producto con menor stock
    $result = $mysqli->query("SELECT product_name, quantity FROM products ORDER BY quantity ASC LIMIT 1");
    $min_stock = $result->fetch_assoc();

    // Producto con mayor stock
    $result = $mysqli->query("SELECT product_name, quantity FROM products ORDER BY quantity DESC LIMIT 1");
    $max_stock = $result->fetch_assoc();

    // Último producto agregado
    $result = $mysqli->query("SELECT product_name, created_at FROM products ORDER BY created_at DESC LIMIT 1");
    $last_product = $result->fetch_assoc();

    // Movimientos de hoy
    $today = date('Y-m-d');
    $result = $mysqli->query("SELECT COUNT(*) AS movimientos_hoy FROM movements WHERE DATE(movement_date) = '$today'");
    $movimientos_hoy = $result->fetch_assoc()['movimientos_hoy'];

    // Producto más movido (más movimientos sumando entradas y salidas)
    $result = $mysqli->query("SELECT p.product_name, COUNT(m.movement_id) AS total_movs FROM movements m JOIN products p ON m.product_id = p.product_id GROUP BY m.product_id ORDER BY total_movs DESC LIMIT 1");
    $most_moved = $result->fetch_assoc();

    // Categoría con más productos
    $result = $mysqli->query("
        SELECT c.name as category_name, COUNT(p.product_id) as total 
        FROM categories c 
        LEFT JOIN products p ON c.category_id = p.category_id 
        GROUP BY c.category_id, c.name 
        ORDER BY total DESC 
        LIMIT 1
    ");
    $top_category = $result->fetch_assoc();

    // Proveedor con más productos
    $result = $mysqli->query("SELECT supplier, COUNT(*) as total FROM products WHERE supplier IS NOT NULL AND supplier != '' GROUP BY supplier ORDER BY total DESC LIMIT 1");
    $top_supplier = $result->fetch_assoc();

    // --- DATOS PARA GRÁFICAS ---
    // Stock por producto (para gráfica de pastel)
    $result = $mysqli->query("SELECT product_name, quantity FROM products ORDER BY product_name");
    $labels_stock = [];
    $data_stock = [];
    while ($row = $result->fetch_assoc()) {
        $labels_stock[] = $row['product_name'];
        $data_stock[] = (int)$row['quantity'];
    }

    // Movimientos últimos 7 días (para gráfica de barras)
    $result = $mysqli->query("
        SELECT DATE(movement_date) as fecha, 
               SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE 0 END) as entradas,
               SUM(CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END) as salidas
        FROM movements
        WHERE movement_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY fecha
        ORDER BY fecha ASC
    ");
    $labels_movs = [];
    $data_entradas = [];
    $data_salidas = [];
    while ($row = $result->fetch_assoc()) {
        $labels_movs[] = date('d/m', strtotime($row['fecha']));
        $data_entradas[] = (int)$row['entradas'];
        $data_salidas[] = (int)$row['salidas'];
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Gestor de inventarios Alarmas y Cámaras de seguridad del sureste</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6fb;
        }
        .main-content {
            background: #f4f6fb;
            border-radius: 18px;
            box-shadow: 0 4px 32px rgba(18,24,102,0.07);
            margin-top: 18px;
            padding: 0 0 32px 0;
        }
        .dashboard-header {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 36px;
            align-items: center;
        }
        .dashboard-header h2 {
            font-size: 2.2rem;
            color: #121866;
            font-weight: 800;
            margin-bottom: 0;
            letter-spacing: 0.5px;
        }
        .dashboard-header p {
            color: #232a7c;
            font-size: 1.15rem;
            margin: 0;
        }
        .dashboard-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            margin: 0 0 18px 0;
            justify-content: center;
        }
        .card {
            flex: 1 1 220px;
            background: #fff;
            padding: 38px 24px 30px 24px;
            border-radius: 14px;
            text-align: center;
            box-shadow: 0 2px 16px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 220px;
            min-height: 140px;
            position: relative;
        }
        .card h3 {
            color: #121866;
            font-size: 1.15rem;
            margin-bottom: 12px;
            font-weight: 700;
        }
        .card p {
            font-size: 2.2rem;
            font-weight: 700;
            color: #232a7c;
            margin: 0;
        }
        .card .icon {
            font-size: 2.2rem;
            color: #232a7c;
            margin-bottom: 8px;
        }
        .dashboard-extra {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            margin-bottom: 32px;
            justify-content: center;
        }
        .extra-card {
            flex: 1 1 220px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(18,24,102,0.07);
            padding: 18px 18px 14px 18px;
            min-width: 220px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            border-left: 5px solid #121866;
        }
        .extra-card h4 {
            color: #121866;
            font-size: 1.05rem;
            margin-bottom: 6px;
            font-weight: 700;
        }
        .extra-card p {
            font-size: 1.1rem;
            color: #232a7c;
            margin: 0;
        }
        .header-hora {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(18,24,102,0.07);
            padding: 18px 28px;
            margin-bottom: 32px;
        }
        .header-hora .saludo {
            font-size: 1.3rem;
            color: #121866;
            font-weight: 700;
        }
        .header-hora .hora {
            font-size: 1.1rem;
            color: #232a7c;
            font-weight: 500;
            letter-spacing: 1px;
        }
        .header-hora .usuario {
            font-size: 1.1rem;
            color: #121866;
            font-weight: 600;
            margin-left: 18px;
        }
        .dashboard-graficas {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            margin-top: 38px;
            justify-content: center;
        }
        .grafica-card {
            flex: 1 1 380px;
            min-width: 320px;
            max-width: 480px;
            background: #fff;
            padding: 28px 18px 18px 18px;
            border-radius: 14px;
            box-shadow: 0 2px 16px rgba(18,24,102,0.10);
            margin-bottom: 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .grafica-card h3 {
            color: #121866;
            font-size: 1.13rem;
            margin-bottom: 18px;
            text-align: center;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .grafica-card canvas {
            display: block;
            width: 320px !important;
            height: 320px !important;
            max-width: 100vw;
            max-height: 60vw;
            aspect-ratio: 1/1;
            margin: 0 auto;
        }
        .grafica-card.barras {
            align-items: stretch;
            overflow-x: auto;
            padding: 18px 0 18px 0;
        }
        .grafica-card.barras canvas {
            width: 100% !important;
            height: 320px !important;
            max-width: 520px;
            min-width: 240px;
            max-height: 400px;
            display: block;
            margin: 0 auto;
        }
        @media (max-width: 900px) {
            .dashboard-graficas { flex-direction: column; gap: 18px; }
            .grafica-card { max-width: 98vw; }
            .grafica-card canvas { width: 90vw !important; height: 90vw !important; max-width: 350px; max-height: 350px; }
            .grafica-card.barras canvas { width: 100% !important; height: 240px !important; max-width: 98vw; max-height: 300px; }
            .dashboard-cards { flex-direction: column; gap: 18px; }
            .dashboard-header h2 { font-size: 1.3rem; }
            .dashboard-extra { flex-direction: column; gap: 12px; }
            .header-hora { flex-direction: column; gap: 8px; padding: 12px 10px; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="header-hora">
            <span class="saludo" id="saludo"></span>
            <span class="hora" id="hora"></span>
            <span class="usuario">👤 <?= htmlspecialchars($username) ?></span>
        </div>
        <div class="dashboard-header">
            <h2><i class="bi bi-speedometer2"></i> Bienvenido al panel de control</h2>
            <p>Consulta el estado general de tu inventario y accede rápidamente a las secciones principales.</p>
        </div>
        <div class="dashboard-cards">
            <div class="card">
                <span class="icon"><i class="bi bi-box-seam"></i></span>
                <h3>Total de productos</h3>
                <p><?= $total_products ?></p>
            </div>
            <div class="card">
                <span class="icon"><i class="bi bi-arrow-down-circle"></i></span>
                <h3>Entradas de inventario</h3>
                <p><?= $total_in ?></p>
            </div>
            <div class="card">
                <span class="icon"><i class="bi bi-arrow-up-circle"></i></span>
                <h3>Salidas de inventario</h3>
                <p><?= $total_out ?></p>
            </div>
            <div class="card">
                <span class="icon"><i class="bi bi-archive"></i></span>
                <h3>Inventario actual</h3>
                <p><?= $total_quantity ?></p>
            </div>
        </div>
        <div class="dashboard-extra">
            <div class="extra-card">
                <h4><i class="bi bi-arrow-bar-down"></i> Producto con menor stock</h4>
                <p><?= $min_stock ? htmlspecialchars($min_stock['product_name']) . ' (' . $min_stock['quantity'] . ')' : 'N/A' ?></p>
            </div>
            <div class="extra-card">
                <h4><i class="bi bi-arrow-bar-up"></i> Producto con mayor stock</h4>
                <p><?= $max_stock ? htmlspecialchars($max_stock['product_name']) . ' (' . $max_stock['quantity'] . ')' : 'N/A' ?></p>
            </div>
            <div class="extra-card">
                <h4><i class="bi bi-calendar-event"></i> Movimientos de hoy</h4>
                <p><?= $movimientos_hoy ?></p>
            </div>
            <div class="extra-card">
                <h4><i class="bi bi-plus-circle"></i> Último producto agregado</h4>
                <p><?= $last_product ? htmlspecialchars($last_product['product_name']) . ' (' . date('d/m/Y', strtotime($last_product['created_at'])) . ')' : 'N/A' ?></p>
            </div>
            <div class="extra-card">
                <h4><i class="bi bi-arrow-repeat"></i> Producto más movido</h4>
                <p><?= $most_moved ? htmlspecialchars($most_moved['product_name']) . ' (' . $most_moved['total_movs'] . ' movimientos)' : 'N/A' ?></p>
            </div>
            <div class="extra-card">
                <h4><i class="bi bi-tags"></i> Categoría principal</h4>
                <p><?= $top_category ? htmlspecialchars($top_category['category_name']) . ' (' . $top_category['total'] . ' productos)' : 'N/A' ?></p>
            </div>
            <div class="extra-card">
                <h4><i class="bi bi-truck"></i> Proveedor principal</h4>
                <p><?= $top_supplier ? htmlspecialchars($top_supplier['supplier']) . ' (' . $top_supplier['total'] . ' productos)' : 'N/A' ?></p>
            </div>
        </div>
        <div class="dashboard-graficas">
            <div class="grafica-card">
                <h3><i class="bi bi-pie-chart"></i> Stock por producto</h3>
                <canvas id="graficaStock"></canvas>
            </div>
            <div class="grafica-card barras">
                <h3><i class="bi bi-bar-chart"></i> Movimientos últimos 7 días</h3>
                <canvas id="graficaMovimientos"></canvas>
            </div>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function actualizarHoraYSaludo() {
            const ahora = new Date();
            const hora = ahora.getHours();
            let saludo = '';
            if (hora >= 6 && hora < 12) saludo = '¡Buenos días!';
            else if (hora >= 12 && hora < 19) saludo = '¡Buenas tardes!';
            else saludo = '¡Buenas noches!';
            document.getElementById('saludo').textContent = saludo;
            document.getElementById('hora').textContent = ahora.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        actualizarHoraYSaludo();
        setInterval(actualizarHoraYSaludo, 1000);
        // Resalta el menú activo
        document.querySelector('.sidebar-dashboard').classList.add('active');

        // --- GRÁFICA DE PASTEL: STOCK POR PRODUCTO ---
        const ctxStock = document.getElementById('graficaStock').getContext('2d');
        new Chart(ctxStock, {
            type: 'pie',
            data: {
                labels: <?= json_encode($labels_stock) ?>,
                datasets: [{
                    data: <?= json_encode($data_stock) ?>,
                    backgroundColor: [
                        '#121866', '#232a7c', '#388e3c', '#e53935', '#ffc107', '#00bcd4', '#8e24aa', '#fbc02d', '#43a047', '#fb8c00', '#3949ab', '#c62828', '#00897b', '#6d4c41', '#f06292'
                    ],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: false }
                }
            }
        });

        // --- GRÁFICA DE BARRAS: MOVIMIENTOS ÚLTIMOS 7 DÍAS ---
        const ctxMovs = document.getElementById('graficaMovimientos').getContext('2d');
        new Chart(ctxMovs, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_movs) ?>,
                datasets: [
                    {
                        label: 'Entradas',
                        data: <?= json_encode($data_entradas) ?>,
                        backgroundColor: '#388e3c',
                    },
                    {
                        label: 'Salidas',
                        data: <?= json_encode($data_salidas) ?>,
                        backgroundColor: '#e53935',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>