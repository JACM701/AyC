<?php if ($result && $result->num_rows > 0): ?>
    <!-- Vista de tabla -->
    <div class="table-header">
        <h5><i class="bi bi-list-ul"></i> Lista de Movimientos</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <?php
                    $cols = [
                        'm.movement_id' => 'ID',
                        'p.product_name' => 'Producto',
                        'mt.name' => 'Tipo',
                        'm.quantity' => 'Cantidad',
                        'u.username' => 'Usuario',
                        't.nombre' => 'Técnico',
                        'm.movement_date' => 'Fecha'
                    ];
                    foreach ($cols as $col => $label):
                        $isActive = $orden_col === $col;
                        $nextDir = ($isActive && strtoupper($orden_dir) === 'ASC') ? 'DESC' : 'ASC';
                        $icon = '';
                        if ($isActive) {
                            $icon = strtoupper($orden_dir) === 'ASC' ? '<i class="bi bi-caret-up-fill sort-icon sort-active"></i>' : '<i class="bi bi-caret-down-fill sort-icon sort-active"></i>';
                        }
                        echo "<th scope='col' data-col='$col' data-dir='$nextDir' style='cursor: pointer;'>$label $icon</th>";
                    endforeach;
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <span class="badge bg-secondary">#<?= $row['movement_id'] ?></span>
                        </td>
                        <td>
                            <div>
                                <strong><?= htmlspecialchars($row['product_name']) ?></strong>
                                <?php if ($row['tipo_gestion'] === 'bobina' && $row['bobina_identificador']): ?>
                                    <div class="bobina-info">
                                        <i class="bi bi-receipt"></i>
                                        <?= htmlspecialchars($row['bobina_identificador']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= strpos(strtolower($row['movement_type_nombre']), 'entrada') !== false ? 'bg-success' : (strpos(strtolower($row['movement_type_nombre']), 'salida') !== false ? 'bg-danger' : 'bg-warning') ?>">
                                <?= htmlspecialchars($row['movement_type_nombre']) ?>
                            </span>
                        </td>
                        <td>
                            <strong class="<?= $row['quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $row['quantity'] > 0 ? '+' : '' ?><?= $row['quantity'] ?>
                                <?php if ($row['tipo_gestion'] === 'bobina'): ?>
                                    m
                                <?php endif; ?>
                            </strong>
                            <?php if ($row['tipo_gestion'] === 'bobina' && $row['bobina_metros_actuales'] !== null): ?>
                                <br><small class="text-muted">Restante: <?= $row['bobina_metros_actuales'] ?>m</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark">
                                <i class="bi bi-person"></i>
                                <?= $row['usuario_nombre'] ? htmlspecialchars($row['usuario_nombre']) : 'Desconocido' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['tecnico_nombre']): ?>
                                <span class="badge bg-primary">
                                    <i class="bi bi-person-badge"></i> <?= htmlspecialchars($row['tecnico_nombre']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted">
                                <i class="bi bi-calendar3"></i>
                                <?= date('d/m/Y H:i', strtotime($row['movement_date'])) ?>
                            </small>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="bi bi-arrow-left-right"></i>
        <h5>No hay movimientos registrados</h5>
        <p>Los movimientos aparecerán aquí cuando registres entradas, salidas o ajustes de inventario.</p>
        <a href="new.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Registrar Primer Movimiento
        </a>
    </div>
<?php endif; ?>
