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

    // NOTA: Se eliminó el paso 1 (Obtener detalles antiguos) ya que no necesitamos devolver stock.

    $sql = "UPDATE ventas SET tipoPago = ?, totalVenta = ? WHERE idVenta = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdi", $tipoPago, $totalVenta, $idVenta);
    $stmt->execute();
    $stmt->close();

    $sql = "DELETE FROM ventasdetalle WHERE idVenta = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idVenta);
    $stmt->execute();
    $stmt->close();

    $sqlInsert = "INSERT INTO ventasdetalle (idVenta, idProducto, cantidad, precioUnitario, descuento, observaciones) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);


    foreach ($productos as $prod) {
        $idProducto = $prod['idProducto'] ?? null;
        
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

        if (!$idProducto) continue; 

        $cantidad = (int)$prod['cantidad'];
        
        $precioUnitario = (float)$prod['precioUnitario']; 
        $porcentajeDescuento = (float)($prod['descuento'] ?? 0);
        $montoDescuento = ($precioUnitario * $cantidad) * ($porcentajeDescuento / 100);

        $observaciones = "";
        if (isset($prod['selectedModifiers']) && is_array($prod['selectedModifiers'])) {
            $obsArray = [];
            foreach ($prod['selectedModifiers'] as $mod) {
                if(isset($mod['value'])) $obsArray[] = $mod['value'];
            }
            $observaciones = implode(", ", $obsArray);
        }

        // Insertar detalle
        $stmtInsert->bind_param("iiidds", $idVenta, $idProducto, $cantidad, $precioUnitario, $montoDescuento, $observaciones);
        $stmtInsert->execute();

        // NOTA: Se eliminó el paso 5 (Gestión de Inventario / Diferencias).
    }

    // NOTA: Se eliminó el paso 6 (Devolver stock de eliminados).

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Venta modificada correctamente']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>