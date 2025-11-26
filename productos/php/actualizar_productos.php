<?php
// productos/php/actualizar_productos.php

include '../../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $idProducto = $_POST['idProducto'];
    $nombre = trim($_POST['nombre']);
    $nombreOriginal = trim($_POST['nombreOriginal']);
    $descripcion = trim($_POST['descripcion']);
    $precioVenta = $_POST['precioVenta'];
    $idCategoria = $_POST['idCategoria'];
    
    
    $errores = [];
    
    
    if (empty($idProducto)) {
        $errores[] = "ID del producto no especificado";
    }
    
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
        $sql_existe = "SELECT idProducto FROM productos WHERE idProducto = ?";
        $stmt_existe = $conn->prepare($sql_existe);
        $stmt_existe->bind_param("i", $idProducto);
        $stmt_existe->execute();
        $result_existe = $stmt_existe->get_result();
        
        if ($result_existe->num_rows == 0) {
            $errores[] = "El producto a modificar no existe";
        }
        $stmt_existe->close();
    }
    
    
    if (empty($errores) && $nombre !== $nombreOriginal) {
        $sql_verificar = "SELECT idProducto FROM productos WHERE nombre = ? AND idProducto != ?";
        $stmt_verificar = $conn->prepare($sql_verificar);
        $stmt_verificar->bind_param("si", $nombre, $idProducto);
        $stmt_verificar->execute();
        $result_verificar = $stmt_verificar->get_result();
        
        if ($result_verificar->num_rows > 0) {
            $errores[] = "Ya existe otro producto con el nombre: " . htmlspecialchars($nombre);
        }
        $stmt_verificar->close();
    }
    
   
    if (empty($errores)) {
       
        $sql = "UPDATE productos 
                SET nombre = ?, descripcion = ?, precioVenta = ?, idCategoria = ? 
                WHERE idProducto = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdii", $nombre, $descripcion, $precioVenta, $idCategoria, $idProducto);
        
        if ($stmt->execute()) {
            
            header("Location: ../modificar_productos.php?id=$idProducto&success=1");
            exit();
        } else {
          
            $errores[] = "Error al actualizar el producto: " . $conn->error;
        }
        
        $stmt->close();
    }
 
    if (!empty($errores)) {
        $error_string = implode("|", $errores);
        header("Location: ../modificar_productos.php?id=$idProducto&error=" . urlencode($error_string));
        exit();
    }
} else {

    header("Location: ../modificar_productos.php");
    exit();
}

$conn->close();
?>