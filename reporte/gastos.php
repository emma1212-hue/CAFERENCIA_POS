<?php
session_start();

// Validar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../indexLogin.php");
    exit();
}

$rol = $_SESSION['rol'] ?? '';

// Incluir el archivo de conexión a la base de datos
// **Ajusta esta ruta si tu archivo de conexión está en otro lugar**
// NOTA: Se asume que 'conexion.php' está en el directorio padre.
include '../conexion.php'; 

$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Obtener y sanitizar datos del formulario
    $montoGasto = filter_input(INPUT_POST, 'montoGasto', FILTER_VALIDATE_FLOAT);
    $conceptoGasto = filter_input(INPUT_POST, 'conceptoGasto', FILTER_SANITIZE_STRING);
    
    // 2. Validar datos
    if ($montoGasto === false || $montoGasto <= 0) {
        $mensaje = "<div class='alert error'>Error: El monto del gasto debe ser un número positivo.</div>";
    } elseif (empty($conceptoGasto)) {
        $mensaje = "<div class='alert error'>Error: El concepto del gasto es obligatorio.</div>";
    } else {
        // 3. Preparar datos para la inserción
        $fechaGasto = date('Y-m-d H:i:s');
        // idCorte se deja NULL o 0 temporalmente. Se recomienda que este campo
        // se actualice al momento de realizar el corte de caja real.
        $idCorte = null; 

        try {
            // Consulta SQL para insertar el gasto
            // NOTA: Si el campo idCorte no acepta NULL en tu DB,
            // debes usar un valor por defecto (ej. 0) o el ID del corte activo.
            $sql = "INSERT INTO gastos (idCorte, fechaGasto, montoGasto, conceptoGasto) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            // Asumiendo que idCorte acepta NULL o usaremos un valor dummy si no.
            // Tipos de binding: 'i' (integer/idCorte si fuera NOT NULL), 's' (string), 'd' (double/float)
            // Ya que idCorte es NULL, debemos modificar la consulta para omitirlo si la columna lo permite
            // O bindear 'null' si el driver lo permite (mysqli no lo hace con 's', necesita 'i' si es entero o ajustar)
            
            // --- USANDO UN ENFOQUE SEGURO CON NULL PARA idCorte ---
            // Si idCorte acepta NULL, ajustamos el SQL para insertar solo los campos con valor.
            $sql_insert = "INSERT INTO gastos (fechaGasto, montoGasto, conceptoGasto) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql_insert);
            
            // Bindear: 's' (fechaGasto), 'd' (montoGasto), 's' (conceptoGasto)
            $stmt->bind_param("sds", $fechaGasto, $montoGasto, $conceptoGasto);
            
            if ($stmt->execute()) {
                // Éxito
                $mensaje = "<div class='alert success'>Gasto registrado exitosamente. ID: " . $stmt->insert_id . "</div>";
                // Limpiar campos después del éxito
                $montoGasto = '';
                $conceptoGasto = '';
            } else {
                // Error en la ejecución
                $mensaje = "<div class='alert error'>Error al registrar el gasto: " . $stmt->error . "</div>";
            }
            
            $stmt->close();
        } catch (Exception $e) {
            // Error en la conexión o preparación
            $mensaje = "<div class='alert error'>Error del sistema: " . $e->getMessage() . "</div>";
        }
        
        // Cerrar conexión
        $conn->close();
    }
}

// Inicializar variables para el formulario
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