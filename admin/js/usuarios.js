// usuarios.js - Lógica para mostrar y editar usuarios
$(document).ready(function() {
    // Verificar si el usuario es administrador
    $.get('../api/auth.php?accion=verificar', function(response) {
        if (!response.autenticado || !response.es_admin) {
            window.location.href = '../auth/login.html';
        }
    });

    // Cargar usuarios
    function loadUsuarios() {
        $.get('../api/usuarios.php', function(response) {
            const tableBody = $('#usuariosTableBody');
            tableBody.empty();
            response.usuarios.forEach(usuario => {
                const row = `
                    <tr>
                        <td>${usuario.id}</td>
                        <td>${usuario.nombre}</td>
                        <td>${usuario.email}</td>
                        <td>${usuario.tipo}</td>
                        <td>${usuario.fecha_registro}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editUsuario(${usuario.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger ms-1" onclick="deleteUsuario(${usuario.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tableBody.append(row);
            });
    // Eliminar usuario
    window.deleteUsuario = function(id) {
        if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) {
            $.ajax({
                url: `../api/usuarios.php?id=${id}`,
                type: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        alert('Usuario eliminado correctamente');
                        loadUsuarios();
                    } else {
                        alert(response.error || 'Error al eliminar el usuario');
                    }
                },
                error: function() {
                    alert('Error al eliminar el usuario');
                }
            });
        }
    }
        });
    }

    // Editar usuario
    window.editUsuario = function(id) {
        $.get(`../api/usuarios.php?id=${id}`, function(response) {
            const usuario = response.usuarios[0];
            $('#editUsuarioId').val(usuario.id);
            $('#editNombre').val(usuario.nombre);
            $('#editEmail').val(usuario.email);
            $('#editTipo').val(usuario.tipo);
            // Mostrar el modal correctamente con Bootstrap 5
            const modal = new bootstrap.Modal(document.getElementById('editUsuarioModal'), {backdrop: false});
            modal.show();
        });
    }

    // Guardar cambios de edición
    $('#saveEditUsuario').click(function() {
        const id = $('#editUsuarioId').val();
        const usuarioData = {
            nombre: $('#editNombre').val(),
            email: $('#editEmail').val(),
            tipo: $('#editTipo').val()
        };
        $.ajax({
            url: `../api/usuarios.php?id=${id}`,
            type: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify(usuarioData),
            success: function(response) {
                if (response.success) {
                    alert('Usuario actualizado correctamente');
                    $('#editUsuarioModal').modal('hide');
                    loadUsuarios();
                } else {
                    alert(response.error || 'Error al guardar el usuario');
                }
            },
            error: function() {
                alert('Error al guardar el usuario');
            }
        });
    });

    // Inicialización
    loadUsuarios();
});
