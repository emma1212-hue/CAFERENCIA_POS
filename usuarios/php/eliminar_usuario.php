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
        
        $sql = "DELETE FROM usuarios WHERE idUsuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idUsuario);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuario eliminado correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No se encontró el usuario o ya fue eliminado'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar el usuario: ' . $conn->error
            ]);
        }
        
        $stmt->close();
        
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