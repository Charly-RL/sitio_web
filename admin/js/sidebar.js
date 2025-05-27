// sidebar.js - Sidebar dinámico para panel admin
function loadSidebar(activePage) {
    const sidebarHtml = `
    <div class="sidebar-header d-flex justify-content-between align-items-center p-3 border-bottom">
        <span class="fw-bold">Administración</span>
        <button class="btn btn-sm btn-close btn-close-white" id="sidebarClose" aria-label="Cerrar"></button>
    </div>
    <ul class="nav flex-column p-3">
        <li class="nav-item mb-2">
            <a class="nav-link text-white${activePage==='index' ? ' active' : ''}" href="index.html"><i class="bi bi-box-seam me-2"></i>Productos</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white${activePage==='usuarios' ? ' active' : ''}" href="usuarios.html"><i class="bi bi-people me-2"></i>Usuarios</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white${activePage==='reportes' ? ' active' : ''}" href="reportes.html"><i class="bi bi-bar-chart me-2"></i>Reportes</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white${activePage==='estadisticas' ? ' active' : ''}" href="estadisticas.html"><i class="bi bi-graph-up-arrow me-2"></i>Estadísticas</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white${activePage==='configuracion' ? ' active' : ''}" href="configuracion.html"><i class="bi bi-gear me-2"></i>Configuración</a>
        </li>
    </ul>`;
    const sidebar = document.getElementById('adminSidebar');
    if (sidebar) sidebar.innerHTML = sidebarHtml;
}

document.addEventListener('DOMContentLoaded', function() {
    // Detectar página activa por nombre de archivo
    const page = location.pathname.split('/').pop().replace('.html','');
    loadSidebar(page);
    // Sidebar toggle logic
    const sidebar = document.getElementById('adminSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.add('show');
        });
    }
    if (sidebarClose && sidebar) {
        sidebarClose.addEventListener('click', function() {
            sidebar.classList.remove('show');
        });
    }
    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
});
