<?php
// usuarios/guardar_usuario.php


include '../../conexion.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nombre = trim($_POST['nombre']);
    $nombreDeUsuario = trim($_POST['nombreDeUsuario']);
    $password = $_POST['password'];
    $confirmar_password = $_POST['confirmar_password'];
    $rolUsuario = $_POST['rolUsuario'];
    
    // Validaciones
    $errores = [];
    
    // Verificar que las contraseñas coincidan
    if ($password !== $confirmar_password) {
        $errores[] = "Las contraseñas no coinciden";
    }
    
    // Verificar campos vacíos
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
        // Encriptar la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (nombre, nombreDeUsuario, password, rolUsuario) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nombre, $nombreDeUsuario, $password_hash, $rolUsuario);
        
        if ($stmt->execute()) {
           
            header("Location: ../agregarUsuarios.php?success=1");

            exit();
        } else {
            
            if ($conn->errno == 1062) { // Error de duplicado
                $errores[] = "El nombre de usuario ya existe";
            } else {
                $errores[] = "Error al guardar el usuario: " . $conn->error;
            }
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