// recomendaciones.js - Lógica para mostrar recomendaciones en el panel admin
$(document).ready(function() {
    // Llama a la función principal para cargar todas las recomendaciones al iniciar la página
    cargarRecomendaciones();

    // Función principal que obtiene los datos y genera las recomendaciones
    function cargarRecomendaciones() {
        // Obtiene productos y productos más vendidos en paralelo
        $.when(
            $.get('../api/productos.php'),
            $.get('../api/pedidos.php?accion=masvendidos')
        ).done(function(productosResp, masVendidosResp) {
            // productosResp y masVendidosResp son arrays [data, status, xhr]
            const productos = productosResp[0].productos || [];
            const masVendidos = masVendidosResp[0].productos || [];

            // --- Stock crítico/bajo ---
            // Separa productos con stock crítico y bajo
            let htmlStock = '';
            const criticos = productos.filter(p => p.stock_estado === 'Crítico');
            const bajos = productos.filter(p => p.stock_estado === 'Bajo');
            // Lista productos críticos
            if (criticos.length > 0) {
                htmlStock += '<h5 class="text-danger"><i class="bi bi-exclamation-triangle"></i> Stock Crítico</h5><ul>';
                criticos.forEach(p => {
                    htmlStock += `<li><b>${p.nombre}</b> (Stock: ${p.stock})</li>`;
                });
                htmlStock += '</ul>';
            }
            // Lista productos bajos
            if (bajos.length > 0) {
                htmlStock += '<h5 class="text-warning mt-3"><i class="bi bi-exclamation-circle"></i> Stock Bajo</h5><ul>';
                bajos.forEach(p => {
                    htmlStock += `<li><b>${p.nombre}</b> (Stock: ${p.stock})</li>`;
                });
                htmlStock += '</ul>';
            }
            // Si no hay ninguno, muestra mensaje
            if (!htmlStock) htmlStock = '<div class="alert alert-success">No hay productos con stock crítico o bajo.</div>';

            // --- Más vendidos (top 10) ---
            let htmlMasVendidos = '';
            if (masVendidos.length > 0) {
                htmlMasVendidos += '<h5 class="text-success"><i class="bi bi-star-fill"></i> Más Vendidos</h5><ul>';
                masVendidos.forEach(p => {
                    htmlMasVendidos += `<li><b>${p.nombre}</b> (Vendidos: ${p.cantidad_vendida})</li>`;
                });
                htmlMasVendidos += '</ul>';
            } else {
                htmlMasVendidos = '<div class="alert alert-info">No hay datos de productos más vendidos.</div>';
            }

            // --- Sugerencia de oferta: los 5 productos con menos ventas (de los que tienen stock abundante o normal) ---
            let htmlOfertas = '';
            // Unimos info de productos y ventas para sugerir ofertas
            let ventasPorId = {};
            masVendidos.forEach(p => { ventasPorId[p.id] = parseInt(p.cantidad_vendida); });
            // Si un producto no aparece en masVendidos, su venta es 0
            let productosOferta = productos
                .filter(p => p.stock_estado === 'Abundante' || p.stock_estado === 'Normal')
                .map(p => ({
                    ...p,
                    vendidos: ventasPorId[p.id] || 0
                }))
                .sort((a, b) => a.vendidos - b.vendidos)
                .slice(0, 5);
            // Lista los productos sugeridos para oferta
            if (productosOferta.length > 0) {
                htmlOfertas += '<h5 class="text-primary"><i class="bi bi-tags"></i> Sugerencia de Oferta</h5><ul>';
                productosOferta.forEach(p => {
                    htmlOfertas += `<li><b>${p.nombre}</b> (Stock: ${p.stock}, Vendidos: ${p.vendidos})</li>`;
                });
                htmlOfertas += '</ul>';
            } else {
                htmlOfertas = '<div class="alert alert-info">No hay productos sugeridos para oferta.</div>';
            }

            // --- Pedir al proveedor (stock crítico) ---
            let htmlPedir = '';
            if (criticos.length > 0) {
                htmlPedir += '<h5 class="text-danger"><i class="bi bi-truck"></i> Pedir al Proveedor</h5><ul>';
                criticos.forEach(p => {
                    htmlPedir += `<li><b>${p.nombre}</b> (Stock: ${p.stock})</li>`;
                });
                htmlPedir += '</ul>';
            } else {
                htmlPedir = '<div class="alert alert-success">No hay productos que requieran pedido urgente.</div>';
            }

            // Inserta el HTML generado en cada panel de recomendaciones
            $('#recomendacionesStock').html(htmlStock);
            $('#recomendacionesOfertas').html(htmlOfertas);
            $('#recomendacionesPedir').html(htmlPedir);
            $('#recomendacionesMasVendidos').html(htmlMasVendidos);
        });
    }
});
