// seller.js - Lógica JS para el panel de vendedor
$(document).ready(function() {
    // Verificar si el usuario está autenticado y es vendedor
    $.get('../api/auth.php?accion=verificar', function(response) {
        if (!response.autenticado) {
            window.location.href = '../auth/login.html';
        } else if (response.tipo !== 'vendedor') {
            // Si no es vendedor, redirigir según el tipo
            if (response.tipo === 'admin') {
                window.location.href = '../admin/index.html';
            } else {
                window.location.href = '../index.html';
            }
        }
    });

    // Cargar productos del vendedor
    // Guardar productos en memoria para acceso seguro
    let productosVendedor = [];
    function loadProducts() {
        $.get('../api/productos.php?mis_productos=1', function(response) {
            const tableBody = $('#productTableBody');
            tableBody.empty();
            productosVendedor = response.productos || [];
            productosVendedor.forEach(product => {
                let badge = '';
                if (product.stock_estado === 'Crítico') badge = '<span class="badge bg-danger">Crítico</span>';
                else if (product.stock_estado === 'Bajo') badge = '<span class="badge bg-warning text-dark">Bajo</span>';
                else if (product.stock_estado === 'Normal') badge = '<span class="badge bg-info text-dark">Normal</span>';
                else badge = '<span class="badge bg-success">Abundante</span>';

                const row = `
                    <tr>
                        <td>${product.id}</td>
                        <td><img src="${product.imagen || ''}" alt="Imagen" style="max-width:50px;"></td>
                        <td>${product.nombre}</td>
                        <td>${product.descripcion || ''}</td>
                        <td>$${product.precio}</td>
                        <td>${product.stock} ${badge}</td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-product-id="${product.id}">
                                <i class="bi bi-envelope"></i> Pedir Cambio
                            </button>
                        </td>
                    </tr>
                `;
                tableBody.append(row);
            });

            // Asignar evento seguro a los botones
            tableBody.find('button[data-product-id]').off('click').on('click', function() {
                const id = $(this).data('product-id');
                abrirPeticionModal(id);
            });
        });
    }

    // El vendedor no puede editar ni eliminar productos, solo puede pedir cambios
    window.abrirPeticionModal = function(id) {
        $('#peticionProductId').val(id);
        $('#peticionMensaje').val('');
        // Si quieres mostrar info del producto en el modal, puedes buscarlo así:
        // const producto = productosVendedor.find(p => p.id == id);
        $('#peticionModal').modal('show');
    }

    // Enviar petición de cambio
    $('#enviarPeticion').click(function() {
        const id = $('#peticionProductId').val();
        const mensaje = $('#peticionMensaje').val();
        if (!mensaje.trim()) {
            alert('Por favor, escribe un mensaje para el administrador.');
            return;
        }
        $.ajax({
            url: '../api/peticiones.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ producto_id: id, mensaje: mensaje }),
            success: function(response) {
                if (response.success) {
                    alert('Petición enviada correctamente');
                    $('#peticionModal').modal('hide');
                } else {
                    alert(response.error || 'Error al enviar la petición');
                }
            },
            error: function() {
                alert('Error al enviar la petición');
            }
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

    // Inicialización
    loadProducts();
});
