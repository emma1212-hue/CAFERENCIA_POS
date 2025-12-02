<?php
session_start();

include '../conexion.php'; 

if (!isset($_SESSION['usuario'])) {
    header("Location: ../indexLogin.php");
    exit();
}

$rol = $_SESSION['rol'] ?? 'Cajero'; 

// Función para obtener el listado principal
function obtenerTodasLasVentas($conn) {
    $sql = "SELECT v.idVenta, v.fechaVenta AS fecha, v.totalVenta AS total, v.tipoPago, u.nombre AS cajero 
            FROM ventas v
            JOIN usuarios u ON v.idUsuario = u.idUsuario
            ORDER BY v.fechaVenta DESC LIMIT 50"; 

    $resultado = $conn->query($sql);
    $ventas = [];

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $ventas[] = $fila;
        }
    }
    return $ventas;
}

// Función auxiliar (Mantenida de tu código original)
function obtenerDetalleVenta($conn, $idVenta) {
    $sql = "SELECT dv.*, p.nombre as nombreProducto, p.idProducto
            FROM ventasdetalle dv
            JOIN productos p ON dv.idProducto = p.idProducto
            WHERE dv.idVenta = ?
            ORDER BY dv.idDetalleVenta ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idVenta);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $detalles = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $detalles[] = $fila;
        }
    }
    return $detalles;
}

$ventas = obtenerTodasLasVentas($conn);
$tiposDePago = ['Efectivo', 'Tarjeta', 'Transferencia'];

// Cargar productos para el catálogo
$productos = [];
$sql = "SELECT idProducto, nombre, precioVenta, idCategoria, descripcion FROM productos WHERE idCategoria != 6 AND idCategoria != 7 ORDER BY nombre ASC";
$resultado = $conn->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $productos[] = $fila;
    }
}

// Cargar Extras y Sabores
$extras_db = [];
$res_extras = $conn->query("SELECT idProducto, nombre, precioVenta FROM productos WHERE idCategoria = 6 ORDER BY nombre ASC");
while ($row = $res_extras->fetch_assoc()) $extras_db[] = $row;

$sabores_db = [];
$res_sabores = $conn->query("SELECT idProducto, nombre, precioVenta FROM productos WHERE idCategoria = 7 ORDER BY nombre ASC");
while ($row = $res_sabores->fetch_assoc()) $sabores_db[] = $row;

// Agrupar productos únicos
$sufijos_a_remover = [' Chico', ' Grande',' Pequeño', ' Mediano', ' CH', ' G', ' M', ' Gde', ' Med'];
$productos_unicos = [];
$conteo_categorias = [];

foreach ($productos as &$producto) {
    $nombre_base = $producto['nombre'];
    foreach ($sufijos_a_remover as $sufijo) {
        if (stripos($nombre_base, $sufijo) !== false && substr(strtolower($nombre_base), -strlen($sufijo)) === strtolower($sufijo)) {
            $nombre_base = substr($nombre_base, 0, -strlen($sufijo));
            break;
        }
    }
    $producto['nombre_base'] = trim($nombre_base);
    
    $id_cat = $producto['idCategoria'];
    if (!isset($conteo_categorias[$id_cat])) $conteo_categorias[$id_cat] = 0;
    $conteo_categorias[$id_cat]++;
    
    $clave_unica = $producto['nombre_base'] . '_' . $id_cat;
    if (!isset($productos_unicos[$clave_unica])) {
        $productos_unicos[$clave_unica] = $producto;
    }
}
unset($producto);

$categorias_a_mostrar = [];
if (!empty($conteo_categorias)) {
    $ids = implode(',', array_keys($conteo_categorias));
    $res_c = $conn->query("SELECT idCategoria, nombre FROM categorias WHERE idCategoria IN ($ids) ORDER BY nombre ASC");
    while ($r = $res_c->fetch_assoc()) $categorias_a_mostrar[] = $r;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Modificar Ventas | Cafetería</title>
    
    <link rel="stylesheet" href="../homepage/css/styleshome.css">
    <link rel="stylesheet" href="css/consultarVentas.css">
    <link rel="stylesheet" href="css/ventasStyle.css">
    <script>
        const globalExtras = <?php echo json_encode($extras_db); ?>;
        const globalFlavors = <?php echo json_encode($sabores_db); ?>;
        const productosUnicos = <?php echo json_encode($productos_unicos); ?>;
        const categoriasAMostrar = <?php echo json_encode($categorias_a_mostrar); ?>;
    </script>
    <style>
        .edit-modal-content { max-width: 900px; max-height: 90vh; overflow-y: auto; }
        .product-item { background: white; padding: 12px; border-radius: 6px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid var(--cafe-medio); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .product-actions button { margin-left: 5px; cursor: pointer; }
    </style>
</head>
<body onload="applyFilters()">
    <div class="container">
        <!-- Header -->
        <div class="header">
            <button class="icon-btn" onclick="window.location.href='../indexhome.php'">&#8592;</button>
            <div>
                <h1>Modificar Ventas</h1>
                <p class="subtitle">Editar detalles de transacciones</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="controls">
            <div class="control-group">
                <label>Buscar (ID/Cajero):</label>
                <input type="text" id="q" placeholder="Ej: 25 o Juan" onkeyup="applyFilters()">
            </div>
            <div class="control-group">
                <label>Tipo Pago:</label>
                <select id="tipoPagoFilter" onchange="applyFilters()">
                    <option value="">Todos</option>
                    <?php foreach ($tiposDePago as $tipo): ?>
                        <option value="<?php echo htmlspecialchars($tipo); ?>"><?php echo htmlspecialchars($tipo); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="control-group">
                <label>Desde:</label>
                <input type="date" id="fromDate" onchange="applyFilters()">
            </div>
            <div class="control-group">
                <label>Hasta:</label>
                <input type="date" id="toDate" onchange="applyFilters()">
            </div>
            <div class="btn-group">
                <button class="btn btn-search" onclick="applyFilters()">Buscar</button>
                <button class="btn btn-reset" onclick="resetFilters()">Limpiar</button>
            </div>
        </div>

        <!-- Tabla de Ventas -->
        <div class="table-wrapper">
            <table class="sales-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th> 
                        <th>Fecha</th>
                        <th>Cajero</th>
                        <th style="text-align: center;">Pago</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody">
                    <?php foreach ($ventas as $v): 
                        $fechaF = date('d/m/Y h:i A', strtotime($v['fecha']));
                        $fechaData = date('Y-m-d', strtotime($v['fecha']));
                    ?>
                    <tr onclick="openEditModal(<?php echo $v['idVenta']; ?>, '<?php echo $v['tipoPago']; ?>')"
                        data-fecha="<?php echo $fechaData; ?>"
                        data-usuario="<?php echo strtolower($v['cajero']); ?>"
                        data-pago="<?php echo strtolower($v['tipoPago']); ?>"
                        style="cursor: pointer;">
                        
                        <td style="text-align: center; font-weight: bold; color: var(--cafe-medio);"><?php echo $v['idVenta']; ?></td>
                        <td><?php echo $fechaF; ?></td>
                        <td><?php echo $v['cajero']; ?></td>
                        <td style="text-align: center;">
                            <span style="padding: 4px 10px; background: #eee; border-radius: 12px; font-size: 0.85rem;">
                                <?php echo $v['tipoPago']; ?>
                            </span>
                        </td>
                        <td style="text-align: right; font-weight: bold; color: var(--cafe-oscuro);">$<?php echo number_format($v['total'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Lista Móvil -->
        <ul class="cards" id="salesCardsList">
            <?php foreach ($ventas as $v): 
                $fechaF = date('d/m/Y h:i A', strtotime($v['fecha']));
                $fechaData = date('Y-m-d', strtotime($v['fecha']));
            ?>
            <li class="sale-card" 
                onclick="openEditModal(<?php echo $v['idVenta']; ?>, '<?php echo $v['tipoPago']; ?>')"
                data-fecha="<?php echo $fechaData; ?>"
                data-usuario="<?php echo strtolower($v['cajero']); ?>"
                data-pago="<?php echo strtolower($v['tipoPago']); ?>"
                style="cursor: pointer;">
                <div class="card-row"><span>ID Venta:</span> <b>#<?php echo $v['idVenta']; ?></b></div>
                <div class="card-row"><span>Fecha:</span> <?php echo $fechaF; ?></div>
                <div class="card-row"><span>Cajero:</span> <?php echo $v['cajero']; ?></div>
                <div class="card-row"><span>Pago:</span> <?php echo $v['tipoPago']; ?></div>
                <div class="card-row total-row"><span>Total:</span> $<?php echo number_format($v['total'], 2); ?></div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- MODAL PRINCIPAL: LISTA DE PRODUCTOS -->
    <div id="editModal" class="modal">
        <div class="modal-content edit-modal-content" style="position: relative;">
            <span class="close-btn" onclick="closeEditModal()" style="position: absolute; top: 15px; right: 15px;">&times;</span>
            <h2>Modificar Venta #<span id="editVentaId"></span></h2>
            
            <div class="edit-form-group">
                <label>Tipo de Pago:</label>
                <select id="editTipoPago">
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Transferencia">Transferencia</option>
                </select>
            </div>
            
            <h3 style="margin-top:20px;">Productos</h3>
            <div id="productsEditList" class="products-list"></div>
            
            <button class="btn-add-product" onclick="addNewProductToVenta()">+ Agregar Producto</button>
            
            <div class="summary-box">
                <div class="summary-line"><span>Subtotal:</span><span id="editSubtotal">$0.00</span></div>
                <div class="summary-line"><span>Descuentos:</span><span id="editDiscounts">-$0.00</span></div>
                <div class="summary-line total"><span>TOTAL:</span><span id="editTotal">$0.00</span></div>
            </div>
            
            <div class="edit-buttons">
                <button class="btn-save" onclick="saveVentaChanges()">Guardar Cambios</button>
                <button class="btn-cancel-edit" onclick="closeEditModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- MODAL PRODUCTO: PERSONALIZACIÓN -->
    <div id="product-modal" class="modal-overlay" style="display: none; align-items: center; justify-content: center;">
        <div class="modal-content product-modal-content" style="position: relative; display: flex; flex-direction: column;">
            <button class="modal-close-btn" onclick="closeProductModal()" style="position: absolute; top: 10px; right: 10px; z-index: 10;">✖</button>
            
            <h2 class="modal-title" style="margin-bottom:5px;">Editar: <span id="modal-product-name"></span></h2>
            
            <div class="modal-grid product-grid-layout" style="flex: 1; overflow: hidden;">
                <div class="modal-options-side" style="overflow-y: auto;">
                    
                    <div class="modifier-group" id="group-size" style="display:none;">
                        <h4>Tamaño:</h4>
                        <div id="size-container" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
                    </div>

                    <div class="modifier-group" id="group-flavors" style="display:none;">
                        <h4>Sabor:</h4>
                        <div id="flavors-container" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
                    </div>

                    <div class="modifier-group" id="group-milk">
                        <h4>Leche Base:</h4>
                        <button class="mod-option active" data-mod-name="MilkBase" data-mod-value="Entera" data-price-adjust="0" onclick="toggleModifier(this)">Entera</button>
                        <button class="mod-option" data-mod-name="MilkBase" data-mod-value="Deslactosada" data-price-adjust="0" onclick="toggleModifier(this)">Deslactosada</button>
                    </div>

                    <div class="modifier-group" id="group-extras">
                        <h4>Extras:</h4>
                        <div id="extras-container" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
                    </div>

                    <div class="modifier-group">
                        <h4>Descuento (%):</h4>
                        <input type="number" id="modal-discount-input" min="0" max="100" placeholder="%" class="input-discount" oninput="updateProductPrice()" onchange="updateProductPrice()">
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
                    
                    <button class="btn-main btn-add-to-cart" onclick="saveProductFromModal()">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CATÁLOGO (Agregar nuevo) -->
    <div id="catalogModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 850px; height: 80vh; overflow: hidden; display: flex; flex-direction: column;">
            <div style="flex-shrink: 0; margin-bottom: 15px;">
                <span class="close-btn" onclick="closeCatalogModal()" style="float:right;">&times;</span>
                <h2>Agregar Producto</h2>
                <input type="text" id="searchProductInput" placeholder="Buscar..." oninput="filterProductsCatalog('all')" style="width: 100%; padding: 10px; margin: 10px 0;">
                <div id="catalogCategoryTabs" class="category-tabs"></div>
            </div>
            <div id="catalogProducts" style="overflow-y: auto; flex-grow: 1; display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; padding-bottom: 20px;"></div>
        </div>
    </div>

    <script>
        // --- VARIABLES GLOBALES ---
        let currentEditVentaId = 0;
        let editProducts = []; 
        let currentEditProductIndex = -1;
        let currentProduct = null;

        // --- FUNCIONES AUXILIARES ---
        function limpiarNombreProducto(nombreCompleto) {
            let base = nombreCompleto.split('(')[0].trim();
            const sufijos = [' Chico', ' Grande', ' Pequeño', ' Mediano', ' Vaso', ' Estándar', ' Gde', ' Med', ' CH', ' G', ' M'];
            for (const sufijo of sufijos) {
                if (base.toLowerCase().endsWith(sufijo.toLowerCase())) {
                    base = base.substring(0, base.length - sufijo.length).trim();
                    break; 
                }
            }
            return base;
        }

        // --- GESTIÓN TABLA PRINCIPAL ---

        function openEditModal(id, tipoPago) {
            currentEditVentaId = id;
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('editVentaId').textContent = id;
            document.getElementById('editTipoPago').value = tipoPago;
            
            let formData = new FormData();
            formData.append('idVenta', id);

            fetch('consultarDetalleVenta.php', { method: 'POST', body: formData })
            .then(r => r.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const rows = doc.querySelectorAll('tr');
                
                editProducts = [];
                rows.forEach((row, index) => {
                    if (index === 0) return; // Skip header
                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 4) {
                        const nombreCompleto = cells[0].textContent.trim();
                        const cantidad = parseInt(cells[1].textContent.trim());
                        // Limpieza agresiva del precio para evitar errores con símbolos
                        const precioUnitario = parseFloat(cells[2].textContent.trim().replace(/[^0-9.]/g, ''));
                        
                        // CORRECCIÓN AQUÍ: Leemos el descuento en PESOS desde el atributo data-discount
                        const descuentoMonto = parseFloat(row.getAttribute('data-discount')) || 0;
                        const idProducto = parseInt(row.getAttribute('data-product-id')) || 0;
                        const idCategoria = parseInt(row.getAttribute('data-category-id')) || 0;
                        
                        // Cálculo del porcentaje para la UI (Monto / Total Bruto) * 100
                        const totalBrutoLinea = precioUnitario * cantidad;
                        let porcentajeDescuento = 0;
                        if(totalBrutoLinea > 0) {
                            porcentajeDescuento = (descuentoMonto / totalBrutoLinea) * 100;
                        }
                        // Redondeamos a 2 decimales para que se vea bonito en el input (ej. 10.00)
                        porcentajeDescuento = Math.round(porcentajeDescuento * 100) / 100;

                        editProducts.push({
                            idDetalleVenta: index,
                            idProducto: idProducto,
                            idCategoria: idCategoria,
                            nombre: nombreCompleto,
                            cantidad: cantidad,
                            // IMPORTANTE: Al cargar de BD, asumimos que precioUnitario es el FINAL (Base + Extras)
                            precioUnitario: precioUnitario,
                            basePrice: precioUnitario, 
                            descuento: porcentajeDescuento, // Guardamos PORCENTAJE en el objeto JS
                            selectedModifiers: {} 
                        });
                    }
                });
                renderEditProducts();
                calculateEditTotals();
            });
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function calculateEditTotals() {
            let subtotal = 0;
            let totalDescuentos = 0;
            
            editProducts.forEach(prod => {
                const linea = prod.precioUnitario * prod.cantidad;
                subtotal += linea;
                
                // Ahora prod.descuento es Porcentaje, así que esta fórmula es correcta
                const descuentoLinea = linea * (prod.descuento / 100);
                totalDescuentos += descuentoLinea;
            });
            
            const total = subtotal - totalDescuentos;
            
            document.getElementById('editSubtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('editDiscounts').textContent = `-$${totalDescuentos.toFixed(2)}`;
            document.getElementById('editTotal').textContent = `$${total.toFixed(2)}`;
        }

        function renderEditProducts() {
            const container = document.getElementById('productsEditList');
            container.innerHTML = '';
            
            editProducts.forEach((prod, index) => {
                const linea = prod.precioUnitario * prod.cantidad;
                const descVal = linea * (prod.descuento / 100);
                const total = linea - descVal;
                
                let modsStr = '';
                if(prod.selectedModifiers && Object.keys(prod.selectedModifiers).length > 0) {
                    const names = Object.values(prod.selectedModifiers)
                        .filter(m => !(m.value === 'Entera' && Object.keys(prod.selectedModifiers).length === 1))
                        .map(m => m.value);
                    if(names.length > 0) modsStr = `<br><small style="color:#666;">(${names.join(', ')})</small>`;
                }

                container.innerHTML += `
                    <div class="product-item">
                        <div class="product-info">
                            <strong>${prod.nombre}</strong>${modsStr}<br>
                            <small>Cant: ${prod.cantidad} | P.Unit: $${prod.precioUnitario.toFixed(2)} | Desc: ${prod.descuento}% | Total: $${total.toFixed(2)}</small>
                        </div>
                        <div class="product-actions">
                            <button class="btn-edit-product" style="background:#7d6a59; color:white; border:none; padding:5px 10px; border-radius:4px;" onclick="editProductInVenta(${index})">Editar</button>
                            <button class="btn-remove-product" style="background:#c0392b; color:white; border:none; padding:5px 10px; border-radius:4px;" onclick="removeProductFromVenta(${index})">Eliminar</button>
                        </div>
                    </div>
                `;
            });
        }

        // --- MODAL DE EDICIÓN DE PRODUCTO ---

        function editProductInVenta(index) {
            currentEditProductIndex = index;
            const prod = editProducts[index];
            currentProduct = { ...prod, isEditing: true };
            
            document.getElementById('modal-product-name').textContent = prod.nombre;
            document.getElementById('modal-quantity-input').value = currentProduct.cantidad;
            document.getElementById('modal-discount-input').value = currentProduct.descuento;

            renderModalExtras();
            renderModalFlavors();

            const isTisana = prod.nombre.toLowerCase().includes('tisana');
            const catId = prod.idCategoria;

            document.getElementById('group-flavors').style.display = isTisana ? 'block' : 'none';
            document.getElementById('group-milk').style.display = (catId == 5 || catId == 6 || isTisana) ? 'none' : 'block';
            document.getElementById('group-extras').style.display = isTisana ? 'none' : 'block';

            restoreActiveButtons();

            // Cargar Tamaños y LIMPIAR PRECIO BASE
            const sizeGroup = document.getElementById('group-size');
            sizeGroup.innerHTML = '<h4>Tamaño:</h4><div id="size-container" style="display:flex; flex-wrap:wrap; gap:8px;"></div>';
            sizeGroup.style.display = 'none'; // Ocultar por defecto

            let searchName = limpiarNombreProducto(prod.nombre); 
            
            fetch(`phps/obtenerTamanios.php?name=${encodeURIComponent(searchName)}&category=${catId}`)
                .then(r => r.json())
                .then(variants => {
                    const sizeContainer = document.getElementById('size-container');
                    
                    if(variants && variants.length > 0 && !variants.error) {
                        sizeGroup.style.display = 'block'; // Mostrar si hay variantes
                        const suffixes = ['Chico', 'Grande', 'Pequeño', 'Mediano', 'Vaso', 'Estándar', 'CH', 'G', 'M', 'Gde'];
                        let matchFound = false;

                        variants.forEach(v => {
                            const btn = document.createElement('button');
                            let disp = 'Estándar';
                            for(const s of suffixes) { if(v.nombre.includes(s)) { disp = s; break; } }
                            
                            btn.textContent = `${disp} ($${parseFloat(v.precioVenta).toFixed(2)})`;
                            btn.className = 'mod-option';
                            
                            if (v.idProducto == currentProduct.idProducto || prod.nombre === v.nombre) {
                                btn.classList.add('active');
                                currentProduct.idProducto = v.idProducto;
                                // CORRECCIÓN: Restauramos el precio base limpio de la BD
                                currentProduct.basePrice = parseFloat(v.precioVenta);
                                matchFound = true;
                            }

                            btn.onclick = (e) => {
                                sizeContainer.querySelectorAll('.mod-option').forEach(s => s.classList.remove('active'));
                                e.target.classList.add('active');
                                currentProduct.basePrice = parseFloat(v.precioVenta);
                                currentProduct.idProducto = v.idProducto; 
                                currentProduct.nombre = v.nombre; 
                                document.getElementById('modal-product-name').textContent = v.nombre;
                                updateProductPrice();
                            };
                            sizeContainer.appendChild(btn);
                        });
                    } 
                    updateProductPrice();
                })
                .catch(e => {
                    console.error("Error sizes", e);
                    sizeGroup.style.display = 'none';
                    updateProductPrice();
                });

            document.getElementById('product-modal').style.display = 'flex';
            updateProductPrice(); 
        }

        function updateProductPrice() {
            if(!currentProduct) return;
            
            let totalMods = 0;
            for(const k in currentProduct.selectedModifiers) {
                totalMods += parseFloat(currentProduct.selectedModifiers[k].adjust) || 0;
            }
            
            // Fórmula Correcta: Base Limpia + Modificadores
            const unitTotal = currentProduct.basePrice + totalMods;
            
            const discInput = document.getElementById('modal-discount-input');
            const discP = parseFloat(discInput.value) || 0;
            currentProduct.descuento = discP; 

            const qty = parseInt(document.getElementById('modal-quantity-input').value) || 1;
            currentProduct.cantidad = qty;

            const lineTotalNoDisc = unitTotal * qty;
            const discAmt = lineTotalNoDisc * (discP / 100);
            const finalTotal = lineTotalNoDisc - discAmt;

            document.getElementById('modal-base-price').textContent = `$${currentProduct.basePrice.toFixed(2)}`;
            document.getElementById('modal-modifier-adjust').textContent = `+$${totalMods.toFixed(2)}`;
            document.getElementById('modal-discount-amt').textContent = discAmt.toFixed(2);
            document.getElementById('modal-discount-applied').textContent = `-$${discAmt.toFixed(2)}`;
            document.getElementById('modal-final-price').textContent = `$${finalTotal.toFixed(2)}`;
        }

        function saveProductFromModal() {
            if (!currentProduct) return;

            let totalMods = 0;
            for(const k in currentProduct.selectedModifiers) {
                totalMods += parseFloat(currentProduct.selectedModifiers[k].adjust) || 0;
            }
            
            // Guardamos el Precio Final Unitario (Base Limpia + Extras)
            const precioFinalUnitario = currentProduct.basePrice + totalMods;

            const updatedProduct = {
                ...currentProduct,
                precioUnitario: precioFinalUnitario,
                selectedModifiers: JSON.parse(JSON.stringify(currentProduct.selectedModifiers))
            };

            if (currentEditProductIndex > -1) {
                editProducts[currentEditProductIndex] = updatedProduct;
            } else {
                editProducts.push(updatedProduct);
            }

            renderEditProducts();
            calculateEditTotals();
            closeProductModal();
        }

        // --- FUNCIONES COMUNES ---
        function renderModalExtras() { 
             const container = document.getElementById('extras-container'); container.innerHTML = '';
             if (typeof globalExtras !== 'undefined') {
                 globalExtras.forEach(ex => {
                    const btn = document.createElement('button'); btn.className = 'mod-option';
                    btn.textContent = `${ex.nombre} (+$${parseFloat(ex.precioVenta)})`;
                    btn.dataset.modName = 'Extra_' + ex.idProducto; btn.dataset.modValue = ex.nombre; btn.dataset.priceAdjust = ex.precioVenta;
                    btn.onclick = function() { toggleModifier(this); };
                    container.appendChild(btn);
                 });
             }
        }
        function renderModalFlavors() { 
             const container = document.getElementById('flavors-container'); container.innerHTML = '';
             if (typeof globalFlavors !== 'undefined') {
                 globalFlavors.forEach(f => {
                    const btn = document.createElement('button'); btn.className = 'mod-option';
                    btn.textContent = f.nombre;
                    btn.dataset.modName = 'Flavor'; btn.dataset.modValue = f.nombre; btn.dataset.priceAdjust = 0;
                    btn.onclick = function() { toggleModifier(this); };
                    container.appendChild(btn);
                 });
             }
        }
        function restoreActiveButtons() {
            document.querySelectorAll('.mod-option').forEach(b => b.classList.remove('active'));
            if (!currentProduct.selectedModifiers['MilkBase']) {
                const def = document.querySelector('#group-milk [data-mod-value="Entera"]');
                if(def) { def.classList.add('active'); currentProduct.selectedModifiers['MilkBase'] = {value:'Entera', adjust:0}; }
            }
            for (const k in currentProduct.selectedModifiers) {
                const mod = currentProduct.selectedModifiers[k];
                let btn = document.querySelector(`#group-milk [data-mod-value="${mod.value}"]`);
                if(btn) btn.classList.add('active');
                document.querySelectorAll('#extras-container .mod-option, #flavors-container .mod-option').forEach(b => {
                    if(b.dataset.modValue === mod.value) b.classList.add('active');
                });
            }
        }
        function toggleModifier(btn) {
            if (!currentProduct) return;
            const modName = btn.dataset.modName; 
            const modValue = btn.dataset.modValue;
            const priceAdjust = parseFloat(btn.dataset.priceAdjust) || 0;

            if (modName === 'MilkBase' || modName === 'Flavor') {
                btn.parentElement.querySelectorAll('.mod-option').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentProduct.selectedModifiers[modName] = { value: modValue, adjust: priceAdjust };
            } else {
                 if (modValue.toLowerCase().includes("leche")) {
                    btn.parentElement.querySelectorAll('.mod-option').forEach(sib => {
                        if (sib !== btn && sib.classList.contains('active') && sib.dataset.modValue.toLowerCase().includes("leche")) {
                            sib.classList.remove('active');
                            delete currentProduct.selectedModifiers[sib.dataset.modName];
                        }
                    });
                }
                btn.classList.toggle('active');
                if (btn.classList.contains('active')) {
                    currentProduct.selectedModifiers[modName] = { value: modValue, adjust: priceAdjust };
                } else {
                    delete currentProduct.selectedModifiers[modName];
                }
            }
            updateProductPrice();
        }
        function updateModalQuantity(chg) {
            let val = parseInt(document.getElementById('modal-quantity-input').value) || 1;
            val += chg; if(val < 1) val = 1;
            document.getElementById('modal-quantity-input').value = val;
            updateProductPrice();
        }
        function closeProductModal() { document.getElementById('product-modal').style.display = 'none'; currentProduct = null; }
        
        // --- CATÁLOGO ---
        function addNewProductToVenta() {
            renderCatalogCategories(); renderCatalogProducts('all');
            document.getElementById('catalogModal').style.display = 'block';
        }
        function closeCatalogModal() { document.getElementById('catalogModal').style.display = 'none'; }
        function renderCatalogCategories() {
            const container = document.getElementById('catalogCategoryTabs');
            container.innerHTML = '<button class="tab active" onclick="filterProductsCatalog(\'all\')">Todos</button>';
            if (typeof categoriasAMostrar !== 'undefined') {
                categoriasAMostrar.forEach(cat => {
                    container.innerHTML += `<button class="tab" onclick="filterProductsCatalog(${cat.idCategoria})">${cat.nombre}</button>`;
                });
            }
        }
        function filterProductsCatalog(catId) {
            document.querySelectorAll('#catalogCategoryTabs .tab').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
            renderCatalogProducts(catId);
        }
        function renderCatalogProducts(catId) {
            let f = Object.values(productosUnicos);
            if(catId !== 'all') f = f.filter(p => p.idCategoria == catId);
            const term = document.getElementById('searchProductInput').value.toLowerCase();
            if(term) f = f.filter(p => p.nombre_base.toLowerCase().includes(term));
            
            const c = document.getElementById('catalogProducts'); c.innerHTML = '';
            f.forEach(p => {
                const safeName = p.nombre_base.replace(/'/g, "\\'");
                c.innerHTML += `<div class="product-card" style="cursor:pointer; padding:10px; border:1px solid #ddd; border-radius:8px;" onclick="selectCatalogProduct('${p.idProducto}','${safeName}',${p.precioVenta},${p.idCategoria})">
                    <strong>${p.nombre_base}</strong><br><span style="color:#7d6a59">$${parseFloat(p.precioVenta).toFixed(2)}</span>
                </div>`;
            });
        }
        function selectCatalogProduct(id, nombre, precio, cat) {
            currentProduct = {
                idProducto: parseInt(id), idCategoria: parseInt(cat), nombre: nombre,
                cantidad: 1, basePrice: parseFloat(precio), precioUnitario: parseFloat(precio), 
                descuento: 0, selectedModifiers: {}
            };
            closeCatalogModal();
            currentEditProductIndex = -1; 
            
            document.getElementById('modal-product-name').textContent = nombre;
            document.getElementById('modal-quantity-input').value = 1;
            document.getElementById('modal-discount-input').value = 0;
            renderModalExtras(); renderModalFlavors();
            
            const isTisana = nombre.toLowerCase().includes('tisana');
            document.getElementById('group-flavors').style.display = isTisana ? 'block' : 'none';
            document.getElementById('group-milk').style.display = (cat == 5 || cat == 6 || isTisana) ? 'none' : 'block';
            document.getElementById('group-extras').style.display = isTisana ? 'none' : 'block';
            restoreActiveButtons();
            
            const sizeGroup = document.getElementById('group-size');
            sizeGroup.innerHTML = '<h4>Tamaño:</h4><div id="size-container" style="display:flex; flex-wrap:wrap; gap:8px;"></div>';
            sizeGroup.style.display = 'none';

            let searchName = limpiarNombreProducto(nombre);
            fetch(`phps/obtenerTamanios.php?name=${encodeURIComponent(searchName)}&category=${cat}`)
                .then(r=>r.json()).then(variants=>{
                     const sizeContainer = document.getElementById('size-container');
                     if(variants && variants.length > 0 && !variants.error) {
                        sizeGroup.style.display = 'block';
                        const suffixes = ['Chico', 'Grande', 'Pequeño', 'Mediano', 'Vaso', 'Estándar', 'CH', 'G', 'M', 'Gde'];
                        let match = false;
                        variants.forEach(v => {
                            const btn = document.createElement('button');
                            let disp = 'Estándar'; for(const s of suffixes) { if(v.nombre.includes(s)) { disp = s; break; } }
                            btn.textContent = `${disp} ($${parseFloat(v.precioVenta).toFixed(2)})`;
                            btn.className = 'mod-option';
                            if(v.idProducto == currentProduct.idProducto) { btn.classList.add('active'); match=true; currentProduct.basePrice=parseFloat(v.precioVenta); }
                            btn.onclick = (e) => {
                                sizeContainer.querySelectorAll('.mod-option').forEach(s=>s.classList.remove('active'));
                                e.target.classList.add('active');
                                currentProduct.basePrice = parseFloat(v.precioVenta);
                                currentProduct.idProducto = v.idProducto; currentProduct.nombre = v.nombre;
                                document.getElementById('modal-product-name').textContent = v.nombre;
                                updateProductPrice();
                            };
                            sizeContainer.appendChild(btn);
                        });
                        if(!match && variants.length>0) {
                             const first = sizeContainer.querySelector('.mod-option');
                             if(first) first.click();
                        }
                     }
                     updateProductPrice();
                })
                .catch(e => { console.error(e); updateProductPrice(); });
            
            document.getElementById('product-modal').style.display = 'flex';
            updateProductPrice();
        }

        function removeProductFromVenta(index) {
            if(confirm('¿Eliminar?')) { editProducts.splice(index, 1); renderEditProducts(); calculateEditTotals(); }
        }
        function saveVentaChanges() {
            if(editProducts.length === 0) { alert('Venta vacía'); return; }
            if(confirm('¿Guardar cambios?')) {
                const data = {
                    idVenta: currentEditVentaId,
                    tipoPago: document.getElementById('editTipoPago').value,
                    total: parseFloat(document.getElementById('editTotal').textContent.replace('$','')),
                    productos: editProducts
                };
                fetch('phps/guardar_modificacion_venta.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) })
                .then(r=>r.json()).then(d=>{ if(d.success) { alert('Guardado'); closeEditModal(); location.reload(); } else alert(d.message); });
            }
        }
        function applyFilters() { 
            let q = document.getElementById('q').value.toLowerCase();
            let pago = document.getElementById('tipoPagoFilter').value.toLowerCase();
            let from = document.getElementById('fromDate').value;
            let to = document.getElementById('toDate').value;
            let rows = document.querySelectorAll('#salesTableBody tr');
            rows.forEach(r => {
                const id = r.cells[0].textContent.toLowerCase();
                const user = r.getAttribute('data-usuario');
                const p = r.getAttribute('data-pago');
                const fecha = r.getAttribute('data-fecha');
                let show = true;
                if(q && !id.includes(q) && !user.includes(q)) show = false;
                if(pago && p !== pago) show = false;
                if(from && fecha < from) show = false;
                if(to && fecha > to) show = false;
                r.style.display = show ? '' : 'none';
            });
            let cards = document.querySelectorAll('#salesCardsList li');
            cards.forEach(c => {
                const txt = c.textContent.toLowerCase();
                const fecha = c.getAttribute('data-fecha');
                let show = true;
                if(q && !txt.includes(q)) show = false;
                if(from && fecha < from) show = false;
                if(to && fecha > to) show = false;
                c.style.display = show ? 'block' : 'none';
            });
        }
        function resetFilters() {
            document.getElementById('q').value='';
            document.getElementById('fromDate').value='';
            document.getElementById('toDate').value='';
            document.getElementById('tipoPagoFilter').value='';
            applyFilters();
        }
    </script>
</body>
</html>