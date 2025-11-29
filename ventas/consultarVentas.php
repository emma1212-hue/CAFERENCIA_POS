<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../indexLogin.php");
    exit();
}

$rol = $_SESSION['rol'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <title>Consultar Ventas | Cafetería</title>
    <link rel="stylesheet" href="../homepage/css/styleshome.css">
    <link rel="stylesheet" href="css/ventasStyle.css">
    <link rel="stylesheet" href="css/consultarVentas.css">
    
    <style>
        /* Contenedor principal de la modal (Overlay/Superposición) */
        .modal {
            display: none; /* Oculto por defecto */
            position: fixed; /* CLAVE: Fija el modal a la ventana */
            z-index: 1000; /* CLAVE: Alto z-index para estar por encima de todo */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5); /* Fondo oscuro semitransparente */
            backdrop-filter: blur(3px); /* Efecto de desenfoque moderno */
            padding-top: 50px;
        }

        /* Contenido de la modal */
        .modal-content {
            background-color: var(--bg);
            margin: 5% auto; /* Centra el modal verticalmente */
            padding: 20px;
            border-radius: 12px;
            width: 90%;
            max-width: 750px; /* Ancho máximo para el contenido */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
            animation-name: animatetop;
            animation-duration: 0.4s;
            position: relative; /* Necesario para el pie de la tabla */
        }
        
        /* Animación de entrada */
        @keyframes animatetop {
            from {top: -100px; opacity: 0} 
            to {top: 0; opacity: 1}
        }

        /* Encabezado del modal (Título y botón de cierre) */
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--brown-muted);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--brown-dark);
        }

        /* Botón de cierre "X" */
        .close-btn {
            color: var(--brown-dark);
            font-size: 28px;
            font-weight: bold;
            background: transparent;
            border: none;
            cursor: pointer;
            line-height: 1;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: #000;
            text-decoration: none;
        }

        /* Estilos de tabla dentro del modal (Detalle de la venta) */
        .detail-wrap {
            width: 100%;
            overflow-x: auto; /* Permite scroll horizontal si la tabla es muy ancha en móvil */
        }
        
        .detail-table {
            width: 100%;
            min-width: 600px; /* Asegura que la tabla no sea demasiado estrecha en móvil */
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 0.9em;
        }

        .detail-table th, .detail-table td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-table thead th {
            background-color: var(--accent);
            color: var(--brown-dark);
            font-weight: 600;
        }

        .detail-table tfoot td {
            border-top: 2px solid var(--brown-muted);
            font-weight: bold;
            font-size: 1em;
        }

        .amount {
            text-align: right;
            font-weight: 700;
            color: var(--brown-dark);
        }
        
        .modal-body p {
            font-size: 0.9em;
            color: var(--brown-mid);
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <button class="icon-btn menu-btn" onclick="window.location.href='../indexhome.php'">←</button>
            <div>
                <h1>
                    <svg class="title-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                        <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></circle>
                        <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    Consultar Ventas
                </h1>
                <p class="subtitle">Este es un apartado para consultar las ventas realizadas</p>
            </div>
        </div>

        <div class="controls">
            <div class="filters-panel" role="region" aria-label="Opciones de filtrado">
                <input id="q" class="search-input" type="text" placeholder="Buscar por idVenta o nombre de usuario" aria-label="Buscar por idVenta o nombre de usuario">

                <label>
                    Desde: <input id="fromDate" class="filter-input" type="date">
                </label>
                <label>
                    Hasta: <input id="toDate" class="filter-input" type="date">
                </label>
                <select id="tipoPagoFilter" class="filter-input">
                    <option value="">Todos los métodos</option>
                    <option value="Efectivo">Efectivo</option>
                    <option value="Transferencia">Transferencia</option>
                </select>
                <div class="filters-actions">
                    <button class="btn" onclick="applyFilters()" aria-label="Buscar ventas">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="btn-icon" aria-hidden="true" focusable="false">
                            <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"></circle>
                            <path d="M21 21l-4.5-4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        Buscar
                    </button>
                    <button class="btn btn-secondary" onclick="resetFilters()" aria-label="Limpiar filtros">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="btn-icon" aria-hidden="true" focusable="false">
                            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"></path>
                        </svg>
                        Limpiar
                    </button>
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table class="sales-table" id="salesTable">
                <thead>
                    <tr>
                        <th>idVenta</th>
                        <th>fechaVenta</th>
                        <th>totalVenta</th>
                        <th>tipoPago</th>
                        <th>idUsuario</th>
                        <th>idCorte</th>
                    </tr>
                </thead>
                <tbody id="salesBody">
                    </tbody>
            </table>
        </div>

        <div class="cards" id="cardsContainer">
            </div>

    </div>
    
    <div id="saleDetailModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Detalle de Venta <span id="modal-idVenta">#</span></h2>
                <button class="close-btn" onclick="closeModal()" aria-label="Cerrar ventana de detalle">&times;</button>
            </div>
            
            <div class="modal-body">
                <p>Información General: **<span id="modal-info-general"></span>**</p>

                <div class="detail-wrap">
                    <table class="detail-table">
                        <thead>
                            <tr>
                                <th>ID Detalle</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Descuento</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody id="detailBody">
                            </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align: right;">Subtotal (antes desc.):</td>
                                <td></td> <td id="modal-subtotal" class="amount"></td>
                            </tr>
                            <tr>
                                <td colspan="4" style="text-align: right;">Descuento Total:</td>
                                <td></td>
                                <td id="modal-descuento" class="amount"></td>
                            </tr>
                            <tr>
                                <td colspan="4" style="text-align: right; font-weight: bold;">TOTAL:</td>
                                <td></td>
                                <td id="modal-total" class="amount total"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // 1. Array de Ventas Originales (Ahora con detalles anidados)
    const allSalesData = [
        { 
            idVenta: '1001', fechaVenta: '2025-11-10', totalVenta: '$45.50', tipoPago: 'Efectivo', idUsuario: '12', nombreUsuario: 'María López', idCorte: '3',
            // --- DETALLES INVENTADOS ---
            details: [
                { idDetalle: '1', idProducto: 'P01', producto: 'Latte Vainilla', cantidad: 2, precioUnitario: 15.00, descuento: 0.00, observaciones: 'Leche de almendra' },
                { idDetalle: '2', idProducto: 'P05', producto: 'Tarta de Zanahoria', cantidad: 1, precioUnitario: 15.50, descuento: 0.00, observaciones: '' }
            ]
        },
        { 
            idVenta: '1002', fechaVenta: '2025-11-12', totalVenta: '$23.00', tipoPago: 'Transferencia', idUsuario: '8', nombreUsuario: 'Carlos Ruiz', idCorte: '3',
            details: [
                { idDetalle: '3', idProducto: 'P02', producto: 'Espresso Doble', cantidad: 1, precioUnitario: 5.00, descuento: 0.00, observaciones: '' },
                { idDetalle: '4', idProducto: 'P03', producto: 'Muffin de Chocolate', cantidad: 2, precioUnitario: 9.00, descuento: 5.00, observaciones: 'Promo 2x1' }
            ]
        },
        { 
            idVenta: '1003', fechaVenta: '2025-11-13', totalVenta: '$12.75', tipoPago: 'Transferencia', idUsuario: '5', nombreUsuario: 'Ana Gómez', idCorte: '4',
            details: [
                { idDetalle: '5', idProducto: 'P04', producto: 'Té Chai Helado', cantidad: 1, precioUnitario: 12.75, descuento: 0.00, observaciones: '' }
            ]
        },
        { 
            idVenta: '1004', fechaVenta: '2025-11-14', totalVenta: '$60.00', tipoPago: 'Efectivo', idUsuario: '3', nombreUsuario: 'Luis Pérez', idCorte: '4',
            details: [
                { idDetalle: '6', idProducto: 'P01', producto: 'Latte Vainilla', cantidad: 4, precioUnitario: 15.00, descuento: 0.00, observaciones: '4 vasos' }
            ]
        },
        { 
            idVenta: '1005', fechaVenta: '2025-11-15', totalVenta: '$8.50', tipoPago: 'Transferencia', idUsuario: '7', nombreUsuario: 'Sofía Díaz', idCorte: '5',
            details: [
                { idDetalle: '7', idProducto: 'P06', producto: 'Agua Embotellada', cantidad: 1, precioUnitario: 8.50, descuento: 0.00, observaciones: '' }
            ]
        },
    ];

    // Contenedores del DOM
    const salesBody = document.getElementById('salesBody');
    const cardsContainer = document.getElementById('cardsContainer');
    const saleDetailModal = document.getElementById('saleDetailModal');
    const detailBody = document.getElementById('detailBody');

    /**
     * 2. Muestra la ventana modal con los detalles de una venta específica.
     * @param {string} idVenta - El ID de la venta a buscar y mostrar.
     */
    function showSaleDetails(idVenta) {
        // Buscar la venta en el array de datos
        const sale = allSalesData.find(s => s.idVenta === idVenta);

        if (!sale) {
            alert('Detalle de venta no encontrado.');
            return;
        }

        // 1. Llenar información general
        document.getElementById('modal-idVenta').textContent = `#${sale.idVenta}`;
        document.getElementById('modal-info-general').textContent = 
            `${sale.fechaVenta} | Usuario: ${sale.nombreUsuario} | Pago: ${sale.tipoPago}`;
        
        // 2. Llenar la tabla de detalles
        detailBody.innerHTML = '';
        let subtotalBruto = 0; // Antes del descuento
        let descuentoTotal = 0;

        sale.details.forEach(detail => {
            const row = detailBody.insertRow();
            const precioLineaBruto = (detail.cantidad * detail.precioUnitario);
            subtotalBruto += precioLineaBruto;
            descuentoTotal += detail.descuento;
            
            row.insertCell().textContent = detail.idDetalle;
            row.insertCell().textContent = detail.producto;
            row.insertCell().textContent = detail.cantidad;
            row.insertCell().textContent = `$${detail.precioUnitario.toFixed(2)}`;
            row.insertCell().textContent = `$${detail.descuento.toFixed(2)}`;
            row.insertCell().textContent = detail.observaciones;
        });
        
        // 3. Llenar el pie de página (Totales)
        const subtotalNeto = subtotalBruto - descuentoTotal;
        document.getElementById('modal-subtotal').textContent = `$${subtotalBruto.toFixed(2)}`;
        document.getElementById('modal-descuento').textContent = `$${descuentoTotal.toFixed(2)}`;
        document.getElementById('modal-total').textContent = `$${subtotalNeto.toFixed(2)}`;

        // 4. Mostrar el modal
        saleDetailModal.style.display = 'block';
    }

    /**
     * Cierra la ventana modal.
     */
    function closeModal() {
        saleDetailModal.style.display = 'none';
    }

    /**
     * 3. Renderiza la tabla y las tarjetas con los datos filtrados, y añade el evento de clic.
     * @param {Array<Object>} data - El array de objetos de ventas a mostrar.
     */
    function renderSales(data) {
        salesBody.innerHTML = ''; // Limpia el cuerpo de la tabla

        data.forEach(sale => {
            const row = salesBody.insertRow();
            
            // Añadir evento de clic para mostrar el detalle
            row.onclick = () => showSaleDetails(sale.idVenta);
            row.style.cursor = 'pointer'; // Indicador visual de clic
            row.setAttribute('role', 'button'); // Para accesibilidad

            // Agregar atributos para el filtrado
            row.setAttribute('data-fecha', sale.fechaVenta);
            row.setAttribute('data-usuario', sale.nombreUsuario);

            row.insertCell().textContent = sale.idVenta;
            row.insertCell().textContent = sale.fechaVenta;
            row.insertCell().textContent = sale.totalVenta;
            row.insertCell().textContent = sale.tipoPago;
            row.insertCell().textContent = sale.idUsuario;
            row.insertCell().textContent = sale.idCorte;
        });

        // --- Renderizar Tarjetas ---
        cardsContainer.innerHTML = ''; 

        data.forEach(sale => {
            const card = document.createElement('div');
            card.className = 'sale-card';
            
            // Añadir evento de clic para mostrar el detalle
            card.onclick = () => showSaleDetails(sale.idVenta);
            card.setAttribute('role', 'button'); // Para accesibilidad
            
            const displayUsuario = sale.nombreUsuario || sale.idUsuario; 

            card.innerHTML = `
                <div class="card-row"><div class="card-label">idVenta</div><div>${sale.idVenta}</div></div>
                <div class="card-row"><div class="card-label">fecha</div><div>${sale.fechaVenta}</div></div>
                <div class="card-row"><div class="card-label">total</div><div>${sale.totalVenta}</div></div>
                <div class="card-row"><div class="card-label">tipoPago</div><div>${sale.tipoPago}</div></div>
                <div class="card-row"><div class="card-label">usuario</div><div>${displayUsuario}</div></div>
                <div class="card-row"><div class="card-label">idUsuario</div><div>${sale.idUsuario}</div></div>
                <div class="card-row"><div class="card-label">idCorte</div><div>${sale.idCorte}</div></div>
            `;
            cardsContainer.appendChild(card);
        });
    }


    /**
     * 4. Aplica los filtros a los datos de prueba y llama a renderSales().
     */
    function applyFilters() {
        const q = document.getElementById('q').value.toLowerCase();
        const from = document.getElementById('fromDate').value;
        const to = document.getElementById('toDate').value;
        const tipo = document.getElementById('tipoPagoFilter').value;

        const filteredSales = allSalesData.filter(sale => {
            let visible = true;
            
            if (q) {
                const id = sale.idVenta.toLowerCase();
                const usuarioNombre = sale.nombreUsuario.toLowerCase();
                const hay = id.includes(q) || usuarioNombre.includes(q);
                if (!hay) visible = false;
            }

            if (tipo) {
                if (tipo !== sale.tipoPago) visible = false;
            }

            if (from && sale.fechaVenta < from) visible = false;
            if (to && sale.fechaVenta > to) visible = false;

            return visible;
        });

        renderSales(filteredSales);
    }

    /**
     * 5. Limpia los campos de filtro y restablece la vista.
     */
    function resetFilters() {
        document.getElementById('q').value = '';
        document.getElementById('fromDate').value = '';
        document.getElementById('toDate').value = '';
        document.getElementById('tipoPagoFilter').value = '';
        applyFilters(); 
    }
    
    // 6. Cierra el modal si el usuario hace clic fuera de la ventana.
    window.onclick = function(event) {
        if (event.target == saleDetailModal) {
            closeModal();
        }
    }

    // Inicializar: Llama a applyFilters al cargar la página.
    document.addEventListener('DOMContentLoaded', () => {
        applyFilters();
    });
</script>
</body>
</html>