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
                    <button class="payment-btn active">💵 Efectivo</button>
                    <button class="payment-btn">💳 Tarjeta</button>
                    <button class="payment-btn">📱 Transferencia</button>
                </div>

                <div class="cash-tender-area">
                    <p class="tender-label">Efectivo Recibido:</p>
                    <input type="number" placeholder="Ej: 0.00" class="tender-input">
                    <div class="change-info">
                        <span>Cambio:</span>
                        <span class="change-value">$0.00</span>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn-main btn-pay">Pagar e Imprimir Ticket</button>
                    <button class="btn-secondary btn-cancel">Cancelar Venta</button>
                    <button class="btn-secondary btn-hold">Retener Venta</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Lógica JS aquí...
    </script>
</body>
</html>