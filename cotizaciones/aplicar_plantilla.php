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
    SELECT c.*, u.username as creador_nombre, cl.nombre as cliente_nombre, cl.telefono as cliente_telefono, cl.ubicacion as cliente_ubicacion, cl.email as cliente_email
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
    // Procesar aplicación de plantilla
    $cliente_id = $_POST['cliente_id'] ?? '';
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $cliente_telefono = trim($_POST['cliente_telefono'] ?? '');
    $cliente_ubicacion = trim($_POST['cliente_ubicacion'] ?? '');
    $cliente_email = trim($_POST['cliente_email'] ?? '');
    
    if (!$cliente_id && !$cliente_nombre) {
        $error = 'Debes seleccionar o registrar un cliente.';
    }
    
    if (!$error) {
        // Crear nueva cotización basada en la plantilla
        $mysqli->begin_transaction();
        try {
            // Cliente: alta si es nuevo
            if (!$cliente_id) {
                $stmt = $mysqli->prepare("SELECT cliente_id FROM clientes WHERE nombre = ? OR telefono = ? OR email = ? LIMIT 1");
                $stmt->bind_param('sss', $cliente_nombre, $cliente_telefono, $cliente_email);
                $stmt->execute();
                $stmt->bind_result($cliente_id_encontrado);
                if ($stmt->fetch()) {
                    $cliente_id = $cliente_id_encontrado;
                }
                $stmt->close();
                if (!$cliente_id) {
                    $stmt = $mysqli->prepare("INSERT INTO clientes (nombre, telefono, ubicacion, email) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param('ssss', $cliente_nombre, $cliente_telefono, $cliente_ubicacion, $cliente_email);
                    $stmt->execute();
                    $cliente_id = $stmt->insert_id;
                    $stmt->close();
                }
            }
            
            // Generar número de cotización
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
            $stmt->bind_param('sisddiii', $numero_cotizacion, $cliente_id, $fecha_cotizacion, $validez_dias, $subtotal, $subtotal, $estado_id, $usuario_id);
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
            $_SESSION['success'] = "Cotización creada exitosamente desde la plantilla '{$plantilla['numero_cotizacion']}'.";
            header("Location: ver.php?id=$cotizacion_id");
            exit;
            
        } catch (Exception $e) {
            $mysqli->rollback();
            $error = 'Error al crear la cotización: ' . $e->getMessage();
        }
    }
}

// Obtener clientes para el formulario
$clientes = $mysqli->query("SELECT cliente_id, nombre, telefono, ubicacion, email FROM clientes ORDER BY nombre ASC");
$clientes_array = $clientes ? $clientes->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Aplicar Plantilla | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
            <h2><i class="bi bi-play-circle"></i> Aplicar Plantilla</h2>
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

        <form method="POST" id="formAplicarPlantilla">
            <!-- Cliente -->
            <div class="form-section">
                <div class="section-title"><i class="bi bi-person"></i> Cliente</div>
                <div class="mb-3">
                    <label for="cliente_select" class="form-label">Seleccionar cliente</label>
                    <select class="form-select" id="cliente_select" name="cliente_id">
                        <option value="">-- Nuevo cliente --</option>
                        <?php foreach ($clientes_array as $cl): ?>
                            <option value="<?= $cl['cliente_id'] ?>" 
                                    data-nombre="<?= htmlspecialchars($cl['nombre']) ?>" 
                                    data-telefono="<?= htmlspecialchars($cl['telefono']) ?>" 
                                    data-ubicacion="<?= htmlspecialchars($cl['ubicacion']) ?>" 
                                    data-email="<?= htmlspecialchars($cl['email']) ?>">
                                <?= htmlspecialchars($cl['nombre']) ?><?= $cl['telefono'] ? ' (' . htmlspecialchars($cl['telefono']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="camposNuevoCliente">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="cliente_nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="cliente_nombre" id="cliente_nombre">
                        </div>
                        <div class="col-md-3">
                            <label for="cliente_telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="cliente_telefono" id="cliente_telefono">
                        </div>
                        <div class="col-md-3">
                            <label for="cliente_ubicacion" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" name="cliente_ubicacion" id="cliente_ubicacion">
                        </div>
                        <div class="col-md-3">
                            <label for="cliente_email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="cliente_email" id="cliente_email">
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
                    <i class="bi bi-check-circle"></i> Crear Cotización
                </button>
            </div>
        </form>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        document.querySelector('.sidebar-cotizaciones').classList.add('active');
        
        // Manejo del cliente
        document.getElementById('cliente_select').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (this.value) {
                document.getElementById('cliente_nombre').value = selectedOption.dataset.nombre || '';
                document.getElementById('cliente_telefono').value = selectedOption.dataset.telefono || '';
                document.getElementById('cliente_ubicacion').value = selectedOption.dataset.ubicacion || '';
                document.getElementById('cliente_email').value = selectedOption.dataset.email || '';
            }
        });
    </script>
</body>
</html> 