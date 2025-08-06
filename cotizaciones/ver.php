<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$cotizacion_id = intval($_GET['id']);

// Obtener cotización
$stmt = $mysqli->prepare("
    SELECT c.*, u.username as usuario_nombre, cl.nombre as cliente_nombre_real, cl.telefono as cliente_telefono_real, cl.ubicacion as cliente_direccion_real, ec.nombre_estado
    FROM cotizaciones c
    LEFT JOIN users u ON c.user_id = u.user_id
    LEFT JOIN clientes cl ON c.cliente_id = cl.cliente_id
    LEFT JOIN est_cotizacion ec ON c.estado_id = ec.est_cot_id
    WHERE c.cotizacion_id = ?
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$cotizacion = $stmt->get_result()->fetch_assoc();

if (!$cotizacion) {
    header('Location: index.php');
    exit;
}

// Extraer descripciones personalizadas de las observaciones
$descripcionesPersonalizadas = [];
if (!empty($cotizacion['observaciones']) && preg_match('/\[DESCRIPCIONES:([^\]]+)\]/', $cotizacion['observaciones'], $match)) {
    $descripcionesData = base64_decode($match[1]);
    $descripcionesJson = json_decode($descripcionesData, true);
    if (is_array($descripcionesJson)) {
        $descripcionesPersonalizadas = $descripcionesJson;
    }
}

// Extraer descripciones personalizadas de insumos de las observaciones
$descripcionesPersonalizadasInsumos = [];
if (!empty($cotizacion['observaciones']) && preg_match('/\[DESCRIPCIONES_INSUMOS:([^\]]+)\]/', $cotizacion['observaciones'], $match)) {
    $descripcionesData = base64_decode($match[1]);
    $descripcionesJson = json_decode($descripcionesData, true);
    if (is_array($descripcionesJson)) {
        $descripcionesPersonalizadasInsumos = $descripcionesJson;
    }
}

// Obtener productos de la cotización
$stmt = $mysqli->prepare("
    SELECT cp.*, p.product_name, p.description, p.sku, p.image as product_image, p.cost_price, p.tipo_gestion,
           CASE 
               WHEN p.tipo_gestion = 'bobina' THEN 
                   COALESCE(SUM(b.metros_actuales), 0)
               ELSE 
                   p.quantity
           END as stock_actual
    FROM cotizaciones_productos cp
    LEFT JOIN products p ON cp.product_id = p.product_id
    LEFT JOIN bobinas b ON p.product_id = b.product_id AND b.is_active = 1
    WHERE cp.cotizacion_id = ?
    GROUP BY cp.cotizacion_producto_id, cp.cotizacion_id, cp.product_id, cp.cantidad, cp.precio_unitario, cp.precio_total, p.product_name, p.description, p.sku, p.image, p.cost_price, p.tipo_gestion, p.quantity
    ORDER BY cp.cotizacion_producto_id
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$productos = $stmt->get_result();

// Obtener servicios de la cotización
$stmt = $mysqli->prepare("
    SELECT cs.*, s.nombre as servicio_nombre, s.descripcion as servicio_descripcion, s.categoria as servicio_categoria, s.imagen as servicio_imagen
    FROM cotizaciones_servicios cs
    LEFT JOIN servicios s ON cs.servicio_id = s.servicio_id
    WHERE cs.cotizacion_id = ?
    ORDER BY cs.cotizacion_servicio_id
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$servicios = $stmt->get_result();

// Obtener insumos de la cotización
$stmt = $mysqli->prepare("
    SELECT ci.*, i.nombre as insumo_nombre, i.categoria as insumo_categoria, i.cantidad as insumo_stock, i.precio_unitario as insumo_precio, i.imagen as insumo_imagen, c.name as categoria_nombre, s.name as proveedor_nombre
    FROM cotizaciones_insumos ci
    LEFT JOIN insumos i ON ci.insumo_id = i.insumo_id
    LEFT JOIN categories c ON i.category_id = c.category_id
    LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
    WHERE ci.cotizacion_id = ?
    ORDER BY ci.cotizacion_insumo_id
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$insumos = $stmt->get_result();

// Calcular costo total de la cotización
$costo_total = 0;

// Costo de productos
$productos_temp = $productos;
$productos_temp->data_seek(0); // Reset pointer
while ($producto = $productos_temp->fetch_assoc()) {
    $cost_price = floatval($producto['cost_price'] ?? 0);
    $cantidad = floatval($producto['cantidad'] ?? 0);
    
    // Detectar si es un cable/bobina para ajustar el cálculo del costo
    $tipo_gestion = $producto['tipo_gestion'] ?? '';
    $es_cable_costo = ($tipo_gestion === 'bobina') || 
                     (stripos($producto['product_name'] ?? '', 'bobina') !== false) ||
                     (stripos($producto['product_name'] ?? '', 'cable utp') !== false) ||
                     (stripos($producto['product_name'] ?? '', 'saxxon out') !== false);
    
    if ($es_cable_costo && $cost_price > 0) {
        $precio_unitario = floatval($producto['precio_unitario'] ?? 0);
        $metros_por_bobina = 305;
        
        // Para cables: cost_price es por BOBINA COMPLETA (como en crear.php)
        // Calcular número de bobinas desde los metros
        $bobinas_completas = round($cantidad / $metros_por_bobina);
        $costo_total += $cost_price * $bobinas_completas;
    } else {
        // Productos normales: cost_price por unidad
        $costo_total += $cost_price * $cantidad;
    }
}

// Costo de servicios (generalmente no tienen costo, pero por si acaso)
$servicios_temp = $servicios;
$servicios_temp->data_seek(0); // Reset pointer
while ($servicio = $servicios_temp->fetch_assoc()) {
    // Los servicios normalmente no tienen costo asociado
    // Si en el futuro se agrega un campo cost_price a servicios, se puede incluir aquí
}

// Costo de insumos
$insumos_temp = $insumos;
$insumos_temp->data_seek(0); // Reset pointer
while ($insumo = $insumos_temp->fetch_assoc()) {
    // Los insumos pueden tener costo en el campo precio_unitario o un campo específico de costo
    $costo_insumo = floatval($insumo['precio_unitario'] ?? 0); // Usar precio como costo por ahora
    $cantidad = floatval($insumo['cantidad'] ?? 0);
    $costo_total += $costo_insumo * $cantidad;
}

// Reset pointers para uso posterior
$productos->data_seek(0);
$servicios->data_seek(0);
$insumos->data_seek(0);

// Debug: verificar si observaciones existe
$observaciones_debug = isset($cotizacion['observaciones']) ? $cotizacion['observaciones'] : 'CAMPO NO EXISTE';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización <?= $cotizacion['numero_cotizacion'] ?> | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6fb; color: #232a7c; margin: 0; padding: 0; }
        .cotizacion-container { 
            background: #fff; 
            max-width: 1200px; 
            margin: 30px auto; 
            box-shadow: 0 4px 24px rgba(18,24,102,0.10); 
            border-radius: 14px; 
            padding: 32px 36px 24px 36px; 
        }
        .cotizacion-header { 
            display: flex; 
            align-items: flex-start; 
            justify-content: space-between; 
            border-bottom: 2px solid #232a7c; 
            padding-bottom: 18px; 
            margin-bottom: 18px; 
        }
        .logo-empresa { height: 80px; margin-right: 18px; }
        .datos-empresa { flex: 1; }
        .datos-empresa h2 { font-size: 1.5rem; margin: 0 0 4px 0; color: #121866; }
        .datos-empresa p { margin: 0; font-size: 1rem; color: #232a7c; }
        .cotizacion-info { text-align: right; }
        .cotizacion-info .titulo-cot { 
            background: #ff9800; 
            color: #fff; 
            font-weight: 700; 
            padding: 6px 18px; 
            border-radius: 8px; 
            font-size: 1.1rem; 
            margin-bottom: 8px; 
            display: inline-block; 
        }
        .cotizacion-info img { height: 28px; margin-left: 8px; vertical-align: middle; }
        .datos-cliente { 
            margin: 18px 0 10px 0; 
            display: flex; 
            flex-wrap: wrap; 
            gap: 32px; 
        }
        .datos-cliente .campo { font-size: 1rem; margin-bottom: 2px; }
        .datos-cliente .campo strong { color: #121866; min-width: 90px; display: inline-block; }
        .info-creador {
            background: linear-gradient(135deg, rgba(35, 42, 124, 0.08), rgba(35, 42, 124, 0.12));
            padding: 8px 14px;
            border-radius: 8px;
            border-left: 4px solid #232a7c;
            font-style: italic;
            box-shadow: 0 2px 8px rgba(35, 42, 124, 0.05);
            transition: all 0.2s ease;
        }
        .info-creador:hover {
            background: linear-gradient(135deg, rgba(35, 42, 124, 0.12), rgba(35, 42, 124, 0.16));
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(35, 42, 124, 0.08);
        }
        .info-creador i {
            font-size: 1.1rem;
        }
        .tabla-cotizacion { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            font-size: 0.98rem; 
        }
        .tabla-cotizacion th, .tabla-cotizacion td { 
            border: 1px solid #b0b6d0; 
            padding: 8px 6px; 
            text-align: center; 
            vertical-align: middle; 
        }
        .tabla-cotizacion th { background: #232a7c; color: #fff; font-size: 1rem; }
        .tabla-cotizacion td.descripcion { text-align: left; font-size: 0.97rem; }
        .tabla-cotizacion td.imagen { 
            background: #f7f9fc; 
            width: 100px; 
            height: 100px; 
            padding: 4px; 
        }
        .tabla-cotizacion td.precio-total { font-weight: 700; color: #232a7c; }
        .tabla-cotizacion td.costo-total { background: #e0e0e0; color: #232a7c; font-weight: 500; }
        .tabla-cotizacion tfoot td { font-weight: 700; background: #f4f6fb; color: #121866; }
        .condiciones { margin-top: 18px; font-size: 0.98rem; }
        .condiciones strong { color: #121866; }
        .btn { 
            background: #232a7c; 
            color: #fff; 
            border: none; 
            border-radius: 6px; 
            padding: 7px 16px; 
            font-size: 1rem; 
            font-weight: 600; 
            cursor: pointer; 
            margin: 4px 0; 
            transition: background 0.15s; 
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #121866; color: #fff; text-decoration: none; }
        .btn-danger { background: #e53935; }
        .btn-danger:hover { background: #b71c1c; }
        .btn-success { background: #43a047; }
        .btn-success:hover { background: #2e7d32; }
        .btn-warning { background: #ff9800; }
        .btn-warning:hover { background: #f57c00; }
        .estado-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .estado-borrador { background: #fff3cd; color: #856404; }
        .estado-enviada { background: #d1ecf1; color: #0c5460; }
        .estado-aprobada { background: #d4edda; color: #155724; }
        .estado-rechazada { background: #f8d7da; color: #721c24; }
        .estado-convertida { background: #d1ecf1; color: #0c5460; }
        .acciones-cotizacion {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .alert {
            padding: 12px 16px;
            margin-bottom: 16px;
            border: 1px solid transparent;
            border-radius: 8px;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .btn-close {
            float: right;
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1;
            color: #000;
            opacity: .5;
            background: none;
            border: 0;
            cursor: pointer;
        }
        .btn-close:hover {
            opacity: .75;
        }
        @media print { 
            /* Limpiar página de impresión */
            body { 
                background: #fff !important; 
                color: #000 !important;
                font-size: 12pt;
                line-height: 1.3;
                margin: 0;
                padding: 0;
            } 
            
            /* Ocultar elementos innecesarios */
            .cotizacion-container { 
                box-shadow: none !important; 
                border-radius: 0 !important; 
                margin: 0 !important; 
                padding: 15px !important;
                max-width: none !important;
            }
            
            /* Ocultar información del creador en impresión */
            .info-creador { display: none !important; }
            
            /* Ocultar columnas de costo e información interna */
            th.costo-total, td.costo-total { display: none !important; }
            .fila-costo-total, .fila-ganancia { display: none !important; }
            .badge.bg-danger { display: none !important; }
            .text-muted { display: none !important; }
            .acciones-cotizacion { display: none !important; }
            .col-md-8 { display: none !important; } /* Ocultar mensaje explicativo */
            
            /* Ocultar separador e información interna completa */
            tr[style*="background: #e9ecef"] { display: none !important; }
            .fila-costo-total { display: none !important; }
            .fila-ganancia { display: none !important; }
            tr[style*="background: #fff3cd"] { display: none !important; }
            tr[style*="background: #d4edda"] { display: none !important; }
            
            /* Ocultar específicamente las filas de información interna */
            .resumen-cotizacion-ver tr:has(.text-center) ~ tr { display: none !important; }
            .resumen-cotizacion-ver tr:has([style*="📊"]) { display: none !important; }
            .resumen-cotizacion-ver tr:has([style*="📊"]) ~ tr { display: none !important; }
            
            /* Marca de agua PAGADO más grande y visible */
            .pagado-watermark {
                position: fixed !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) rotate(-45deg) !important;
                font-size: 120pt !important;
                font-weight: 900 !important;
                color: rgba(40, 167, 69, 0.15) !important;
                z-index: 1000 !important;
                pointer-events: none !important;
                user-select: none !important;
                text-transform: uppercase !important;
                letter-spacing: 1rem !important;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.1) !important;
                font-family: Arial, sans-serif !important;
            }
            
            /* Asegurar que la marca aparezca para cotizaciones convertidas */
            .cotizacion-container.pagado .pagado-watermark {
                display: block !important;
            }
            
            /* Optimizar tabla para impresión */
            .tabla-cotizacion {
                width: 100% !important;
                font-size: 10pt !important;
                border-collapse: collapse !important;
            }
            
            .tabla-cotizacion th,
            .tabla-cotizacion td {
                border: 1px solid #000 !important;
                padding: 6px 4px !important;
                font-size: 10pt !important;
            }
            
            .tabla-cotizacion th {
                background: #f0f0f0 !important;
                color: #000 !important;
                font-weight: bold !important;
            }
            
            /* Ajustar resumen para impresión */
            .resumen-cotizacion-ver table {
                border: 2px solid #000 !important;
                background: #fff !important;
            }
            
            .resumen-cotizacion-ver td {
                border: none !important;
                color: #000 !important;
            }
            
            /* Ocultar observaciones si están vacías */
            .mt-3.p-3:has(em.text-muted) {
                display: none !important;
            }
            
            /* Forzar salto de página si es necesario */
            .cotizacion-header {
                page-break-inside: avoid !important;
            }
            
            .tabla-cotizacion {
                page-break-inside: auto !important;
            }
            
            /* Asegurar que el resumen se mantenga junto */
            .resumen-cotizacion-ver {
                page-break-inside: avoid !important;
            }
        }
        .alert.shadow.rounded-4 {
            box-shadow: 0 4px 24px rgba(18,24,102,0.10) !important;
            border-radius: 1.2rem !important;
        }
        #modalConfirmarEstado .modal-content {
            border-radius: 1rem;
            box-shadow: 0 6px 32px rgba(18,24,102,0.13);
        }
        #modalConfirmarEstado .modal-header {
            background: #232a7c;
            color: #fff;
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
        }
        #modalConfirmarEstado .modal-footer {
            border-bottom-left-radius: 1rem;
            border-bottom-right-radius: 1rem;
        }
        #modalConfirmarEstado .btn-success {
            background: #43a047;
            border: none;
        }
        #modalConfirmarEstado .btn-success:hover {
            background: #388e3c;
        }
        
        /* Marca de agua PAGADO para cotizaciones convertidas */
        .pagado-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 8rem;
            font-weight: 900;
            color: rgba(40, 167, 69, 0.25);
            z-index: 1000;
            pointer-events: none;
            user-select: none;
            text-transform: uppercase;
            letter-spacing: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .cotizacion-container.pagado {
            position: relative;
            overflow: hidden;
        }
        
        @media print {
            .pagado-watermark {
                position: absolute;
                color: rgba(40, 167, 69, 0.4) !important;
                font-size: 6rem;
                z-index: 1;
            }
        }
    </style>
</head>
<body>
    <div class="cotizacion-container<?= $cotizacion['nombre_estado'] === 'Convertida' ? ' pagado' : '' ?>">
        <?php if ($cotizacion['nombre_estado'] === 'Convertida'): ?>
            <div class="pagado-watermark">PAGADO</div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow rounded-4" role="alert" style="font-size:1.08rem;">
                <i class="bi bi-check-circle-fill me-2"></i> <?= 
                    $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow rounded-4" role="alert" style="font-size:1.08rem;">
                <i class="bi bi-x-octagon-fill me-2"></i> <?= 
                    $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="acciones-cotizacion">
            <a href="index.php" class="btn" style="background:#757575;">← Volver al listado</a>
            <a href="editar.php?id=<?= $cotizacion_id ?>" class="btn btn-success">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="historial.php?id=<?= $cotizacion_id ?>" class="btn btn-info">
                <i class="bi bi-clock-history"></i> Historial
            </a>
            <a href="#" class="btn" onclick="window.print(); return false;">
                <i class="bi bi-printer"></i> Imprimir
            </a>
            
            <!-- Botones de cambio de estado -->
            <?php if ($cotizacion['nombre_estado'] === 'Borrador'): ?>
                <button type="button" class="btn btn-primary" onclick="cambiarEstado(<?= $cotizacion_id ?>, 2, <?= $cotizacion['estado_id'] ?>)">
                    <i class="bi bi-send"></i> Enviar
                </button>
            <?php endif; ?>
            
            <?php if ($cotizacion['nombre_estado'] === 'Enviada'): ?>
                <button type="button" class="btn btn-success" onclick="cambiarEstado(<?= $cotizacion_id ?>, 3, <?= $cotizacion['estado_id'] ?>)">
                    <i class="bi bi-check-circle"></i> Aprobar
                </button>
                <button type="button" class="btn btn-warning" onclick="cambiarEstado(<?= $cotizacion_id ?>, 4, <?= $cotizacion['estado_id'] ?>)">
                    <i class="bi bi-x-circle"></i> Rechazar
                </button>
            <?php endif; ?>
            
            <?php if ($cotizacion['nombre_estado'] === 'Aprobada'): ?>
                <button type="button" class="btn btn-warning" onclick="cambiarEstado(<?= $cotizacion_id ?>, 4, <?= $cotizacion['estado_id'] ?>)">
                    <i class="bi bi-x-circle"></i> Rechazar
                </button>
                <a href="convertir.php?id=<?= $cotizacion_id ?>" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Convertir a Venta
                </a>
            <?php endif; ?>
            
            <?php if ($cotizacion['nombre_estado'] === 'Rechazada'): ?>
                <button type="button" class="btn btn-success" onclick="cambiarEstado(<?= $cotizacion_id ?>, 3, <?= $cotizacion['estado_id'] ?>)">
                    <i class="bi bi-check-circle"></i> Aprobar
                </button>
            <?php endif; ?>
            
            <span class="estado-badge estado-<?= strtolower($cotizacion['nombre_estado']) ?>">
                <?= htmlspecialchars($cotizacion['nombre_estado']) ?>
            </span>
        </div>

        <div class="cotizacion-header">
            <img src="../assets/img/LogoWeb.png" alt="Logo empresa" class="logo-empresa" style="height: 160px; margin-right: 18px;">
            <div class="datos-empresa">
                <p style="margin: 0; font-size: 1rem; color: #232a7c;">999-270-3642</p>
                <div class="direccion-fija" style="margin: 5px 0; color: #232a7c;">
                    <strong>Dirección:</strong><br>
                    Calle 20 #45B, Leandro Valle, Mérida, Yucatán
                </div>
            </div>
            <div class="cotizacion-info">
                <div class="titulo-cot quote-title">
                    <?php
                    if ($cotizacion['nombre_estado'] === 'Aprobada') {
                        echo 'Nota de pago';
                    } elseif ($cotizacion['nombre_estado'] === 'Convertida') {
                        echo 'Factura';
                    } else {
                        echo 'Cotización';
                    }
                    ?>
                </div>
                
                <!-- Título personalizado de cotización -->
                <?php 
                // Buscar título personalizado en observaciones con formato [TITULO:texto]
                $titulo_personalizado = '';
                if (!empty($cotizacion['observaciones']) && preg_match('/\[TITULO:([^\]]+)\]/', $cotizacion['observaciones'], $match)) {
                    $titulo_personalizado = trim($match[1]);
                }
                
                if (!empty($titulo_personalizado)): 
                ?>
                <div style="margin: 5px 0; font-size: 0.9rem; color: #333; font-weight: bold;">
                    <?= htmlspecialchars($titulo_personalizado) ?>
                </div>
                <?php endif; ?>
                
                <div><strong><?= htmlspecialchars($cotizacion['numero_cotizacion']) ?></strong></div>
                <div>Fecha: <?= date('d/m/Y', strtotime($cotizacion['fecha_cotizacion'])) ?></div>
                <div>Válida hasta: <?= date('d/m/Y', strtotime($cotizacion['fecha_cotizacion'] . ' + ' . $cotizacion['validez_dias'] . ' days')) ?></div>
                
                <!-- Nota de garantía -->
                <div style="margin-top: 8px; padding: 6px 10px; background-color: rgba(255,255,255,0.2); border-radius: 4px; font-size: 0.85rem; font-weight: bold;">
                    ⚡ 1 AÑO DE GARANTÍA POR DEFECTOS DE FÁBRICA
                </div>
                
                <br>
                <div class="logos-dinamicos"></div>
            </div>
        </div>

        <div class="datos-cliente">
            <div class="campo"><strong>Cliente:</strong> <?= htmlspecialchars($cotizacion['cliente_nombre_real'] ?? $cotizacion['cliente_nombre']) ?></div>
            <?php if (($cotizacion['cliente_telefono_real'] ?? $cotizacion['cliente_telefono'])): ?>
                <div class="campo"><strong>Teléfono:</strong> <?= htmlspecialchars($cotizacion['cliente_telefono_real'] ?? $cotizacion['cliente_telefono']) ?></div>
            <?php endif; ?>
            <?php if (($cotizacion['cliente_direccion_real'] ?? $cotizacion['cliente_ubicacion'])): ?>
                <div class="campo"><strong>Ubicación:</strong> <?= htmlspecialchars($cotizacion['cliente_direccion_real'] ?? $cotizacion['cliente_ubicacion']) ?></div>
            <?php endif; ?>
            <div class="campo"><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($cotizacion['fecha_cotizacion'])) ?></div>
            <!-- Información del creador - NO se imprime -->
            <div class="campo info-creador">
                <i class="bi bi-person-fill me-1" style="color: #232a7c;"></i>
                <strong>Creada por:</strong> <?= htmlspecialchars($cotizacion['usuario_nombre'] ?? 'Usuario desconocido') ?>
                <small class="text-muted ms-2">(<?= date('d/m/Y H:i', strtotime($cotizacion['created_at'] ?? $cotizacion['fecha_cotizacion'])) ?>)</small>
            </div>
        </div>

        <table class="tabla-cotizacion">
            <?php
            // Calcular IVA especial manual antes del tfoot
            $ivaManual = 0;
            $ivaVal = null;
            // Extraer siempre el IVA especial desde observaciones si existe
            if (!empty($cotizacion['observaciones']) && preg_match('/\[IVA_ESPECIAL:([^\]]+)\]/', $cotizacion['observaciones'], $match)) {
                $ivaVal = floatval($match[1]);
            } else if (!empty($cotizacion['condicion_iva']) && is_numeric($cotizacion['condicion_iva']) && floatval($cotizacion['condicion_iva']) > 0) {
                $ivaVal = floatval($cotizacion['condicion_iva']);
            }
            if ($ivaVal !== null && is_numeric($ivaVal) && $ivaVal > 0) {
                $base = $cotizacion['subtotal'] - $cotizacion['descuento_monto'];
                if ($ivaVal <= 1) {
                    $ivaManual = $base * $ivaVal;
                } else if ($ivaVal > 1 && $ivaVal <= 100) {
                    $ivaManual = $base * ($ivaVal / 100);
                } else {
                    $ivaManual = $ivaVal;
                }
            }
            ?>
            <thead>
                <tr>
                    <th>ITEM</th>
                    <th>DESCRIPCIÓN DEL PRODUCTO / SERVICIO</th>
                    <th>IMAGEN ILUSTRATIVA</th>
                    <th>CANT</th>
                    <th>PRECIO UNITARIO</th>
                    <th>PRECIO TOTAL</th>
                    <th class="costo-total">COSTO TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($producto = $productos->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="descripcion">
                            <?php
                            // Priorizar descripción personalizada, luego descripción original, luego nombre del producto
                            $product_id = $producto['product_id'];
                            $descripcion = '';
                            
                            // 1. Verificar si hay descripción personalizada
                            if (isset($descripcionesPersonalizadas[$product_id]) && !empty(trim($descripcionesPersonalizadas[$product_id]))) {
                                $descripcion = $descripcionesPersonalizadas[$product_id];
                            }
                            // 2. Si no, usar descripción original del producto
                            elseif (!empty(trim($producto['description']))) {
                                $descripcion = $producto['description'];
                            }
                            // 3. Si no, usar el nombre del producto como fallback
                            else {
                                $descripcion = $producto['product_name'] ?? 'Producto sin descripción';
                            }
                            
                            echo htmlspecialchars($descripcion);
                            
                            // Mostrar badge "Sin stock" si el stock actual es menor que la cantidad requerida
                            if ($producto['product_id'] && $producto['stock_actual'] < $producto['cantidad']) {
                                echo '<span class="badge bg-danger ms-2" style="font-size: 0.75rem;">Sin stock</span>';
                                // Para bobinas, mostrar información adicional
                                if ($producto['product_id']) {
                                    $stmt_tipo = $mysqli->prepare("SELECT tipo_gestion FROM products WHERE product_id = ?");
                                    $stmt_tipo->bind_param('i', $producto['product_id']);
                                    $stmt_tipo->execute();
                                    $tipo_gestion = $stmt_tipo->get_result()->fetch_assoc()['tipo_gestion'] ?? '';
                                    $stmt_tipo->close();
                                    
                                    if ($tipo_gestion === 'bobina') {
                                        echo '<br><small class="text-muted">Stock disponible: ' . number_format($producto['stock_actual'], 2) . 'm | Solicitado: ' . number_format($producto['cantidad'], 2) . 'm</small>';
                                    }
                                }
                            }
                            ?>
                        </td>
                        <td class="imagen">
                            <?php
                            $img = $producto['product_image'] ?? '';
                            if ($img && strpos($img, 'uploads/products/') === false) {
                                $img = 'uploads/products/' . $img;
                            }
                            ?>
                            <?php if ($img): ?>
                                <img src="../<?= htmlspecialchars($img) ?>" alt="Imagen" style="width:90px; height:90px; object-fit:cover; border-radius:6px; border:none; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                            <?php else: ?>
                                <span style="color:#ccc;">Sin imagen</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            // 🎯 DETECTAR MODO ORIGINAL USANDO LA MISMA LÓGICA QUE CREAR.PHP
                            $cantidad_mostrar = $producto['cantidad'];
                            $unidad = '';
                            
                            // Detectar si es un cable/bobina usando tipo_gestion primero, luego nombre específico
                            $tipo_gestion = $producto['tipo_gestion'] ?? '';
                            $es_cable = ($tipo_gestion === 'bobina') || 
                                       (stripos($producto['product_name'] ?? '', 'bobina') !== false) ||
                                       (stripos($producto['product_name'] ?? '', 'cable utp') !== false) ||
                                       (stripos($producto['product_name'] ?? '', 'saxxon out') !== false);
                            
                            if ($es_cable) {
                                $metros_por_bobina = 305; // Misma constante que crear.php PRECIO_CONFIG
                                $cantidad = $producto['cantidad'];
                                $precio_unitario = $producto['precio_unitario'];
                                
                                // DETECTAR MODO ORIGINAL usando la misma heurística que crear.php
                                // Si precio > 50, es precio por bobina completa (líneas 1579-1583)
                                // Si precio <= 50, es precio por metro (líneas 1587-1590)
                                if ($precio_unitario > 50) {
                                    // MODO BOBINAS COMPLETAS (como crear.php línea 1580)
                                    // 🎯 PERMITIR FRACCIONES DE BOBINAS (1.5, 2.5, etc.)
                                    $bobinas_completas = $cantidad / $metros_por_bobina;
                                    // Redondear a 1 decimal para mostrar fracciones como 1.5
                                    $cantidad_mostrar = round($bobinas_completas, 1);
                                    $unidad = $cantidad_mostrar !== 1 ? ' bobinas' : ' bobina';
                                    echo number_format($cantidad_mostrar, 1) . $unidad;
                                } else {
                                    // MODO POR METROS (como crear.php línea 1587)
                                    $cantidad_mostrar = $cantidad;
                                    $unidad = ' m';
                                    echo number_format($cantidad_mostrar, 2) . $unidad;
                                }
                            } else {
                                // Para productos normales (no bobinas/cables): mostrar solo enteros
                                $cantidad_mostrar = round($producto['cantidad']);
                                echo number_format($cantidad_mostrar, 0);
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            // 🎯 DETECTAR MODO ORIGINAL USANDO LA MISMA LÓGICA QUE CREAR.PHP
                            $precio_mostrar = $producto['precio_unitario'];
                            $precio_unidad = '';
                            
                            // Usar la misma detección de modo que en cantidad
                            if ($es_cable) {
                                $precio_unitario = $producto['precio_unitario'];
                                
                                // DETECTAR MODO ORIGINAL usando la misma heurística que crear.php
                                // Si precio > 50, es precio por bobina completa (líneas 1579-1583)
                                // Si precio <= 50, es precio por metro (líneas 1587-1590)
                                if ($precio_unitario > 50) {
                                    // MODO BOBINAS COMPLETAS - Precio está por bobina
                                    $precio_unidad = ' /bobina';
                                } else {
                                    // MODO POR METROS - Precio está por metro
                                    $precio_unidad = ' /m';
                                }
                            }
                            
                            echo '$' . number_format($precio_mostrar, 2) . $precio_unidad;
                            ?>
                        </td>
                        <td class="precio-total">
                            <?php
                            // Verificar si el precio total necesita recálculo para cables/bobinas
                            $precio_total_mostrar = $producto['precio_total'];
                            
                            if ($es_cable) {
                                $precio_unitario = $producto['precio_unitario'];
                                $cantidad = $producto['cantidad'];
                                $metros_por_bobina = 305;
                                
                                if ($precio_unitario > 50) {
                                    // MODO BOBINAS COMPLETAS
                                    // 🎯 PERMITIR FRACCIONES DE BOBINAS PARA CÁLCULO CORRECTO
                                    $bobinas_completas = $cantidad / $metros_por_bobina;
                                    $precio_total_recalculado = $precio_unitario * $bobinas_completas;
                                } else {
                                    // MODO POR METROS
                                    $precio_total_recalculado = $precio_unitario * $cantidad;
                                }
                                
                                // Usar el precio total recalculado si difiere significativamente del almacenado
                                if (abs($precio_total_mostrar - $precio_total_recalculado) > 0.01) {
                                    $precio_total_mostrar = $precio_total_recalculado;
                                }
                            }
                            
                            echo '$' . number_format($precio_total_mostrar, 2);
                            ?>
                        </td>
                        <td class="costo-total">
                            <?php
                            if (!empty($producto['cost_price'])) {
                                $cantidad_para_costo = $producto['cantidad'];
                                
                                // Para cables/bobinas, el cost_price es por BOBINA COMPLETA (como en crear.php)
                                if ($es_cable) {
                                    $metros_por_bobina = 305;
                                    // 🎯 PERMITIR FRACCIONES DE BOBINAS PARA CÁLCULO CORRECTO DEL COSTO
                                    $bobinas_completas = $cantidad_para_costo / $metros_por_bobina;
                                    $costo_total_producto = $producto['cost_price'] * $bobinas_completas;
                                } else {
                                    // Productos normales: cost_price por unidad
                                    $costo_total_producto = $producto['cost_price'] * $cantidad_para_costo;
                                }
                                
                                echo '$' . number_format($costo_total_producto, 2);
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php while ($insumo = $insumos->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="descripcion" style="max-width:320px;word-break:break-word;white-space:normal;">
                            <?php
                            // Priorizar descripción personalizada, luego nombre del insumo
                            $insumo_id = $insumo['insumo_id'];
                            $descripcion = '';
                            
                            // 1. Verificar si hay descripción personalizada
                            if (isset($descripcionesPersonalizadasInsumos[$insumo_id]) && !empty(trim($descripcionesPersonalizadasInsumos[$insumo_id]))) {
                                $descripcion = $descripcionesPersonalizadasInsumos[$insumo_id];
                            }
                            // 2. Si no, usar el nombre del insumo como fallback
                            else {
                                $descripcion = $insumo['nombre_insumo'] ?? $insumo['insumo_nombre'] ?? 'Insumo sin descripción';
                            }
                            
                            echo '<strong>' . htmlspecialchars($descripcion) . '</strong>';
                            ?>
                            <?php if ($insumo['categoria'] || $insumo['categoria_nombre']): ?>
                                <br><span class="badge bg-info" style="font-size: 0.75rem;"><?= htmlspecialchars($insumo['categoria'] ?? $insumo['categoria_nombre']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="imagen">
                            <?php
                            $img = $insumo['insumo_imagen'] ?? '';
                            if ($img) {
                                if (strpos($img, 'uploads/insumos/') === false) {
                                    $img = 'uploads/insumos/' . $img;
                                }
                            }
                            ?>
                            <?php if ($img): ?>
                                <img src="../<?= htmlspecialchars($img) ?>" alt="Imagen Insumo" style="width:90px; height:90px; object-fit:cover; border-radius:6px; border:none; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                            <?php else: ?>
                                <span style="color:#ccc;"><i class="bi bi-tools"></i> Insumo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($insumo['cantidad'], 0) ?></td>
                        <td>$<?= number_format($insumo['precio_unitario'], 2) ?></td>
                        <td class="precio-total">$<?= number_format($insumo['precio_total'], 2) ?></td>
                        <td class="costo-total">N/A</td>
                    </tr>
                <?php endwhile; ?>
                
                <?php while ($servicio = $servicios->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="descripcion">
                            <strong><?= htmlspecialchars($servicio['nombre_servicio']) ?></strong>
                            <?php if ($servicio['descripcion']): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($servicio['descripcion']) ?></small>
                            <?php endif; ?>
                            <?php if ($servicio['servicio_categoria']): ?>
                                <br><span class="badge bg-info" style="font-size: 0.75rem;"><?= htmlspecialchars($servicio['servicio_categoria']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="imagen">
                            <?php
                            $img = $servicio['imagen'] ?? $servicio['servicio_imagen'] ?? '';
                            if ($img) {
                                if (strpos($img, 'uploads/services/') === false) {
                                    $img = 'uploads/services/' . $img;
                                }
                            }
                            ?>
                            <?php if ($img): ?>
                                <img src="../<?= htmlspecialchars($img) ?>" alt="Imagen" style="width:90px; height:90px; object-fit:cover; border-radius:6px; border:none; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                            <?php else: ?>
                                <span style="color:#ccc;"><i class="bi bi-tools"></i> Servicio</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($servicio['cantidad'], 0) ?></td>
                        <td>$<?= number_format($servicio['precio_unitario'], 2) ?></td>
                        <td class="precio-total">$<?= number_format($servicio['precio_total'], 2) ?></td>
                        <td class="costo-total">N/A</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <!-- Resumen Visual Mejorado -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div style="margin-top:10px; font-size:0.97rem; color:#888;">
                    <i class="bi bi-info-circle" style="color:#198754;"></i>
                    <?php
                    $subtotal_base = $cotizacion['subtotal'];
                    $descuento_monto = $cotizacion['descuento_monto'];
                    $subtotal_con_descuento = $subtotal_base - $descuento_monto;
                    $total_final = $cotizacion['total'];
                    $iva_calculado = $total_final - $subtotal_con_descuento;
                    
                    $mensaje = '';
                    
                    if ($descuento_monto > 0) {
                        $mensaje .= 'Se aplicó un descuento de <strong>' . $cotizacion['descuento_porcentaje'] . '%</strong> ($' . number_format($descuento_monto, 2) . '). ';
                    }
                    
                    if ($iva_calculado > 0) {
                        // Calcular el porcentaje de IVA
                        $porcentaje_iva = ($iva_calculado / $subtotal_con_descuento) * 100;
                        $mensaje .= 'Se aplicó un IVA especial de <strong>' . number_format($porcentaje_iva, 2) . '%</strong> ($' . number_format($iva_calculado, 2) . ').';
                    }
                    
                    if (empty($mensaje)) {
                        if (abs($total_final - $subtotal_con_descuento) < 0.01) {
                            $mensaje = 'El <strong>total</strong> coincide con el <strong>subtotal</strong>.';
                        } else {
                            $mensaje = 'El <strong>total</strong> puede diferir del <strong>subtotal</strong> por descuentos o cargos adicionales.';
                        }
                    }
                    
                    echo $mensaje;
                    ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="resumen-cotizacion-ver">
                    <table class="table table-borderless mb-0" style="border: 2px solid #dee2e6; border-radius: 8px; background: #f8f9fa;">
                        <tbody>
                            <!-- IMPORTE (subtotal original antes de descuento) -->
                            <tr>
                                <td class="text-end fw-bold" style="padding: 12px;">IMPORTE</td>
                                <td class="text-end" style="width: 120px; padding: 12px;">
                                    <span class="fw-bold">$<?= number_format($cotizacion['subtotal'], 2) ?></span>
                                </td>
                            </tr>
                            
                            <!-- DESCUENTO (si hay) -->
                            <?php if ($cotizacion['descuento_porcentaje'] > 0): ?>
                            <tr>
                                <td class="text-end fw-bold" style="padding: 12px; color: #dc3545;">DESCUENTO <?= $cotizacion['descuento_porcentaje'] ?>%</td>
                                <td class="text-end" style="padding: 12px;">
                                    <span class="fw-bold text-danger">-$<?= number_format($cotizacion['descuento_monto'], 2) ?></span>
                                </td>
                            </tr>
                            <?php endif; ?>
                            
                            <!-- SUBTOTAL (después de descuento) -->
                            <tr style="border-top: 1px solid #dee2e6;">
                                <td class="text-end fw-bold" style="padding: 12px;">SUBTOTAL</td>
                                <td class="text-end" style="padding: 12px;">
                                    <span class="fw-bold">$<?= number_format($cotizacion['subtotal'] - $cotizacion['descuento_monto'], 2) ?></span>
                                </td>
                            </tr>
                            
                            <!-- IVA ESPECIAL (si hay) -->
                            <?php 
                            $subtotal_con_descuento = $cotizacion['subtotal'] - $cotizacion['descuento_monto'];
                            $iva_especial_calculado = $cotizacion['total'] - $subtotal_con_descuento;
                            if ($iva_especial_calculado > 0.01): 
                            ?>
                            <tr>
                                <td class="text-end fw-bold" style="padding: 12px; color:#198754;">IVA ESPECIAL</td>
                                <td class="text-end" style="padding: 12px;">
                                    <span class="fw-bold" style="color:#198754;">+$<?= number_format($iva_especial_calculado, 2) ?></span>
                                </td>
                            </tr>
                            <?php endif; ?>
                            
                            <!-- TOTAL FINAL -->
                            <tr class="border-top" style="border-top: 2px solid #232a7c !important; background: #f8f9fa;">
                                <td class="text-end fw-bold fs-5" style="padding: 15px; color: #232a7c;">TOTAL</td>
                                <td class="text-end fw-bold fs-5" style="padding: 15px; color: #e53935;">
                                    <span>$<?= number_format($cotizacion['total'], 2) ?></span>
                                </td>
                            </tr>
                            
                            <!-- SEPARADOR PARA INFORMACIÓN INTERNA -->
                            <tr style="background: #e9ecef;">
                                <td colspan="2" class="text-center" style="padding: 8px; font-size: 0.85rem; color: #6c757d; font-weight: bold; letter-spacing: 0.5px;">
                                    📊 INFORMACIÓN INTERNA
                                </td>
                            </tr>
                            
                            <!-- COSTO TOTAL -->
                            <tr class="fila-costo-total" style="background: #fff3cd;">
                                <td class="text-end fw-bold" style="padding: 12px; color: #856404;">COSTO TOTAL</td>
                                <td class="text-end" style="padding: 12px;">
                                    <span class="fw-bold" style="color: #856404;">$<?= number_format($costo_total, 2) ?></span>
                                </td>
                            </tr>
                            
                            <!-- GANANCIAS -->
                            <tr class="fila-ganancia" style="background: #d4edda;">
                                <td class="text-end fw-bold" style="padding: 12px; color: #155724;">GANANCIAS</td>
                                <td class="text-end fw-bold" style="padding: 12px; color: #155724;">
                                    <span>$<?= number_format($cotizacion['total'] - $costo_total, 2) ?></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="mt-3 p-3" style="background: #e9ecef; border-radius: 8px; border: 1px solid #dee2e6;">
                        <div class="row g-2">
                            <div class="col-12">
                                <strong>OBSERVACIONES:</strong>
                                <div class="text-dark mt-1">
                                    <?php 
                                    // Debug: verificar si el campo existe y su contenido
                                    $observaciones_valor = isset($cotizacion['observaciones']) ? trim($cotizacion['observaciones']) : '';
                                    
                                    if (!empty($observaciones_valor)): 
                                    ?>
                                        <?= htmlspecialchars($observaciones_valor) ?>
                                    <?php else: ?>
                                        <em class="text-muted">Sin observaciones</em>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
let estadoParams = {};
function cambiarEstado(cotizacionId, nuevoEstadoId, estadoAnteriorId) {
    // Guarda los parámetros para usarlos después
    estadoParams = { cotizacionId, nuevoEstadoId, estadoAnteriorId };
    // Muestra el modal
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarEstado'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btnConfirmarCambioEstado').addEventListener('click', function() {
        // Crea y envía el formulario como antes
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'cambiar_estado.php';

        const cotizacionIdInput = document.createElement('input');
        cotizacionIdInput.type = 'hidden';
        cotizacionIdInput.name = 'cotizacion_id';
        cotizacionIdInput.value = estadoParams.cotizacionId;

        const nuevoEstadoInput = document.createElement('input');
        nuevoEstadoInput.type = 'hidden';
        nuevoEstadoInput.name = 'nuevo_estado_id';
        nuevoEstadoInput.value = estadoParams.nuevoEstadoId;

        const estadoAnteriorInput = document.createElement('input');
        estadoAnteriorInput.type = 'hidden';
        estadoAnteriorInput.name = 'estado_anterior_id';
        estadoAnteriorInput.value = estadoParams.estadoAnteriorId;

        const redirectInput = document.createElement('input');
        redirectInput.type = 'hidden';
        redirectInput.name = 'redirect_url';
        redirectInput.value = window.location.href;

        form.appendChild(cotizacionIdInput);
        form.appendChild(nuevoEstadoInput);
        form.appendChild(estadoAnteriorInput);
        form.appendChild(redirectInput);

        document.body.appendChild(form);
        form.submit();
    });
});
</script>
<!-- Modal de confirmación para cambio de estado -->
<div class="modal fade" id="modalConfirmarEstado" tabindex="-1" aria-labelledby="modalConfirmarEstadoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header" style="background: #232a7c; color: #fff; border-top-left-radius: 1rem; border-top-right-radius: 1rem;">
        <h5 class="modal-title d-flex align-items-center gap-2" id="modalConfirmarEstadoLabel">
          <i class="bi bi-question-circle-fill" style="font-size:2rem;color:#ffc107;"></i>
          Confirmar cambio de estado
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" style="font-size:1.15rem; color:#232a7c; padding: 2rem 1.5rem;">
        ¿Estás seguro de que quieres cambiar el estado de esta cotización?
      </div>
      <div class="modal-footer" style="border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btnConfirmarCambioEstado">Aceptar</button>
      </div>
    </div>
  </div>
</div>
<script>
// Personalización de encabezado desde localStorage - DESACTIVADO PARA MANTENER DATOS FIJOS
(function() {
  // Solo mantener logos dinámicos si es necesario
  const cfg = JSON.parse(localStorage.getItem('cotiz_config_encabezado') || '{}');
  
  console.log('Config encontrada (solo para logs):', cfg); // Debug
  
  // ❌ TELÉFONO Y LOGO FIJOS - NO CAMBIAR
  // El teléfono y logo ahora están fijos en el HTML y no se modifican
  
  // ❌ DIRECCIÓN FIJA - NO CAMBIAR  
  // La dirección está fija en el HTML y no se modifica
  
  // ✅ Solo mantener logos dinámicos si es necesario
  if (cfg.logos && Array.isArray(cfg.logos)) {
    const logosContainer = document.querySelector('.cotizacion-info .logos-dinamicos');
    if (logosContainer) logosContainer.innerHTML = '';
    cfg.logos.forEach(function(file) {
      const img = document.createElement('img');
      img.src = '../assets/img/' + file;
      img.alt = file.replace(/\.[a-zA-Z0-9]+$/, '');
      img.style.height = '28px';
      img.style.marginLeft = '8px';
      img.style.verticalAlign = 'middle';
      if (logosContainer) logosContainer.appendChild(img);
    });
  }
})();

// Mejorar impresión - ocultar información interna
window.addEventListener('beforeprint', function() {
  // Ocultar filas de información interna
  const informacionInterna = document.querySelectorAll('.fila-costo-total, .fila-ganancia');
  informacionInterna.forEach(row => {
    row.style.display = 'none';
  });
  
  // Ocultar separador de información interna y filas siguientes
  const separador = document.querySelector('td[colspan="2"]:contains("📊")');
  if (separador) {
    let currentRow = separador.closest('tr');
    while (currentRow) {
      currentRow.style.display = 'none';
      currentRow = currentRow.nextElementSibling;
    }
  }
  
  // Cambiar título de la página para impresión
  document.title = 'Cotización ' + (document.querySelector('.cotizacion-info strong')?.textContent || '');
});

window.addEventListener('afterprint', function() {
  // Restaurar visibilidad después de imprimir
  const informacionInterna = document.querySelectorAll('.fila-costo-total, .fila-ganancia');
  informacionInterna.forEach(row => {
    row.style.display = '';
  });
  
  // Restaurar título original
  document.title = 'Cotización <?= $cotizacion['numero_cotizacion'] ?> | Gestor de inventarios';
});

// Lanzar impresión automática si la URL tiene ?imprimir=1
if (window.location.search.includes('imprimir=1')) {
  window.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() { window.print(); }, 300);
  });
}
</script>
<script>
(function() {
  const cfg = JSON.parse(localStorage.getItem('cotiz_config_encabezado') || '{}');
  if (cfg.mostrarSinStock !== false) {
    // Mostrar badge solo si corresponde
    document.querySelectorAll('.descripcion').forEach(function(td) {
      const badge = td.querySelector('.badge-sin-stock');
      if (!badge) return;
      // Lógica PHP embebida para saber si es sin stock
      const sinStock = td.innerHTML.includes('data-sin-stock="1"');
      if (sinStock) {
        badge.textContent = 'Sin stock';
        badge.className = 'badge bg-danger ms-2 badge-sin-stock';
        badge.style.display = '';
      }
    });
  }
})();
</script>
<script>
  // Cierra las alertas automáticamente después de 4 segundos
  setTimeout(function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
      if (alert.classList.contains('show')) {
        var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        bsAlert.close();
      }
    });
  }, 4000);
</script>
</body>
</html>