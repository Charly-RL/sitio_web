// peticiones.js - Admin: gestión de peticiones de vendedores
$(document).ready(function() {
    // Verificar si el usuario es administrador
    $.get('../api/auth.php?accion=verificar', function(response) {
        if (!response.autenticado || !response.es_admin) {
            window.location.href = '../auth/login.html';
        }
    });

    function cargarPeticiones() {
        $.get('../api/peticiones.php?admin=1', function(response) {
            const tbody = $('#peticionesTableBody');
            tbody.empty();
            if (!response.peticiones || response.peticiones.length === 0) {
                tbody.append('<tr><td colspan="7" class="text-center">No hay peticiones</td></tr>');
                return;
            }
            response.peticiones.forEach(pet => {
                let badge = '';
                if (pet.estado === 'pendiente') badge = '<span class="badge bg-warning text-dark">Pendiente</span>';
                else if (pet.estado === 'atendida') badge = '<span class="badge bg-success">Atendida</span>';
                else badge = '<span class="badge bg-danger">Rechazada</span>';
                let acciones = '';
                if (pet.estado === 'pendiente') {
                    acciones = `
                        <button class="btn btn-sm btn-success atender" data-id="${pet.id}">Atender</button>
                        <button class="btn btn-sm btn-danger rechazar" data-id="${pet.id}">Rechazar</button>
                    `;
                }
                const row = `
                    <tr>
                        <td>${pet.id}</td>
                        <td>${pet.vendedor}</td>
                        <td>${pet.producto}</td>
                        <td>${pet.mensaje}</td>
                        <td>${badge}</td>
                        <td>${pet.fecha}</td>
                        <td>${acciones}</td>
                    </tr>
                `;
                tbody.append(row);
            });
        });
    }

    // Acciones atender/rechazar
    $('#peticionesTableBody').on('click', '.atender', function() {
        const id = $(this).data('id');
        $.ajax({
            url: '../api/peticiones.php',
            type: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ id: id, estado: 'atendida' }),
            success: function() { cargarPeticiones(); }
        });
    });
    $('#peticionesTableBody').on('click', '.rechazar', function() {
        const id = $(this).data('id');
        $.ajax({
            url: '../api/peticiones.php',
            type: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ id: id, estado: 'rechazada' }),
            success: function() { cargarPeticiones(); }
        });
    });

    // Cerrar sesión
    $('#logoutBtn').click(function() {
        $.get('../api/auth.php?accion=logout', function(response) {
            if (response.success) {
                window.location.href = '../auth/login.html';
            }
        });
    });

    cargarPeticiones();
});
