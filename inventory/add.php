<?php
require_once '../auth/middleware.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simular agregar producto
    $success = 'Producto agregado correctamente al inventario.';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Producto | Inventario</title>
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
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            padding: 32px;
        }
        .form-title {
            font-size: 2rem;
            color: #121866;
            font-weight: 700;
            text-align: center;
            margin-bottom: 32px;
        }
        .form-section {
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e3e6f0;
        }
        .form-section:last-child {
            border-bottom: none;
        }
        .form-label {
            font-weight: 600;
            color: #121866;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid #cfd8dc;
            background: #f7f9fc;
            font-size: 1rem;
            padding: 12px 16px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #121866;
            box-shadow: 0 0 0 2px #e3e6fa;
        }
        .btn-primary {
            background: linear-gradient(135deg, #121866, #232a7c);
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(18,24,102,0.3);
        }
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        .form-actions button {
            flex: 1;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .form-container {
                margin: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="form-container">
            <h2 class="form-title">
                <i class="bi bi-plus-circle"></i> Agregar Producto al Inventario
            </h2>
            
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
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-section">
                    <h5 class="mb-3"><i class="bi bi-info-circle"></i> Información Básica</h5>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre del producto</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required 
                                       placeholder="Ej: Cámara Bullet 5MP">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="sku" class="form-label">SKU</label>
                                <input type="text" class="form-control" id="sku" name="sku" required 
                                       placeholder="Ej: CAM-001">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                  placeholder="Descripción detallada del producto"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categoria" class="form-label">Categoría</label>
                                <select class="form-select" id="categoria" name="categoria" required>
                                    <option value="">Selecciona una categoría</option>
                                    <option value="Cámaras">Cámaras</option>
                                    <option value="Cables">Cables</option>
                                    <option value="Grabadores">Grabadores</option>
                                    <option value="Accesorios">Accesorios</option>
                                    <option value="Conectores">Conectores</option>
                                    <option value="Alarmas">Alarmas</option>
                                    <option value="Redes">Redes</option>
                                    <option value="Sensores">Sensores</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="proveedor" class="form-label">Proveedor</label>
                                <input type="text" class="form-control" id="proveedor" name="proveedor" 
                                       placeholder="Ej: Dahua, Syscom, etc.">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h5 class="mb-3"><i class="bi bi-currency-dollar"></i> Información de Stock y Precio</h5>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock inicial</label>
                                <input type="number" class="form-control" id="stock" name="stock" required 
                                       min="0" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="precio" class="form-label">Precio unitario</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="precio" name="precio" 
                                           step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="stock_minimo" class="form-label">Stock mínimo</label>
                                <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" 
                                       min="0" placeholder="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ubicacion" class="form-label">Ubicación en almacén</label>
                                <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                                       placeholder="Ej: Estante A, Nivel 2">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="codigo_barras" class="form-label">Código de barras</label>
                                <input type="text" class="form-control" id="codigo_barras" name="codigo_barras" 
                                       placeholder="Escanea o ingresa el código">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h5 class="mb-3"><i class="bi bi-image"></i> Imagen del Producto</h5>
                    
                    <div class="mb-3">
                        <label for="imagen" class="form-label">Imagen del producto</label>
                        <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                        <div class="form-text">Formatos permitidos: JPG, PNG, GIF. Máximo 2MB.</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Agregar al Inventario
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Resalta el menú activo
        document.querySelector('.sidebar-inventario').classList.add('active');
        
        // Auto-generar SKU basado en categoría
        document.getElementById('categoria').addEventListener('change', function() {
            const categoria = this.value;
            const skuInput = document.getElementById('sku');
            
            if (categoria && !skuInput.value) {
                const prefix = categoria.substring(0, 3).toUpperCase();
                const random = Math.floor(Math.random() * 999) + 1;
                skuInput.value = prefix + '-' + random.toString().padStart(3, '0');
            }
        });
    </script>
</body>
</html> 