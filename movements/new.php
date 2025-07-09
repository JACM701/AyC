<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Obtener productos para el select
$products = $mysqli->query("SELECT product_id, product_name, tipo_gestion FROM products ORDER BY product_name");

// Obtener tipos de movimiento para el select
$movement_types = $mysqli->query("SELECT movement_type_id, name FROM movement_types ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $movement_type_id = isset($_POST['movement_type_id']) ? intval($_POST['movement_type_id']) : 0;
    $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
    $bobina_id = isset($_POST['bobina_id']) ? intval($_POST['bobina_id']) : null;

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
                        $stmt = $mysqli->prepare("INSERT INTO movements (product_id, movement_type_id, quantity, movement_date, bobina_id) VALUES (?, ?, ?, NOW(), ?)");
                        $stmt->bind_param("iiid", $product_id, $movement_type_id, $movement_quantity, $bobina_id);
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
                        $stmt = $mysqli->prepare("UPDATE products SET quantity = (SELECT COALESCE(SUM(metros_actuales), 0) FROM bobinas WHERE product_id = ?) WHERE product_id = ?");
                        $stmt->bind_param("ii", $product_id, $product_id);
                        $stmt->execute();
                        $stmt->close();

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
                $stmt = $mysqli->prepare("INSERT INTO movements (product_id, movement_type_id, quantity, movement_date) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iid", $product_id, $movement_type_id, $movement_quantity);
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
    $products = $mysqli->query("SELECT product_id, product_name, tipo_gestion FROM products ORDER BY product_name");
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
            <select name="product_id" id="product_id" class="form-select" required>
                <option value="">-- Selecciona un producto --</option>
                <?php while ($row = $products->fetch_assoc()): ?>
                    <option value="<?= $row['product_id'] ?>" 
                            data-tipo="<?= $row['tipo_gestion'] ?>"
                            <?= (isset($_POST['product_id']) && $_POST['product_id'] == $row['product_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['product_name']) ?>
                        <?php if ($row['tipo_gestion'] === 'bobina'): ?>
                            (Bobina)
                        <?php endif; ?>
                    </option>
                <?php endwhile; ?>
            </select>
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

        <div class="mb-3">
            <label for="movement_type_id" class="form-label">Tipo de movimiento:</label>
            <div class="input-group">
                <select name="movement_type_id" id="movement_type_id" class="form-select" required>
                    <option value="">-- Selecciona un tipo --</option>
                    <?php while ($mt = $movement_types->fetch_assoc()): ?>
                        <option value="<?= $mt['movement_type_id'] ?>" <?= (isset($_POST['movement_type_id']) && $_POST['movement_type_id'] == $mt['movement_type_id']) ? 'selected' : '' ?>><?= htmlspecialchars($mt['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoTipoMovimiento" title="Agregar nuevo tipo de movimiento">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
        </div>
        <div class="mb-3">
            <label for="quantity" class="form-label">Cantidad:</label>
            <input type="number" name="quantity" id="quantity" class="form-control" min="0.01" step="0.01" required value="<?= isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '' ?>">
            <small class="text-muted" id="quantityHelp">Ingresa la cantidad</small>
        </div>
        
        <!-- Campo para especificar tipo de movimiento (solo para productos normales) -->
        <div class="mb-3" id="tipoMovimientoSection" style="display:none;">
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
    // Manejar cambio de producto
    document.getElementById('product_id').addEventListener('change', function() {
        const productId = this.value;
        const selectedOption = this.options[this.selectedIndex];
        const tipoGestion = selectedOption.dataset.tipo;
        const bobinaSection = document.getElementById('bobinaSection');
        const tipoMovimientoSection = document.getElementById('tipoMovimientoSection');
        const quantityInput = document.getElementById('quantity');
        const quantityHelp = document.getElementById('quantityHelp');
        
        if (tipoGestion === 'bobina') {
            bobinaSection.style.display = 'block';
            tipoMovimientoSection.style.display = 'none';
            quantityInput.step = '0.01';
            quantityInput.min = '0.01';
            quantityHelp.textContent = 'Ingresa los metros a consumir';
            
            // Cargar bobinas disponibles
            fetch(`../bobinas/bobinas_por_producto.php?product_id=${productId}`)
                .then(response => response.json())
                .then(bobinas => {
                    const bobinaSelect = document.getElementById('bobina_id');
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
            tipoMovimientoSection.style.display = 'block';
            quantityInput.step = '1';
            quantityInput.min = '1';
            quantityHelp.textContent = 'Ingresa la cantidad';
        }
    });

    // Manejar cambio de bobina
    document.getElementById('bobina_id').addEventListener('change', function() {
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

    // Validar formulario
    document.querySelector('form').addEventListener('submit', function(e) {
        const productId = document.getElementById('product_id').value;
        const selectedOption = document.getElementById('product_id').options[document.getElementById('product_id').selectedIndex];
        const tipoGestion = selectedOption.dataset.tipo;
        const bobinaId = document.getElementById('bobina_id').value;
        
        if (tipoGestion === 'bobina' && !bobinaId) {
            e.preventDefault();
            alert('Para productos tipo bobina, debes seleccionar una bobina específica.');
        }
    });

    // Función para agregar nuevo tipo de movimiento
    function agregarNuevoTipoMovimiento() {
        const nombre = document.getElementById('nuevoTipoMovimiento').value.trim();
        if (!nombre) {
            alert('Por favor, ingresa un nombre para el tipo de movimiento.');
            return;
        }

        const formData = new FormData();
        formData.append('name', nombre);

        fetch('../movements/add_type.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Agregar la nueva opción al select
                const select = document.getElementById('movement_type_id');
                const option = document.createElement('option');
                option.value = data.id;
                option.textContent = data.name;
                select.appendChild(option);
                select.value = data.id; // Seleccionar el nuevo tipo
                
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevoTipoMovimiento'));
                modal.hide();
                
                // Limpiar campo
                document.getElementById('nuevoTipoMovimiento').value = '';
                
                // Mostrar mensaje de éxito
                alert('Tipo de movimiento agregado correctamente.');
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al agregar el tipo de movimiento.');
        });
    }

    // Enfocar el campo cuando se abra el modal
    document.getElementById('modalNuevoTipoMovimiento').addEventListener('shown.bs.modal', function() {
        document.getElementById('nuevoTipoMovimiento').focus();
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