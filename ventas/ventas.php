<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: indexLogin.php");
    exit();
}

// RUTA CORREGIDA: Subir un nivel para llegar a la raíz donde está conexion.php
require_once('../conexion.php'); 

$rol = $_SESSION['rol'];   

$productos = [];
// Consulta SQL ajustada para usar los campos de tu tabla
$sql = "SELECT idProducto, nombre, precioVenta FROM productos ORDER BY nombre ASC";
$resultado = $conn->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $productos[] = $fila;
    }
} else if (!$resultado) {
    die("Error en la consulta SQL: " . $conn->error);
}

// ----------------------------------------------------
// *** LÓGICA DE EXTRACCIÓN DEL NOMBRE BASE (CLAVE) ***
// ----------------------------------------------------
$sufijos_a_remover = [' Chico', ' Grande', ' Pequeño', ' Mediano', 'Ch','G','Med']; 
$productos_unicos = [];
foreach ($productos as &$producto) {
    $nombre_base = $producto['nombre'];
    
    foreach ($sufijos_a_remover as $sufijo) {
        // Comparación case-insensitive del sufijo
        if (stripos($nombre_base, $sufijo) !== false && 
            substr(strtolower($nombre_base), -strlen($sufijo)) === strtolower($sufijo)) {
            $nombre_base = substr($nombre_base, 0, -strlen($sufijo));
            break; 
        }
    }
    $producto['nombre_base'] = trim($nombre_base);
    if (!isset($productos_unicos[$nombre_base])) {
        $productos_unicos[$nombre_base] = $producto;
    }
}
unset($producto);
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
                        <button class="tab active">Bebidas Calientes</button>
                        <button class="tab">Bebidas Frías</button>
                        <button class="tab">Frappés</button>
                        <button class="tab">Especialidades</button>
                        <button class="tab">Desayunos</button>
                    </div>
                </div>

                <div class="product-grid scrollable-content">
                    <?php 
        
        if (count($productos_unicos) > 0): 
            foreach ($productos_unicos as $producto): 
    ?>
                            
            <div class="product-card clickable" 
                data-product-id="<?php echo htmlspecialchars($producto['idProducto']); ?>"
                data-product-name-full="<?php echo htmlspecialchars($producto['nombre']); ?>"
                data-base-price="<?php echo htmlspecialchars($producto['precioVenta']); ?>"
                
                onclick="openProductModal(
                    '<?php echo htmlspecialchars($producto['idProducto']); ?>',
                    '<?php echo htmlspecialchars($producto['nombre_base']); ?>', // Pasa el nombre base
                    <?php echo htmlspecialchars($producto['precioVenta']); ?> // Pasa el precio de la variante mostrada
                )">
                
                <p class="product-name"><?php echo htmlspecialchars($producto['nombre_base']); ?></p>
                <p class="product-price">$<?php echo number_format($producto['precioVenta'], 2); ?></p>
            </div>

        <?php 
            endforeach; 
        else: 
        ?>
            <p style="text-align: center; color: var(--text-dark); padding: 20px;">No hay productos disponibles en el catálogo.</p>
        <?php endif; ?>
                </div>
            </div>

            <div class="pos-panel pos-carrito">
                <h2>Detalle de Venta</h2>
                
                <div class="cart-items-list scrollable-content">
                    <div class="cart-header">
                        <span class="cart-col-prod">Producto</span>
                        <span class="cart-col-qty">Cant.</span>
                        <span class="cart-col-desc">Desc.</span>
                        <span class="cart-col-total">Total</span>
                    </div>
                    
                    </div>
                
                <div class="cart-summary fixed-footer">
                    <div class="summary-line">
                        <span>Subtotal:</span>
                        <span class="summary-value" id="cart-subtotal">$0.00</span>
                    </div>
                    <div class="summary-line">
                        <span>Descuentos:</span>
                        <span class="summary-value discount" id="cart-discount">-$0.00</span>
                    </div>
                    <div class="summary-line summary-total">
                        <span class="total-label">TOTAL:</span>
                        <span class="total-value" id="cart-total">$0.00</span>
                    </div>
                </div>
            </div>

            <div class="pos-panel pos-pago">
                <h2>Finalizar</h2>
                
                <div class="payment-options">
                    <p>Método de Pago:</p>
                    <button class="payment-btn" data-method="Efectivo" onclick="openPaymentModal('Efectivo')">💵 Efectivo</button>
                    <button class="payment-btn" data-method="Transferencia" onclick="openPaymentModal('Transferencia')">📱 Transferencia</button>
                    </div>
            </div>
        </div>
    </div>
    
    <div id="payment-modal" class="modal-overlay">
        <div class="modal-content">
            <h2 class="modal-title">Finalizar Venta</h2>
            
            <div class="modal-grid">
                
                <div class="modal-summary-side">
                    <h3>Detalle de la Orden</h3>
                    
                    <div class="modal-cart-list">
                        <div id="modal-cart-items-list"></div>
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

            </div>
            <button class="modal-close-btn" onclick="closeModal()">✖</button>
        </div>
    </div>
    
    <div id="product-modal" class="modal-overlay">
        <div class="modal-content product-modal-content">
            <h2 class="modal-title">Personalizar Producto: <span id="modal-product-name">Producto Nombre</span></h2>
            
            <div class="modal-grid product-grid-layout">
                
                <div class="modal-options-side">
                    <h3>Opciones Básicas</h3>

                    <div class="modifier-group" id="group-size">
                        <h4>Tamaño:</h4>
                        </div>

                    <div class="modifier-group" id="group-milk">
                        <h4>Tipo de Leche:</h4>
                        <button class="mod-option active" data-mod-name="Milk" data-mod-value="Whole" data-price-adjust="0.00">Entera</button>
                        <button class="mod-option" data-mod-name="Milk" data-mod-value="Skim" data-price-adjust="0.00">Descremada</button>
                        <button class="mod-option" data-mod-name="Milk" data-mod-value="Almond" data-price-adjust="8">Almendra (+ $8.00)</button>
                    </div>
                    
                    <div class="modifier-group">
                        <h4>Descuento Individual:</h4>
                        <input type="number" id="modal-discount-input" min="0" max="100" placeholder="%" class="input-discount">
                        <p class="discount-value-display">Descuento aplicado: $0.00</p>
                    </div>

                </div>

                <div class="modal-summary-side product-summary-side">
                    <h3>Resumen del Pedido</h3>
                    
                    <div class="summary-box">
                        <p>Precio Base: <span id="modal-base-price">$0.00</span></p>
                        <p>Ajuste Modificadores: <span id="modal-modifier-adjust">$0.00</span></p>
                        <p>Descuento Aplicado: <span id="modal-discount-applied">$0.00</span></p>
                        <hr>
                        <h3 class="total-modal-price">Precio Final: <span id="modal-final-price">$0.00</span></h3>
                    </div>

                    <div class="quantity-controls">
                        <h4>Cantidad:</h4>
                        <div class="qty-btn-group">
                            <button class="qty-btn" onclick="updateModalQuantity(-1)">-</button>
                            <input type="number" id="modal-quantity-input" value="1" min="1" readonly>
                            <button class="qty-btn" onclick="updateModalQuantity(1)">+</button>
                        </div>
                    </div>
                    
                    <button class="btn-main btn-add-to-cart" onclick="addToCart()">Añadir al Carrito</button>
                </div>

            </div>
            <button class="modal-close-btn" onclick="closeProductModal()">✖</button>
        </div>
    </div>
    
    <script>
        // Array global para almacenar los items del carrito (el motor del POS)
        let cartItems = [];
        let currentProduct = null;
        let lineIdCounter = 0; // Contador para dar IDs únicos a cada línea del carrito

        // --- FUNCIONES CENTRALES DEL CARRITO ---

        function renderCart() {
            const listContainer = document.querySelector('.cart-items-list');
            
            const headerHtml = `
                <div class="cart-header">
                    <span class="cart-col-prod">Producto</span>
                    <span class="cart-col-qty">Cant.</span>
                    <span class="cart-col-desc">Desc.</span>
                    <span class="cart-col-total">Total</span>
                </div>
            `;
            
            let itemsHtml = '';

            if (cartItems.length === 0) {
                listContainer.innerHTML = headerHtml + '<p style="text-align: center; color: var(--text-light); padding: 20px;">El carrito está vacío.</p>';
                updateTotals();
                return;
            }

            cartItems.forEach(item => {
                const totalLinePrice = item.finalPrice * item.quantity;
                
                let modDesc = '';
                for (const group in item.selectedModifiers) {
                    if (item.selectedModifiers[group].adjust !== 0 || group === 'Milk') {
                        modDesc += ` <span style="font-size: 0.8em; color: var(--cafe-medio); margin-right: 5px;">(${item.selectedModifiers[group].value})</span>`;
                    }
                }

                itemsHtml += `
                    <div class="cart-item clickable-edit" 
                         data-line-id="${item.lineId}" 
                         onclick="openProductModalForEdit(${item.lineId})">
                        
                        <span class="cart-col-prod">${item.name} ${modDesc}</span>
                        
                        <span class="cart-col-qty">${item.quantity}</span> 
                        <span class="cart-col-desc">-${item.discountPercentage}%</span>
                        <span class="cart-col-total">$${totalLinePrice.toFixed(2)}</span>
                        
                        <button class="remove-btn" onclick="event.stopPropagation(); removeCartItem(${item.lineId})">✖</button>
                    </div>
                `;
            });
            
            listContainer.innerHTML = headerHtml + itemsHtml;
            updateTotals();
        }

        function updateTotals() {
            let subtotal = 0;
            let totalDiscount = 0;
            
            cartItems.forEach(item => {
                const pricePerUnit = item.basePrice + (Object.values(item.selectedModifiers).reduce((sum, mod) => sum + mod.adjust, 0));
                const lineSubtotal = pricePerUnit * item.quantity;
                const discountAmount = (lineSubtotal * item.discountPercentage) / 100;
                
                subtotal += lineSubtotal;
                totalDiscount += discountAmount;
            });

            const finalTotal = subtotal - totalDiscount;

            document.getElementById('cart-subtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('cart-discount').textContent = `-$${totalDiscount.toFixed(2)}`;
            document.getElementById('cart-total').textContent = `$${finalTotal.toFixed(2)}`;

            if (document.getElementById('payment-modal').style.display === 'flex') {
                document.getElementById('subtotal-modal').textContent = `$${subtotal.toFixed(2)}`;
                document.getElementById('discount-modal').textContent = `-$${totalDiscount.toFixed(2)}`;
                document.getElementById('total-value-modal').textContent = `$${finalTotal.toFixed(2)}`;
            }
        }

        function removeCartItem(lineId) {
            cartItems = cartItems.filter(item => item.lineId !== lineId);
            renderCart();
        }

        // --- LÓGICA DE AGREGAR PRODUCTO (desde la Modal) ---
        
        function updateCartItem() {
            if (!currentProduct || !currentProduct.isEditing) return;

            const itemIndex = cartItems.findIndex(item => item.lineId === currentProduct.lineId);
            
            if (itemIndex > -1) {
                const total = parseFloat(document.getElementById('modal-final-price').textContent.replace('$', ''));
                currentProduct.finalPrice = total / currentProduct.quantity;
                
                cartItems[itemIndex] = currentProduct;
            }
            
            closeProductModal();
            renderCart();
            
            const addButton = document.querySelector('.btn-add-to-cart');
            addButton.textContent = "Añadir al Carrito";
            addButton.onclick = addToCart; 
        }
        
        function addToCart() {
            if (!currentProduct) return;
            
            const itemToAdd = {
                ...currentProduct,
                lineId: ++lineIdCounter,
                finalPrice: parseFloat(document.getElementById('modal-final-price').textContent.replace('$', '')) / currentProduct.quantity
            };
            
            cartItems.push(itemToAdd);
            
            closeProductModal();
            renderCart();
        }


        // --- LÓGICA DEL MODAL DE PRODUCTO (Personalización) ---

        function openProductModalForEdit(lineId) {
            const itemToEdit = cartItems.find(item => item.lineId === lineId);
            if (!itemToEdit) {
                alert('Error: Producto no encontrado.');
                return;
            }

            const modal = document.getElementById('product-modal');
            currentProduct = { ...itemToEdit };
            currentProduct.isEditing = true; 
            
            document.getElementById('modal-product-name').textContent = currentProduct.name;
            document.getElementById('modal-quantity-input').value = currentProduct.quantity;
            document.getElementById('modal-discount-input').value = currentProduct.discountPercentage;

            const addButton = document.querySelector('.btn-add-to-cart');
            addButton.textContent = "Guardar Cambios";
            addButton.onclick = updateCartItem; 
            
            document.querySelectorAll('#product-modal .mod-option').forEach(btn => {
                btn.classList.remove('active');
                const groupName = btn.dataset.modName;
                const modValue = btn.dataset.modValue;

                if (currentProduct.selectedModifiers[groupName] && 
                    currentProduct.selectedModifiers[groupName].value === modValue) {
                    btn.classList.add('active');
                }
            });

            updateProductPrice();
            modal.style.display = 'flex';
        }

        // FUNCIÓN PRINCIPAL PARA CARGAR LAS VARIANTES DEL PRODUCTO
        function openProductModal(productId, productNameBase, basePrice) {
            const modal = document.getElementById('product-modal');
            document.getElementById('modal-product-name').textContent = productNameBase;

            const sizeGroup = document.getElementById('group-size');
            sizeGroup.innerHTML = '<h4>Tamaño:</h4><p style="text-align:center;">Cargando opciones...</p>';
            sizeGroup.style.display = 'block';
            modal.style.display = 'flex';

            // RUTA CORREGIDA: phps/obtenerTamanios.php (relativa a ventas/ventas.php)
            fetch(`phps/obtenerTamanios.php?name=${encodeURIComponent(productNameBase)}`)
            .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(variants => {
                    const sizeGroup = document.getElementById('group-size');
                    
                    // LÓGICA PARA OCULTAR LA SECCIÓN SI NO HAY VARIANTES O ERROR
                    if (variants.error || variants.length === 0) {
                        sizeGroup.style.display = 'none'; 
                        
                        selectFinalProduct({ 
                            idProducto: productId, 
                            nombre: productNameBase, 
                            precioVenta: basePrice 
                        });
                        return;
                    }
                    
                    sizeGroup.style.display = 'block'; 
                    sizeGroup.innerHTML = '<h4>Tamaño:</h4>';
                    
                    variants.forEach(variant => {
                        const btn = document.createElement('button');
                        
                        let suffix = variant.nombre.replace(productNameBase, '').trim(); 
                        if (!suffix) {
                            suffix = 'Estándar'; 
                        }

                        btn.textContent = suffix + ` ($${parseFloat(variant.precioVenta).toFixed(2)})`;
                        btn.className = 'mod-option';
                        btn.type = 'button';
                        
                        btn.onclick = (e) => {
                            e.currentTarget.closest('.modifier-group').querySelectorAll('.mod-option').forEach(sibling => {
                                sibling.classList.remove('active');
                            });
                            e.currentTarget.classList.add('active');
                            selectFinalProduct(variant); 
                        };
                        sizeGroup.appendChild(btn);
                    });
                    
                    // Seleccionar la primera opción por defecto
                    if (variants.length > 0) {
                        const firstButton = sizeGroup.querySelector('.mod-option');
                        if (firstButton) {
                            firstButton.classList.add('active');
                            selectFinalProduct(variants[0]); 
                        }
                    }

                })
                .catch(error => {
                    console.error("Error en la carga AJAX de variantes:", error);
                    const sizeGroup = document.getElementById('group-size');
                    sizeGroup.style.display = 'block'; 
                    sizeGroup.innerHTML = '<h4>Tamaño:</h4><p style="color:var(--alert);">Error al comunicar con el servidor.</p>';
                });
        }

        function selectFinalProduct(finalProductData) {
            
            currentProduct = {
                id: finalProductData.idProducto,
                name: finalProductData.nombre, 
                basePrice: parseFloat(finalProductData.precioVenta), 
                quantity: 1,
                selectedModifiers: {}, 
                discountPercentage: 0,
                isEditing: false
            };
            
            document.getElementById('modal-quantity-input').value = 1;
            document.getElementById('modal-discount-input').value = 0;
            
            document.querySelectorAll('#group-milk .mod-option').forEach(btn => {
                btn.classList.remove('active');
            });
            const defaultMilk = document.querySelector('#group-milk [data-mod-value="Whole"]');
            if (defaultMilk) {
                defaultMilk.classList.add('active');
                currentProduct.selectedModifiers['Milk'] = {
                    value: 'Whole',
                    adjust: 0.00
                };
            }

            updateProductPrice(); 
        }

        function closeProductModal() {
            document.getElementById('product-modal').style.display = 'none';
            currentProduct = null;
        }

        function updateModalQuantity(change) {
            if (!currentProduct) return;
            let newQty = currentProduct.quantity + change;
            if (newQty < 1) newQty = 1;
            currentProduct.quantity = newQty;
            document.getElementById('modal-quantity-input').value = newQty;
            updateProductPrice();
        }

        function updateProductPrice() {
            if (!currentProduct) return;

            let totalModAdjustment = Object.values(currentProduct.selectedModifiers).reduce((sum, mod) => sum + mod.adjust, 0);
            let pricePerUnit = currentProduct.basePrice + totalModAdjustment;
            document.getElementById('modal-modifier-adjust').textContent = `$${totalModAdjustment.toFixed(2)}`;
            
            const discountPercentage = parseFloat(document.getElementById('modal-discount-input').value) || 0;
            currentProduct.discountPercentage = discountPercentage;
            
            const subtotal = pricePerUnit * currentProduct.quantity;
            const discountAmount = (subtotal * discountPercentage) / 100;
            const finalPrice = subtotal - discountAmount;

            document.getElementById('modal-base-price').textContent = `$${currentProduct.basePrice.toFixed(2)}`;
            document.querySelector('.discount-value-display').textContent = `Descuento aplicado: $${discountAmount.toFixed(2)}`;
            document.getElementById('modal-discount-applied').textContent = `-$${discountAmount.toFixed(2)}`;
            document.getElementById('modal-final-price').textContent = `$${finalPrice.toFixed(2)}`;
        }
        
        // --- LÓGICA DEL MODAL DE PAGO ---

        function openPaymentModal(method) {
            if (cartItems.length === 0) {
                alert("El carrito está vacío. Agrega productos para pagar.");
                return;
            }

            const modal = document.getElementById('payment-modal');
            const selectedMethodSpan = document.getElementById('selected-payment-method');
            
            const finalTotal = parseFloat(document.getElementById('cart-total').textContent.replace('$', ''));
            
            document.getElementById('subtotal-modal').textContent = document.getElementById('cart-subtotal').textContent;
            document.getElementById('discount-modal').textContent = document.getElementById('cart-discount').textContent;
            document.getElementById('total-value-modal').textContent = document.getElementById('cart-total').textContent;

            const cartHtml = document.querySelector('.cart-items-list').outerHTML;
            document.querySelector('#modal-cart-items-list').innerHTML = cartHtml;
            document.querySelectorAll('#modal-cart-items-list .remove-btn').forEach(btn => btn.remove());
            document.querySelectorAll('#modal-cart-items-list .clickable-edit').forEach(item => item.onclick = null);


            modal.style.display = 'flex';
            selectedMethodSpan.textContent = method;

            const cashArea = modal.querySelector('.cash-tender-area');
            
            if (method === 'Efectivo') {
                cashArea.style.display = 'block';
                document.getElementById('tender-input-modal').value = finalTotal.toFixed(2);
                document.getElementById('tender-input-modal').oninput = () => calculateChange(finalTotal);
                calculateChange(finalTotal);
            } else {
                cashArea.style.display = 'none';
            }
        }
        
        function calculateChange(total) {
            const received = parseFloat(document.getElementById('tender-input-modal').value) || 0;
            const change = received - total;
            document.getElementById('change-value-modal').textContent = `$${Math.abs(change).toFixed(2)}`;
            
            const changeElement = document.getElementById('change-value-modal');
            if (change < 0) {
                changeElement.textContent = `Falta: $${Math.abs(change).toFixed(2)}`;
                changeElement.style.color = 'var(--alert)';
            } else {
                changeElement.textContent = `Cambio: $${change.toFixed(2)}`;
                changeElement.style.color = 'var(--success)';
            }
        }

        function closeModal() {
            document.getElementById('tender-input-modal').oninput = null;
            document.getElementById('payment-modal').style.display = 'none';
        }

        function processPayment() {
            const total = parseFloat(document.getElementById('cart-total').textContent.replace('$', ''));
            const method = document.getElementById('selected-payment-method').textContent;
            let received = total;
            
            if (method === 'Efectivo') {
                received = parseFloat(document.getElementById('tender-input-modal').value) || 0;
                if (received < total) {
                    alert("El efectivo recibido es insuficiente. Por favor, ajuste el pago.");
                    return;
                }
            }

            if (confirm(`Confirmar pago de $${total.toFixed(2)} con ${method}?`)) {
                console.log("Datos de la Venta a enviar a PHP:", {
                    items: cartItems,
                    total: total,
                    method: method,
                    received: received,
                });
                
                alert('¡Venta procesada con éxito! (Listo para enviar datos al backend)');
                
                cartItems = [];
                renderCart();
                closeModal();
            }
        }

        function cancelSale() {
            if (confirm('¿Estás seguro que deseas cancelar esta venta? Se perderán los productos agregados.')) {
                cartItems = [];
                renderCart();
                closeModal();
                alert('Venta cancelada.');
            }
        }

        function holdSale() {
            alert('Venta retenida (guardada en espera). - Pendiente de implementar lógica AJAX de retención.');
            closeModal();
        }

        // --- ENLACES DE LISTENERS Y EVENTOS INICIALES ---

        document.addEventListener('DOMContentLoaded', renderCart);

        document.getElementById('modal-discount-input').addEventListener('input', updateProductPrice);

        document.querySelectorAll('#group-milk .mod-option').forEach(btn => { 
            btn.addEventListener('click', function() {
                this.closest('.modifier-group').querySelectorAll('.mod-option').forEach(sibling => {
                    sibling.classList.remove('active');
                });
                this.classList.add('active');

                if (currentProduct) {
                    const groupName = this.dataset.modName;
                    const modValue = this.dataset.modValue;
                    const priceAdjust = parseFloat(this.dataset.priceAdjust) || 0;

                    currentProduct.selectedModifiers[groupName] = {
                        value: modValue,
                        adjust: priceAdjust
                    };
                    updateProductPrice();
                }
            });
        });
    </script>
</body>
</html>