<?php
// ventas/phps/guardar_venta.php

// 1. Conexión
require_once('../../conexion.php'); 
session_start();

// --- CONFIGURACIÓN DE ZONA HORARIA ---
// IMPORTANTE: Ajusta esto a tu zona horaria para que coincida con el negocio
date_default_timezone_set('America/Mexico_City'); 

header('Content-Type: application/json');

// --- VALIDACIONES INICIALES ---

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


// --- LÓGICA DE CORTE DE CAJA AUTOMÁTICO ---
$idCorte = null;
$fechaHoy = date('Y-m-d'); // Fecha actual según PHP

// A. Buscar si existe un corte abierto
// MODIFICACIÓN: Buscamos NULL o '00:00:00' para evitar errores si la BD pone ceros por defecto
$sqlBuscar = "SELECT idCorte, fechaCorte FROM cortecaja 
              WHERE (horaCierre IS NULL OR horaCierre = '00:00:00') 
              ORDER BY idCorte DESC LIMIT 1";

$resBuscar = $conn->query($sqlBuscar);

if ($resBuscar && $resBuscar->num_rows > 0) {
    $fila = $resBuscar->fetch_assoc();
    
    // Validar si el corte abierto es del día de HOY
    if ($fila['fechaCorte'] == $fechaHoy) {
        // Coincide la fecha, usamos este corte
        $idCorte = $fila['idCorte'];
    } else {
        // Hay un corte abierto pero es de una FECHA ANTERIOR.
        // Lo cerramos y forzamos la creación de uno nuevo.
        $idViejo = $fila['idCorte'];
        $conn->query("UPDATE cortecaja SET horaCierre = '23:59:59' WHERE idCorte = $idViejo");
        
        $idCorte = null; 
    }
}

// B. Si no se encontró corte válido (o se cerró el viejo), CREAR UNO NUEVO
if ($idCorte === null) {
    // MODIFICACIÓN:
    // 1. Usamos '$fechaHoy' (PHP) en lugar de CURRENT_DATE() (MySQL) para asegurar consistencia al comparar.
    // 2. Forzamos NULL en horaCierre explícitamente si tu tabla lo permite, o simplemente omitimos el campo.
    // 3. Asegúrate que tu tabla 'cortecaja' permita NULL en 'horaCierre'.
    
    $horaActual = date('H:i:s');
    
    $sqlNuevo = "INSERT INTO cortecaja (fechaCorte, horaInicio, fondoInicial, totalVentasSistema, totalGasto, horaCierre) 
                 VALUES (?, ?, 0, 0, 0, NULL)";
    
    $stmtCorte = $conn->prepare($sqlNuevo);
    // s = string (fecha), s = string (hora)
    $stmtCorte->bind_param("ss", $fechaHoy, $horaActual);
    
    if ($stmtCorte->execute()) {
        $idCorte = $conn->insert_id;
        $stmtCorte->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error crítico al abrir Corte de Caja: ' . $conn->error]);
        exit();
    }
}


// --- TRANSACCIÓN DE VENTA ---
$conn->begin_transaction();

try {
    // 1. Insertar Venta
    $sqlVenta = "INSERT INTO ventas (fechaVenta, totalVenta, tipoPago, idUsuario, idCorte) VALUES (NOW(), ?, ?, ?, ?)";
    $stmt = $conn->prepare($sqlVenta);
    
    if (!$stmt) throw new Exception("Error preparando venta: " . $conn->error);
    
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

    // 3. Recorrer productos
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

        // Descontar Stock
        $stmtStock->bind_param("ii", $cant, $idProd);
        $stmtStock->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Venta registrada', 'idVenta' => $idVenta]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>