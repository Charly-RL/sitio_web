// sidebar.js - Sidebar dinámico para panel vendedor
function loadSellerSidebar(activePage) {
    const sidebarHtml = `
    <div class="sidebar-header d-flex justify-content-between align-items-center p-3 border-bottom">
        <span class="fw-bold">Panel Vendedor</span>
        <button class="btn btn-sm btn-close btn-close-white" id="sidebarClose" aria-label="Cerrar"></button>
    </div>
    <ul class="nav flex-column p-3">
        <li class="nav-item mb-2">
            <a class="nav-link text-white${activePage==='ventas' ? ' active' : ''}" href="ventas.html"><i class="bi bi-cash-coin me-2"></i>Ventas hechas</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white${activePage==='inventario' ? ' active' : ''}" href="inventario.html"><i class="bi bi-box-seam me-2"></i>Inventario</a>
        </li>
    </ul>`;
    const sidebar = document.getElementById('sellerSidebar');
    if (sidebar) sidebar.innerHTML = sidebarHtml;
}

document.addEventListener('DOMContentLoaded', function() {
    const page = location.pathname.split('/').pop().replace('.html','');
    loadSellerSidebar(page);
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
});
