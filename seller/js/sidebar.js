// sidebar.js - Sidebar dinámico para panel vendedor
function loadSidebarSeller(activePage) {
    const sidebarHtml = `
    <div class="sidebar-header d-flex justify-content-between align-items-center p-3 border-bottom">
        <span class="fw-bold">Vendedor</span>
        <button class="btn btn-sm btn-close btn-close-white" id="sidebarClose" aria-label="Cerrar"></button>
    </div>
    <ul class="nav flex-column p-3">
        <li class="nav-item mb-2">
            <a class="nav-link text-white${activePage==='index' ? ' active' : ''}" href="index.html"><i class="bi bi-box-seam me-2"></i>Mis Productos</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white${activePage==='estadisticas' ? ' active' : ''}" href="estadisticas.html"><i class="bi bi-bar-chart me-2"></i>Estadísticas</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white${activePage==='reportes' ? ' active' : ''}" href="reportes.html"><i class="bi bi-flag me-2"></i>Reportes</a>
        </li>
    </ul>`;
    const sidebar = document.getElementById('sellerSidebar');
    if (sidebar) sidebar.innerHTML = sidebarHtml;
}

document.addEventListener('DOMContentLoaded', function() {
    // Detectar página activa por nombre de archivo
    const page = location.pathname.split('/').pop().replace('.html','');
    loadSidebarSeller(page);
    // Sidebar toggle logic
    const sidebar = document.getElementById('sellerSidebar');
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
