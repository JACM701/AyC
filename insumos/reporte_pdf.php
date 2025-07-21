<?php
// Instrucciones: Instala DomPDF con Composer: composer require dompdf/dompdf
require_once '../connection.php';
require_once '../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Ajusta la ruta si es necesario
use Dompdf\Dompdf;
use Dompdf\Options;

$insumo_id = isset($_GET['insumo_id']) ? intval($_GET['insumo_id']) : 0;
if (!$insumo_id) {
    die('ID de insumo no válido.');
}
// Consulta datos del insumo
$stmt = $mysqli->prepare("SELECT i.*, c.name as categoria_nombre, s.name as proveedor_nombre FROM insumos i LEFT JOIN categories c ON i.category_id = c.category_id LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id WHERE i.insumo_id = ?");
$stmt->bind_param('i', $insumo_id);
$stmt->execute();
$insumo = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$insumo) die('Insumo no encontrado.');
// Consulta movimientos
$stmt = $mysqli->prepare("SELECT m.*, u.username as usuario FROM insumos_movements m LEFT JOIN users u ON m.user_id = u.user_id WHERE m.insumo_id = ? ORDER BY m.fecha_movimiento ASC");
$stmt->bind_param('i', $insumo_id);
$stmt->execute();
$movs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
// HTML para el PDF
$logo = '../assets/img/LogoWeb.png';
$html = '<html><head><style>
body { font-family: Arial, sans-serif; color: #232a7c; }
.titulo { background: #232a7c; color: #fff; text-align: center; font-size: 1.5em; font-weight: bold; padding: 10px 0; }
.tabla-datos { margin: 20px 0; width: 100%; }
.tabla-datos td { padding: 4px 8px; }
.tabla-movs { border-collapse: collapse; width: 100%; font-size: 0.98em; }
.tabla-movs th { background: #f4f6fb; color: #232a7c; font-weight: bold; border: 1px solid #ccc; padding: 6px; }
.tabla-movs td { border: 1px solid #ccc; padding: 5px; }
</style></head><body>';
$html .= "<img src='$logo' style='height:50px; float:right;'>";
$html .= "<div class='titulo'>Reporte de Movimientos de Insumo</div>";
$html .= "<table class='tabla-datos'>
<tr><td><b>Nombre:</b></td><td>{$insumo['nombre']}</td></tr>
<tr><td><b>Categoría:</b></td><td>{$insumo['categoria_nombre']}</td></tr>
<tr><td><b>Proveedor:</b></td><td>{$insumo['proveedor_nombre']}</td></tr>
<tr><td><b>Stock actual:</b></td><td>{$insumo['cantidad']} {$insumo['unidad']}</td></tr>
<tr><td><b>Stock mínimo:</b></td><td>{$insumo['minimo']} {$insumo['unidad']}</td></tr>
<tr><td><b>Precio unitario:</b></td><td>${$insumo['precio_unitario']}/{$insumo['unidad']}</td></tr>
<tr><td><b>Estado:</b></td><td>{$insumo['estado']}</td></tr>
<tr><td><b>Exportado:</b></td><td>" . (isset($_SESSION['username']) ? $_SESSION['username'] : 'Usuario') . "</td></tr>
<tr><td><b>Fecha de exportación:</b></td><td>" . date('d/m/Y H:i:s') . "</td></tr>
</table>";
$html .= "<table class='tabla-movs'><tr><th>Fecha</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th><th>Usuario</th></tr>";
if (count($movs) > 0) {
    foreach ($movs as $mov) {
        $fecha = date('d/m/Y', strtotime($mov['fecha_movimiento']));
        $tipo = $mov['tipo_movimiento'] === 'entrada' ? 'Entrada' : 'Salida';
        $motivo = $mov['motivo'] ?: 'Sin motivo';
        $usuario = $mov['usuario'] ?: 'Sistema';
        $cantidad = $mov['piezas_movidas'] && floatval($mov['piezas_movidas']) > 0 ? intval($mov['piezas_movidas']) . ' piezas' : floatval($mov['cantidad']) . ' ' . $insumo['unidad'];
        $html .= "<tr><td>$fecha</td><td>$tipo</td><td>$cantidad</td><td>$motivo</td><td>$usuario</td></tr>";
    }
} else {
    $html .= "<tr><td colspan='5' style='text-align:center;'>No hay movimientos registrados</td></tr>";
}
$html .= "</table></body></html>";
// Generar PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$filename = 'reporte_insumo_' . preg_replace('/\s+/', '_', $insumo['nombre']) . '_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, ['Attachment' => false]);
exit; 