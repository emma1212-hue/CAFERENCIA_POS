<?php
// usuarios/php/eliminar_usuario.php

header('Content-Type: application/json');
include '../../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idUsuario = $_POST['idUsuario'] ?? '';
    
    if (empty($idUsuario)) {
        echo json_encode([
            'success' => false,
            'message' => 'No se ha proporcionado un ID de usuario'
        ]);
        exit;
    }
    
    try {
        
        $conn->begin_transaction();
        
       
        $sql = "DELETE FROM usuarios WHERE idUsuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idUsuario);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al eliminar el usuario: ' . $conn->error);
        }
        
        $filasAfectadas = $stmt->affected_rows;
        $stmt->close();
        
        if ($filasAfectadas > 0) {
            // 2. Calcular y establecer el próximo AUTO_INCREMENT
            $sql_max = "SELECT COALESCE(MAX(idUsuario), 0) + 1 as next_id FROM usuarios";
            $result = $conn->query($sql_max);
            
            if ($result) {
                $row = $result->fetch_assoc();
                $next_id = $row['next_id'];
                
                // Reiniciar AUTO_INCREMENT al siguiente ID secuencial
                $sql_reset = "ALTER TABLE usuarios AUTO_INCREMENT = ?";
                $stmt_reset = $conn->prepare($sql_reset);
                $stmt_reset->bind_param("i", $next_id);
                
                if (!$stmt_reset->execute()) {
                    throw new Exception('Error al reiniciar auto_increment: ' . $conn->error);
                }
                
                $stmt_reset->close();
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Usuario eliminado correctamente'
            ]);
            
        } else {
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'No se encontró el usuario o ya fue eliminado'
            ]);
        }
        
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