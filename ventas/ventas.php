<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: indexLogin.php");
    exit();
}

$rol = $_SESSION['rol'];   
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <title>Nueva Venta | Cafetería</title>
    <link rel="stylesheet" href="../homepage/css/styleshome.css"> 
    <link rel="stylesheet" href="css/ventasStyle.css"> 
</head>
<body>
    <div class="container">
        
        <div class="header">
            <div class="header-left">
                <button class="icon-btn menu-btn" onclick="window.location.href='../indexhome.php'">
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><polyline points="12 19 5 12 12 5"></polyline></svg>
                </button>
             <h1>CAFÉRENCIA - NUEVA VENTA</h1>
            </div>
        </div>

        <div class="pos-layout">
            
            <div class="pos-panel pos-catalogo">
                <h2>Catálogo de Productos</h2>
                
                <div class="catalogo-header">
                    <div class="search-bar">
                        <input type="text" placeholder="Buscar producto por nombre o código..." class="search-input">
                        <button class="search-btn">🔍</button>
                    </div>
                    
                    <div class="category-tabs">
                        <button class="tab active">Bebidas</button>
                        <button class="tab">Comida</button>
                        <button class="tab">Postres</button>
                        <button class="tab">Especiales</button>
                    </div>
                </div>

                <div class="product-grid scrollable-content">
                    </div>
            </div>

            <div class="pos-panel pos-carrito">
                <h2>Detalle de Venta</h2>
                
                <div class="cart-items-list scrollable-content">
                    <div class="cart-header">
                        <span class="cart-col-prod">Producto</span>
                        <span class="cart-col-qty">Cant.</span>
                        <span class="cart-col-total">Total</span>
                    </div>
                    
                    </div>
                
                <div class="cart-summary fixed-footer">
                    <div class="summary-line">
                        <span>Subtotal:</span>
                        <span class="summary-value">$0.00</span>
                    </div>
                    <div class="summary-line">
                        <span>Descuento:</span>
                        <span class="summary-value discount">-$0.00</span>
                    </div>
                    <div class="summary-line summary-total">
                        <span class="total-label">TOTAL:</span>
                        <span class="total-value">$0.00</span>
                    </div>
                </div>
            </div>

            <div class="pos-panel pos-pago">
                <h2>Cierre de Caja</h2>
                
                <div class="payment-options">
                    <p>Método de Pago:</p>
                    <button class="payment-btn" data-method="Efectivo" onclick="openPaymentModal('Efectivo')">💵 Efectivo</button>
                    <button class="payment-btn" data-method="Tarjeta" onclick="openPaymentModal('Tarjeta')">💳 Tarjeta</button>
                    <button class="payment-btn" data-method="Transferencia" onclick="openPaymentModal('Transferencia')">📱 Transferencia</button>
                </div>
            </div>
        </div>
    </div>
    </div> <div id="payment-modal" class="modal-overlay">
        <div class="modal-content">
            <h2 class="modal-title">Finalizar Venta</h2>
            
            <div class="modal-grid">
                
                <div class="modal-payment-side">
                    <h3>Método Seleccionado: <span id="selected-payment-method">Efectivo</span></h3>
                    
                    <div class="cash-tender-area">
                        <p class="tender-label">Efectivo Recibido:</p>
                        <input type="number" id="tender-input-modal" placeholder="Ej: 0.00" class="tender-input">
                        <div class="change-info">
                            <span>Cambio:</span>
                            <span class="change-value" id="change-value-modal">$0.00</span>
                        </div>
                    </div>
                    <div class="action-buttons modal-actions">
                        <button class="btn-main btn-pay" onclick="processPayment()">Pagar e Imprimir Ticket</button>
                        <button class="btn-secondary btn-cancel" onclick="cancelSale()">Cancelar Venta</button>
                        <button class="btn-secondary btn-hold" onclick="holdSale()">Retener Venta</button>
                    </div>
                </div>

                <div class="modal-summary-side">
                    <h3>Detalle de la Orden</h3>
                    
                    <div class="modal-cart-list">
                        </div>
                    
                    <div class="cart-summary modal-summary-footer">
                        <div class="summary-line">
                            <span>Subtotal:</span>
                            <span class="summary-value" id="subtotal-modal">$0.00</span>
                        </div>
                        <div class="summary-line">
                            <span>Descuento:</span>
                            <span class="summary-value discount" id="discount-modal">-$0.00</span>
                        </div>
                        <div class="summary-line summary-total">
                            <span class="total-label">TOTAL:</span>
                            <span class="total-value" id="total-value-modal">$0.00</span>
                        </div>
                    </div>
                </div>

            </div>
            <button class="modal-close-btn" onclick="closeModal()">✖</button>
        </div>
    </div>
    <script>
        // Lógica JS aquí...
        function openPaymentModal(method) {
    const modal = document.getElementById('payment-modal');
    const selectedMethodSpan = document.getElementById('selected-payment-method');
    
    // 1. Mostrar el modal
    modal.style.display = 'flex';
    
    // 2. Actualizar el método seleccionado
    selectedMethodSpan.textContent = method;

    // 3. Ocultar o mostrar el campo "Efectivo Recibido" según el método
    const cashArea = modal.querySelector('.cash-tender-area');
    if (method === 'Efectivo') {
        cashArea.style.display = 'block';
    } else {
        cashArea.style.display = 'none';
        // En un POS real, aquí se abriría la pasarela de pago (tarjeta/transferencia)
    }

    // Nota: Aquí también llamarías a una función para copiar los datos
    // (items, subtotal, total) del carrito principal al modal.
}

function closeModal() {
    const modal = document.getElementById('payment-modal');
    modal.style.display = 'none';
}

function processPayment() {
    alert('¡Venta procesada con éxito!');
    closeModal();
    // Aquí iría la lógica PHP/AJAX para guardar la venta y limpiar el carrito.
}

function cancelSale() {
    if (confirm('¿Desea cancelar esta venta?')) {
        alert('Venta cancelada.');
        closeModal();
        // Lógica para limpiar el carrito.
    }
}

function holdSale() {
    alert('Venta retenida (guardada en espera).');
    closeModal();
    // Lógica para guardar la orden sin finalizarla.
}
    </script>
</body>
</html>