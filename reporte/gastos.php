<?php
session_start();

// Configurar Zona Horaria (Crucial para que coincida con la BD)
date_default_timezone_set('America/Mexico_City');

// Validar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../indexLogin.php");
    exit();
}

$rol = $_SESSION['rol'] ?? '';
include '../conexion.php'; 

$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Obtener y sanitizar datos
    $montoGasto = filter_input(INPUT_POST, 'montoGasto', FILTER_VALIDATE_FLOAT);
    $conceptoGasto = filter_input(INPUT_POST, 'conceptoGasto', FILTER_SANITIZE_STRING);
    
    // 2. Validaciones básicas
    if ($montoGasto === false || $montoGasto <= 0) {
        $mensaje = "<div class='alert error'>Error: El monto debe ser positivo.</div>";
    } elseif (empty($conceptoGasto)) {
        $mensaje = "<div class='alert error'>Error: El concepto es obligatorio.</div>";
    } else {
        
        // --- 3. LÓGICA DE CORTE DE CAJA (IGUAL A VENTAS) ---
        $idCorte = null;
        $fechaHoy = date('Y-m-d');
        $horaActual = date('H:i:s');

        // A. Buscar si existe un corte abierto
        $sqlBuscar = "SELECT idCorte, fechaCorte FROM cortecaja 
                      WHERE (horaCierre IS NULL OR horaCierre = '00:00:00') 
                      ORDER BY idCorte DESC LIMIT 1";
        $resBuscar = $conn->query($sqlBuscar);

        if ($resBuscar && $resBuscar->num_rows > 0) {
            $fila = $resBuscar->fetch_assoc();
            
            // Validar fecha del corte
            if ($fila['fechaCorte'] == $fechaHoy) {
                // Es de hoy, lo usamos
                $idCorte = $fila['idCorte'];
            } else {
                // Es viejo, lo cerramos y forzamos uno nuevo
                $idViejo = $fila['idCorte'];
                $conn->query("UPDATE cortecaja SET horaCierre = '23:59:59' WHERE idCorte = $idViejo");
                $idCorte = null; 
            }
        }

        // B. Si no hay corte válido, CREAR UNO NUEVO
        if ($idCorte === null) {
            $sqlNuevo = "INSERT INTO cortecaja (fechaCorte, horaInicio, fondoInicial, totalVentasSistema, totalGasto, horaCierre) 
                         VALUES (?, ?, 0, 0, 0, NULL)"; // Fondo en 0 por defecto
            
            $stmtCorte = $conn->prepare($sqlNuevo);
            $stmtCorte->bind_param("ss", $fechaHoy, $horaActual);
            
            if ($stmtCorte->execute()) {
                $idCorte = $conn->insert_id;
                $stmtCorte->close();
            } else {
                $mensaje = "<div class='alert error'>Error crítico: No se pudo abrir caja para registrar el gasto.</div>";
            }
        }

        // --- 4. INSERTAR EL GASTO (Si tenemos corte) ---
        if ($idCorte) {
            try {
                $fechaGasto = date('Y-m-d H:i:s');
                
                // Actualizado a la tabla 'gastosdiarios' incluyendo idCorte
                $sql = "INSERT INTO gastosdiarios (idCorte, fechaGasto, montoGasto, conceptoGasto) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                
                // i=entero, s=string, d=double, s=string
                $stmt->bind_param("isds", $idCorte, $fechaGasto, $montoGasto, $conceptoGasto);
                
                if ($stmt->execute()) {
                    // Éxito: También actualizamos el acumulado en la tabla cortecaja para mantener sincronía
                    $conn->query("UPDATE cortecaja SET totalGasto = totalGasto + $montoGasto WHERE idCorte = $idCorte");
                    
                    $fechaFormateada = date('d/m/Y');
                    $mensaje = "<div class='alert success'>Gasto registrado correctamente para el día $fechaFormateada.</div>";
                    $montoGasto = '';
                    $conceptoGasto = '';
                } else {
                    $mensaje = "<div class='alert error'>Error al registrar en BD: " . $stmt->error . "</div>";
                }
                $stmt->close();

            } catch (Exception $e) {
                $mensaje = "<div class='alert error'>Excepción: " . $e->getMessage() . "</div>";
            }
        }
        
        $conn->close();
    }
}

// Inicializar variables para repoblar formulario si falla
$montoGasto = $montoGasto ?? '';
$conceptoGasto = $conceptoGasto ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <title>Registrar Gastos | Cafetería</title>
    <!-- CSS General del Home para variables -->
    <link rel="stylesheet" href="../homepage/css/styleshome.css">
    <!-- Enlaza el nuevo archivo de estilos CSS (gastos.css) -->
    <link rel="stylesheet" href="css/gastos.css"> 
</head>
<body>
    <div class="container-wrapper">
        <div class="header">
            <!-- Botón para volver al menú principal -->
            <button class="icon-btn menu-btn" onclick="window.location.href='../indexhome.php'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </button>
            <div>
                <h1>Registrar Gastos Diarios</h1>
                <p class="subtitle">Gestiona gastos extra (agua, limpieza, etc.)</p>
            </div>
        </div>

        <div class="main-content">
            <?php echo $mensaje; // Mostrar mensajes de éxito o error ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="expense-form">
                
                <div class="form-group">
                    <label for="montoGasto">Monto del Gasto ($)</label>
                    <input 
                        type="number" 
                        id="montoGasto" 
                        name="montoGasto" 
                        step="0.01" 
                        min="0.01" 
                        value="<?php echo htmlspecialchars($montoGasto); ?>"
                        placeholder="Ej. 50.00" 
                        required>
                </div>

                <div class="form-group">
                    <label for="conceptoGasto">Concepto del Gasto</label>
                    <textarea 
                        id="conceptoGasto" 
                        name="conceptoGasto" 
                        rows="3" 
                        placeholder="Ej. Pago de garrafón de agua, insumos de limpieza" 
                        required><?php echo htmlspecialchars($conceptoGasto); ?></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Registrar Gasto
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Función para limpiar el mensaje después de un tiempo
        document.addEventListener('DOMContentLoaded', () => {
            const alertElement = document.querySelector('.alert');
            if (alertElement) {
                // Usamos la duración de la animación para la transición
                setTimeout(() => {
                    alertElement.style.opacity = '0';
                    setTimeout(() => alertElement.remove(), 500); 
                }, 3000); 
            }
        });
    </script>
</body>
</html>