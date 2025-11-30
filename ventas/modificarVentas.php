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
        <div class="modal-content" style="max-width: 850px; max-height: 90vh; overflow-y: auto; background: white; border-radius: 12px; padding: 25px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15); position: relative; z-index: 3501;">
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
                    <h4>Elige un Sabor:</h4>
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
                
                <button class="btn-main btn-add-to-cart" onclick="saveProductFromModal()">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

    <script>
    // --- VARIABLES GLOBALES ---
    let currentEditVentaId = 0;
    let editProducts = []; // Array principal de productos en edición
    let currentEditProductIndex = -1; // Índice del producto que se está editando en el array
    let currentProduct = null; // Objeto temporal para el Modal

    // --- FUNCIONES DEL MODAL DE EDICIÓN DE VENTA (TABLA PRINCIPAL) ---
function limpiarNombreProducto(nombreCompleto) {
    // 1. Quitar paréntesis o notas extras si las hubiera
    let base = nombreCompleto.split('(')[0].trim();
    
    // 2. Lista de sufijos idéntica a la que usas en ventas.php
    // Ordenada por longitud para asegurar que quite "Grande" antes que "G"
    const sufijos = [' Chico', ' Grande', ' Pequeño', ' Mediano', ' Vaso', ' Estándar', ' Gde', ' Med', ' CH', ' G', ' M'];

    // 3. Buscar y eliminar sufijo si existe al final (case-insensitive)
    for (const sufijo of sufijos) {
        if (base.toLowerCase().endsWith(sufijo.toLowerCase())) {
            // Cortar el sufijo del final
            base = base.substring(0, base.length - sufijo.length).trim();
            break; // Solo quitamos el primero que coincida
        }
    }
    return base;
}
    function openEditModal(id, fecha, total, tipoPago) {
        currentEditVentaId = id;
        const modal = document.getElementById('editModal');
        modal.style.display = 'block';
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
                if (index === 0) return; // Saltar header
                const cells = row.querySelectorAll('td');
                if (cells.length >= 4) {
                    const nombreCompleto = cells[0].textContent.trim();
                    const cantidad = parseInt(cells[1].textContent.trim());
                    // Limpieza agresiva del precio para evitar errores con símbolos
                    const precioUnitario = parseFloat(cells[2].textContent.trim().replace(/[^0-9.]/g, ''));
                    
                    const descuento = parseFloat(row.getAttribute('data-discount')) || 0;
                    const idProducto = parseInt(row.getAttribute('data-product-id')) || 0;
                    const idCategoria = parseInt(row.getAttribute('data-category-id')) || 0;
                    
                    // Intentar recuperar modificadores si existen en atributos data (opcional, si el backend lo soporta)
                    // Por ahora iniciamos vacíos para ventas antiguas
                    editProducts.push({
                        idDetalleVenta: index, // ID temporal para UI
                        idProducto: idProducto,
                        idCategoria: idCategoria,
                        nombre: nombreCompleto,
                        cantidad: cantidad,
                        precioUnitario: precioUnitario, // Precio final unitario con extras
                        basePrice: precioUnitario, // Se ajustará al editar
                        descuento: descuento,
                        selectedModifiers: {}, // Se llenará al editar
                        discountPercentage: descuento
                    });
                }
            });
            renderEditProducts();
            calculateEditTotals();
        })
        .catch(e => {
            console.error(e);
            alert('Error al cargar detalles.');
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
            
            // Generar string de modificadores para visualización
            let modsStr = '';
            if(prod.selectedModifiers) {
                const mods = [];
                for(const k in prod.selectedModifiers) {
                    const m = prod.selectedModifiers[k];
                    if(k === 'MilkBase' && m.value === 'Entera') continue;
                    mods.push(m.value);
                }
                if(mods.length > 0) modsStr = `<br><small style="color:#666;">(${mods.join(', ')})</small>`;
            }

            container.innerHTML += `
                <div class="product-item">
                    <div class="product-info">
                        <strong>${prod.nombre}</strong>${modsStr}<br>
                        <small>Cant: ${prod.cantidad} | P.Unit: $${prod.precioUnitario.toFixed(2)} | Desc: ${prod.descuento}% | Total: $${total}</small>
                    </div>
                    <div class="product-actions">
                        <button class="btn-edit-product" onclick="editProductInVenta(${index})">Editar</button>
                        <button class="btn-remove-product" onclick="removeProductFromVenta(${index})">Eliminar</button>
                    </div>
                </div>
            `;
        });
    }

    // --- LÓGICA DEL MODAL DE PRODUCTO (LO QUE QUERÍAS ARREGLAR) ---

   function editProductInVenta(index) {
        currentEditProductIndex = index;
        const prod = editProducts[index];
        
        // 1. Configurar objeto temporal
        currentProduct = {
            ...prod, 
            isEditing: true
        };
        
        if(!currentProduct.basePrice) currentProduct.basePrice = prod.precioUnitario;

        // 2. Llenar Modal UI
        document.getElementById('modal-product-name').textContent = prod.nombre;
        document.getElementById('modal-quantity-input').value = currentProduct.cantidad;
        document.getElementById('modal-discount-input').value = currentProduct.descuento;

        // 3. Renderizar Extras y Sabores
        renderModalExtras();
        renderModalFlavors();

        // 4. Lógica de Visibilidad (Categorías)
        const isTisana = prod.nombre.toLowerCase().includes('tisana');
        const catId = prod.idCategoria;

        document.getElementById('group-flavors').style.display = isTisana ? 'block' : 'none';
        
        const showMilk = !(catId == 5 || catId == 6 || isTisana);
        document.getElementById('group-milk').style.display = showMilk ? 'block' : 'none';

        document.getElementById('group-extras').style.display = isTisana ? 'none' : 'block';

        // 5. Restaurar botones activos
        restoreActiveButtons();

        // 6. Cargar Tamaños (CORREGIDO: Lógica de visualización)
        const sizeGroup = document.getElementById('group-size');
        
        // Reiniciamos el contenedor y lo OCULTAMOS por defecto
        sizeGroup.innerHTML = '<h4>Tamaño:</h4><div id="size-container" style="display:flex; flex-wrap:wrap; gap:8px;"></div>';
        sizeGroup.style.display = 'none'; // <--- CAMBIO IMPORTANTE: Oculto al inicio

        // Obtenemos el nombre limpio (base)
        let searchName = limpiarNombreProducto(prod.nombre); 
        
        fetch(`phps/obtenerTamanios.php?name=${encodeURIComponent(searchName)}&category=${catId}`)
            .then(r => r.json())
            .then(variants => {
                // Obtenemos la referencia al contenedor recién creado
                const sizeContainer = document.getElementById('size-container');
                
                // Si no hay variantes, o es un error, o el array está vacío:
                if(!variants || variants.length === 0 || variants.error) {
                    sizeGroup.style.display = 'none'; // Se mantiene oculto
                } else {
                    // Si encontramos variantes, AHORA sí mostramos la caja
                    sizeGroup.style.display = 'block';
                    
                    const suffixes = ['Chico', 'Grande', 'Pequeño', 'Mediano', 'Vaso', 'Estándar', 'CH', 'G', 'M', 'Gde'];
                    let matchFound = false;

                    variants.forEach(v => {
                        const btn = document.createElement('button');
                        let disp = 'Estándar';
                        for(const s of suffixes) { if(v.nombre.includes(s)) { disp = s; break; } }
                        
                        btn.textContent = `${disp} ($${parseFloat(v.precioVenta).toFixed(2)})`;
                        btn.className = 'mod-option';
                        
                        // Verificar selección actual
                        if (v.idProducto == currentProduct.idProducto || prod.nombre === v.nombre) {
                            btn.classList.add('active');
                            currentProduct.idProducto = v.idProducto;
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
                    
                    // Si hay tamaños pero ninguno coincide (ej. producto nuevo), seleccionar el primero
                    if (!matchFound && variants.length > 0) {
                         // Opcional: sizeContainer.firstChild.click(); 
                    }
                }
                updateProductPrice();
            })
            .catch(e => {
                console.error("Error sizes", e);
                sizeGroup.style.display = 'none'; // Ocultar en caso de error
                updateProductPrice();
            });

        document.getElementById('product-modal').style.display = 'flex';
        updateProductPrice();
    }
    
    function renderModalExtras() {
        const container = document.getElementById('extras-container');
        container.innerHTML = '';
        if (typeof globalExtras !== 'undefined') {
            globalExtras.forEach(ex => {
                const btn = document.createElement('button');
                btn.className = 'mod-option';
                btn.textContent = `${ex.nombre} (+$${parseFloat(ex.precioVenta)})`;
                btn.dataset.modName = 'Extra_' + ex.idProducto;
                btn.dataset.modValue = ex.nombre;
                btn.dataset.priceAdjust = ex.precioVenta;
                btn.onclick = function() { toggleModifier(this); };
                container.appendChild(btn);
            });
        }
    }

    function renderModalFlavors() {
        const container = document.getElementById('flavors-container');
        container.innerHTML = '';
        if (typeof globalFlavors !== 'undefined') {
            globalFlavors.forEach(f => {
                const btn = document.createElement('button');
                btn.className = 'mod-option';
                btn.textContent = f.nombre;
                btn.dataset.modName = 'Flavor';
                btn.dataset.modValue = f.nombre;
                btn.dataset.priceAdjust = 0;
                btn.onclick = function() { toggleModifier(this); };
                container.appendChild(btn);
            });
        }
    }

    function restoreActiveButtons() {
        // Limpiar todo primero
        document.querySelectorAll('.mod-option').forEach(b => b.classList.remove('active'));

        // Leche por defecto si no hay nada seleccionado
        if (!currentProduct.selectedModifiers['MilkBase']) {
            const defMilk = document.querySelector('#group-milk [data-mod-value="Entera"]');
            if(defMilk) {
                defMilk.classList.add('active');
                currentProduct.selectedModifiers['MilkBase'] = { value: 'Entera', adjust: 0 };
            }
        }

        // Iterar modificadores guardados y activar botones
        for (const key in currentProduct.selectedModifiers) {
            const mod = currentProduct.selectedModifiers[key];
            
            // Buscar en leche
            let btn = document.querySelector(`#group-milk [data-mod-value="${mod.value}"]`);
            if(btn) btn.classList.add('active');

            // Buscar en extras
            // Nota: Para extras usamos dataset.modName que es 'Extra_ID', pero aquí buscamos por valor para simplificar
            // O mejor, buscamos por el texto o data attribute especifico
            const extraBtns = document.querySelectorAll('#extras-container .mod-option');
            extraBtns.forEach(b => {
                if(b.dataset.modValue === mod.value) b.classList.add('active');
            });

            // Buscar en sabores
            const flavBtns = document.querySelectorAll('#flavors-container .mod-option');
            flavBtns.forEach(b => {
                if(b.dataset.modValue === mod.value) b.classList.add('active');
            });
        }
    }

    function toggleModifier(btn) {
        if (!currentProduct) return;
        
        const modName = btn.dataset.modName; // Ej: 'MilkBase' o 'Extra_5'
        const modValue = btn.dataset.modValue;
        const priceAdjust = parseFloat(btn.dataset.priceAdjust) || 0;

        // Lógica Leche y Sabores (Exclusivos)
        if (modName === 'MilkBase' || modName === 'Flavor') {
            // Quitar active a hermanos
            btn.parentElement.querySelectorAll('.mod-option').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            // Asignar (Sobrescribir)
            currentProduct.selectedModifiers[modName] = { value: modValue, adjust: priceAdjust };
        } 
        // Lógica Extras (Acumulativos)
        else {
             // Lógica especial para Leche dentro de Extras (Exclusividad mutua entre leches de extras)
             if (modValue.toLowerCase().includes("leche")) {
                const container = btn.parentElement;
                container.querySelectorAll('.mod-option').forEach(sib => {
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

    function updateProductPrice() {
        if(!currentProduct) return;
        
        let totalMods = 0;
        for(const k in currentProduct.selectedModifiers) {
            totalMods += currentProduct.selectedModifiers[k].adjust;
        }
        
        const base = currentProduct.basePrice;
        const unitTotal = base + totalMods;
        
        const discInput = document.getElementById('modal-discount-input');
        const discP = parseFloat(discInput.value) || 0;
        currentProduct.descuento = discP; // Actualizar modelo

        const qty = parseInt(document.getElementById('modal-quantity-input').value) || 1;
        currentProduct.cantidad = qty;

        const lineTotalNoDisc = unitTotal * qty;
        const discAmt = lineTotalNoDisc * (discP / 100);
        const finalTotal = lineTotalNoDisc - discAmt;

        // UI
        document.getElementById('modal-base-price').textContent = `$${base.toFixed(2)}`;
        document.getElementById('modal-modifier-adjust').textContent = totalMods > 0 ? `+$${totalMods.toFixed(2)}` : `+$0.00`;
        document.getElementById('modal-discount-amt').textContent = discAmt.toFixed(2);
        document.getElementById('modal-discount-applied').textContent = `-$${discAmt.toFixed(2)}`;
        document.getElementById('modal-final-price').textContent = `$${finalTotal.toFixed(2)}`;
    }

    function updateModalQuantity(change) {
        let val = parseInt(document.getElementById('modal-quantity-input').value) || 1;
        val += change;
        if(val < 1) val = 1;
        document.getElementById('modal-quantity-input').value = val;
        updateProductPrice();
    }

    function saveProductFromModal() {
        if (currentEditProductIndex === -1 || !currentProduct) return;

        // Calcular precio unitario final para guardar en el array
        let totalMods = 0;
        for(const k in currentProduct.selectedModifiers) {
            totalMods += currentProduct.selectedModifiers[k].adjust;
        }
        
        // Actualizar el producto en el array principal
        editProducts[currentEditProductIndex] = {
            ...currentProduct,
            precioUnitario: currentProduct.basePrice + totalMods, // Guardamos precio unitario ya sumado
            descuento: currentProduct.descuento,
            cantidad: currentProduct.cantidad,
            selectedModifiers: JSON.parse(JSON.stringify(currentProduct.selectedModifiers)) // Clonar objeto
        };

        renderEditProducts();
        calculateEditTotals();
        closeProductModal();
    }

    function closeProductModal() {
        document.getElementById('product-modal').style.display = 'none';
        currentProduct = null;
    }

    // --- FUNCIONES AUXILIARES DE EDICIÓN GENERAL ---

    function removeProductFromVenta(index) {
        if (confirm('¿Eliminar este producto?')) {
            editProducts.splice(index, 1);
            renderEditProducts();
            calculateEditTotals();
        }
    }

    function calculateEditTotals() {
        let subtotal = 0;
        let totalDescuentos = 0;
        
        editProducts.forEach(prod => {
            const linea = prod.precioUnitario * prod.cantidad;
            subtotal += linea;
            const desc = linea * (prod.descuento / 100);
            totalDescuentos += desc;
        });
        
        const total = subtotal - totalDescuentos;
        
        document.getElementById('editSubtotal').textContent = `$${subtotal.toFixed(2)}`;
        document.getElementById('editDiscounts').textContent = `-$${totalDescuentos.toFixed(2)}`;
        document.getElementById('editTotal').textContent = `$${total.toFixed(2)}`;
    }

    function saveVentaChanges() {
        if (editProducts.length === 0) {
            alert("No puedes dejar una venta vacía.");
            return;
        }

        if (confirm('¿Guardar cambios en la venta #' + currentEditVentaId + '?')) {
            const tipoPago = document.getElementById('editTipoPago').value;
            const totalText = document.getElementById('editTotal').textContent.replace('$', '');
            const total = parseFloat(totalText);
            
            const datosVenta = {
                idVenta: currentEditVentaId,
                tipoPago: tipoPago,
                total: total,
                productos: editProducts.map(p => ({
                    idProducto: p.idProducto,
                    nombre: p.nombre,
                    cantidad: p.cantidad,
                    precioUnitario: p.precioUnitario,
                    descuento: p.descuento,
                    selectedModifiers: p.selectedModifiers
                }))
            };
            
            fetch('phps/guardar_modificacion_venta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datosVenta)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('¡Venta actualizada!');
                    closeEditModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error(error);
                alert('Error al guardar.');
            });
        }
    }

    // --- AGREGAR NUEVO PRODUCTO DESDE EL CATÁLOGO ---
    
    // Funciones del catálogo (similares a las que ya tenías, solo adaptando la inserción)
    function addNewProductToVenta() {
        renderCatalogCategories();
        renderCatalogProducts('all');
        const modal = document.getElementById('catalogModal');
        modal.style.display = 'block';
        modal.style.zIndex = '3500'; // Encima de todo
    }

    function closeCatalogModal() {
        document.getElementById('catalogModal').style.display = 'none';
        document.getElementById('searchProductInput').value = '';
    }

    function renderCatalogCategories() {
        const container = document.getElementById('catalogCategoryTabs');
        container.innerHTML = '<button class="tab active" onclick="filterProductsCatalog(\'all\')" style="padding: 8px 16px; border: 2px solid var(--cafe-medio); background: var(--cafe-medio); color: white; border-radius: 6px; cursor: pointer;">Todos</button>';
        
        if(typeof categoriasAMostrar !== 'undefined') {
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
    }

    function filterProductsCatalog(categoryId) {
        // ... (Tu lógica existente de cambio de estilo de pestañas) ...
        document.querySelectorAll('#catalogCategoryTabs .tab').forEach(b => {
             // Reset estilos simple
             b.style.background = 'white'; b.style.color = '#4a3b30'; 
             if(b.textContent.includes('Todos') && categoryId === 'all') {
                 b.style.background = '#7d6a59'; b.style.color = 'white';
             }
        });
        // Render simple
        renderCatalogProducts(categoryId);
    }

    function renderCatalogProducts(categoryId) {
        let filtered = Object.values(productosUnicos);
        if (categoryId !== 'all') filtered = filtered.filter(p => p.idCategoria == categoryId);
        
        const term = document.getElementById('searchProductInput').value.toLowerCase();
        if (term) filtered = filtered.filter(p => p.nombre_base.toLowerCase().includes(term));
        
        const container = document.getElementById('catalogProducts');
        container.innerHTML = '';

        filtered.forEach(prod => {
            const precioNum = parseFloat(prod.precioVenta);
            // Escapar comillas para el onclick
            const safeName = prod.nombre_base.replace(/'/g, "\\'").replace(/"/g, '&quot;');
            
            container.innerHTML += `
                <div class="product-card clickable" 
                     style="padding: 12px; background: white; border-radius: 8px; border: 2px solid var(--crema-claro); cursor: pointer;"
                     onclick="selectProductFromCatalog(
                        '${prod.idProducto}',
                        '${safeName}',
                        ${precioNum},
                        ${prod.idCategoria}
                    )">
                    <p class="product-name" style="font-weight: 600;">${prod.nombre_base}</p>
                    <p class="product-price" style="color: var(--cafe-medio);">$${precioNum.toFixed(2)}</p>
                </div>
            `;
        });
    }

    function selectProductFromCatalog(idProducto, nombre, precio, idCategoria) {
        // Crear nuevo item
        const nuevoProducto = {
            idDetalleVenta: Date.now(), // ID Temporal
            idProducto: parseInt(idProducto),
            idCategoria: parseInt(idCategoria),
            nombre: nombre,
            cantidad: 1,
            precioUnitario: parseFloat(precio),
            basePrice: parseFloat(precio), // Base para calcular mods
            descuento: 0,
            selectedModifiers: {},
            discountPercentage: 0
        };
        
        editProducts.push(nuevoProducto);
        closeCatalogModal();
        
        // Abrir inmediatamente el modal de edición para este nuevo producto
        setTimeout(() => {
            editProductInVenta(editProducts.length - 1);
        }, 100);
    }

    // --- EVENT LISTENERS ---
    window.onclick = function(e) {
        const editM = document.getElementById('editModal');
        const prodM = document.getElementById('product-modal');
        const catM = document.getElementById('catalogModal');
        if (e.target === editM) closeEditModal();
        if (e.target === prodM) closeProductModal();
        if (e.target === catM) closeCatalogModal();
    }
    
    // Filtros tabla principal (copiados de tu original)
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
        
        // Aplicar también a las tarjetas móviles...
        let cards = document.querySelectorAll('#salesCardsList li');
        cards.forEach(c => {
             // Misma lógica para mobile...
             const txt = c.textContent.toLowerCase();
             const fecha = c.getAttribute('data-fecha');
             let show = true;
             if(q && !txt.includes(q)) show = false;
             if(from && fecha < from) show = false;
             // etc...
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