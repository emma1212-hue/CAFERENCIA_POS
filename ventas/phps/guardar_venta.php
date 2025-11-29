<?php
// ventas/phps/guardar_venta.php

// 1. Conexión (Subimos 2 niveles para llegar a la raíz)
require_once('../../conexion.php'); 
session_start();

header('Content-Type: application/json');

// --- VALIDACIONES INICIALES ---

// Validar Sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida o expirada']);
    exit();
}

// Recibir JSON de JavaScript
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos de la venta']);
    exit();
}

// Extraer datos
$items = $input['items'];
$totalVenta = $input['total'];
$tipoPago = $input['method'];
// Usamos el ID del usuario en sesión, o 0 si no está definido (ajusta según tu lógica de login)
$idUsuario = $_SESSION['id_usuario'] ?? 0; 


// --- LÓGICA DE CORTE DE CAJA AUTOMÁTICO ---
$idCorte = null;
$fechaHoy = date('Y-m-d');

// A. Buscar si existe un corte abierto (horaCierre IS NULL)
$sqlBuscar = "SELECT idCorte, fechaCorte FROM cortecaja WHERE horaCierre IS NULL ORDER BY idCorte DESC LIMIT 1";
$resBuscar = $conn->query($sqlBuscar);

if ($resBuscar && $resBuscar->num_rows > 0) {
    $fila = $resBuscar->fetch_assoc();
    
    // Validar si el corte abierto es del día de HOY
    if ($fila['fechaCorte'] == $fechaHoy) {
        // Todo en orden, usamos este corte para la venta
        $idCorte = $fila['idCorte'];
    } else {
        // ¡OJO! Hay un corte abierto pero es de una FECHA ANTERIOR (olvidaron cerrar ayer).
        // 1. Cerramos el corte viejo automáticamente para no mezclar días.
        $idViejo = $fila['idCorte'];
        // Cerramos a última hora del día anterior o la hora actual, usualmente se cierra para abrir uno nuevo.
        $conn->query("UPDATE cortecaja SET horaCierre = '23:59:59' WHERE idCorte = $idViejo");
        
        // 2. Forzamos la creación de uno nuevo abajo estableciendo idCorte en null.
        $idCorte = null; 
    }
}

// B. Si no se encontró corte válido (o se cerró el viejo), CREAR UNO NUEVO
if ($idCorte === null) {
    // Insertamos un nuevo corte automáticamente
    // Fondo inicial en 0 porque es automático (el cajero puede ajustarlo después si tienes esa opción)
    $sqlNuevo = "INSERT INTO cortecaja (fechaCorte, horaInicio, fondoInicial, totalVentasSistema, totalGasto) 
                 VALUES (CURRENT_DATE(), CURRENT_TIME(), 0, 0, 0)";
                 
    if ($conn->query($sqlNuevo) === TRUE) {
        $idCorte = $conn->insert_id;
    } else {
        echo json_encode(['success' => false, 'message' => 'Error crítico: No se pudo abrir el Corte de Caja. ' . $conn->error]);
        exit();
    }
}


// --- TRANSACCIÓN DE VENTA ---
$conn->begin_transaction();

try {
    // 1. Insertar Venta (Encabezado)
    $sqlVenta = "INSERT INTO ventas (fechaVenta, totalVenta, tipoPago, idUsuario, idCorte) VALUES (NOW(), ?, ?, ?, ?)";
    $stmt = $conn->prepare($sqlVenta);
    
    if (!$stmt) throw new Exception("Error preparando venta: " . $conn->error);
    
    // d=double, s=string, i=integer
    $stmt->bind_param("dsii", $totalVenta, $tipoPago, $idUsuario, $idCorte);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar venta: " . $stmt->error);
    }
    
    $idVenta = $conn->insert_id; 
    $stmt->close();

    // 2. Preparar consultas para Detalles y Stock
    $sqlDetalle = "INSERT INTO ventasdetalle (idVenta, idProducto, cantidad, precioUnitario, observaciones, descuento) VALUES (?, ?, ?, ?, ?, ?)";
    $stmtDetalle = $conn->prepare($sqlDetalle);
    
    $sqlStock = "UPDATE productos SET stockActual = stockActual - ? WHERE idProducto = ?";
    $stmtStock = $conn->prepare($sqlStock);

    // 3. Recorrer productos del carrito
    foreach ($items as $item) {
        $idProd = $item['id'];
        $cant = $item['quantity'];
        $precioUnitarioFinal = $item['finalPrice']; 
        
        // Cálculo del monto de descuento para el registro
        $precioBaseConExtras = $item['basePrice'];
        foreach ($item['selectedModifiers'] as $mod) { 
            $precioBaseConExtras += $mod['adjust']; 
        }
        $montoDescuento = ($precioBaseConExtras * $cant) * ($item['discountPercentage'] / 100);

        // Construir string de Observaciones (Ej: "Entera, Shot Extra")
        $obsArray = [];
        foreach ($item['selectedModifiers'] as $key => $mod) {
            $obsArray[] = $mod['value'];
        }
        $observaciones = implode(", ", $obsArray);

        // Insertar Detalle
        // Tipos: i(idVenta), i(idProd), i(cant), d(precio), s(obs), d(desc)
        $stmtDetalle->bind_param("iiidsd", $idVenta, $idProd, $cant, $precioUnitarioFinal, $observaciones, $montoDescuento);
        
        if (!$stmtDetalle->execute()) {
            throw new Exception("Error al guardar detalle del producto ID $idProd: " . $stmtDetalle->error);
        }

        // Descontar Stock
        $stmtStock->bind_param("ii", $cant, $idProd);
        $stmtStock->execute();
    }

    // 4. Confirmar todo
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Venta registrada correctamente', 'idVenta' => $idVenta]);

} catch (Exception $e) {
    // Si algo falla, deshacer cambios en la DB
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>