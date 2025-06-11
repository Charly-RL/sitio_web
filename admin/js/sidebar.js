// sidebar.js - Sidebar dinámico para panel admin

// Genera el HTML de la barra lateral y marca la página activa
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
            <a class="nav-link text-white${activePage==='recomendaciones' ? ' active' : ''}" href="recomendaciones.html"><i class="bi bi-stars me-2"></i>Recomendaciones</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white${activePage==='pedidos' ? ' active' : ''}" href="pedidos.html"><i class="bi bi-box me-2"></i>Pedidos</a>
        </li>
    </ul>`;
    const sidebar = document.getElementById('adminSidebar');
    if (sidebar) sidebar.innerHTML = sidebarHtml;
}

document.addEventListener('DOMContentLoaded', function() {
    // Detecta la página activa por el nombre del archivo actual
    const page = location.pathname.split('/').pop().replace('.html','');
    loadSidebar(page);

    // Lógica para mostrar/ocultar la sidebar en móvil y escritorio
    const sidebar = document.getElementById('adminSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    // Abre la sidebar al hacer clic en el botón de menú
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.add('show');
        });
    }
    // Cierra la sidebar al hacer clic en el botón de cerrar
    if (sidebarClose && sidebar) {
        sidebarClose.addEventListener('click', function() {
            sidebar.classList.remove('show');
        });
    }
    // Cierra la sidebar si se hace clic fuera de ella (en móvil/tablet)
    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
});
