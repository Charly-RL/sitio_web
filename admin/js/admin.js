// admin.js - Lógica JS separada del index.html para el panel de administración
$(document).ready(function() {

    // Verifica si el usuario es administrador antes de permitir el acceso al panel
    $.get('../api/auth.php?accion=verificar', function(response) {
        if (!response.autenticado || !response.es_admin) {
            window.location.href = '../auth/login.html';
        }
    });

    // Carga la lista de productos y los muestra en la tabla
    function loadProducts() {
        $.get('../api/productos.php', function(response) {
            const tableBody = $('#productTableBody');
            tableBody.empty();

            // Por cada producto recibido, crea una fila en la tabla
            response.productos.forEach(product => {
                let badge = '';
                // Muestra un badge según el estado del stock
                if (product.stock_estado === 'Crítico') badge = '<span class="badge bg-danger">Crítico</span>';
                else if (product.stock_estado === 'Bajo') badge = '<span class="badge bg-warning text-dark">Bajo</span>';
                else if (product.stock_estado === 'Normal') badge = '<span class="badge bg-info text-dark">Normal</span>';
                else badge = '<span class="badge bg-success">Abundante</span>';

                // Estructura de la fila de la tabla
                const row = `
                    <tr>
                        <td>${product.id}</td>
                        <td>${product.nombre}</td>
                        <td>${product.descripcion || ''}</td>
                        <td>$${product.precio}</td>
                        <td>${product.stock} ${badge}</td>
                        <td>
                            <button class="btn btn-sm btn-primary editar" onclick="editProduct(${product.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tableBody.append(row);
            });
        });
    }


    // Guarda un nuevo producto usando los datos del formulario
    $('#saveNewProduct').click(function() {
        const productData = {
            nombre: $('#newNombre').val(),
            descripcion: $('#newDescripcion').val(),
            precio: parseFloat($('#newPrecio').val()),
            stock: parseInt($('#newStock').val()),
            imagen: $('#newImagen').val()
        };
        // Envía los datos al backend para crear el producto
        $.ajax({
            url: '../api/productos.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(productData),
            success: function(response) {
                if (response.success) {
                    alert('Producto creado correctamente');
                    $('#newProductModal').modal('hide');
                    loadProducts();
                    $('#newProductForm')[0].reset();
                } else {
                    alert(response.error || 'Error al guardar el producto');
                }
            },
            error: function() {
                alert('Error al guardar el producto');
            }
        });
    });


    // Abre el modal de edición y carga los datos del producto seleccionado
    window.editProduct = function(id) {
        $.get(`../api/productos.php?id=${id}`, function(response) {
            const product = response.productos[0];
            // Llena el formulario de edición con los datos del producto
            $('#editProductId').val(product.id);
            $('#editNombre').val(product.nombre);
            $('#editDescripcion').val(product.descripcion);
            $('#editPrecio').val(product.precio);
            $('#editStock').val(product.stock);
            $('#editImagen').val(product.imagen);
            // Muestra el modal de edición
            $('#editProductModal').modal('show');
        });
    }


    // Guarda los cambios realizados en la edición de un producto
    $('#saveEditProduct').click(function() {
        const id = $('#editProductId').val();
        const productData = {
            nombre: $('#editNombre').val(),
            descripcion: $('#editDescripcion').val(),
            precio: parseFloat($('#editPrecio').val()),
            stock: parseInt($('#editStock').val()),
            imagen: $('#editImagen').val()
        };
        // Envía los datos modificados al backend para actualizar el producto
        $.ajax({
            url: `../api/productos.php?id=${id}`,
            type: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify(productData),
            success: function(response) {
                if (response.success) {
                    alert('Producto actualizado correctamente');
                    $('#editProductModal').modal('hide');
                    loadProducts();
                } else {
                    alert(response.error || 'Error al guardar el producto');
                }
            },
            error: function() {
                alert('Error al guardar el producto');
            }
        });
    });


    // Elimina un producto después de confirmar con el usuario
    window.deleteProduct = function(id) {
        if (confirm('¿Está seguro de que desea eliminar este producto?')) {
            // Solicita al backend eliminar el producto por su ID
            $.ajax({
                url: `../api/productos.php?id=${id}`,
                type: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        alert('Producto eliminado correctamente');
                        loadProducts();
                    } else {
                        alert(response.error || 'Error al eliminar el producto');
                    }
                },
                error: function(xhr) {
                    console.error('Error en la petición:', xhr.responseText);
                    alert('Error al eliminar el producto. Por favor, intente nuevamente.');
                }
            });
        }
    }


    // Cierra la sesión del usuario administrador
    $('#logoutBtn').click(function() {
        $.get('../api/auth.php?accion=logout', function(response) {
            if (response.success) {
                window.location.href = '../auth/login.html';
            }
        });
    });

    // Inicializa la carga de productos al abrir la página
    loadProducts();
});
