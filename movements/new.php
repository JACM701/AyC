<?php
require_once '../auth/middleware.php';
require_once '../connection.php';
require_once '../includes/bobina_helpers.php';

$success = $error = '';

// Obtener productos para el select
$products = $mysqli->query("SELECT product_id, product_name, tipo_gestion, barcode FROM products ORDER BY product_name");

// Obtener tipos de movimiento para el select
$movement_types = $mysqli->query("SELECT movement_type_id, name FROM movement_types ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $movement_type_id = isset($_POST['movement_type_id']) ? intval($_POST['movement_type_id']) : 0;
    $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
    $bobina_id = isset($_POST['bobina_id']) ? intval($_POST['bobina_id']) : null;
    $usuario_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

    if ($product_id && $movement_type_id && $quantity > 0) {
        // Verificar si es producto tipo bobina
        $stmt = $mysqli->prepare("SELECT tipo_gestion FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        if ($product['tipo_gestion'] === 'bobina') {
            // Para productos tipo bobina, verificar que se seleccionó una bobina
            if (!$bobina_id) {
                $error = "Para productos tipo bobina, debes seleccionar una bobina específica.";
            } else {
                // Verificar metros disponibles en la bobina
                $stmt = $mysqli->prepare("SELECT metros_actuales FROM bobinas WHERE bobina_id = ? AND product_id = ?");
                $stmt->bind_param("ii", $bobina_id, $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $bobina = $result->fetch_assoc();
                $stmt->close();

                // Obtener el tipo de movimiento con el campo is_entry
                $stmt = $mysqli->prepare("SELECT name, is_entry FROM movement_types WHERE movement_type_id = ?");
                $stmt->bind_param("i", $movement_type_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $movement_type = $result->fetch_assoc();
                $stmt->close();

                // Determinar si es entrada o salida usando el campo is_entry
                $is_entrada = $movement_type['is_entry'] == 1;

                if (!$is_entrada && $bobina['metros_actuales'] < $quantity) {
                    $error = "No hay suficientes metros disponibles en la bobina. Disponible: " . $bobina['metros_actuales'] . "m";
                } else {
                    // Registrar movimiento y actualizar bobina
                    $mysqli->begin_transaction();
                    try {
                        // Para bobinas: suma si es entrada, resta si es salida
                        $movement_quantity = $is_entrada ? $quantity : -$quantity;
                        // Insertar movimiento
                        $stmt = $mysqli->prepare("INSERT INTO movements (product_id, movement_type_id, quantity, movement_date, bobina_id, user_id) VALUES (?, ?, ?, NOW(), ?, ?)");
                        $stmt->bind_param("iiidi", $product_id, $movement_type_id, $movement_quantity, $bobina_id, $usuario_id);
                        $stmt->execute();
                        $stmt->close();

                        // Actualizar metros en la bobina
                        if ($is_entrada) {
                            $stmt = $mysqli->prepare("UPDATE bobinas SET metros_actuales = metros_actuales + ? WHERE bobina_id = ?");
                        } else {
                            $stmt = $mysqli->prepare("UPDATE bobinas SET metros_actuales = metros_actuales - ? WHERE bobina_id = ?");
                        }
                        $stmt->bind_param("di", $quantity, $bobina_id);
                        $stmt->execute();
                        $stmt->close();

                        // Actualizar stock del producto (suma de todas las bobinas)
                        actualizarStockBobina($mysqli, $product_id);

                        $mysqli->commit();
                        $accion = $is_entrada ? 'entrada' : 'consumo';
                        $success = "Movimiento de $accion de bobina registrado correctamente. Stock actualizado.";
                    } catch (Exception $e) {
                        $mysqli->rollback();
                        $error = "Error al registrar movimiento: " . $e->getMessage();
                    }
                }
            }
        } else {
            // Para productos normales, determinar si es entrada o salida usando is_entry
            $stmt = $mysqli->prepare("SELECT name, is_entry FROM movement_types WHERE movement_type_id = ?");
            $stmt->bind_param("i", $movement_type_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $movement_type = $result->fetch_assoc();
            $stmt->close();

            // Determinar si es entrada o salida usando el campo is_entry
            $is_entrada = $movement_type['is_entry'] == 1;

            $mysqli->begin_transaction();
            try {
                // Insertar movimiento (cantidad positiva para entradas, negativa para salidas)
                $movement_quantity = $is_entrada ? $quantity : -$quantity;
                $stmt = $mysqli->prepare("INSERT INTO movements (product_id, movement_type_id, quantity, movement_date, user_id) VALUES (?, ?, ?, NOW(), ?)");
                $stmt->bind_param("iidi", $product_id, $movement_type_id, $movement_quantity, $usuario_id);
                $stmt->execute();
                $stmt->close();

                // Actualizar stock del producto
                $stock_change = $is_entrada ? $quantity : -$quantity;
                $stmt = $mysqli->prepare("UPDATE products SET quantity = quantity + ? WHERE product_id = ?");
                $stmt->bind_param("di", $stock_change, $product_id);
                $stmt->execute();
                $stmt->close();

                $mysqli->commit();
                $tipo_texto = $is_entrada ? "entrada" : "salida";
                $success = "Movimiento de {$tipo_texto} registrado correctamente. Stock actualizado.";
            } catch (Exception $e) {
                $mysqli->rollback();
                $error = "Error al registrar movimiento: " . $e->getMessage();
            }
        }
    } else {
        $error = "Por favor, completa todos los campos correctamente.";
    }
    // Recargar selects tras el POST
    $products = $mysqli->query("SELECT product_id, product_name, tipo_gestion, barcode FROM products ORDER BY product_name");
    $movement_types = $mysqli->query("SELECT movement_type_id, name FROM movement_types ORDER BY name");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar movimiento | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content { max-width: 440px; margin: 40px auto 0 auto; }
        .main-content h2 { margin-bottom: 22px; }
        .form-group { margin-bottom: 14px; }
        .form-group input, .form-group select { margin-top: 4px; }
        .form-actions { display: flex; gap: 10px; margin-top: 18px; }
        .form-actions button { flex: 1; }
        .bobina-info { 
            background: #e3f2fd; 
            padding: 10px; 
            border-radius: 8px; 
            margin-top: 10px; 
            display: none; 
        }
        .barcode-result {
            transition: all 0.3s ease;
        }
        .barcode-result .alert {
            margin-bottom: 0;
            border-radius: 8px;
        }
        #barcodeInput {
            font-family: monospace;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }
        #btnBuscarBarcode {
            border-left: none;
        }
        #btnBuscarBarcode:hover {
            background-color: #6c757d;
            color: white;
        }
        @media (max-width: 900px) { .main-content { max-width: 98vw; padding: 0 2vw; } }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h2>Registrar movimiento de inventario</h2>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <form action="" method="POST" autocomplete="off">
        <div class="mb-3">
            <label for="product_id" class="form-label">Producto:</label>
            <div class="input-group">
                <select name="product_id" id="product_id" class="form-select" required>
                    <option value="">-- Selecciona un producto --</option>
                    <?php while ($row = $products->fetch_assoc()): ?>
                        <option value="<?= $row['product_id'] ?>" 
                                data-tipo="<?= $row['tipo_gestion'] ?>"
                                data-barcode="<?= htmlspecialchars($row['barcode'] ?? '') ?>"
                                <?= (isset($_POST['product_id']) && $_POST['product_id'] == $row['product_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['product_name']) ?>
                            <?php if ($row['tipo_gestion'] === 'bobina'): ?>
                                (Bobina)
                            <?php endif; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="button" class="btn btn-outline-secondary" id="btnBuscarBarcode" title="Buscar por código de barras">
                    <i class="bi bi-upc-scan"></i>
                </button>
            </div>
            <small class="text-muted">O usa el botón de escáner para buscar por código de barras</small>
        </div>

        <!-- Modal para búsqueda por código de barras -->
        <div class="modal fade" id="modalBuscarBarcode" tabindex="-1" aria-labelledby="modalBuscarBarcodeLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalBuscarBarcodeLabel">
                            <i class="bi bi-upc-scan"></i> Buscar por código de barras
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="barcodeInput" class="form-label">Código de barras:</label>
                            <input type="text" class="form-control" id="barcodeInput" placeholder="Escanea o ingresa el código de barras" autofocus>
                            <div class="form-text">Escanea el código de barras del producto o ingrésalo manualmente</div>
                        </div>
                        <div id="barcodeResult" class="mt-3 barcode-result" style="display:none;"></div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                <strong>Consejos:</strong>
                                <ul class="mt-1 mb-0">
                                    <li>Coloca el cursor en el campo y escanea el código de barras</li>
                                    <li>También puedes escribir el código manualmente</li>
                                    <li>Presiona Enter para seleccionar el producto encontrado</li>
                                </ul>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" id="btnSeleccionarProducto" style="display:none;">
                            <i class="bi bi-check-circle"></i> Seleccionar producto
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de bobinas (solo para productos tipo bobina) -->
        <div id="bobinaSection" style="display:none;">
            <div class="mb-3">
                <label for="bobina_id" class="form-label">Bobina:</label>
                <select name="bobina_id" id="bobina_id" class="form-select">
                    <option value="">-- Selecciona una bobina --</option>
                </select>
            </div>
            <div id="bobinaInfo" class="bobina-info">
                <i class="bi bi-info-circle"></i>
                <span id="bobinaInfoText"></span>
            </div>
        </div>

        <!-- Campo de cantidad SIEMPRE visible -->
        <div class="mb-3" id="cantidadSection">
            <label for="quantity" class="form-label" id="cantidadLabel">Cantidad *</label>
            <input type="number" class="form-control" name="quantity" id="quantity" min="1" step="1" required>
            <div class="form-text" id="quantityHelp">Ingresa la cantidad</div>
        </div>

        <!-- Radios de tipo de movimiento SIEMPRE visibles -->
        <div class="mb-3" id="tipoMovimientoSection">
            <label class="form-label">Tipo de movimiento:</label>
            <div class="d-flex gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="is_entrada" id="is_entrada_1" value="1" checked>
                    <label class="form-check-label" for="is_entrada_1">
                        <i class="bi bi-arrow-down-circle text-success"></i> Entrada (aumenta stock)
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="is_entrada" id="is_entrada_0" value="0">
                    <label class="form-check-label" for="is_entrada_0">
                        <i class="bi bi-arrow-up-circle text-danger"></i> Salida (disminuye stock)
                    </label>
                </div>
            </div>
            <small class="text-muted">Selecciona si este movimiento aumenta o disminuye el inventario</small>
            <!-- Campo oculto para movement_type_id -->
            <input type="hidden" name="movement_type_id" id="movement_type_id" value="1">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="bi bi-plus-circle"></i> Registrar movimiento
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <a href="manage_types.php" class="btn btn-outline-info">
                <i class="bi bi-gear"></i> Gestionar Tipos
            </a>
        </div>
    </form>
</main>
<script src="../assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- Lógica robusta para producto, bobina y cantidad ---
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    const bobinaSection = document.getElementById('bobinaSection');
    const bobinaSelect = document.getElementById('bobina_id');
    const cantidadInput = document.getElementById('quantity');
    const cantidadLabel = document.getElementById('cantidadLabel');
    const cantidadHelp = document.getElementById('quantityHelp');
    const tipoMovimientoSection = document.getElementById('tipoMovimientoSection');
    const isEntradaRadio1 = document.getElementById('is_entrada_1');
    const isEntradaRadio0 = document.getElementById('is_entrada_0');
    const movementTypeIdHidden = document.getElementById('movement_type_id');

    function actualizarCamposPorProducto() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const tipoGestion = selectedOption ? selectedOption.dataset.tipo : '';
        if (tipoGestion === 'bobina') {
            bobinaSection.style.display = 'block';
            cantidadInput.step = '0.01';
            cantidadInput.min = '0.01';
            cantidadLabel.textContent = 'Metros a mover *';
            cantidadHelp.textContent = 'Ingresa los metros a consumir o ingresar';
            // Cargar bobinas disponibles
            fetch(`../bobinas/bobinas_por_producto.php?product_id=${productSelect.value}`)
                .then(response => response.json())
                .then(bobinas => {
                    bobinaSelect.innerHTML = '<option value="">-- Selecciona una bobina --</option>';
                    bobinas.forEach(bobina => {
                        const option = document.createElement('option');
                        option.value = bobina.bobina_id;
                        option.textContent = `${bobina.identificador || 'Bobina #' + bobina.bobina_id} - ${bobina.metros_actuales}m disponibles`;
                        option.dataset.metros = bobina.metros_actuales;
                        bobinaSelect.appendChild(option);
                    });
                });
        } else {
            bobinaSection.style.display = 'none';
            cantidadInput.step = '1';
            cantidadInput.min = '1';
            cantidadLabel.textContent = 'Cantidad *';
            cantidadHelp.textContent = 'Ingresa la cantidad';
        }
    }
    productSelect.addEventListener('change', actualizarCamposPorProducto);
    actualizarCamposPorProducto();

    // Mostrar info de bobina seleccionada
    if (bobinaSelect) {
        bobinaSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const bobinaInfo = document.getElementById('bobinaInfo');
            const bobinaInfoText = document.getElementById('bobinaInfoText');
            if (this.value) {
                const metros = selectedOption.dataset.metros;
                bobinaInfoText.textContent = `Metros disponibles: ${metros}m`;
                bobinaInfo.style.display = 'block';
            } else {
                bobinaInfo.style.display = 'none';
            }
        });
    }

    // Actualizar el campo hidden de movement_type_id cuando cambian los radios
    function actualizarMovementTypeId() {
        const isEntrada = isEntradaRadio1.checked;
        movementTypeIdHidden.value = isEntrada ? '1' : '2'; // Asumiendo que '1' es Entrada y '2' es Salida
    }

    isEntradaRadio1.addEventListener('change', actualizarMovementTypeId);
    isEntradaRadio0.addEventListener('change', actualizarMovementTypeId);
    // Establecer el valor inicial al cargar la página
    actualizarMovementTypeId();

    // Validar formulario antes de enviar
    document.querySelector('form').addEventListener('submit', function(e) {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const tipoGestion = selectedOption ? selectedOption.dataset.tipo : '';
        if (tipoGestion === 'bobina' && !bobinaSelect.value) {
            e.preventDefault();
            alert('Para productos tipo bobina, debes seleccionar una bobina específica.');
        }
    });
});

// --- Búsqueda por código de barras ---
let productoEncontrado = null;
document.getElementById('btnBuscarBarcode').addEventListener('click', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalBuscarBarcode'));
    modal.show();
    setTimeout(() => {
        const input = document.getElementById('barcodeInput');
        if (input) input.focus();
    }, 300);
});
document.getElementById('barcodeInput').addEventListener('input', function() {
    const barcode = this.value.trim().toUpperCase().replace(/\s+/g, '');
    const resultDiv = document.getElementById('barcodeResult');
    const btnSeleccionar = document.getElementById('btnSeleccionarProducto');
    const productSelect = document.getElementById('product_id');
    let encontrado = false;
    for (let option of productSelect.options) {
        let optBarcode = (option.dataset.barcode || '').trim().toUpperCase().replace(/\s+/g, '');
        if (optBarcode && optBarcode === barcode) {
            productoEncontrado = {
                id: option.value,
                name: option.textContent,
                tipo: option.dataset.tipo
            };
            encontrado = true;
            break;
        }
    }
    if (encontrado) {
        resultDiv.innerHTML = `
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <strong>Producto encontrado:</strong><br>
                ${productoEncontrado.name}
            </div>
        `;
        resultDiv.style.display = 'block';
        btnSeleccionar.style.display = 'inline-block';
    } else {
        resultDiv.innerHTML = `
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Producto no encontrado</strong><br>
                No se encontró ningún producto con el código de barras: <code>${barcode}</code>
            </div>
        `;
        resultDiv.style.display = 'block';
        btnSeleccionar.style.display = 'none';
        productoEncontrado = null;
    }
});
document.getElementById('btnSeleccionarProducto').addEventListener('click', function() {
    if (productoEncontrado) {
        const productSelect = document.getElementById('product_id');
        productSelect.value = productoEncontrado.id;
        // Disparar el evento change para activar la lógica de bobinas
        const event = new Event('change');
        productSelect.dispatchEvent(event);
        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalBuscarBarcode'));
        modal.hide();
        // Limpiar campo de búsqueda
        document.getElementById('barcodeInput').value = '';
        document.getElementById('barcodeResult').style.display = 'none';
        this.style.display = 'none';
        productoEncontrado = null;
    }
});
document.getElementById('modalBuscarBarcode').addEventListener('hidden.bs.modal', function() {
    document.getElementById('barcodeInput').value = '';
    document.getElementById('barcodeResult').style.display = 'none';
    document.getElementById('btnSeleccionarProducto').style.display = 'none';
    productoEncontrado = null;
});
document.getElementById('barcodeInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        if (productoEncontrado) {
            document.getElementById('btnSeleccionarProducto').click();
        }
    }
});
</script>

<!-- Modal para agregar nuevo tipo de movimiento -->
<div class="modal fade" id="modalNuevoTipoMovimiento" tabindex="-1" aria-labelledby="modalNuevoTipoMovimientoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoTipoMovimientoLabel">
                    <i class="bi bi-plus-circle"></i> Agregar Nuevo Tipo de Movimiento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="nuevoTipoMovimiento" class="form-label">Nombre del tipo de movimiento:</label>
                    <input type="text" class="form-control" id="nuevoTipoMovimiento" placeholder="Ej: Transferencia, Devolución, etc." onkeypress="if(event.key === 'Enter') { event.preventDefault(); agregarNuevoTipoMovimiento(); }">
                    <div class="form-text">Ingresa un nombre descriptivo para el nuevo tipo de movimiento.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="agregarNuevoTipoMovimiento()">
                    <i class="bi bi-check-circle"></i> Agregar
                </button>
            </div>
        </div>
    </div>
</div>
</body>
</html>