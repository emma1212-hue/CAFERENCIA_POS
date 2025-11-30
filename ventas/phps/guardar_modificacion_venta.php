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
    // Iniciar transacción
    $conn->begin_transaction();

    // Obtener detalles antiguos para calcular diferencias en inventario
    $sql = "SELECT idDetalle, idProducto, cantidad FROM ventasDetalle WHERE idVenta = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en prepare: " . $conn->error);
    }
    $stmt->bind_param("i", $idVenta);
    $stmt->execute();
    $resultadoAntiguo = $stmt->get_result();
    
    $detallesAntiguos = [];
    while ($row = $resultadoAntiguo->fetch_assoc()) {
        $detallesAntiguos[$row['idProducto']] = [
            'idDetalle' => $row['idDetalle'],
            'cantidad' => $row['cantidad']
        ];
    }
    $stmt->close();

    // Actualizar tipo de pago y total de la venta
    $sql = "UPDATE ventas SET tipoPago = ?, totalVenta = ? WHERE idVenta = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en prepare: " . $conn->error);
    }
    $stmt->bind_param("sdi", $tipoPago, $totalVenta, $idVenta);
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar venta: " . $stmt->error);
    }
    $stmt->close();

    // Eliminar detalles antiguos
    $sql = "DELETE FROM ventasDetalle WHERE idVenta = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en prepare: " . $conn->error);
    }
    $stmt->bind_param("i", $idVenta);
    if (!$stmt->execute()) {
        throw new Exception("Error al eliminar detalles: " . $stmt->error);
    }
    $stmt->close();

    // Insertar nuevos detalles y actualizar inventario
    foreach ($productos as $prod) {
        $idProducto = $prod['idProducto'] ?? null;
        $cantidad = (int)$prod['cantidad'];
        $precioUnitario = (float)$prod['precioUnitario'];
        $descuento = (float)($prod['descuento'] ?? 0);

        // Si no tiene idProducto, intentar encontrarlo por nombre
        if (!$idProducto) {
            $sql = "SELECT idProducto FROM productos WHERE nombre LIKE ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $nombre = '%' . $prod['nombre'] . '%';
                $stmt->bind_param("s", $nombre);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $idProducto = $row['idProducto'];
                }
                $stmt->close();
            }
        }

        // Si aún no tiene idProducto, saltar
        if (!$idProducto) {
            continue;
        }

        // Insertar nuevo detalle
        $sql = "INSERT INTO ventasDetalle (idVenta, idProducto, cantidad, precioUnitario, descuento) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en prepare: " . $conn->error);
        }
        $stmt->bind_param("iiddd", $idVenta, $idProducto, $cantidad, $precioUnitario, $descuento);
        if (!$stmt->execute()) {
            throw new Exception("Error al insertar detalle: " . $stmt->error);
        }
        $stmt->close();

        // Actualizar inventario basado en cambios de cantidad
        if (isset($detallesAntiguos[$idProducto])) {
            $cantidadAntigua = $detallesAntiguos[$idProducto]['cantidad'];
            $diferencia = $cantidad - $cantidadAntigua; // Positivo = aumentó, Negativo = disminuyó
            
            if ($diferencia !== 0) {
                // Restar la diferencia del inventario (si aumentó, se resta más; si disminuyó, se suma)
                $sql = "UPDATE productos SET stockActual = stockActual - ? WHERE idProducto = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Error en prepare: " . $conn->error);
                }
                $stmt->bind_param("ii", $diferencia, $idProducto);
                if (!$stmt->execute()) {
                    throw new Exception("Error al actualizar inventario: " . $stmt->error);
                }
                $stmt->close();
            }
            
            // Marcar como procesado
            unset($detallesAntiguos[$idProducto]);
        } else {
            // Es un producto nuevo, restar del inventario
            $sql = "UPDATE productos SET stockActual = stockActual - ? WHERE idProducto = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error en prepare: " . $conn->error);
            }
            $stmt->bind_param("ii", $cantidad, $idProducto);
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar inventario: " . $stmt->error);
            }
            $stmt->close();
        }
    }

    // Procesar productos que fueron eliminados (devolver al inventario)
    foreach ($detallesAntiguos as $idProductoEliminado => $detalle) {
        $cantidadADevolverAlInventario = $detalle['cantidad'];
        $sql = "UPDATE productos SET stockActual = stockActual + ? WHERE idProducto = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en prepare: " . $conn->error);
        }
        $stmt->bind_param("ii", $cantidadADevolverAlInventario, $idProductoEliminado);
        if (!$stmt->execute()) {
            throw new Exception("Error al devolver inventario: " . $stmt->error);
        }
        $stmt->close();
    }

    // Confirmar transacción
    $conn->commit();
    die(json_encode(['success' => true, 'message' => 'Venta actualizada correctamente']));

} catch (Exception $e) {
    // Revertir en caso de error
    $conn->rollback();
    die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}
?>

