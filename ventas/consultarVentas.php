<?php
session_start();

// AJUSTA LA RUTA DE CONEXIÓN SI ES NECESARIO
include '../conexion.php'; 

if (!isset($_SESSION['usuario'])) {
    header("Location: ../indexLogin.php");
    exit();
}

$rol = $_SESSION['rol'] ?? 'Cajero'; 

// FUNCIÓN PARA OBTENER VENTAS
function obtenerTodasLasVentas($conn) {
    // Usamos u.nombre para mostrar el nombre real del cajero
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

$ventas = obtenerTodasLasVentas($conn);
$tiposDePago = ['Efectivo', 'Tarjeta', 'Transferencia']; // Ajusta según tu DB si tienes más
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Consultar Ventas | Cafetería</title>
    
    <link rel="stylesheet" href="../homepage/css/styleshome.css">
    <link rel="stylesheet" href="css/consultarVentas.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <button class="icon-btn" onclick="window.location.href='../indexhome.php'">&#8592;</button>
            <div>
                <h1>Consultar Ventas</h1>
                <p class="subtitle">Historial de transacciones</p>
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
                    <tr onclick="openDetailModal(<?php echo $v['idVenta']; ?>, '<?php echo $fechaF; ?>', '<?php echo $v['total']; ?>')"
                        data-fecha="<?php echo $fechaData; ?>"
                        data-usuario="<?php echo strtolower($v['cajero']); ?>"
                        data-pago="<?php echo strtolower($v['tipoPago']); ?>">
                        
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
                onclick="openDetailModal(<?php echo $v['idVenta']; ?>, '<?php echo $fechaF; ?>', '<?php echo $v['total']; ?>')"
                data-fecha="<?php echo $fechaData; ?>"
                data-usuario="<?php echo strtolower($v['cajero']); ?>"
                data-pago="<?php echo strtolower($v['tipoPago']); ?>">
                <div class="card-row"><span>ID Venta:</span> <b>#<?php echo $v['idVenta']; ?></b></div>
                <div class="card-row"><span>Fecha:</span> <?php echo $fechaF; ?></div>
                <div class="card-row"><span>Cajero:</span> <?php echo $v['cajero']; ?></div>
                <div class="card-row"><span>Pago:</span> <?php echo $v['tipoPago']; ?></div>
                <div class="card-row total-row"><span>Total:</span> $<?php echo number_format($v['total'], 2); ?></div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div id="detailModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2 style="color: var(--cafe-oscuro); border-bottom: 2px solid var(--crema-claro); padding-bottom: 10px; margin-top:0;">
                Detalle Venta #<span id="modalId"></span>
            </h2>
            <p style="color: var(--cafe-medio); margin-bottom: 20px;">Fecha: <span id="modalFecha" style="font-weight:bold;"></span></p>
            
            <div id="modalContent" style="max-height: 350px; overflow-y: auto;">
                Cargando...
            </div>
            
            <div style="text-align:right; margin-top:20px; font-size:1.4em; color: var(--cafe-oscuro); border-top: 1px solid #eee; padding-top: 10px;">
                <strong>Total: $<span id="modalTotal"></span></strong>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <button onclick="printTicket()" class="btn btn-search" style="width: 100%; height: 45px;">🖨️ Re-imprimir Ticket</button>
            </div>
        </div>
    </div>

    <script>
        // ID GLOBAL PARA REIMPRESIÓN
        let currentVentaId = 0;

        function openDetailModal(id, fecha, total) {
            currentVentaId = id;
            document.getElementById('detailModal').style.display = 'block';
            document.getElementById('modalId').textContent = id;
            document.getElementById('modalFecha').textContent = fecha;
            document.getElementById('modalTotal').textContent = parseFloat(total).toFixed(2);
            document.getElementById('modalContent').innerHTML = '<p style="text-align:center; color:#888;">Cargando productos...</p>';

            // AJAX para obtener detalles
            let formData = new FormData();
            formData.append('idVenta', id);

            fetch('consultarDetalleVenta.php', { method: 'POST', body: formData })
            .then(r => r.text())
            .then(html => { 
                document.getElementById('modalContent').innerHTML = html; 
            })
            .catch(e => { 
                document.getElementById('modalContent').innerHTML = '<p style="color:var(--alerta);">Error al cargar detalles.</p>';
                console.error(e);
            });
        }

        function closeModal() { document.getElementById('detailModal').style.display = 'none'; }
        
        function printTicket() {
            if(currentVentaId > 0) {
                // Abre el PDF en nueva pestaña
                window.open('phps/generar_ticket.php?id=' + currentVentaId, '_blank');
            }
        }

        window.onclick = function(e) {
            if (e.target == document.getElementById('detailModal')) closeModal();
        }

        // Cargar filtros desde URL al cargar la página
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Aplicar parámetros de URL a los inputs
            if (urlParams.has('tipoPago')) {
                document.getElementById('tipoPagoFilter').value = urlParams.get('tipoPago');
            }
            if (urlParams.has('fromDate')) {
                document.getElementById('fromDate').value = urlParams.get('fromDate');
            }
            if (urlParams.has('toDate')) {
                document.getElementById('toDate').value = urlParams.get('toDate');
            }
            
            // Aplicar filtros automáticamente
            applyFilters();
        });

        // LÓGICA DE FILTRADO
        function applyFilters() {
            let q = document.getElementById('q').value.toLowerCase();
            let pago = document.getElementById('tipoPagoFilter').value.toLowerCase();
            let from = document.getElementById('fromDate').value;
            let to = document.getElementById('toDate').value;

            let items = document.querySelectorAll('#salesTableBody tr, #salesCardsList li');

            items.forEach(el => {
                // Obtener datos
                const idVenta = el.tagName === 'TR' ? el.cells[0].textContent : el.querySelector('b').textContent.replace('#','');
                const elUser = el.getAttribute('data-usuario');
                const elPago = el.getAttribute('data-pago');
                const elFecha = el.getAttribute('data-fecha');
                
                let show = true;

                // 1. Filtro Texto (ID o Cajero)
                if (q && !(idVenta.includes(q) || elUser.includes(q))) show = false;
                // 2. Filtro Pago
                if (pago && elPago !== pago) show = false;
                // 3. Filtro Fechas
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