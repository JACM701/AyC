<aside class="sidebar" id="sidebar">
    <div class="sidebar-header sidebar-header-compact">
        <div class="sidebar-toggle-container">
            <button id="toggleSidebar" aria-label="Mostrar/ocultar menú">
                <i class="bi bi-list"></i>
            </button>
        </div>
        <div class="sidebar-brand sidebar-brand-compact">
            <div class="brand-logo brand-logo-compact">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="brand-text brand-text-compact">
                <h1>Gestor de Inventarios</h1>
                <p>Alarmas y Cámaras de seguridad del sureste</p>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Sección Principal -->
        <div class="nav-section">
            <div class="nav-section-header">
                <span class="section-title">Principal</span>
            </div>
            <div class="nav-links">
                <a href="../dashboard/index.php" class="sidebar-dashboard" title="Dashboard">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="../inventory/index.php" class="sidebar-inventario" title="Inventario">
                    <i class="bi bi-boxes"></i>
                    <span class="nav-text">Inventario</span>
                    <span class="badge bg-success">8</span>
                </a>
                <a href="../cotizaciones/maqueta.php" class="sidebar-cotizaciones" title="Realizar cotización">
                    <i class="bi bi-file-earmark-text"></i>
                    <span class="nav-text">Realizar cotización</span>
                </a>
            </div>
        </div>

        <!-- Sección Gestión -->
        <div class="nav-section">
            <div class="nav-section-header">
                <span class="section-title">Gestión</span>
            </div>
            <div class="nav-links">
                <a href="../products/list.php" class="sidebar-productos" title="Productos">
                    <i class="bi bi-box-seam-fill"></i>
                    <span class="nav-text">Productos</span>
                </a>
                <a href="../movements/index.php" class="sidebar-movimientos" title="Movimientos">
                    <i class="bi bi-arrow-left-right"></i>
                    <span class="nav-text">Movimientos</span>
                </a>
                <a href="../categories/index.php" class="sidebar-categorias" title="Categorías">
                    <i class="bi bi-tags"></i>
                    <span class="nav-text">Categorías</span>
                </a>
            </div>
        </div>

        <!-- Sección Comercial -->
        <div class="nav-section">
            <div class="nav-section-header">
                <span class="section-title">Comercial</span>
            </div>
            <div class="nav-links">
                <a href="../suppliers/index.php" class="sidebar-proveedores" title="Proveedores">
                    <i class="bi bi-truck"></i>
                    <span class="nav-text">Proveedores</span>
                </a>
                <a href="../clients/index.php" class="sidebar-clientes" title="Clientes">
                    <i class="bi bi-people"></i>
                    <span class="nav-text">Clientes</span>
                </a>
                <a href="../purchase_orders/index.php" class="sidebar-ordenes" title="Órdenes de Compra">
                    <i class="bi bi-cart-check"></i>
                    <span class="nav-text">Órdenes de Compra</span>
                </a>
            </div>
        </div>

        <!-- Sección Almacén -->
        <div class="nav-section">
            <div class="nav-section-header">
                <span class="section-title">Almacén</span>
            </div>
            <div class="nav-links">
                <a href="../warehouses/index.php" class="sidebar-almacenes" title="Almacenes">
                    <i class="bi bi-building"></i>
                    <span class="nav-text">Almacenes</span>
                </a>
                <a href="../locations/index.php" class="sidebar-ubicaciones" title="Ubicaciones">
                    <i class="bi bi-geo-alt"></i>
                    <span class="nav-text">Ubicaciones</span>
                </a>
            </div>
        </div>

        <!-- Sección Reportes -->
        <div class="nav-section">
            <div class="nav-section-header">
                <span class="section-title">Reportes</span>
            </div>
            <div class="nav-links">
                <a href="../reports/index.php" class="sidebar-reportes" title="Reportes">
                    <i class="bi bi-graph-up"></i>
                    <span class="nav-text">Reportes</span>
                </a>
                <a href="../audit/index.php" class="sidebar-auditorias" title="Auditorías">
                    <i class="bi bi-clipboard-check"></i>
                    <span class="nav-text">Auditorías</span>
                </a>
            </div>
        </div>

        <!-- Sección Sistema -->
        <div class="nav-section">
            <div class="nav-section-header">
                <span class="section-title">Sistema</span>
            </div>
            <div class="nav-links">
                <a href="../configuracion/index.php" class="sidebar-configuracion" title="Configuración">
                    <i class="bi bi-gear"></i>
                    <span class="nav-text">Configuración</span>
                </a>
                <a href="../system_config/index.php" class="sidebar-sistema" title="Sistema">
                    <i class="bi bi-sliders"></i>
                    <span class="nav-text">Sistema</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="sidebar-footer mid-compact-footer">
        <div class="mid-compact-user-row">
            <span class="user-avatar mid-compact-avatar"><i class="bi bi-person-circle"></i></span>
            <span class="user-name mid-compact-name">Usuario</span>
            <a href="../auth/logout.php" class="mid-compact-logout" title="Cerrar sesión">
                <i class="bi bi-box-arrow-right"></i>
                <span class="logout-text">Salir</span>
            </a>
        </div>
    </div>
</aside>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    
    if (!sidebar || !toggleBtn) return;
    
    // Cargar estado guardado
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        sidebar.classList.add('collapsed');
    }
    
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
        
        // Forzar reflow para asegurar que las transiciones funcionen
        sidebar.offsetHeight;
    });
    
    // Debug: mostrar en consola el estado del sidebar
    console.log('Sidebar inicial:', sidebar.classList.contains('collapsed') ? 'colapsado' : 'expandido');
});
</script> 