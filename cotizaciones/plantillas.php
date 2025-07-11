<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Filtros
$filtro_categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
$filtro_usuario = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
$filtro_busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

// Construir consulta con filtros usando cotizaciones como plantillas
$where_conditions = [];
$params = [];
$param_types = '';

if ($filtro_busqueda) {
    $where_conditions[] = "(c.numero_cotizacion LIKE ? OR cl.nombre LIKE ?)";
    $params[] = "%$filtro_busqueda%";
    $params[] = "%$filtro_busqueda%";
    $param_types .= 'ss';
}

// Mostrar cotizaciones del usuario actual como plantillas
$where_conditions[] = "c.user_id = ?";
$params[] = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0;
$param_types .= 'i';

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Consulta principal - usar cotizaciones como plantillas
$query = "
    SELECT 
        c.cotizacion_id as plantilla_id,
        CONCAT('Plantilla: ', c.numero_cotizacion) as nombre,
        CONCAT('Cotización del ', DATE_FORMAT(c.fecha_cotizacion, '%d/%m/%Y'), ' - Cliente: ', cl.nombre) as descripcion,
        'Cotizaciones' as categoria,
        u.username as creador_nombre,
        cl.nombre as cliente_nombre,
        COUNT(cp.cotizacion_producto_id) as total_productos,
        SUM(cp.precio_total) as valor_total,
        1 as es_publica,
        c.created_at
    FROM cotizaciones c
    LEFT JOIN users u ON c.user_id = u.user_id
    LEFT JOIN clientes cl ON c.cliente_id = cl.cliente_id
    LEFT JOIN cotizaciones_productos cp ON c.cotizacion_id = cp.cotizacion_id
    $where_clause
    GROUP BY c.cotizacion_id
    ORDER BY c.created_at DESC
    LIMIT 20
";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$plantillas = $stmt->get_result();

// Obtener categorías para filtro (simuladas)
$categorias_array = [
    ['categoria' => 'Cotizaciones'],
    ['categoria' => 'Ventas'],
    ['categoria' => 'Servicios']
];

// Obtener usuarios para filtro
$usuarios = $mysqli->query("SELECT user_id, username FROM users ORDER BY username ASC");
$usuarios_array = $usuarios ? $usuarios->fetch_all(MYSQLI_ASSOC) : [];

// Estadísticas
$stats_query = "
    SELECT 
        COUNT(*) as total_plantillas,
        1 as total_categorias,
        AVG(total) as promedio_valor
    FROM cotizaciones 
    WHERE user_id = ?
";
$stmt_stats = $mysqli->prepare($stats_query);
$user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0;
$stmt_stats->bind_param('i', $user_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plantillas de Cotizaciones | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; }
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
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
            border-left: 4px solid #121866;
        }
        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #121866;
            margin: 0 0 8px 0;
        }
        .stat-card p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }
        .filtros-container {
            background: #fff;
            border-radius: 12px;
            padding: 20px 24px 10px 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: end;
        }
        .filtros-container .form-label { font-weight: 600; color: #232a7c; }
        .filtros-container .form-select, .filtros-container .form-control { min-width: 120px; }
        .plantilla-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
            border-left: 4px solid #121866;
            transition: transform 0.2s ease;
        }
        .plantilla-card:hover {
            transform: translateY(-2px);
        }
        .categoria-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .acciones-plantilla {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }
        .btn-accion {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .plantilla-publica {
            border-left-color: #28a745;
        }
        .plantilla-privada {
            border-left-color: #6c757d;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .stats-cards { grid-template-columns: repeat(2, 1fr); }
            .acciones-plantilla { flex-wrap: wrap; }
            .filtros-container { flex-direction: column; gap: 10px; padding: 12px 6px 6px 6px; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-file-earmark-text"></i> Plantillas de Cotizaciones</h2>
            <div>
                <a href="crear.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nueva Cotización
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats-cards">
            <div class="stat-card">
                <h3><?= $stats['total_plantillas'] ?: 0 ?></h3>
                <p>Total Plantillas</p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['total_categorias'] ?: 0 ?></h3>
                <p>Categorías</p>
            </div>
            <div class="stat-card">
                <h3>$<?= number_format($stats['promedio_valor'] ?? 0, 2) ?></h3>
                <p>Valor Promedio</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros-container">
            <form method="GET" class="row g-3 flex-grow-1" autocomplete="off" style="width:100%;">
                <div class="col-md-6 col-12">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="busqueda" class="form-control" value="<?= htmlspecialchars($filtro_busqueda) ?>" placeholder="Número de cotización o cliente">
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label">Categoría</label>
                    <select name="categoria" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($categorias_array as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['categoria']) ?>" <?= $filtro_categoria === $cat['categoria'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['categoria']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-12 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="plantillas.php" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>

        <!-- Lista de plantillas -->
        <?php if ($plantillas && $plantillas->num_rows > 0): ?>
            <?php while ($plantilla = $plantillas->fetch_assoc()): ?>
                <div class="plantilla-card plantilla-publica" data-id="<?= $plantilla['plantilla_id'] ?>">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-1"><?= htmlspecialchars($plantilla['nombre']) ?></h5>
                            <p class="text-muted mb-0"><?= htmlspecialchars($plantilla['descripcion']) ?></p>
                            <span class="categoria-badge" style="background-color: #17a2b8; color: #fff;">
                                <?= htmlspecialchars($plantilla['categoria']) ?>
                            </span>
                            <span class="badge bg-success ms-2">Pública</span>
                        </div>
                        <div class="col-md-2">
                            <strong>$<?= number_format($plantilla['valor_total'] ?? 0, 2) ?></strong>
                            <br><small class="text-muted"><?= $plantilla['total_productos'] ?> productos</small>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Creada por:</small><br>
                            <strong><?= htmlspecialchars($plantilla['creador_nombre'] ?? 'Sistema') ?></strong>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Cliente original:</small><br>
                            <strong><?= htmlspecialchars($plantilla['cliente_nombre'] ?? 'No especificado') ?></strong>
                        </div>
                        <div class="col-md-2">
                            <div class="acciones-plantilla">
                                <a href="aplicar_plantilla.php?id=<?= $plantilla['plantilla_id'] ?>" class="btn-accion btn btn-success">
                                    <i class="bi bi-play-circle"></i> Aplicar
                                </a>
                                <a href="ver.php?id=<?= $plantilla['plantilla_id'] ?>" class="btn-accion btn btn-outline-primary">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <a href="duplicar_plantilla.php?id=<?= $plantilla['plantilla_id'] ?>" class="btn-accion btn btn-outline-info">
                                    <i class="bi bi-files"></i> Duplicar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-file-earmark-text" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">No hay plantillas disponibles</h4>
                <p class="text-muted">Crea algunas cotizaciones primero para que aparezcan como plantillas</p>
                <a href="crear.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Crear Cotización
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-cotizaciones').classList.add('active');
    </script>
</body>
</html> 