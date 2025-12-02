<?php
session_start();
include '../../conexion.php';

if (!isset($_SESSION['usuario'])) {
    die(json_encode(['success' => false, 'message' => 'No autenticado']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Método no permitido']));
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['idVenta']) || !isset($data['tipoPago']) || !isset($data['productos'])) {
    die(json_encode(['success' => false, 'message' => 'Datos incompletos']));
}

$idVenta = (int)$data['idVenta'];
$tipoPago = $data['tipoPago'];
$productos = $data['productos'];
$totalVenta = (float)$data['total'];

try {
    $conn->begin_transaction();

    // 1. Obtener detalles antiguos para inventario
    $sql = "SELECT idDetalle, idProducto, cantidad FROM ventasdetalle WHERE idVenta = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idVenta);
    $stmt->execute();
    $resultadoAntiguo = $stmt->get_result();
    
    $detallesAntiguos = [];
    while ($row = $resultadoAntiguo->fetch_assoc()) {
        $detallesAntiguos[$row['idProducto']] = [
            'cantidad' => $row['cantidad']
        ];
    }
    $stmt->close();

    // 2. Actualizar cabecera de venta
    $sql = "UPDATE ventas SET tipoPago = ?, totalVenta = ? WHERE idVenta = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdi", $tipoPago, $totalVenta, $idVenta);
    $stmt->execute();
    $stmt->close();

    // 3. Limpiar detalles viejos
    $sql = "DELETE FROM ventasdetalle WHERE idVenta = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idVenta);
    $stmt->execute();
    $stmt->close();

    // 4. Insertar nuevos detalles (CORREGIDO)
    $sqlInsert = "INSERT INTO ventasdetalle (idVenta, idProducto, cantidad, precioUnitario, descuento, observaciones) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);

    $sqlUpdateStock = "UPDATE productos SET stockActual = stockActual - ? WHERE idProducto = ?";
    $stmtStock = $conn->prepare($sqlUpdateStock);

    foreach ($productos as $prod) {
        $idProducto = $prod['idProducto'] ?? null;
        
        // Si no tiene ID, buscar por nombre (para productos agregados manualmente)
        if (!$idProducto) {
            $stmtFind = $conn->prepare("SELECT idProducto FROM productos WHERE nombre LIKE ? LIMIT 1");
            $likeName = '%' . $prod['nombre'] . '%';
            $stmtFind->bind_param("s", $likeName);
            $stmtFind->execute();
            $stmtFind->bind_result($foundId);
            if ($stmtFind->fetch()) {
                $idProducto = $foundId;
            }
            $stmtFind->close();
        }

        if (!$idProducto) continue; // Saltar si no se encuentra

        $cantidad = (int)$prod['cantidad'];
        
        // CORRECCIÓN CRÍTICA:
        // Recibimos precioUnitario (Bruto) y descuento (Porcentaje) desde JS
        $precioUnitario = (float)$prod['precioUnitario']; 
        $porcentajeDescuento = (float)($prod['descuento'] ?? 0);

        // Calculamos el MONTO del descuento para guardarlo en la BD (pesos)
        $montoDescuento = ($precioUnitario * $cantidad) * ($porcentajeDescuento / 100);

        // Reconstruir observaciones si existen
        $observaciones = "";
        if (isset($prod['selectedModifiers']) && is_array($prod['selectedModifiers'])) {
            $obsArray = [];
            foreach ($prod['selectedModifiers'] as $mod) {
                if(isset($mod['value'])) $obsArray[] = $mod['value'];
            }
            $observaciones = implode(", ", $obsArray);
        }

        // Insertar (Guardamos Precio Bruto y Monto Descuento por separado)
        $stmtInsert->bind_param("iiidds", $idVenta, $idProducto, $cantidad, $precioUnitario, $montoDescuento, $observaciones);
        $stmtInsert->execute();

        // 5. Gestión de Inventario (Diferencia)
        $cantidadAntigua = isset($detallesAntiguos[$idProducto]) ? $detallesAntiguos[$idProducto]['cantidad'] : 0;
        $diferencia = $cantidad - $cantidadAntigua;

        if ($diferencia != 0) {
            $stmtStock->bind_param("ii", $diferencia, $idProducto);
            $stmtStock->execute();
        }
        
        // Marcar como procesado para no devolver stock después
        if (isset($detallesAntiguos[$idProducto])) {
            unset($detallesAntiguos[$idProducto]);
        }
    }

    // 6. Devolver stock de productos eliminados completamente
    $sqlDevolver = "UPDATE productos SET stockActual = stockActual + ? WHERE idProducto = ?";
    $stmtDevolver = $conn->prepare($sqlDevolver);
    foreach ($detallesAntiguos as $idProdEliminado => $datos) {
        $stmtDevolver->bind_param("ii", $datos['cantidad'], $idProdEliminado);
        $stmtDevolver->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Venta modificada correctamente']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
