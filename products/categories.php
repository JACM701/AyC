<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    $success = $error = "";

    // Procesar formulario de nueva categoría
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'add' && !empty($_POST['category_name'])) {
            $category_name = trim($_POST['category_name']);
            $description = trim($_POST['description'] ?? '');
            
            // Verificar si la categoría ya existe
            $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM categories WHERE name = ?");
            $stmt->bind_param("s", $category_name);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->fetch_assoc()['count'] > 0;
            $stmt->close();
            
            if ($exists) {
                $error = "La categoría ya existe.";
            } else {
                // Insertar nueva categoría
                $stmt = $mysqli->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $category_name, $description);
                if ($stmt->execute()) {
                    $success = "Categoría agregada correctamente.";
                } else {
                    $error = "Error al agregar la categoría: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }

    // Obtener todas las categorías con conteo de productos
    $categorias = $mysqli->query("
        SELECT 
            c.category_id,
            c.name, 
            c.description,
            COUNT(p.product_id) as total_productos,
            SUM(p.quantity) as stock_total,
            AVG(p.price) as precio_promedio
        FROM categories c
        LEFT JOIN products p ON c.category_id = p.category_id
        GROUP BY c.category_id, c.name, c.description
        ORDER BY total_productos DESC
    ");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Categorías | Gestor de inventarios Alarmas y Cámaras de seguridad del sureste</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content {
            position: relative;
        }
        .main-content h2 {
            margin-bottom: 10px;
        }
        .category-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(18,24,102,0.07);
            border-left: 4px solid #121866;
        }
        .category-stats {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #121866;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <h2>Gestión de Categorías</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i>
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nueva Categoría</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label for="category_name" class="form-label">Nombre de la categoría:</label>
                                <input type="text" class="form-control" name="category_name" id="category_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción:</label>
                                <textarea class="form-control" name="description" id="description" rows="3" placeholder="Descripción opcional de la categoría"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Agregar Categoría
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Información</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>¿Cómo usar las categorías?</strong></p>
                        <ul class="mb-0">
                            <li>Las categorías te ayudan a organizar tus productos</li>
                            <li>Puedes filtrar productos por categoría en el listado</li>
                            <li>Las categorías se crean automáticamente al agregar productos</li>
                            <li>Aquí puedes ver estadísticas de cada categoría</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <h3>Categorías Existentes</h3>
        <?php if ($categorias && $categorias->num_rows > 0): ?>
            <div class="row">
                <?php while ($cat = $categorias->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="category-card">
                            <h5 class="mb-2">
                                <i class="bi bi-tags"></i> 
                                <?= htmlspecialchars($cat['name']) ?>
                            </h5>
                            <div class="category-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $cat['total_productos'] ?></div>
                                    <div class="stat-label">Productos</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?= $cat['stock_total'] ?></div>
                                    <div class="stat-label">Stock Total</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">$<?= number_format($cat['precio_promedio'], 2) ?></div>
                                    <div class="stat-label">Precio Prom.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                No hay categorías creadas aún. Agrega productos con categorías para verlas aquí.
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="list.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al listado de productos
            </a>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Resalta el menú activo
        document.querySelector('.sidebar-productos').classList.add('active');
    </script>
</body>
</html> 