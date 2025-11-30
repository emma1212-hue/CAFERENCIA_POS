<?php
session_start();

// AJUSTA ESTA RUTA: Si conexion.php está en la carpeta padre, usa '../conexion.php'
include '../conexion.php'; 

if (!isset($_SESSION['usuario'])) {
    header("Location: ../indexLogin.php");
    exit();
}

$rol = $_SESSION['rol'] ?? 'Cajero'; 

// =========================================================
// 1. FUNCIÓN PARA OBTENER DATOS REALES DE LA BD
// =========================================================
function obtenerTodasLasVentas($conn) {
    // CORRECCIÓN CRÍTICA: Se cambió 'u.usuario' a 'u.nombre' para coincidir con el esquema de la tabla usuarios.
    $sql = "SELECT 
                v.idVenta, 
                v.fechaVenta AS fecha, 
                v.totalVenta AS total, 
                v.tipoPago, 
                u.nombre AS cajero  -- <--- CORRECCIÓN APLICADA AQUÍ
            FROM 
                ventas v
            JOIN 
                usuarios u ON v.idUsuario = u.idUsuario
            ORDER BY 
                v.fechaVenta DESC"; 

    $resultado = $conn->query($sql);
    $ventas = [];

    if ($resultado === false) {
        // MUY IMPORTANTE: Registrar error si la consulta falla.
        error_log("Error al obtener ventas (SQL): " . $conn->error);
        return [];
    }

    if ($resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $ventas[] = $fila;
        }
    }
    return $ventas;
}

$ventas = obtenerTodasLasVentas($conn);

// Tipos de pago para el filtro
$tiposDePago = ['Efectivo', 'Tarjeta de Crédito', 'Tarjeta de Débito', 'Transferencia']; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <title>Consultar Ventas | Cafetería</title>
    <!-- Asegúrate de que las rutas a tus CSS sean correctas -->
    <link rel="stylesheet" href="../homepage/css/styleshome.css">
    <!-- Si tienes estilos específicos en consultarVentas.css, inclúyelo: -->
    <link rel="stylesheet" href="css/consultarVentas.css"> 
    
    <style>
        /* Estilos CSS críticos para el Modal */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            padding-top: 60px;
        }
        .modal-content {
            background-color: #fff;
            margin: 5% auto; 
            padding: 20px;
            border-radius: 12px;
            width: 90%; 
            max-width: 600px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }
        .close-btn {
            color: #4a3b30;
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-btn:hover { color: #E74C3C; }
        
        .detail-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .detail-table th, .detail-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: right;
        }
        .detail-table th { background-color: #f4e1d2; text-align: center; }
        .detail-table td:first-child { text-align: left; }
        
        /* Estilos Responsive para Tabla/Tarjetas */
        @media (max-width: 768px) {
            .sales-table { display: none; }
            .cards { display: flex; flex-direction: column; gap: 10px; list-style: none; padding: 0; }
            .sale-card { 
                background-color: #fff; 
                padding: 15px; 
                border-radius: 8px; 
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                cursor: pointer;
                border-left: 4px solid #d7c5b2;
            }
            .card-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
            .total-row { border-top: 1px solid #eee; padding-top: 5px; font-weight: bold; }
        }
        @media (min-width: 769px) {
            .cards { display: none; }
        }
        
        /* Filas clickeables */
        tbody tr { cursor: pointer; transition: background 0.2s; }
        tbody tr:hover { background-color: #f9f9f9; }
        
        /* Estilos generales del contenedor y controles (si no están en styleshome.css) */
        :root {
            --brown-dark: #4a3b30; /* principal */
            --brown-muted: #a89b8f; /* tonos medios */
            --bg: #f4e1d2; /* fondo suave */
            --text: #2b2b2b;
        }
        body { background: var(--bg); color: var(--text); font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 0; font-size: 15px; }
        .container { max-width: 1200px; margin: 18px auto; padding: 16px; }
        .header { display: flex; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; color: var(--brown-dark); font-size: 1.5rem; }
        .subtitle { margin: 6px 0 0 0; font-size: 0.9rem; color: var(--brown-muted); }
        .icon-btn { background: none; border: none; font-size: 1.5rem; color: var(--brown-dark); cursor: pointer; padding: 0 10px 0 0; }
        
        .controls { 
            display: flex; 
            gap: 20px; 
            margin-bottom: 20px; 
            flex-wrap: wrap; 
            align-items: flex-end; 
        }
        .controls > div { display: flex; flex-direction: column; min-width: 120px; }
        .controls label { font-weight: 500; font-size: 0.85rem; color: var(--brown-dark); margin-bottom: 4px; }
        .controls input, .controls select, .btn { 
            padding: 8px; 
            border: 1px solid #ccc; 
            border-radius: 6px; 
            font-size: 1rem;
            min-height: 38px; /* Asegura altura consistente */
        }
        .btn-group { display: flex; gap: 10px; }
        .btn-search { 
            background-color: #3498DB; 
            color: white; 
            border: none; 
            cursor: pointer; 
            transition: background-color 0.2s; 
        }
        .btn-search:hover { background-color: #2980b9; }
        .btn-reset { 
            background-color: #e74c3c; 
            color: white; 
            border: none; 
            cursor: pointer; 
            transition: background-color 0.2s; 
        }
        .btn-reset:hover { background-color: #c0392b; }
        
        .table-wrapper { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .sales-table th, .sales-table td { padding: 12px 8px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        .sales-table tbody tr:nth-child(even) { background-color: #fcfcfc; }
    </style>
</head>
<body onload="applyFilters()"> <!-- Ejecuta filtros iniciales al cargar -->
    <div class="container">
        <div class="header">
            <button class="icon-btn menu-btn" onclick="window.location.href='../indexhome.php'">&#8592;</button>
            <div>
                <h1>Consultar Ventas</h1>
                <p class="subtitle">Historial de transacciones</p>
            </div>
        </div>

        <!-- Controles de Filtrado -->
        <div class="controls">
            <div>
                <label>Buscar (ID/Cajero):</label>
                <input type="text" id="q" placeholder="ID o Cajero">
            </div>
            <div>
                <label>Tipo Pago:</label>
                <select id="tipoPagoFilter">
                    <option value="">Todos</option>
                    <?php foreach ($tiposDePago as $tipo): ?>
                        <option value="<?php echo htmlspecialchars($tipo); ?>"><?php echo htmlspecialchars($tipo); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Desde:</label>
                <input type="date" id="fromDate">
            </div>
            <div>
                <label>Hasta:</label>
                <input type="date" id="toDate">
            </div>
            <div class="btn-group" style="align-self: flex-end;">
                <button class="btn btn-search" onclick="applyFilters()">Buscar</button>
                <button class="btn btn-reset" onclick="resetFilters()">Limpiar</button>
            </div>
        </div>

        <!-- Tabla (Desktop) -->
        <div class="table-wrapper">
            <table class="sales-table" style="width:100%; border-collapse: collapse;">
                <thead style="background-color: #4a3b30; color: white;">
                    <tr>
                        <th style="padding:12px;">ID Venta</th>
                        <th>Fecha</th>
                        <th>Cajero</th>
                        <th>Tipo Pago</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody">
                    <?php foreach ($ventas as $v): 
                        $fechaF = date('d/m/Y H:i', strtotime($v['fecha']));
                        $fechaData = date('Y-m-d', strtotime($v['fecha']));
                    ?>
                    <tr onclick="openDetailModal(<?php echo $v['idVenta']; ?>, '<?php echo $fechaF; ?>', '<?php echo $v['total']; ?>')"
                        data-fecha="<?php echo $fechaData; ?>"
                        data-usuario="<?php echo strtolower($v['cajero']); ?>"
                        data-pago="<?php echo strtolower($v['tipoPago']); ?>">
                        <td style="padding:10px; text-align:center;"><?php echo $v['idVenta']; ?></td>
                        <td style="text-align:center;"><?php echo $fechaF; ?></td>
                        <td style="text-align:center;"><?php echo $v['cajero']; ?></td>
                        <td style="text-align:center;"><?php echo $v['tipoPago']; ?></td>
                        <td style="text-align:center; font-weight:bold;">$<?php echo number_format($v['total'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tarjetas (Móvil) -->
        <ul class="cards" id="salesCardsList">
            <?php foreach ($ventas as $v): 
                $fechaF = date('d/m/Y H:i', strtotime($v['fecha']));
                $fechaData = date('Y-m-d', strtotime($v['fecha']));
            ?>
            <li class="sale-card" 
                onclick="openDetailModal(<?php echo $v['idVenta']; ?>, '<?php echo $fechaF; ?>', '<?php echo $v['total']; ?>')"
                data-fecha="<?php echo $fechaData; ?>"
                data-usuario="<?php echo strtolower($v['cajero']); ?>"
                data-pago="<?php echo strtolower($v['tipoPago']); ?>">
                <div class="card-row"><span>ID:</span> <b><?php echo $v['idVenta']; ?></b></div>
                <div class="card-row"><span>Fecha:</span> <?php echo $fechaF; ?></div>
                <div class="card-row"><span>Cajero:</span> <?php echo $v['cajero']; ?></div>
                <div class="card-row"><span>Pago:</span> <?php echo $v['tipoPago']; ?></div>
                <div class="card-row total-row"><span>Total:</span> $<?php echo number_format($v['total'], 2); ?></div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- MODAL DETALLE -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2>Detalle de Venta #<span id="modalId"></span></h2>
            <p>Fecha: <span id="modalFecha"></span></p>
            <div id="modalContent">Cargando...</div>
            <div style="text-align:right; margin-top:15px; font-size:1.2em;">
                <strong>Total: $<span id="modalTotal"></span></strong>
            </div>
        </div>
    </div>

    <script>
        function openDetailModal(id, fecha, total) {
            document.getElementById('detailModal').style.display = 'block';
            document.getElementById('modalId').textContent = id;
            document.getElementById('modalFecha').textContent = fecha;
            document.getElementById('modalTotal').textContent = parseFloat(total).toFixed(2);
            document.getElementById('modalContent').innerHTML = 'Cargando...';

            // AJAX para obtener detalles
            let formData = new FormData();
            formData.append('idVenta', id);

            // Fetch a 'consultarDetalleVenta.php'
            fetch('consultarDetalleVenta.php', { method: 'POST', body: formData })
            .then(r => r.text())
            .then(html => { 
                document.getElementById('modalContent').innerHTML = html; 
            })
            .catch(e => { 
                document.getElementById('modalContent').innerHTML = 'Error al cargar los detalles.';
                console.error("Error fetching detail:", e);
            });
        }

        function closeModal() { document.getElementById('detailModal').style.display = 'none'; }
        
        window.onclick = function(e) {
            if (e.target == document.getElementById('detailModal')) closeModal();
        }

        // Función para aplicar los filtros al hacer clic en 'Buscar' o al cargar la página
        function applyFilters() {
            let q = document.getElementById('q').value.toLowerCase();
            let pago = document.getElementById('tipoPagoFilter').value.toLowerCase();
            let from = document.getElementById('fromDate').value;
            let to = document.getElementById('toDate').value;

            let items = document.querySelectorAll('#salesTableBody tr, #salesCardsList li');

            items.forEach(el => {
                // Obtener datos desde atributos personalizados (más seguro que innerText)
                // Para la tabla (<tr>), obtenemos el ID de la primera celda
                const idVenta = el.querySelector('td:first-child')?.textContent?.toLowerCase() || '';
                const elUser = el.getAttribute('data-usuario');
                const elPago = el.getAttribute('data-pago');
                const elFecha = el.getAttribute('data-fecha');
                
                let show = true;

                // Filtro de Búsqueda (ID o Cajero)
                if (q && !(idVenta.includes(q) || elUser.includes(q))) show = false;
                
                // Filtro por tipo de pago
                if (pago && elPago !== pago) show = false;
                
                // Filtro por fecha (Desde)
                if (from && elFecha < from) show = false;
                
                // Filtro por fecha (Hasta)
                if (to && elFecha > to) show = false;

                // Mostrar/Ocultar elemento
                if (show) {
                    el.style.display = '';
                } else {
                    // La lógica para ocultar elementos <li> en móvil es más estricta
                    if (el.tagName === 'LI') {
                        el.style.setProperty('display', 'none', 'important');
                    } else {
                        el.style.display = 'none';
                    }
                }
            });
        }

        function resetFilters() {
            document.getElementById('q').value = '';
            document.getElementById('tipoPagoFilter').value = '';
            document.getElementById('fromDate').value = '';
            document.getElementById('toDate').value = '';
            applyFilters(); // Aplicar sin filtros
        }
    </script>
</body>
</html>