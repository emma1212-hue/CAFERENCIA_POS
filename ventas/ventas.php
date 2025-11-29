<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../indexLogin.php");
    exit();
}
require_once('../conexion.php'); 

$rol = $_SESSION['rol'];   

$productos = [];
// Seleccionamos ID, Nombre, Precio, Categoría y Descripción
$sql = "SELECT idProducto, nombre, precioVenta, idCategoria, descripción FROM productos WHERE idCategoria != 6  AND idCategoria != 7 ORDER BY nombre ASC";
$resultado = $conn->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $productos[] = $fila;
    }
} else if (!$resultado) {
    die("Error SQL: " . $conn->error);
}

$extras_db = [];
$sql_extras = "SELECT idProducto, nombre, precioVenta FROM productos WHERE idCategoria = 6 ORDER BY nombre ASC";
$res_extras = $conn->query($sql_extras);
if ($res_extras) {
    while ($row = $res_extras->fetch_assoc()) {
        $extras_db[] = $row;
    }
}
$sabores_db = [];
$sql_sabores = "SELECT idProducto, nombre, precioVenta FROM productos WHERE idCategoria = 7 ORDER BY nombre ASC";
$res_sabores = $conn->query($sql_sabores);
if ($res_sabores) {
    while ($row = $res_sabores->fetch_assoc()) {
        $sabores_db[] = $row;
    }
}

$sufijos_a_remover = [' Chico', ' Grande',' Pequeño', ' Mediano', ' CH', ' G', ' M', ' Gde', ' Med']; 

$productos_unicos = [];     
$conteo_categorias = [];    

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

    // Contar categorías
    $id_cat = $producto['idCategoria'];
    if (!isset($conteo_categorias[$id_cat])) $conteo_categorias[$id_cat] = 0;
    $conteo_categorias[$id_cat]++;

    // Agrupar por Nombre Base + Categoría (Ej: Carajillo Frappe vs Carajillo Bebida)
    $clave_unica = $producto['nombre_base'] . '_' . $id_cat;
    
    if (!isset($productos_unicos[$clave_unica])) {
        $productos_unicos[$clave_unica] = $producto;
    }
}
unset($producto);

$categorias_a_mostrar = [];
if (!empty($conteo_categorias)) {
    $ids = implode(',', array_keys($conteo_categorias));
    // Ajusta 'categorias' o 'categoria' según tu tabla real
    $sql_c = "SELECT idCategoria, nombre FROM categorias WHERE idCategoria IN ($ids) ORDER BY nombre ASC";
    $res_c = $conn->query($sql_c);
    if ($res_c) {
        while ($r = $res_c->fetch_assoc()) $categorias_a_mostrar[] = $r;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Punto de Venta</title>
    <link rel="stylesheet" href="../homepage/css/styleshome.css"> 
    <link rel="stylesheet" href="css/ventasStyle.css">
    
    <script>
        const globalExtras = <?php echo json_encode($extras_db); ?>;
        const globalFlavors = <?php echo json_encode($sabores_db); ?>;
    </script>
</head>
<body>
    <div class="container">
        
        <div class="header">
            <div class="header-left">
                <button class="icon-btn menu-btn" onclick="window.location.href='../indexhome.php'">
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M19 12H5"/><polyline points="12 19 5 12 12 5"></polyline></svg>
                </button>
             <h1>CAFÉRENCIA - NUEVA VENTA</h1>
            </div>
        </div>

        <div class="pos-layout">
            
            <div class="pos-panel pos-catalogo">
                <h2>Catálogo</h2>
                
                <div class="catalogo-header">
                    <div class="search-bar">
                        <input type="text" placeholder="Buscar producto..." class="search-input" oninput="searchProducts()">
                        <button class="search-btn" onclick="searchProducts()">🔍</button>
                    </div>
                    
                    <div class="category-tabs">
                        <button class="tab active" onclick="filterProducts('all')">Todos</button>
                        <?php foreach ($categorias_a_mostrar as $cat): ?>
                            <button class="tab" 
                                    data-category-id="<?php echo $cat['idCategoria']; ?>"
                                    onclick="filterProducts(<?php echo $cat['idCategoria']; ?>)">
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="product-grid scrollable-content">
                    <?php if (count($productos_unicos) > 0): ?>
                        <?php foreach ($productos_unicos as $prod): ?>
                            
                            <div class="product-card clickable" 
                                data-product-id="<?php echo $prod['idProducto']; ?>"
                                data-category-id="<?php echo $prod['idCategoria']; ?>"
                                
                                onclick="openProductModal(
                                    '<?php echo $prod['idProducto']; ?>',
                                    '<?php echo htmlspecialchars($prod['nombre_base']); ?>',
                                    <?php echo $prod['precioVenta']; ?>,
                                    <?php echo $prod['idCategoria']; ?>,
                                    '<?php echo htmlspecialchars($prod['descripción'] ?? ''); ?>'
                                )">
                                
                                <p class="product-name"><?php echo htmlspecialchars($prod['nombre_base']); ?></p>
                                <p class="product-price">$<?php echo number_format($prod['precioVenta'], 2); ?></p>
                            </div>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="padding:20px; text-align:center;">No hay productos disponibles.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pos-panel pos-carrito">
                <h2>Detalle Venta</h2>
                
                <div class="cart-items-list scrollable-content">
                    <div class="cart-header">
                        <span class="cart-col-prod">Producto</span>
                        <span class="cart-col-qty">Cant.</span>
                        <span class="cart-col-desc">Desc.</span>
                        <span class="cart-col-total">Total</span>
                        <span></span>
                    </div>
                    <div id="cart-list-container"></div> 
                </div>
                
                <div class="cart-summary fixed-footer">
                    <div class="summary-line"><span>Subtotal:</span><span class="summary-value" id="cart-subtotal">$0.00</span></div>
                    <div class="summary-line"><span>Descuentos:</span><span class="summary-value discount" id="cart-discount">-$0.00</span></div>
                    <div class="summary-line summary-total"><span class="total-label">TOTAL:</span><span class="total-value" id="cart-total">$0.00</span></div>
                </div>
            </div>

            <div class="pos-panel pos-pago">
                <h2>Finalizar</h2>
                <div class="payment-options">
                    <p>Método:</p>
                    <button class="payment-btn" onclick="openPaymentModal('Efectivo')">💵 Efectivo</button>
                    <button class="payment-btn" onclick="openPaymentModal('Transferencia')">📱 Transferencia</button>
                </div>
            </div>
        </div>
    </div>

    <div id="product-modal" class="modal-overlay">
        <div class="modal-content product-modal-content">
            <h2 class="modal-title" style="margin-bottom:5px;">Personalizar: <span id="modal-product-name"></span></h2>
            <p id="modal-product-desc" style="color:var(--text-light); font-style:italic; border-bottom:1px solid #ddd; padding-bottom:10px; margin-bottom:15px;"></p>
            
            <div class="modal-grid product-grid-layout">
                <div class="modal-options-side">
                    <div class="modifier-group" id="group-size" style="display:none;">
                        <h4>Tamaño:</h4>
                    </div>

                    <div class="modifier-group" id="group-flavors" style="display:none;">
                        <h4>Elige un Sabor:</h4>
                        <div id="flavors-container" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
                    </div>

                    <div class="modifier-group" id="group-milk">
                        <h4>Leche Base:</h4>
                        <button class="mod-option active" data-mod-name="MilkBase" data-mod-value="Entera" data-price-adjust="0">Entera</button>
                        <button class="mod-option" data-mod-name="MilkBase" data-mod-value="Deslactosada" data-price-adjust="0">Deslactosada</button>
                    </div>

                    <div class="modifier-group" id="group-extras">
                        <h4>Extras:</h4>
                        <div id="extras-container" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
                    </div>

                    <div class="modifier-group">
                        <h4>Descuento (%):</h4>
                        <input type="number" id="modal-discount-input" min="0" max="100" placeholder="%" class="input-discount">
                        <span class="discount-value-display" style="font-size:0.8rem;">Desc: $<span id="modal-discount-amt">0.00</span></span>
                    </div>
                </div>

                <div class="modal-summary-side product-summary-side">
                    <div class="summary-box">
                        <p>Base: <span id="modal-base-price">$0.00</span></p>
                        <p>Extras: <span id="modal-modifier-adjust">+$0.00</span></p>
                        <p>Desc: <span id="modal-discount-applied">-$0.00</span></p>
                        <hr>
                        <h3 class="total-modal-price"><span id="modal-final-price">$0.00</span></h3>
                    </div>

                    <div class="quantity-controls">
                        <h4>Cantidad:</h4>
                        <div class="qty-btn-group">
                            <button class="qty-btn" onclick="updateModalQuantity(-1)">-</button>
                            <input type="number" id="modal-quantity-input" value="1" min="1" readonly>
                            <button class="qty-btn" onclick="updateModalQuantity(1)">+</button>
                        </div>
                    </div>
                    
                    <button class="btn-main btn-add-to-cart" onclick="addToCart()">Añadir</button>
                </div>
            </div>
            <button class="modal-close-btn" onclick="closeProductModal()">✖</button>
        </div>
    </div>

    <div id="payment-modal" class="modal-overlay">
        <div class="modal-content">
            <h2 class="modal-title">Cobrar</h2>
            <div class="modal-grid">
                
                <div class="modal-summary-side">
                    <h3>Resumen</h3>
                    <div class="modal-cart-list" id="modal-cart-copy"></div>
                    
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
                            <span class="total-value" id="pay-total-val">$0.00</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-payment-side">
                    <h3>Método: <span id="pay-method-display"></span></h3>
                    <div class="cash-tender-area">
                        <p>Recibido:</p>
                        <input type="number" id="pay-input" placeholder="0.00" class="tender-input">
                        <div class="change-info">
                            <span>Cambio:</span>
                            <span class="change-value" id="pay-change">$0.00</span>
                        </div>
                    </div>
                    <div class="action-buttons modal-actions">
                        <button class="btn-main btn-pay" onclick="processPayment()">Cobrar</button>
                        <button class="btn-secondary btn-cancel" onclick="closeModal()">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- ESTADO GLOBAL ---
        let currentCatId = 0; // NUEVA VARIABLE para recordar la categoría al abrir modal
        let cartItems = [];
        let lineIdCounter = 1;
        let currentProduct = null;

        // --- INICIALIZACIÓN ---
        document.addEventListener('DOMContentLoaded', () => {
            renderCart();
            filterProducts('all');
            
            // Listeners globales
            document.getElementById('modal-discount-input').addEventListener('input', updateProductPrice);
            
            // Listener Leche (Exclusivo)
            document.querySelectorAll('#group-milk .mod-option').forEach(btn => {
                btn.addEventListener('click', function() {
                    this.parentElement.querySelectorAll('.mod-option').forEach(s => s.classList.remove('active'));
                    this.classList.add('active');
                    if(currentProduct) {
                        currentProduct.selectedModifiers['MilkBase'] = { value: this.dataset.modValue, adjust: 0 };
                        updateProductPrice();
                    }
                });
            });
        });

        // --- FILTROS Y BÚSQUEDA ---
        function filterProducts(catId) {
            document.querySelectorAll('.category-tabs .tab').forEach(t => {
                const isActive = (t.dataset.categoryId == catId) || (catId === 'all' && t.textContent.includes('Todos'));
                t.classList.toggle('active', isActive);
            });
            document.querySelectorAll('.product-card').forEach(c => {
                c.style.display = (catId === 'all' || c.dataset.categoryId == catId) ? 'flex' : 'none';
            });
        }

        function searchProducts() {
            const term = document.querySelector('.search-input').value.toLowerCase().trim();
            const activeTab = document.querySelector('.category-tabs .tab.active');
            const catId = activeTab ? (activeTab.dataset.categoryId || 'all') : 'all';

            document.querySelectorAll('.product-card').forEach(c => {
                const name = c.querySelector('.product-name').textContent.toLowerCase();
                const matchSearch = name.includes(term);
                const matchCat = (catId === 'all' || c.dataset.categoryId == catId);
                c.style.display = (matchSearch && matchCat) ? 'flex' : 'none';
            });
        }

        // --- MODAL PRODUCTO ---
        function closeProductModal() {
            document.getElementById('product-modal').style.display = 'none';
            currentProduct = null;
        }

        function openProductModal(id, nameBase, priceBase, catId, desc) {
            currentCatId = catId; // Guardar categoría actual
            
            const modal = document.getElementById('product-modal');
            document.getElementById('modal-product-name').textContent = nameBase;
            document.getElementById('modal-product-desc').textContent = desc || '';
            
            const sizeGroup = document.getElementById('group-size');
            sizeGroup.innerHTML = '<p>Cargando...</p>';
            sizeGroup.style.display = 'block';
            
            renderExtras();
            renderFlavors(); // Importante llamar esto

            // --- VISIBILIDAD ---
            const isTisana = nameBase.toLowerCase().includes('tisana');
            
            // Sabores
            document.getElementById('group-flavors').style.display = isTisana ? 'block' : 'none';
            
            // Leche (Ocultar si es Comida, Extra o Tisana)
            const showMilk = !(catId == 1 || catId == 6 || isTisana);
            document.getElementById('group-milk').style.display = showMilk ? 'block' : 'none';
            
            // Extras (Ocultar si es Tisana)
            document.getElementById('group-extras').style.display = isTisana ? 'none' : 'block';

            // Botón Añadir
            const addBtn = document.querySelector('.btn-add-to-cart');
            addBtn.textContent = "Añadir al Carrito";
            addBtn.onclick = addToCart;

            modal.style.display = 'flex';

            fetch(`phps/obtenerTamanios.php?name=${encodeURIComponent(nameBase)}&category=${catId}`)
                .then(r => r.json())
                .then(variants => {
                    if(!variants || variants.length === 0 || variants.error) {
                        sizeGroup.style.display = 'none';
                        // Pasamos catId para guardarlo
                        selectFinalProduct({ idProducto: id, nombre: nameBase, precioVenta: priceBase });
                        return;
                    }
                    
                    sizeGroup.style.display = 'block';
                    sizeGroup.innerHTML = '<h4>Tamaño:</h4>';
                    const suffixes = ['Chico', 'Grande', 'Pequeño', 'Mediano', 'Vaso', 'Estándar', 'CH', 'G', 'M', 'Gde'];
                    
                    variants.forEach(v => {
                        const btn = document.createElement('button');
                        let disp = 'Estándar';
                        for(const s of suffixes) { if(v.nombre.includes(s)) { disp = s; break; } }
                        
                        btn.textContent = `${disp} ($${parseFloat(v.precioVenta).toFixed(2)})`;
                        btn.className = 'mod-option';
                        btn.onclick = (e) => {
                            e.target.parentElement.querySelectorAll('.mod-option').forEach(s=>s.classList.remove('active'));
                            e.target.classList.add('active');
                            selectFinalProduct(v);
                        };
                        sizeGroup.appendChild(btn);
                    });

                    const first = sizeGroup.querySelector('.mod-option');
                    if(first) { first.classList.add('active'); selectFinalProduct(variants[0]); }
                })
                .catch(e => {
                    console.error(e);
                    sizeGroup.style.display = 'none';
                    selectFinalProduct({ idProducto: id, nombre: nameBase, precioVenta: priceBase });
                });
        }
        function renderExtras() {
            const container = document.getElementById('extras-container');
            container.innerHTML = '';
            
            if(typeof globalExtras === 'undefined' || globalExtras.length === 0) {
                document.getElementById('group-extras').style.display = 'none';
                return;
            }
            document.getElementById('group-extras').style.display = 'block';

            globalExtras.forEach(ex => {
                const btn = document.createElement('button');
                const p = parseFloat(ex.precioVenta);
                btn.className = 'mod-option';
                btn.textContent = `${ex.nombre} (+$${p})`;
                btn.dataset.modName = 'Extra_' + ex.idProducto;
                btn.dataset.modValue = ex.nombre;
                btn.dataset.priceAdjust = p;
                
                btn.onclick = function() {
                    // Lógica de exclusividad para "Leche" en Extras
                    const thisName = this.dataset.modValue.toLowerCase();
                    if (thisName.includes("leche")) {
                        const siblings = container.querySelectorAll('.mod-option');
                        siblings.forEach(sib => {
                            if (sib !== this && sib.classList.contains('active')) {
                                const sibName = sib.dataset.modValue.toLowerCase();
                                if (sibName.includes("leche")) {
                                    sib.classList.remove('active');
                                    // Limpiar del objeto
                                    const sibModName = sib.dataset.modName;
                                    if(currentProduct && currentProduct.selectedModifiers[sibModName]) {
                                        delete currentProduct.selectedModifiers[sibModName];
                                    }
                                }
                            }
                        });
                    }

                    this.classList.toggle('active');
                    updateModifierInProduct(this);
                };
                container.appendChild(btn);
            });
        }

        // --- LÓGICA INTERNA DEL PRODUCTO ---
function selectFinalProduct(data) {
            // 1. Inicializar el producto base
            currentProduct = {
                id: data.idProducto,
                name: data.nombre,
                basePrice: parseFloat(data.precioVenta),
                quantity: 1,
                selectedModifiers: {},
                discountPercentage: 0,
                isEditing: false
            };
            
            // 2. Resetear Inputs
            document.getElementById('modal-quantity-input').value = 1;
            document.getElementById('modal-discount-input').value = 0;
            
            // 3. Resetear y Asignar Leche por Defecto
            document.querySelectorAll('#group-milk .mod-option').forEach(b => b.classList.remove('active'));
            
            const milkGroup = document.getElementById('group-milk');
            if (milkGroup.style.display !== 'none') {
                const defMilk = document.querySelector('#group-milk [data-mod-value="Entera"]');
                if(defMilk) {
                    defMilk.classList.add('active');
                    currentProduct.selectedModifiers['MilkBase'] = { value: 'Entera', adjust: 0 };
                }
            }
            
            // 4. Resetear Extras Visualmente
            document.querySelectorAll('#extras-container .mod-option').forEach(b => b.classList.remove('active'));

            // 5. LÓGICA DE SABOR POR DEFECTO (TISANAS)
            // Primero limpiamos cualquier selección visual anterior en sabores
            document.querySelectorAll('#flavors-container .mod-option').forEach(b => b.classList.remove('active'));

            // Si el nombre incluye "tisana", seleccionamos el primero automáticamente
            if (data.nombre.toLowerCase().includes('tisana')) {
                const firstFlavor = document.querySelector('#flavors-container .mod-option');
                if (firstFlavor) {
                    firstFlavor.classList.add('active'); // Activar visualmente
                    
                    // Guardar en el objeto del producto
                    currentProduct.selectedModifiers['Flavor'] = { 
                        value: firstFlavor.dataset.modValue, 
                        adjust: 0 
                    };
                }
            }
            
            // 6. Calcular Totales
            updateProductPrice();
        }        
        function updateModifierInProduct(btn) {
            if(!currentProduct) return;
            const key = btn.dataset.modName;
            
            if(btn.classList.contains('active')) {
                currentProduct.selectedModifiers[key] = {
                    value: btn.dataset.modValue,
                    adjust: parseFloat(btn.dataset.priceAdjust)
                };
            } else {
                delete currentProduct.selectedModifiers[key];
            }
            updateProductPrice();
        }

        function updateModalQuantity(chg) {
            if(!currentProduct) return;
            let val = parseInt(document.getElementById('modal-quantity-input').value) || 1;
            if(chg !== 0) val += chg;
            if(val < 1) val = 1;
            document.getElementById('modal-quantity-input').value = val;
            currentProduct.quantity = val;
            updateProductPrice();
        }

        function updateProductPrice() {
            if(!currentProduct) return;
            
            let totalMods = Object.values(currentProduct.selectedModifiers).reduce((acc, m) => acc + m.adjust, 0);
            let unitTotal = currentProduct.basePrice + totalMods;
            
            // Descuento
            const discP = parseFloat(document.getElementById('modal-discount-input').value) || 0;
            currentProduct.discountPercentage = discP;
            const lineTotalNoDisc = unitTotal * currentProduct.quantity;
            const discAmt = lineTotalNoDisc * (discP / 100);
            const finalTotal = lineTotalNoDisc - discAmt;

            document.getElementById('modal-base-price').textContent = `$${currentProduct.basePrice.toFixed(2)}`;
            document.getElementById('modal-modifier-adjust').textContent = `+$${totalMods.toFixed(2)}`;
            document.getElementById('modal-discount-amt').textContent = discAmt.toFixed(2);
            document.getElementById('modal-discount-applied').textContent = `-$${discAmt.toFixed(2)}`;
            document.getElementById('modal-final-price').textContent = `$${finalTotal.toFixed(2)}`;
        }

        // --- CARRITO ---
        function addToCart() {
            if(!currentProduct) return;
            
            const total = parseFloat(document.getElementById('modal-final-price').textContent.replace('$',''));
            const finalUnitPrice = total / currentProduct.quantity;

            if(currentProduct.isEditing) {
                const idx = cartItems.findIndex(i => i.lineId === currentProduct.lineId);
                if(idx > -1) {
                    currentProduct.finalPrice = finalUnitPrice;
                    cartItems[idx] = currentProduct;
                }
            } else {
                const newItem = {
                    ...currentProduct,
                    lineId: ++lineIdCounter,
                    finalPrice: finalUnitPrice
                };
                cartItems.push(newItem);
            }
            closeProductModal();
            renderCart();
        }
function openProductModalForEdit(lineId) {
            const item = cartItems.find(i => i.lineId === lineId);
            if(!item) return;
            
            const modal = document.getElementById('product-modal');
            currentProduct = { ...item, isEditing: true };
            
            document.getElementById('modal-product-name').textContent = currentProduct.name;
            document.getElementById('modal-quantity-input').value = currentProduct.quantity;
            document.getElementById('modal-discount-input').value = currentProduct.discountPercentage;
            
            document.getElementById('group-size').style.display = 'none';
            renderExtras();
            renderFlavors();

            // Restaurar Visibilidad basada en datos guardados o nombre
            const isTisana = currentProduct.name.toLowerCase().includes('tisana');
            document.getElementById('group-flavors').style.display = isTisana ? 'block' : 'none';
            
            // Leche: Usar categoryId guardado
            const catId = currentProduct.categoryId || 0;
            const showMilk = !(catId == 1 || catId == 6 || isTisana);
            document.getElementById('group-milk').style.display = showMilk ? 'block' : 'none';
            
            document.getElementById('group-extras').style.display = isTisana ? 'none' : 'block';

            // Restaurar Botones Activos
            document.querySelectorAll('.mod-option').forEach(b => b.classList.remove('active'));
            
            for(const k in currentProduct.selectedModifiers) {
                const m = currentProduct.selectedModifiers[k];
                // Buscar en todos los grupos posibles
                let btn = document.querySelector(`#group-milk [data-mod-value="${m.value}"]`);
                if(btn) btn.classList.add('active');
                
                btn = document.querySelector(`#extras-container [data-mod-value="${m.value}"]`);
                if(btn) btn.classList.add('active');
                
                btn = document.querySelector(`#flavors-container [data-mod-value="${m.value}"]`);
                if(btn) btn.classList.add('active');
            }

            const addBtn = document.querySelector('.btn-add-to-cart');
            addBtn.textContent = "Guardar Cambios";
            addBtn.onclick = addToCart; // Reutilizamos addToCart

            updateProductPrice();
            modal.style.display = 'flex';
        }
        
        function renderCart() {
            const list = document.getElementById('cart-list-container');
            let html = '';
            let sub = 0; 
            
            cartItems.forEach(item => {
                const totalLine = item.finalPrice * item.quantity;
                sub += totalLine;
                
                let mods = [];
                for(const k in item.selectedModifiers) {
                    const mod = item.selectedModifiers[k];
                    
                    // LÓGICA DE LIMPIEZA VISUAL:
                    // Si es Leche Base Y el valor es "Entera", NO mostrarlo.
                    if (k === 'MilkBase' && mod.value === 'Entera') {
                        continue; 
                    }

                    // Formato de texto
                    if (mod.adjust > 0) {
                        mods.push(`${mod.value} (+$${mod.adjust})`);
                    } else {
                        mods.push(mod.value);
                    }
                }
                const modStr = mods.length ? `<small style="color:#666">(${mods.join(', ')})</small>` : '';
                
                html += `
                <div class="cart-item clickable-edit" onclick="openProductModalForEdit(${item.lineId})">
                    <span class="cart-col-prod">${item.name} ${modStr}</span>
                    <span class="cart-col-qty">${item.quantity}</span>
                    <span class="cart-col-desc">${item.discountPercentage}%</span>
                    <span class="cart-col-total">$${totalLine.toFixed(2)}</span>
                    <button class="remove-btn" onclick="event.stopPropagation(); removeCartItem(${item.lineId})">✖</button>
                </div>`;
            });
            
            list.innerHTML = html || '<p style="text-align:center; padding:10px;">Vacío</p>';
            
            document.getElementById('cart-subtotal').textContent = `$${sub.toFixed(2)}`;
            document.getElementById('cart-total').textContent = `$${sub.toFixed(2)}`;
        }

        function removeCartItem(id) {
            cartItems = cartItems.filter(i => i.lineId !== id);
            renderCart();
        }

        // --- PAGO ---
        function openPaymentModal(method) {
            if(cartItems.length === 0) { alert("Carrito vacío"); return; }
            const modal = document.getElementById('payment-modal');
            
            // Copiar HTML
            document.getElementById('modal-cart-copy').innerHTML = document.getElementById('cart-list-container').innerHTML;
            const copy = document.getElementById('modal-cart-copy');
            copy.querySelectorAll('.remove-btn').forEach(b => b.remove());
            copy.querySelectorAll('.clickable-edit').forEach(d => d.onclick = null);

            document.getElementById('pay-method-display').textContent = method;
            
            // --- CORRECCIÓN AQUÍ: Obtener y asignar Subtotal y Descuentos ---
            const subtotalTxt = document.getElementById('cart-subtotal').textContent;
            const discountTxt = document.getElementById('cart-discount').textContent;
            const totalTxt = document.getElementById('cart-total').textContent;

            document.getElementById('subtotal-modal').textContent = subtotalTxt; // <--- ESTA FALTABA
            document.getElementById('discount-modal').textContent = discountTxt; // <--- ESTA TAMBIÉN ES IMPORTANTE
            document.getElementById('pay-total-val').textContent = totalTxt;
            // ---------------------------------------------------------------
            
            const areaCash = document.querySelector('.cash-tender-area');
            areaCash.style.display = (method === 'Efectivo') ? 'block' : 'none';
            
            if(method === 'Efectivo') {
                const totNum = parseFloat(totalTxt.replace('$',''));
                document.getElementById('pay-input').value = totNum.toFixed(2);
                document.getElementById('pay-input').oninput = function() {
                    const val = parseFloat(this.value) || 0;
                    document.getElementById('pay-change').textContent = `$${Math.max(0, val - totNum).toFixed(2)}`;
                };
            }
            modal.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('payment-modal').style.display = 'none';
        }

      function processPayment() {
            const method = document.getElementById('pay-method-display').textContent;
            const total = parseFloat(document.getElementById('pay-total-val').textContent.replace('$',''));
            
            // Validación básica de efectivo
            if(method === 'Efectivo') {
                const rec = parseFloat(document.getElementById('pay-input').value) || 0;
                if(rec < total) { alert("Monto insuficiente"); return; }
            }

            if(confirm("¿Confirmar venta?")) {
                
                // Preparar el paquete de datos
                const saleData = {
                    items: cartItems, // Enviamos el array completo del carrito
                    total: total,
                    method: method
                };

                // Bloquear botón para evitar doble click
                const btnPay = document.querySelector('.btn-pay');
                const originalText = btnPay.textContent;
                btnPay.disabled = true;
                btnPay.textContent = "Procesando...";

                // ENVIAR AL BACKEND
                fetch('phps/guardar_venta.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(saleData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Venta Guardada! Ticket #" + data.idVenta);
                        
                        // Limpiar carrito y cerrar modal
                        cartItems = [];
                        lineIdCounter = 1;
                        renderCart();
                        closeModal();
                        
                        // Opcional: Abrir ticket para imprimir
                        // window.open('phps/ticket.php?id=' + data.idVenta, '_blank');
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Error de red o servidor."+error);
                })
                .finally(() => {
                    btnPay.disabled = false;
                    btnPay.textContent = originalText;
                });
            }
        }
        function cancelSale() {
            if(confirm("¿Borrar todo?")) { cartItems=[]; renderCart(); closeModal(); }
        }
        
        function holdSale() { alert("Venta en espera"); closeModal(); }
        function renderFlavors() {
            const container = document.getElementById('flavors-container');
            container.innerHTML = '';
            // Si no hay variable global de sabores (definida en PHP), salir
            if (typeof globalFlavors === 'undefined' || globalFlavors.length === 0) return;

            globalFlavors.forEach(flavor => {
                const btn = document.createElement('button');
                btn.className = 'mod-option';
                btn.textContent = flavor.nombre;
                btn.dataset.modName = 'Flavor';
                btn.dataset.modValue = flavor.nombre;
                btn.dataset.priceAdjust = 0;
                
                btn.onclick = function() {
                    container.querySelectorAll('.mod-option').forEach(s => s.classList.remove('active'));
                    this.classList.add('active');
                    if(currentProduct) {
                        currentProduct.selectedModifiers['Flavor'] = { value: this.dataset.modValue, adjust: 0 };
                    }
                };
                container.appendChild(btn);
            });
        }
    </script>
</body>
</html>