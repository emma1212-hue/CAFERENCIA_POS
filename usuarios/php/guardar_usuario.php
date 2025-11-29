<?php
// usuarios/php/guardar_usuario.php

include '../../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nombre = trim($_POST['nombre']);
    $nombreDeUsuario = trim($_POST['nombreDeUsuario']);
    $password = $_POST['password'];
    $confirmar_password = $_POST['confirmar_password'];
    $rolUsuario = $_POST['rolUsuario'];
    

    $errores = [];
    
   
    if ($password !== $confirmar_password) {
        $errores[] = "Las contraseñas no coinciden";
    }

    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }
    
    if (empty($nombreDeUsuario)) {
        $errores[] = "El nombre de usuario es obligatorio";
    }
    
    if (empty($password)) {
        $errores[] = "La contraseña es obligatoria";
    }
    
    if (empty($rolUsuario)) {
        $errores[] = "El rol de usuario es obligatorio";
    }
    
    
    if (empty($errores)) {
        $sql_verificar = "SELECT idUsuario FROM usuarios WHERE nombreDeUsuario = ? AND status = 'activo'";
        $stmt_verificar = $conn->prepare($sql_verificar);
        $stmt_verificar->bind_param("s", $nombreDeUsuario);
        $stmt_verificar->execute();
        $result_verificar = $stmt_verificar->get_result();
        
        if ($result_verificar->num_rows > 0) {
            $errores[] = "Ya existe un usuario activo con ese nombre de usuario: " . htmlspecialchars($nombreDeUsuario);
        }
        $stmt_verificar->close();
    }

    if (empty($errores)) {
       
        $sql = "INSERT INTO usuarios (nombre, nombreDeUsuario, password, rolUsuario, status) 
                VALUES (?, ?, ?, ?, 'activo')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nombre, $nombreDeUsuario, $password, $rolUsuario);
        
        if ($stmt->execute()) {
           
            header("Location: ../agregarUsuarios.php?success=1");
            exit();
        } else {
            
            $errores[] = "Error al guardar el usuario: " . $conn->error;
        }
        
        $stmt->close();
    }
    
    
    if (!empty($errores)) {
        $error_string = implode("|", $errores);
        header("Location: ../agregarUsuarios.php?error=" . urlencode($error_string));
        exit();
    }
} else {
    
    header("Location: ../agregarUsuarios.php");
    exit();
}

$conn->close();
?>