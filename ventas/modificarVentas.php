<?php
session_start();

include '../conexion.php'; 

if (!isset($_SESSION['usuario'])) {
    header("Location: ../indexLogin.php");
    exit();
}

$rol = $_SESSION['rol'] ?? 'Cajero'; 

function obtenerTodasLasVentas($conn) {
    $sql = "SELECT v.idVenta, v.fechaVenta AS fecha, v.totalVenta AS total, v.tipoPago, u.nombre AS cajero 
            FROM ventas v
            JOIN usuarios u ON v.idUsuario = u.idUsuario
            ORDER BY v.fechaVenta DESC"; 

    $resultado = $conn->query($sql);
    $ventas = [];

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $ventas[] = $fila;
        }
    }
    return $ventas;
}

function obtenerDetalleVenta($conn, $idVenta) {
    $sql = "SELECT dv.*, p.nombre as nombreProducto, p.idProducto
            FROM ventasDetalle dv
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

// Cargar productos para agregar
$productos = [];
$sql = "SELECT idProducto, nombre, precioVenta, idCategoria, descripcion FROM productos WHERE idCategoria != 6 AND idCategoria != 7 ORDER BY nombre ASC";
$resultado = $conn->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $productos[] = $fila;
    }
}

// Cargar extras y sabores
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

// Agrupar productos únicos (igual que en ventas.php)
$sufijos_a_remover = [' Chico', ' Grande',' Pequeño', ' Mediano', ' CH', ' G', ' M', ' Gde', ' Med'];
$productos_unicos = [];
$conteo_categorias = [];

foreach ($productos as &$producto) {
    $nombre_base = $producto['nombre'];
    
    foreach ($sufijos_a_remover as $sufijo) {
        if (stripos($nombre_base, $sufijo) !== false && 
            substr(strtolower($nombre_base), -strlen($sufijo)) === strtolower($sufijo)) {
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

// Cargar categorías
$categorias_a_mostrar = [];
if (!empty($conteo_categorias)) {
    $ids = implode(',', array_keys($conteo_categorias));
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
        .edit-modal-content {
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .edit-form-group {
            margin-bottom: 15px;
        }
        
        .edit-form-group label {
            display: block;
            font-weight: 600;
            color: var(--cafe-oscuro);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .edit-form-group input,
        .edit-form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            box-sizing: border-box;
        }
        
        .edit-form-group input:focus,
        .edit-form-group select:focus {
            outline: none;
            border-color: var(--cafe-medio);
            box-shadow: 0 0 0 3px rgba(125, 106, 89, 0.15);
        }
        
        .products-list {
            background: var(--crema-fondo);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .product-item {
            background: white;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid var(--cafe-medio);
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-edit-product {
            padding: 6px 12px;
            background: var(--cafe-medio);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        
        .btn-remove-product {
            padding: 6px 12px;
            background: var(--alerta);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        
        .btn-add-product {
            padding: 10px 20px;
            background: var(--cafe-medio);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin: 15px 0;
        }
        
        .summary-box {
            background: var(--crema-fondo);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .summary-line {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 1rem;
        }
        
        .summary-line.total {
            border-top: 2px solid var(--cafe-medio);
            padding-top: 10px;
            margin-top: 10px;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--cafe-oscuro);
        }
        
        .edit-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .btn-save {
            padding: 12px 30px;
            background: var(--cafe-medio);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .btn-cancel-edit {
            padding: 12px 30px;
            background: #999;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
        }
    </style>
</head>
<body onload="applyFilters()">
    <div class="container">
        <div class="header">
            <button class="icon-btn" onclick="window.location.href='../indexhome.php'">&#8592;</button>
            <div>
                <h1>Modificar Ventas</h1>
                <p class="subtitle">Editar detalles de transacciones</p>
            </div>
        </div>

        <div class="controls">
            <div class="control-group">
                <label>Buscar (ID/Cajero):</label>
                <input type="text" id="q" placeholder="Ej: 25 o Juan">
            </div>
            
            <div class="control-group">
                <label>Tipo Pago:</label>
                <select id="tipoPagoFilter">
                    <option value="">Todos</option>
                    <?php foreach ($tiposDePago as $tipo): ?>
                        <option value="<?php echo htmlspecialchars($tipo); ?>"><?php echo htmlspecialchars($tipo); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="control-group">
                <label>Desde:</label>
                <input type="date" id="fromDate">
            </div>
            
            <div class="control-group">
                <label>Hasta:</label>
                <input type="date" id="toDate">
            </div>
            
            <div class="btn-group">
                <button class="btn btn-search" onclick="applyFilters()">Buscar</button>
                <button class="btn btn-reset" onclick="resetFilters()">Limpiar</button>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="sales-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th> 
                        <th>Fecha y Hora</th>
                        <th>Cajero</th>
                        <th style="text-align: center;">Tipo Pago</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody">
                    <?php foreach ($ventas as $v): 
                        $fechaF = date('d/m/Y h:i A', strtotime($v['fecha']));
                        $fechaData = date('Y-m-d', strtotime($v['fecha']));
                    ?>
                    <tr onclick="openEditModal(<?php echo $v['idVenta']; ?>, '<?php echo htmlspecialchars($fechaF, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($v['total'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($v['tipoPago'], ENT_QUOTES); ?>')"
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

        <ul class="cards" id="salesCardsList">
            <?php foreach ($ventas as $v): 
                $fechaF = date('d/m/Y h:i A', strtotime($v['fecha']));
                $fechaData = date('Y-m-d', strtotime($v['fecha']));
            ?>
            <li class="sale-card" 
                onclick="openEditModal(<?php echo $v['idVenta']; ?>, '<?php echo htmlspecialchars($fechaF, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($v['total'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($v['tipoPago'], ENT_QUOTES); ?>')"
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

    <!-- Modal Catálogo de Productos para Agregar -->
    <div id="catalogModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 850px; max-height: 90vh; overflow-y: auto; background: white; border-radius: 12px; padding: 25px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15); position: relative;">
            <span class="close-btn" onclick="closeCatalogModal()" style="position: absolute; top: 15px; right: 15px; cursor: pointer; font-size: 28px; color: var(--cafe-oscuro); width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">&times;</span>
            
            <h2 style="color: var(--cafe-oscuro); margin: 0 0 20px 0; border-bottom: 2px solid var(--crema-claro); padding-bottom: 10px;">Agregar Producto a la Venta</h2>
            
            <div style="margin-bottom: 15px;">
                <input type="text" id="searchProductInput" placeholder="Buscar producto..." 
                       style="width: 100%; padding: 12px; border: 2px solid var(--crema-claro); border-radius: 6px; font-size: 1rem; box-sizing: border-box; transition: border-color 0.3s;"
                       onfocus="this.style.borderColor='var(--cafe-medio)'"
                       onblur="this.style.borderColor='var(--crema-claro)'"
                       oninput="filterProductsCatalog('all')">
            </div>
            
            <div style="margin-bottom: 20px;">
                <div class="category-tabs" id="catalogCategoryTabs" style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <button class="tab active" onclick="filterProductsCatalog('all')" style="padding: 8px 16px; border: 2px solid var(--cafe-medio); background: var(--cafe-medio); color: white; border-radius: 6px; cursor: pointer; font-weight: 600;">Todos</button>
                </div>
            </div>
            
            <div id="catalogProducts" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px;">
                <!-- Los productos se cargarán aquí -->
            </div>
        </div>
    </div>

    <!-- Modal de Edición de Venta -->
    <div id="editModal" class="modal">
        <div class="modal-content edit-modal-content" style="position: relative;">
            <span class="close-btn" onclick="closeEditModal()" style="position: absolute; top: 15px; right: 15px;">&times;</span>
            <h2 style="color: var(--cafe-oscuro); border-bottom: 2px solid var(--crema-claro); padding-bottom: 10px; margin-top:0;">
                Modificar Venta #<span id="editVentaId"></span>
            </h2>
            
            <div class="edit-form-group">
                <label>Tipo de Pago:</label>
                <select id="editTipoPago">
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Transferencia">Transferencia</option>
                </select>
            </div>
            
            <div>
                <h3 style="color: var(--cafe-oscuro); margin-top: 20px;">Productos</h3>
                <div id="productsEditList" class="products-list">
                    <!-- Los productos se cargarán aquí -->
                </div>
                <button class="btn-add-product" onclick="addNewProductToVenta()">+ Agregar Producto</button>
            </div>
            
            <div class="summary-box">
                <div class="summary-line">
                    <span>Subtotal:</span>
                    <span id="editSubtotal">$0.00</span>
                </div>
                <div class="summary-line">
                    <span>Descuentos:</span>
                    <span id="editDiscounts">-$0.00</span>
                </div>
                <div class="summary-line total">
                    <span>TOTAL:</span>
                    <span id="editTotal">$0.00</span>
                </div>
            </div>
            
            <div class="edit-buttons">
                <button class="btn-save" onclick="saveVentaChanges()">Guardar Cambios</button>
                <button class="btn-cancel-edit" onclick="closeEditModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Personalización de Producto (desde ventas.php) -->
    <div id="product-modal" class="modal-overlay">
        <div class="modal-content product-modal-content" style="position: relative;">
            <button class="modal-close-btn" onclick="closeProductModal()" style="position: absolute; top: 10px; right: 10px; z-index: 10;">✖</button>
            <h2 class="modal-title" style="margin-bottom:5px;">Editar: <span id="modal-product-name"></span></h2>
            
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
                        <button class="mod-option active" data-mod-name="MilkBase" data-mod-value="Entera" data-price-adjust="0" onclick="toggleModifier(this, 'MilkBase', 'Entera', 0)">Entera</button>
                        <button class="mod-option" data-mod-name="MilkBase" data-mod-value="Deslactosada" data-price-adjust="0" onclick="toggleModifier(this, 'MilkBase', 'Deslactosada', 0)">Deslactosada</button>
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
                    
                    <button class="btn-main btn-add-to-cart" onclick="saveVentaChanges()">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentEditVentaId = 0;
        let editProducts = [];
        let currentEditProductIndex = -1;
        let currentProduct = null;

        function openEditModal(id, fecha, total, tipoPago) {
            console.log('openEditModal called with:', {id, fecha, total, tipoPago});
            currentEditVentaId = id;
            const modal = document.getElementById('editModal');
            console.log('Modal element:', modal);
            if (!modal) {
                console.error('Modal element not found!');
                return;
            }
            modal.style.display = 'block';
            document.getElementById('editVentaId').textContent = id;
            document.getElementById('editTipoPago').value = tipoPago;
            
            // Cargar detalles de la venta
            let formData = new FormData();
            formData.append('idVenta', id);

            fetch('consultarDetalleVenta.php', { method: 'POST', body: formData })
            .then(r => r.text())
            .then(html => {
                // Parsear la tabla HTML para extraer productos
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
                        const precioUnitario = parseFloat(cells[2].textContent.trim().replace('$', ''));
                        const descuento = parseFloat(row.getAttribute('data-discount')) || 0;
                        const idProducto = parseInt(row.getAttribute('data-product-id')) || null;
                        
                        editProducts.push({
                            idDetalleVenta: index,
                            idProducto: idProducto,
                            nombre: nombreCompleto,
                            cantidad: cantidad,
                            precioUnitario: precioUnitario,
                            descuento: descuento,
                            selectedModifiers: {},
                            discountPercentage: descuento
                        });
                    }
                });
                
                renderEditProducts();
                calculateEditTotals();
            })
            .catch(e => {
                console.error(e);
                alert('Error al cargar los detalles de la venta');
            });
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            currentEditProductIndex = -1;
        }

        function renderEditProducts() {
            const container = document.getElementById('productsEditList');
            container.innerHTML = '';
            
            editProducts.forEach((prod, index) => {
                const linea = prod.precioUnitario * prod.cantidad;
                const descuentoAmt = linea * (prod.descuento / 100);
                const total = (linea - descuentoAmt).toFixed(2);
                
                container.innerHTML += `
                    <div class="product-item">
                        <div class="product-info">
                            <strong>${prod.nombre}</strong><br>
                            <small>Cantidad: ${prod.cantidad} | Precio: $${prod.precioUnitario.toFixed(2)} | Desc: ${prod.descuento}% | Total: $${total}</small>
                        </div>
                        <div class="product-actions">
                            <button class="btn-edit-product" onclick="editProductInVenta(${index})">Editar</button>
                            <button class="btn-remove-product" onclick="removeProductFromVenta(${index})">Eliminar</button>
                        </div>
                    </div>
                `;
            });
        }

        function editProductInVenta(index) {
            currentEditProductIndex = index;
            const prod = editProducts[index];
            
            currentProduct = {
                id: prod.idDetalleVenta,
                name: prod.nombre,
                basePrice: prod.precioUnitario,
                quantity: prod.cantidad,
                selectedModifiers: prod.selectedModifiers || {},
                discountPercentage: prod.descuento,
                isEditing: true
            };
            
            // Preparar el modal
            document.getElementById('modal-product-name').textContent = prod.nombre;
            document.getElementById('modal-quantity-input').value = prod.cantidad;
            document.getElementById('modal-discount-input').value = prod.descuento;
            
            // Mostrar opciones según corresponda
            document.getElementById('group-size').style.display = 'none';
            document.getElementById('group-milk').style.display = 'block';
            document.getElementById('group-extras').style.display = globalExtras.length > 0 ? 'block' : 'none';
            document.getElementById('group-flavors').style.display = globalFlavors.length > 0 ? 'block' : 'none';
            
            // Llenar opciones de extras
            const extrasContainer = document.getElementById('extras-container');
            extrasContainer.innerHTML = '';
            globalExtras.forEach(extra => {
                const isSelected = currentProduct.selectedModifiers['Extra_' + extra.idProducto] ? 'active' : '';
                extrasContainer.innerHTML += `
                    <button class="mod-option ${isSelected}" 
                            data-mod-name="Extra" 
                            data-mod-value="${extra.nombre}" 
                            data-price-adjust="${extra.precioVenta}"
                            onclick="toggleModifier(this, 'Extra_${extra.idProducto}', '${extra.nombre}', ${extra.precioVenta})">
                        ${extra.nombre}
                    </button>
                `;
            });
            
            // Llenar opciones de sabores
            const flavorsContainer = document.getElementById('flavors-container');
            flavorsContainer.innerHTML = '';
            globalFlavors.forEach(flavor => {
                const isSelected = currentProduct.selectedModifiers['Flavor_' + flavor.idProducto] ? 'active' : '';
                flavorsContainer.innerHTML += `
                    <button class="mod-option ${isSelected}" 
                            data-mod-name="Flavor" 
                            data-mod-value="${flavor.nombre}" 
                            data-price-adjust="${flavor.precioVenta}"
                            onclick="toggleModifier(this, 'Flavor_${flavor.idProducto}', '${flavor.nombre}', ${flavor.precioVenta})">
                        ${flavor.nombre}
                    </button>
                `;
            });
            
            // Inicializar botones de leche seleccionados
            document.querySelectorAll('#group-milk .mod-option').forEach(btn => {
                btn.classList.remove('active');
                if (currentProduct.selectedModifiers['MilkBase'] && 
                    currentProduct.selectedModifiers['MilkBase'].value === btn.dataset.modValue) {
                    btn.classList.add('active');
                }
            });
            
            updateProductPrice();
            document.getElementById('product-modal').style.display = 'flex';
            
            // El evento onclick para guardar cambios está en el HTML
        }

        function closeProductModal() {
            document.getElementById('product-modal').style.display = 'none';
            currentProduct = null;
            currentEditProductIndex = -1;
        }

        function toggleModifier(buttonElement, modifierKey, modifierValue, priceAdjust) {
            if (!currentProduct) return;
            
            const modType = buttonElement.dataset.modName;
            
            if (modType === 'MilkBase') {
                // Solo una opción de leche base activa a la vez
                document.querySelectorAll('#group-milk .mod-option').forEach(btn => btn.classList.remove('active'));
                buttonElement.classList.add('active');
                currentProduct.selectedModifiers['MilkBase'] = { value: modifierValue, adjust: parseFloat(priceAdjust) };
            } else if (modType === 'Extra') {
                // Múltiples extras pueden estar activos
                buttonElement.classList.toggle('active');
                if (buttonElement.classList.contains('active')) {
                    currentProduct.selectedModifiers[modifierKey] = { value: modifierValue, adjust: parseFloat(priceAdjust) };
                } else {
                    delete currentProduct.selectedModifiers[modifierKey];
                }
            } else if (modType === 'Flavor') {
                // Solo un sabor activo a la vez
                document.querySelectorAll('#group-flavors .mod-option').forEach(btn => btn.classList.remove('active'));
                buttonElement.classList.add('active');
                currentProduct.selectedModifiers['Flavor'] = { value: modifierValue, adjust: parseFloat(priceAdjust) };
            }
            
            updateProductPrice();
        }

        function removeProductFromVenta(index) {
            if (confirm('¿Eliminar este producto?')) {
                editProducts.splice(index, 1);
                renderEditProducts();
                calculateEditTotals();
            }
        }

        function addNewProductToVenta() {
            // Inicializar categorías en el modal del catálogo
            renderCatalogCategories();
            renderCatalogProducts('all');
            document.getElementById('catalogModal').style.display = 'block';
        }

        function closeCatalogModal() {
            document.getElementById('catalogModal').style.display = 'none';
            document.getElementById('searchProductInput').value = '';
        }

        function renderCatalogCategories() {
            const container = document.getElementById('catalogCategoryTabs');
            container.innerHTML = '<button class="tab active" onclick="filterProductsCatalog(\'all\')" style="padding: 8px 16px; border: 2px solid var(--cafe-medio); background: var(--cafe-medio); color: white; border-radius: 6px; cursor: pointer;">Todos</button>';
            
            categoriasAMostrar.forEach(cat => {
                container.innerHTML += `
                    <button class="tab" data-category-id="${cat.idCategoria}" 
                            onclick="filterProductsCatalog(${cat.idCategoria})"
                            style="padding: 8px 16px; border: 2px solid var(--cafe-claro); background: white; color: var(--cafe-oscuro); border-radius: 6px; cursor: pointer; transition: all 0.3s;">
                        ${cat.nombre}
                    </button>
                `;
            });
        }

        function renderCatalogProducts(categoryFilter = 'all') {
            let filtered = Object.values(productosUnicos);
            
            if (categoryFilter !== 'all') {
                filtered = filtered.filter(p => p.idCategoria == categoryFilter);
            }
            
            const searchTerm = document.getElementById('searchProductInput').value.toLowerCase();
            if (searchTerm) {
                filtered = filtered.filter(p => p.nombre_base.toLowerCase().includes(searchTerm));
            }
            
            const container = document.getElementById('catalogProducts');
            
            if (filtered.length === 0) {
                container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--cafe-medio);">No hay productos disponibles</p>';
                return;
            }
            
            container.innerHTML = '';
            filtered.forEach(prod => {
                const precioNum = parseFloat(prod.precioVenta);
                container.innerHTML += `
                    <div class="product-card clickable" 
                         style="padding: 12px; background: white; border-radius: 8px; border: 2px solid var(--crema-claro); cursor: pointer; transition: all 0.3s;"
                         onclick="selectProductFromCatalog(
                            '${prod.idProducto}',
                            '${prod.nombre_base.replace(/'/g, "\\'").replace(/"/g, '\\"')}',
                            ${precioNum},
                            ${prod.idCategoria},
                            '${(prod.descripcion || '').replace(/'/g, "\\'").replace(/"/g, '\\"')}'
                        )">
                        <p class="product-name" style="margin: 0 0 8px 0; font-weight: 600; color: var(--cafe-oscuro);">${prod.nombre_base}</p>
                        <p class="product-price" style="margin: 0; color: var(--cafe-medio); font-weight: 700; font-size: 1.1rem;">$${precioNum.toFixed(2)}</p>
                    </div>
                `;
            });
        }

        function filterProductsCatalog(categoryId = 'all') {
            // Actualizar botones activos
            document.querySelectorAll('#catalogCategoryTabs .tab').forEach(btn => {
                btn.style.background = 'white';
                btn.style.color = 'var(--cafe-oscuro)';
                btn.style.border = '2px solid var(--crema-claro)';
            });
            
            if (categoryId === 'all') {
                document.querySelectorAll('#catalogCategoryTabs .tab')[0].style.background = 'var(--cafe-medio)';
                document.querySelectorAll('#catalogCategoryTabs .tab')[0].style.color = 'white';
                document.querySelectorAll('#catalogCategoryTabs .tab')[0].style.border = '2px solid var(--cafe-medio)';
            } else {
                event.target.style.background = 'var(--cafe-medio)';
                event.target.style.color = 'white';
                event.target.style.border = '2px solid var(--cafe-medio)';
            }
            
            renderCatalogProducts(categoryId);
        }

        function selectProductFromCatalog(idProducto, nombre, precio, idCategoria, descripcion) {
            // Agregar el producto a la lista de edición
            const nuevoProducto = {
                idDetalleVenta: Date.now(), // ID temporal único
                idProducto: idProducto,
                nombre: nombre,
                cantidad: 1,
                precioUnitario: precio,
                descuento: 0,
                selectedModifiers: {},
                discountPercentage: 0
            };
            
            editProducts.push(nuevoProducto);
            const nuevoIndex = editProducts.length - 1;
            
            // Abrir el modal de edición del producto recién agregado
            closeCatalogModal();
            setTimeout(() => {
                editProductInVenta(nuevoIndex);
            }, 300);
        }


        function calculateEditTotals() {
            let subtotal = 0;
            let totalDescuentos = 0;
            
            editProducts.forEach(prod => {
                // Calcular ajustes por modificadores
                let modAdjust = 0;
                if (prod.selectedModifiers && typeof prod.selectedModifiers === 'object') {
                    Object.values(prod.selectedModifiers).forEach(mod => {
                        if (mod && typeof mod === 'object' && mod.adjust) {
                            modAdjust += parseFloat(mod.adjust) || 0;
                        }
                    });
                }
                
                // Precio unitario con modificadores
                const precioConMods = prod.precioUnitario + modAdjust;
                const linea = precioConMods * prod.cantidad;
                subtotal += linea;
                
                // Descuento sobre la línea con modificadores
                const descuentoLinea = linea * (parseFloat(prod.descuento || 0) / 100);
                totalDescuentos += descuentoLinea;
            });
            
            const total = subtotal - totalDescuentos;
            
            document.getElementById('editSubtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('editDiscounts').textContent = `-$${totalDescuentos.toFixed(2)}`;
            document.getElementById('editTotal').textContent = `$${total.toFixed(2)}`;
        }

        function updateProductPrice() {
            if(!currentProduct) return;
            
            // Calcular ajustes por modificadores
            let totalMods = 0;
            Object.values(currentProduct.selectedModifiers).forEach(m => {
                if (m && typeof m === 'object' && m.adjust) {
                    totalMods += parseFloat(m.adjust) || 0;
                }
            });
            
            // Precio unitario con modificadores
            let unitTotal = currentProduct.basePrice + totalMods;
            
            // Obtener descuento del input
            const discInput = document.getElementById('modal-discount-input');
            const discP = parseFloat(discInput.value) || 0;
            currentProduct.discountPercentage = discP;
            
            // Calcular total sin descuento (cantidad * precio unitario con mods)
            const lineTotalNoDisc = unitTotal * currentProduct.quantity;
            const discAmt = lineTotalNoDisc * (discP / 100);
            const finalTotal = lineTotalNoDisc - discAmt;

            // Actualizar display
            document.getElementById('modal-base-price').textContent = `$${currentProduct.basePrice.toFixed(2)}`;
            document.getElementById('modal-modifier-adjust').textContent = totalMods > 0 ? `+$${totalMods.toFixed(2)}` : `+$0.00`;
            document.getElementById('modal-discount-amt').textContent = discAmt.toFixed(2);
            document.getElementById('modal-discount-applied').textContent = `-$${discAmt.toFixed(2)}`;
            document.getElementById('modal-final-price').textContent = `$${finalTotal.toFixed(2)}`;
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

        function saveVentaChanges() {
            // Si se está editando un producto en el modal, guardar los cambios del producto
            if (currentEditProductIndex !== null && currentEditProductIndex !== undefined) {
                const cantidad = parseInt(document.getElementById('modal-quantity-input').value) || 1;
                const descuento = parseFloat(document.getElementById('modal-discount-input').value) || 0;
                const selectedMods = currentProduct.selectedModifiers || {};
                
                // Calcular ajustes por modificadores
                let modAdjust = 0;
                Object.values(selectedMods).forEach(mod => {
                    if (mod && typeof mod === 'object' && mod.adjust) {
                        modAdjust += parseFloat(mod.adjust) || 0;
                    }
                });
                
                // Guardar cambios en el producto
                const prod = editProducts[currentEditProductIndex];
                prod.cantidad = cantidad;
                prod.descuento = descuento;
                prod.selectedModifiers = selectedMods;
                prod.precioUnitario = currentProduct.basePrice + modAdjust; // Guardar precio con modificadores
                
                renderEditProducts();
                calculateEditTotals();
                closeProductModal();
                
                currentEditProductIndex = null;
                currentProduct = null;
            } else {
                // Guardar cambios de la venta completa en la BD
                if (confirm('¿Guardar cambios en la venta #' + currentEditVentaId + '?')) {
                    const tipoPago = document.getElementById('editTipoPago').value;
                    const totalText = document.getElementById('editTotal').textContent.replace('$', '');
                    const total = parseFloat(totalText);
                    
                    // Preparar datos para enviar
                    const datosVenta = {
                        idVenta: currentEditVentaId,
                        tipoPago: tipoPago,
                        total: total,
                        productos: editProducts.map(p => ({
                            idProducto: p.idProducto || null,
                            nombre: p.nombre,
                            cantidad: p.cantidad,
                            precioUnitario: p.precioUnitario,
                            descuento: p.descuento,
                            selectedModifiers: p.selectedModifiers || {}
                        }))
                    };
                    
                    // Enviar al servidor
                    fetch('phps/guardar_modificacion_venta.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(datosVenta)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('¡Venta actualizada exitosamente!');
                            closeEditModal();
                            // Recargar la página para mostrar cambios
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al guardar cambios: ' + error);
                    });
                }
            }
        }

        window.onclick = function(e) {
            const editModal = document.getElementById('editModal');
            const productModal = document.getElementById('product-modal');
            
            if (e.target === editModal) closeEditModal();
            if (e.target === productModal) closeProductModal();
        }

        // LÓGICA DE FILTRADO
        function applyFilters() {
            let q = document.getElementById('q').value.toLowerCase();
            let pago = document.getElementById('tipoPagoFilter').value.toLowerCase();
            let from = document.getElementById('fromDate').value;
            let to = document.getElementById('toDate').value;

            let items = document.querySelectorAll('#salesTableBody tr, #salesCardsList li');

            items.forEach(el => {
                const idVenta = el.tagName === 'TR' ? el.cells[0].textContent : el.querySelector('b').textContent.replace('#','');
                const elUser = el.getAttribute('data-usuario');
                const elPago = el.getAttribute('data-pago');
                const elFecha = el.getAttribute('data-fecha');
                
                let show = true;

                if (q && !(idVenta.includes(q) || elUser.includes(q))) show = false;
                if (pago && elPago !== pago) show = false;
                if (from && elFecha < from) show = false;
                if (to && elFecha > to) show = false;

                el.style.display = show ? '' : 'none';
            });
        }

        function resetFilters() {
            document.getElementById('q').value = '';
            document.getElementById('tipoPagoFilter').value = '';
            document.getElementById('fromDate').value = '';
            document.getElementById('toDate').value = '';
            applyFilters();
        }
    </script>
</body>
</html>