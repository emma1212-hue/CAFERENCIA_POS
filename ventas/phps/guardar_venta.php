<?php
require_once('../../conexion.php'); 
session_start();

date_default_timezone_set('America/Mexico_City'); 

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida o expirada']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos de la venta']);
    exit();
}

$items = $input['items'];
$totalVenta = $input['total'];
$tipoPago = $input['method'];
$idUsuario = $_SESSION['id_usuario'] ?? 0; 


$idCorte = null;
$fechaHoy = date('Y-m-d'); 

$sqlBuscar = "SELECT idCorte, fechaCorte FROM cortecaja 
              WHERE (horaCierre IS NULL OR horaCierre = '00:00:00') 
              ORDER BY idCorte DESC LIMIT 1";

$resBuscar = $conn->query($sqlBuscar);

if ($resBuscar && $resBuscar->num_rows > 0) {
    $fila = $resBuscar->fetch_assoc();
    
    if ($fila['fechaCorte'] == $fechaHoy) {
        $idCorte = $fila['idCorte'];
    } else {
        $idViejo = $fila['idCorte'];
        $conn->query("UPDATE cortecaja SET horaCierre = '23:59:59' WHERE idCorte = $idViejo");
        
        $idCorte = null; 
    }
}

if ($idCorte === null) {
    $horaActual = date('H:i:s');
    
    $sqlNuevo = "INSERT INTO cortecaja (fechaCorte, horaInicio, fondoInicial, totalVentasSistema, totalGasto, horaCierre) 
                 VALUES (?, ?, 0, 0, 0, NULL)";
    
    $stmtCorte = $conn->prepare($sqlNuevo);

    $stmtCorte->bind_param("ss", $fechaHoy, $horaActual);
    
    if ($stmtCorte->execute()) {
        $idCorte = $conn->insert_id;
        $stmtCorte->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error crítico al abrir Corte de Caja: ' . $conn->error]);
        exit();
    }
}

$conn->begin_transaction();

try {
    $sqlVenta = "INSERT INTO ventas (fechaVenta, totalVenta, tipoPago, idUsuario, idCorte) VALUES (NOW(), ?, ?, ?, ?)";
    $stmt = $conn->prepare($sqlVenta);
    
    if (!$stmt) throw new Exception("Error preparando venta: " . $conn->error);
    
    $stmt->bind_param("dsii", $totalVenta, $tipoPago, $idUsuario, $idCorte);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar venta: " . $stmt->error);
    }
    
    $idVenta = $conn->insert_id; 
    $stmt->close();

    $sqlDetalle = "INSERT INTO ventasdetalle (idVenta, idProducto, cantidad, precioUnitario, observaciones, descuento) VALUES (?, ?, ?, ?, ?, ?)";
    $stmtDetalle = $conn->prepare($sqlDetalle);
    
    foreach ($items as $item) {
        $idProd = $item['id'];
        $cant = $item['quantity'];
        $precioUnitarioFinal = $item['finalPrice']; 
        
        // Cálculo del monto de descuento
        $precioBaseConExtras = $item['basePrice'];
        if(isset($item['selectedModifiers'])){
            foreach ($item['selectedModifiers'] as $mod) { 
                $precioBaseConExtras += $mod['adjust']; 
            }
        }
        $montoDescuento = ($precioBaseConExtras * $cant) * ($item['discountPercentage'] / 100);

        // Observaciones
        $obsArray = [];
        if(isset($item['selectedModifiers'])){
            foreach ($item['selectedModifiers'] as $key => $mod) {
                $obsArray[] = $mod['value'];
            }
        }
        $observaciones = implode(", ", $obsArray);

        // Insertar Detalle
        $stmtDetalle->bind_param("iiidsd", $idVenta, $idProd, $cant, $precioUnitarioFinal, $observaciones, $montoDescuento);
        
        if (!$stmtDetalle->execute()) {
            throw new Exception("Error al guardar detalle ID $idProd: " . $stmtDetalle->error);
        }

        // SE ELIMINÓ EL UPDATE DE STOCK 
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Venta registrada', 'idVenta' => $idVenta]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>