<?php
session_start();
date_default_timezone_set('America/Mexico_City'); 

if (!isset($_SESSION['usuario'])) {
    header("Location: ../indexLogin.php");
    exit();
}

include '../conexion.php'; 

// --- 1. OBTENER EL CORTE ACTIVO ---
$sqlCorte = "SELECT * FROM cortecaja WHERE (horaCierre IS NULL OR horaCierre = '00:00:00') ORDER BY idCorte DESC LIMIT 1";
$resCorte = $conn->query($sqlCorte);
$corte = null;
$mensaje = "";

// --- LÓGICA DE CIERRE (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_corte'])) {
    $idCorteCerrar = $_POST['id_corte'];
    $totalVentas = $_POST['total_ventas'];
    $totalGastos = $_POST['total_gastos'];
    
    // Recibimos el fondo inicial editado por el usuario
    $nuevoFondo = isset($_POST['fondo_inicial_input']) ? floatval($_POST['fondo_inicial_input']) : 0;
    
    $horaCierre = date('H:i:s');

    // ACTUALIZAMOS TAMBIÉN EL FONDO INICIAL
    $sqlUpdate = "UPDATE cortecaja SET 
                  fondoInicial = ?, 
                  totalVentasSistema = ?, 
                  totalGasto = ?, 
                  horaCierre = ? 
                  WHERE idCorte = ?";
    
    $stmt = $conn->prepare($sqlUpdate);
    // d=double, d=double, d=double, s=string, i=int
    $stmt->bind_param("dddsi", $nuevoFondo, $totalVentas, $totalGastos, $horaCierre, $idCorteCerrar);
    
    if ($stmt->execute()) {
        $mensaje = "<div class='alert success'>¡Corte de caja cerrado exitosamente!</div>";
        $corteCerrado = true;
    } else {
        $mensaje = "<div class='alert error'>Error al cerrar caja: " . $conn->error . "</div>";
    }
}

// Volvemos a consultar si hay corte activo
if (!isset($corteCerrado)) {
    $resCorte = $conn->query($sqlCorte);
    if ($resCorte && $resCorte->num_rows > 0) {
        $corte = $resCorte->fetch_assoc();
    }
}

// --- 2. CALCULAR DATOS ---
$stats = [
    'ventas_total' => 0,
    'ventas_efectivo' => 0,
    'ventas_digital' => 0, 
    'num_ventas' => 0,
    'gastos_total' => 0,
    'fondo_inicial' => 0,
    'total_caja_teorico' => 0
];

$listaGastos = [];

if ($corte) {
    $idCorte = $corte['idCorte'];
    $fechaCorte = $corte['fechaCorte'];
    $stats['fondo_inicial'] = floatval($corte['fondoInicial']);

    // Ventas
    $sqlVentas = "SELECT totalVenta, tipoPago FROM ventas WHERE idCorte = ?";
    $stmtV = $conn->prepare($sqlVentas);
    $stmtV->bind_param("i", $idCorte);
    $stmtV->execute();
    $resV = $stmtV->get_result();

    while ($row = $resV->fetch_assoc()) {
        $total = floatval($row['totalVenta']);
        $stats['ventas_total'] += $total;
        $stats['num_ventas']++;
        if (strtolower($row['tipoPago']) === 'efectivo') {
            $stats['ventas_efectivo'] += $total;
        } else {
            $stats['ventas_digital'] += $total;
        }
    }

    // Gastos
    $sqlGastos = "SELECT * FROM gastosDiarios WHERE DATE(fechaGasto) = ?";
    $stmtG = $conn->prepare($sqlGastos);
    $stmtG->bind_param("s", $fechaCorte);
    $stmtG->execute();
    $resG = $stmtG->get_result();

    while ($row = $resG->fetch_assoc()) {
        $monto = floatval($row['montoGasto']);
        $stats['gastos_total'] += $monto;
        $listaGastos[] = $row;
    }

    // Cálculo inicial (se actualizará con JS si cambian el fondo)
    $stats['total_caja_teorico'] = $stats['fondo_inicial'] + $stats['ventas_efectivo'] - $stats['gastos_total'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corte de Caja | Cafetería</title>
    <link rel="stylesheet" href="../homepage/css/styleshome.css">
    <link rel="stylesheet" href="css/gastos.css">
    <link rel="stylesheet" href="css/reporte.css">
    <style>
        /* Estilo simple para el input del fondo dentro de la tabla de balance */
        .input-fondo {
            width: 100px;
            padding: 5px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            text-align: right;
            font-weight: bold;
            color: var(--text-primary);
        }
        .input-fondo:focus {
            border-color: var(--text-primary);
            outline: none;
        }
    </style>
</head>
<body>
    <div class="container-wrapper report-container">
        
        <div class="header">
            <button class="icon-btn menu-btn" onclick="window.location.href='../indexhome.php'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            </button>
            <div>
                <h1>Corte de Caja</h1>
                <p class="subtitle">
                    <?php 

                        if ($corte){
                            $fechaCompleta = $corte['fechaCorte'] . ' ' . $corte['horaInicio'];
                            echo "Turno activo del: " . date('d/m/Y h:i A', strtotime($fechaCompleta));
                        }else echo "No hay turno abierto actualmente.";
                    ?>
                </p>
            </div>
        </div>

        <?php echo $mensaje; ?>

        <?php if ($corte): ?>
            <form method="POST" onsubmit="return confirm('¿Confirmar cierre de turno?');">
                
                <!-- Campos ocultos para enviar datos fijos -->
                <input type="hidden" name="id_corte" value="<?php echo $idCorte; ?>">
                <input type="hidden" name="total_ventas" value="<?php echo $stats['ventas_total']; ?>">
                <input type="hidden" name="total_gastos" value="<?php echo $stats['gastos_total']; ?>">

                <div class="stats-grid">
                    <!-- Ventas -->
                    <div class="stat-card blue">
                        <div class="stat-icon">🛒</div>
                        <div class="stat-info">
                            <h3>Ventas Totales</h3>
                            <p class="stat-value">$<?php echo number_format($stats['ventas_total'], 2); ?></p>
                            <span class="stat-sub"><?php echo $stats['num_ventas']; ?> Ventas</span>
                        </div>
                    </div>

                    <!-- Gastos -->
                    <div class="stat-card red">
                        <div class="stat-icon">💸</div>
                        <div class="stat-info">
                            <h3>Gastos</h3>
                            <p class="stat-value">-$<?php echo number_format($stats['gastos_total'], 2); ?></p>
                            <span class="stat-sub"><?php echo count($listaGastos); ?> Movimientos</span>
                        </div>
                    </div>

                    <!-- Efectivo (Dinámico) -->
                    <div class="stat-card green highlight">
                        <div class="stat-icon">💵</div>
                        <div class="stat-info">
                            <h3>Efectivo en Caja</h3>
                            <!-- Este valor se actualiza con JS -->
                            <p class="stat-value" id="display-caja-teorica">$<?php echo number_format($stats['total_caja_teorico'], 2); ?></p>
                            <span class="stat-sub">Debe haber físico</span>
                        </div>
                    </div>
                </div>

                <div class="balance-section">
                    <h3>Desglose de Efectivo</h3>
                    
                    <div class="balance-row">
                        <span style="align-self:center;">(+) Fondo Inicial:</span>
                        <!-- INPUT EDITABLE -->
                        <span>
                            $ <input type="number" 
                                     name="fondo_inicial_input" 
                                     id="fondoInput" 
                                     class="input-fondo"
                                     step="0.01" 
                                     min="0"
                                     value="<?php echo number_format($stats['fondo_inicial'], 2, '.', ''); ?>" 
                                     oninput="recalcularCaja()">
                        </span>
                    </div>

                    <div class="balance-row">
                        <span>(+) Ventas Efectivo:</span>
                        <span>$<?php echo number_format($stats['ventas_efectivo'], 2); ?></span>
                    </div>
                    
                    <div class="balance-row minus">
                        <span>(-) Gastos:</span>
                        <span>$<?php echo number_format($stats['gastos_total'], 2); ?></span>
                    </div>

                    <div class="balance-row" style="opacity:0.6; font-size:0.9rem;">
                        <span>(i) Ventas Digitales (No suman a caja):</span>
                        <span>$<?php echo number_format($stats['ventas_digital'], 2); ?></span>
                    </div>

                    <hr>
                    <div class="balance-row total">
                        <span>(=) Total Efectivo en Caja:</span>
                        <span id="display-total-balance">$<?php echo number_format($stats['total_caja_teorico'], 2); ?></span>
                    </div>
                </div>

                <!-- Detalle de Gastos (Igual que antes) -->
                <div class="gastos-section">
                    <h3>Gastos Registrados</h3>
                    <?php if (count($listaGastos) > 0): ?>
                        <div class="table-responsive">
                            <table class="gastos-table">
                                <thead>
                                    <tr><th>Hora</th><th>Concepto</th><th>Monto</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listaGastos as $g): ?>
                                        <tr>
                                            <td><?php echo date('H:i', strtotime($g['fechaGasto'])); ?></td>
                                            <td><?php echo htmlspecialchars($g['conceptoGasto']); ?></td>
                                            <td class="amount">-$<?php echo number_format($g['montoGasto'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">Sin gastos.</p>
                    <?php endif; ?>
                </div>

                <div class="actions-section">
                    <button type="submit" name="cerrar_corte" class="btn-cerrar-corte">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Guardar y Cerrar Corte
                    </button>
                </div>
            </form>

        <?php elseif(isset($corteCerrado)): ?>
            <div class="empty-state">
                <h2>✅ Turno Cerrado</h2>
                <p>Datos guardados. Inicia una nueva venta para abrir otro turno.</p>
                <button type="button" class="btn-cerrar-corte" onclick="window.location.href='../indexhome.php'">Volver</button>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>💤 Sin Actividad</h2>
                <p>No hay turno abierto. Realiza una venta para iniciar el turno automáticamente.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Variables desde PHP para el cálculo en vivo
        const ventasEfectivo = <?php echo isset($stats) ? $stats['ventas_efectivo'] : 0; ?>;
        const gastosTotal = <?php echo isset($stats) ? $stats['gastos_total'] : 0; ?>;

        function recalcularCaja() {
            const input = document.getElementById('fondoInput');
            let fondo = parseFloat(input.value);
            
            if (isNaN(fondo) || fondo < 0) fondo = 0;

            // Fórmula: Fondo + Ventas(Efectivo) - Gastos
            const totalCaja = fondo + ventasEfectivo - gastosTotal;

            // Formatear a moneda
            const formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2
            });

            const totalStr = formatter.format(totalCaja);

            // Actualizar DOM
            document.getElementById('display-caja-teorica').textContent = totalStr;
            document.getElementById('display-total-balance').textContent = totalStr;
        }
    </script>
</body>
</html>