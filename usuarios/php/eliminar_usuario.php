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
        $sql_check = "SELECT idUsuario, nombre, rolUsuario, status FROM usuarios WHERE idUsuario = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $idUsuario);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'El usuario no existe'
            ]);
            exit;
        }
        
        $usuario = $result_check->fetch_assoc();
        $stmt_check->close();
        
        if ($usuario['status'] === 'inactivo') {
            echo json_encode([
                'success' => false,
                'message' => 'El usuario ya fue eliminado'
            ]);
            exit;
        }
        
        // Validar que no sea el último administrador activo
        if ($usuario['rolUsuario'] === 'admin') {
            $sql_admin_check = "SELECT COUNT(*) as admin_count FROM usuarios WHERE rolUsuario = 'admin' AND status = 'activo'";
            $result_admin = $conn->query($sql_admin_check);
            $admin_count = $result_admin->fetch_assoc()['admin_count'];
            
            if ($admin_count <= 1) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No se puede eliminar el último administrador'
                ]);
                exit;
            }
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al validar usuario: ' . $e->getMessage()
        ]);
        exit;
    }
    
    try {
        
        $sql = "UPDATE usuarios SET status = 'inactivo' WHERE idUsuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idUsuario);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al eliminar el usuario: ' . $conn->error);
        }
        
        $filasAfectadas = $stmt->affected_rows;
        $stmt->close();
        
        if ($filasAfectadas > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Usuario eliminado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo eliminar el usuario'
            ]);
        }
        
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