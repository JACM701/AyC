<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: plantillas.php');
    exit;
}

$plantilla_id = intval($_GET['id']);

// Obtener información de la cotización como plantilla
$stmt = $mysqli->prepare("
    SELECT c.*, u.username as creador_nombre, cl.nombre as cliente_nombre
    FROM cotizaciones c
    LEFT JOIN users u ON c.user_id = u.user_id
    LEFT JOIN clientes cl ON c.cliente_id = cl.cliente_id
    WHERE c.cotizacion_id = ? AND c.user_id = ?
");
$user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0;
$stmt->bind_param('ii', $plantilla_id, $user_id);
$stmt->execute();
$plantilla = $stmt->get_result()->fetch_assoc();

if (!$plantilla) {
    $_SESSION['error'] = 'Plantilla no encontrada o no tienes permisos para acceder a ella.';
    header('Location: plantillas.php');
    exit;
}

// Obtener productos de la cotización
$stmt = $mysqli->prepare("
    SELECT cp.*, p.product_name, p.sku, p.price, p.quantity
    FROM cotizaciones_productos cp
    LEFT JOIN products p ON cp.product_id = p.product_id
    WHERE cp.cotizacion_id = ?
    ORDER BY cp.cotizacion_producto_id
");
$stmt->bind_param('i', $plantilla_id);
$stmt->execute();
$productos = $stmt->get_result();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $categoria = trim($_POST['categoria']);
    $tipo_servicio = trim($_POST['tipo_servicio']);
    $cliente_frecuente_id = $_POST['cliente_frecuente_id'] ?: null;
    $es_publica = isset($_POST['es_publica']) ? 1 : 0;
    
    if (!$nombre) {
        $error = 'El nombre de la plantilla es obligatorio.';
    }
    
    if (!$error) {
        $mysqli->begin_transaction();
        try {
            // Crear nueva cotización basada en la plantilla
            $year = date('Y');
            $stmt_count = $mysqli->prepare("SELECT COUNT(*) FROM cotizaciones WHERE numero_cotizacion LIKE ?");
            $pattern = "COT-$year-%";
            $stmt_count->bind_param('s', $pattern);
            $stmt_count->execute();
            $stmt_count->bind_result($count);
            $stmt_count->fetch();
            $stmt_count->close();
            
            $next_number = $count + 1;
            $numero_cotizacion = sprintf("COT-%s-%04d", $year, $next_number);
            
            // Crear cotización
            $fecha_cotizacion = date('Y-m-d');
            $validez_dias = 30;
            $estado_id = 1; // Borrador
            $usuario_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
            
            // Calcular totales
            $subtotal = 0;
            $productos->data_seek(0);
            while ($prod = $productos->fetch_assoc()) {
                $subtotal += floatval($prod['precio_unitario']) * intval($prod['cantidad']);
            }
            
            $stmt = $mysqli->prepare("INSERT INTO cotizaciones (numero_cotizacion, cliente_id, fecha_cotizacion, validez_dias, subtotal, total, estado_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sisddiii', $numero_cotizacion, $cliente_frecuente_id, $fecha_cotizacion, $validez_dias, $subtotal, $subtotal, $estado_id, $usuario_id);
            $stmt->execute();
            $cotizacion_id = $stmt->insert_id;
            $stmt->close();
            
            // Insertar productos
            $productos->data_seek(0);
            while ($prod = $productos->fetch_assoc()) {
                $product_id = $prod['product_id'] ?? null;
                $cantidad = $prod['cantidad'];
                $precio_unitario = $prod['precio_unitario'];
                $precio_total = $precio_unitario * $cantidad;
                
                $stmt = $mysqli->prepare("INSERT INTO cotizaciones_productos (cotizacion_id, product_id, cantidad, precio_unitario, precio_total) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('iiddd', $cotizacion_id, $product_id, $cantidad, $precio_unitario, $precio_total);
                $stmt->execute();
                $stmt->close();
            }
            
            $mysqli->commit();
            $_SESSION['success'] = "Cotización duplicada exitosamente como '{$numero_cotizacion}'.";
            header("Location: ver.php?id=$cotizacion_id");
            exit;
            
        } catch (Exception $e) {
            $mysqli->rollback();
            $error = 'Error al duplicar la cotización: ' . $e->getMessage();
        }
    }
}

// Obtener clientes para el formulario
$clientes = $mysqli->query("SELECT cliente_id, nombre, telefono FROM clientes ORDER BY nombre ASC");
$clientes_array = $clientes ? $clientes->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Duplicar Plantilla | Gestor de inventarios</title>
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
        .form-section { 
            background: #fff; 
            border-radius: 12px; 
            padding: 24px; 
            margin-bottom: 24px; 
            box-shadow: 0 2px 12px rgba(18,24,102,0.07); 
        }
        .section-title { 
            font-size: 1.3rem; 
            font-weight: 700; 
            color: #121866; 
            margin-bottom: 18px; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
        }
        .plantilla-info {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-files"></i> Duplicar Plantilla</h2>
            <a href="plantillas.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
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

        <!-- Información de la plantilla -->
        <div class="plantilla-info">
            <h5><i class="bi bi-file-earmark-text"></i> Plantilla: <?= htmlspecialchars($plantilla['numero_cotizacion']) ?></h5>
            <p class="mb-2">Cotización del <?= date('d/m/Y', strtotime($plantilla['fecha_cotizacion'])) ?> - Cliente: <?= htmlspecialchars($plantilla['cliente_nombre']) ?></p>
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted">Creada por:</small><br>
                    <strong><?= htmlspecialchars($plantilla['creador_nombre'] ?? 'Sistema') ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Productos:</small><br>
                    <strong><?= $productos->num_rows ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Total original:</small><br>
                    <strong>$<?= number_format($plantilla['total'], 2) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Estado:</small><br>
                    <strong><?= $plantilla['estado_id'] == 1 ? 'Borrador' : 'Otro' ?></strong>
                </div>
            </div>
        </div>

        <form method="POST" id="formDuplicarPlantilla">
            <!-- Información básica -->
            <div class="form-section">
                <div class="section-title"><i class="bi bi-info-circle"></i> Información de la Nueva Cotización</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre de la nueva cotización *</label>
                        <input type="text" class="form-control" name="nombre" id="nombre" value="Duplicado de <?= htmlspecialchars($plantilla['numero_cotizacion']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="categoria" class="form-label">Categoría</label>
                        <select class="form-select" name="categoria" id="categoria">
                            <option value="">Seleccionar categoría</option>
                            <option value="Cotizaciones">Cotizaciones</option>
                            <option value="Ventas">Ventas</option>
                            <option value="Servicios">Servicios</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="tipo_servicio" class="form-label">Tipo de servicio</label>
                        <input type="text" class="form-control" name="tipo_servicio" id="tipo_servicio" placeholder="Ej: Instalación, Mantenimiento, etc.">
                    </div>
                    <div class="col-md-6">
                        <label for="cliente_frecuente_id" class="form-label">Cliente frecuente</label>
                        <select class="form-select" name="cliente_frecuente_id" id="cliente_frecuente_id">
                            <option value="">Sin cliente específico</option>
                            <?php foreach ($clientes_array as $cl): ?>
                                <option value="<?= $cl['cliente_id'] ?>"><?= htmlspecialchars($cl['nombre']) ?> (<?= htmlspecialchars($cl['telefono']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" id="descripcion" rows="3" placeholder="Describe para qué sirve esta cotización..."></textarea>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="es_publica" id="es_publica">
                            <label class="form-check-label" for="es_publica">
                                Hacer cotización pública (visible para todos los usuarios)
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos de la plantilla -->
            <div class="form-section">
                <div class="section-title"><i class="bi bi-box"></i> Productos de la Plantilla</div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>SKU</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $productos->data_seek(0); while ($prod = $productos->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($prod['product_name'] ?? 'Producto externo') ?></td>
                                    <td><?= htmlspecialchars($prod['sku'] ?? '-') ?></td>
                                    <td><?= $prod['cantidad'] ?></td>
                                    <td>$<?= number_format($prod['precio_unitario'], 2) ?></td>
                                    <td>$<?= number_format($prod['precio_unitario'] * $prod['cantidad'], 2) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="plantillas.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Duplicar Cotización
                </button>
            </div>
        </form>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-cotizaciones').classList.add('active');
    </script>
</body>
</html> 