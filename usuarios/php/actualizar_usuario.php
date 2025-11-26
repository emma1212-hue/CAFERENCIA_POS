<?php
// usuarios/php/actualizar_usuario.php

include '../../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $idUsuario = $_POST['idUsuario'] ?? '';
    $nombre = trim($_POST['nombre']);
    $nombreDeUsuario = trim($_POST['nombreDeUsuario']);
    $password = $_POST['password'];
    $confirmar_password = $_POST['confirmar_password'];
    $rolUsuario = $_POST['rolUsuario'];
    
    $errores = [];
    
    
    if (empty($idUsuario)) $errores[] = "No se ha seleccionado un usuario";
    if (empty($nombre)) $errores[] = "El nombre es obligatorio";
    if (empty($nombreDeUsuario)) $errores[] = "El nombre de usuario es obligatorio";
    if (empty($rolUsuario)) $errores[] = "El rol de usuario es obligatorio";
    
    
    if (!empty($password)) {
        if ($password !== $confirmar_password) {
            $errores[] = "Las contraseñas no coinciden";
        }
    }
    
   
    if (empty($errores)) {
        $sql_check = "SELECT idUsuario FROM usuarios WHERE nombreDeUsuario = ? AND idUsuario != ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("si", $nombreDeUsuario, $idUsuario);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows > 0) {
            $errores[] = "El nombre de usuario ya existe";
        }
        $stmt_check->close();
    }
    
    
    if (empty($errores)) {
        if (!empty($password)) {
           
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET nombre = ?, nombreDeUsuario = ?, password = ?, rolUsuario = ? WHERE idUsuario = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $nombre, $nombreDeUsuario, $password_hash, $rolUsuario, $idUsuario);
        } else {
            
            $sql = "UPDATE usuarios SET nombre = ?, nombreDeUsuario = ?, rolUsuario = ? WHERE idUsuario = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $nombre, $nombreDeUsuario, $rolUsuario, $idUsuario);
        }
        
        if ($stmt->execute()) {
            header("Location: ../modificarUsuarios.php?success=1");
            exit();
        } else {
            $errores[] = "Error al actualizar el usuario: " . $conn->error;
        }
        
        $stmt->close();
    }
    
    
    if (!empty($errores)) {
        $error_string = implode("|", $errores);
        header("Location: ../modificarUsuarios.php?error=" . urlencode($error_string));
        exit();
    }
} else {
    header("Location: ../modificarUsuarios.php");
    exit();
}

$conn->close();
?>