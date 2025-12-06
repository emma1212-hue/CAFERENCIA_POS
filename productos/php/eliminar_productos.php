<?php
// productos/php/eliminar_productos.php

header('Content-Type: application/json');
include '../../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idProducto = $_POST['idProducto'] ?? '';
    
    if (empty($idProducto)) {
        echo json_encode([
            'success' => false,
            'message' => 'No se ha proporcionado un ID de producto'
        ]);
        exit;
    }
    
    try {
        
        $sql_update = "UPDATE productos SET status = 'inactivo' WHERE idProducto = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $idProducto);
        
        if (!$stmt_update->execute()) {
            throw new Exception('Error al eliminar el producto: ' . $conn->error);
        }
        
        if ($stmt_update->affected_rows === 0) {
            throw new Exception('No se encontró el producto o ya fue eliminado');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Producto eliminado correctamente'
        ]);
        
        $stmt_update->close();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}

$conn->close();
?>