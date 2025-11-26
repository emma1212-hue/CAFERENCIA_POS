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
       
        $conn->begin_transaction();
        
        $sql_delete = "DELETE FROM productos WHERE idProducto = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $idProducto);
        
        if (!$stmt_delete->execute()) {
            throw new Exception('Error al eliminar el producto: ' . $conn->error);
        }
        
        if ($stmt_delete->affected_rows === 0) {
            throw new Exception('No se encontró el producto o ya fue eliminado');
        }
   
        $sql_update_ids = "SET @new_id = 0;
                          UPDATE productos SET idProducto = @new_id:=@new_id+1 ORDER BY idProducto;
                          ALTER TABLE productos AUTO_INCREMENT = 1;";
        
        if ($conn->multi_query($sql_update_ids)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
        } else {
            throw new Exception('Error al reorganizar IDs: ' . $conn->error);
        }
        
       
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Producto eliminado correctamente y IDs reorganizados'
        ]);
        
        $stmt_delete->close();
        
    } catch (Exception $e) {
      
        $conn->rollback();
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