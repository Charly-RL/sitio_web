// admin.js - Lógica JS separada del index.html para el panel de administración
$(document).ready(function() {
    // Verificar si el usuario es administrador
    $.get('../api/auth.php?accion=verificar', function(response) {
        if (!response.autenticado || !response.es_admin) {
            window.location.href = '../auth/login.html';
        }
    });

    // Cargar productos
    function loadProducts() {
        $.get('../api/productos.php', function(response) {
            const tableBody = $('#productTableBody');
            tableBody.empty();

            response.productos.forEach(product => {
                let badge = '';
                if (product.stock_estado === 'Crítico') badge = '<span class="badge bg-danger">Crítico</span>';
                else if (product.stock_estado === 'Bajo') badge = '<span class="badge bg-warning text-dark">Bajo</span>';
                else if (product.stock_estado === 'Normal') badge = '<span class="badge bg-info text-dark">Normal</span>';
                else badge = '<span class="badge bg-success">Abundante</span>';

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

    // Guardar nuevo producto
    $('#saveNewProduct').click(function() {
        const productData = {
            nombre: $('#newNombre').val(),
            descripcion: $('#newDescripcion').val(),
            precio: parseFloat($('#newPrecio').val()),
            stock: parseInt($('#newStock').val()),
            imagen: $('#newImagen').val()
        };
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

    // Editar producto
    window.editProduct = function(id) {
        $.get(`../api/productos.php?id=${id}`, function(response) {
            const product = response.productos[0];
            $('#editProductId').val(product.id);
            $('#editNombre').val(product.nombre);
            $('#editDescripcion').val(product.descripcion);
            $('#editPrecio').val(product.precio);
            $('#editStock').val(product.stock);
            $('#editImagen').val(product.imagen);
            $('#editProductModal').modal('show');
        });
    }

    // Guardar cambios de edición
    $('#saveEditProduct').click(function() {
        const id = $('#editProductId').val();
        const productData = {
            nombre: $('#editNombre').val(),
            descripcion: $('#editDescripcion').val(),
            precio: parseFloat($('#editPrecio').val()),
            stock: parseInt($('#editStock').val()),
            imagen: $('#editImagen').val()
        };
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

    // Eliminar producto
    window.deleteProduct = function(id) {
        if (confirm('¿Está seguro de que desea eliminar este producto?')) {
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
