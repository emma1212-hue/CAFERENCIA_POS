<?php
// productos/php/guardar_productos.php

include '../../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precioVenta = $_POST['precioVenta'];
    $idCategoria = $_POST['idCategoria'];
    
    
    $errores = [];
   
    if (empty($nombre)) {
        $errores[] = "El nombre del producto es obligatorio";
    }
    
    if (empty($descripcion)) {
        $errores[] = "La descripción es obligatoria";
    }
    
    if (empty($precioVenta) || $precioVenta <= 0) {
        $errores[] = "El precio de venta debe ser mayor a 0";
    }
    
    if (empty($idCategoria)) {
        $errores[] = "La categoría es obligatoria";
    }
    
    
    if (empty($errores)) {
        $sql_verificar = "SELECT idProducto FROM productos WHERE nombre = ?";
        $stmt_verificar = $conn->prepare($sql_verificar);
        $stmt_verificar->bind_param("s", $nombre);
        $stmt_verificar->execute();
        $result_verificar = $stmt_verificar->get_result();
        
        if ($result_verificar->num_rows > 0) {
            $errores[] = "Ya existe un producto con el nombre: " . htmlspecialchars($nombre);
        }
        $stmt_verificar->close();
    }
    
    
    if (empty($errores)) {
        
        $sql = "INSERT INTO productos (nombre, descripcion, precioVenta, idCategoria) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdi", $nombre, $descripcion, $precioVenta, $idCategoria);
        
        if ($stmt->execute()) {
         
            header("Location: ../registrarProductos.php?success=1");
            exit();
        } else {
           
            $errores[] = "Error al guardar el producto: " . $conn->error;
        }
        
        $stmt->close();
    }
    
    
    if (!empty($errores)) {
        $error_string = implode("|", $errores);
        header("Location: ../registrarProductos.php?error=" . urlencode($error_string));
        exit();
    }
} else {
    
    header("Location: ../registrarProductos.php");
    exit();
}

$conn->close();
?>