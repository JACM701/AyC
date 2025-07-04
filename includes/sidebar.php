<aside class="sidebar" id="sidebar">
    <div class="sidebar-toggle-container">
        <button id="toggleSidebar" aria-label="Mostrar/ocultar menú">
            <i class="bi bi-list"></i>
        </button>
    </div>
    <h1>Gestor de inventarios<br>Alarmas y Cámaras de seguridad del sureste</h1>
    <nav>
        <a href="../dashboard/index.php" class="sidebar-dashboard" title="Dashboard">
            <i class="bi bi-grid-3x3-gap-fill"></i>
            <span class="nav-text">Dashboard</span>
        </a>
        <a href="../products/list.php" class="sidebar-productos" title="Productos">
            <i class="bi bi-box-seam-fill"></i>
            <span class="nav-text">Productos</span>
        </a>
        <a href="../movements/index.php" class="sidebar-movimientos" title="Movimientos">
            <i class="bi bi-arrow-left-right"></i>
            <span class="nav-text">Movimientos</span>
        </a>
        <a href="../configuracion/index.php" class="sidebar-configuracion" title="Configuración">
            <i class="bi bi-gear"></i>
            <span class="nav-text">Configuración</span>
        </a>
    </nav>
    <a href="../auth/logout.php" class="logout" title="Cerrar sesión">
        <i class="bi bi-box-arrow-right"></i>
        <span class="nav-text">Cerrar sesión</span>
    </a>
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
    });
});
</script> 