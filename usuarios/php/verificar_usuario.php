<?php
// usuarios/php/verificar_usuario.php

header('Content-Type: application/json');
include '../../conexion.php';

if (isset($_GET['usuario'])) {
    $usuario = trim($_GET['usuario']);
    
    if (!empty($usuario)) {
      
        $sql = "SELECT idUsuario FROM usuarios WHERE nombreDeUsuario = ? AND status = 'activo'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $stmt->store_result();
        
        $existe = $stmt->num_rows > 0;
        
        echo json_encode(['existe' => $existe]);
        $stmt->close();
    } else {
        echo json_encode(['existe' => false]);
    }
} else {
    echo json_encode(['existe' => false]);
}

$conn->close();
?>