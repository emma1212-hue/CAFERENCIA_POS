<?php
// productos/php/actualizar_inventario.php

include '../../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $idInventario = $_POST['idInventario'];
    $nombre = trim($_POST['nombre']);
    $nombreOriginal = trim($_POST['nombreOriginal']);
    $descripcion = trim($_POST['descripcion']);
    $unidad = trim($_POST['unidad']);
    $stockActual = $_POST['stockActual'];
    $costoUnitario = $_POST['costoUnitario'];
    $idCategoria = $_POST['idCategoria'];
    
    
    $errores = [];
    
   
    if (empty($idInventario)) {
        $errores[] = "ID del insumo no especificado";
    }
    
    if (empty($nombre)) {
        $errores[] = "El nombre del insumo es obligatorio";
    } elseif (strlen($nombre) > 255) {
        $errores[] = "El nombre del insumo es demasiado largo";
    }
    
    if (empty($descripcion)) {
        $errores[] = "La descripción del insumo es obligatoria";
    }
    
    if (empty($unidad)) {
        $errores[] = "La unidad de medida es obligatoria";
    }
    
    if (!is_numeric($stockActual) || $stockActual < 0) {
        $errores[] = "El stock actual debe ser un número válido mayor o igual a 0";
    }
    
    if (!is_numeric($costoUnitario) || $costoUnitario < 0) {
        $errores[] = "El costo unitario debe ser un número válido mayor o igual a 0";
    }
    
    if (empty($idCategoria) || !is_numeric($idCategoria)) {
        $errores[] = "Debe seleccionar una categoría válida";
    }
  
    if (empty($errores)) {
        $sql_existe = "SELECT idInventario FROM inventario WHERE idInventario = ?";
        $stmt_existe = $conn->prepare($sql_existe);
        $stmt_existe->bind_param("i", $idInventario);
        $stmt_existe->execute();
        $result_existe = $stmt_existe->get_result();
        
        if ($result_existe->num_rows == 0) {
            $errores[] = "El insumo a modificar no existe";
        }
        $stmt_existe->close();
    }
    
    
    if (empty($errores) && $nombre !== $nombreOriginal) {
        $sql_verificar = "SELECT idInventario FROM inventario WHERE nombre = ? AND idInventario != ?";
        $stmt_verificar = $conn->prepare($sql_verificar);
        $stmt_verificar->bind_param("si", $nombre, $idInventario);
        $stmt_verificar->execute();
        $result_verificar = $stmt_verificar->get_result();
        
        if ($result_verificar->num_rows > 0) {
            $errores[] = "Ya existe otro insumo con el nombre: " . htmlspecialchars($nombre);
        }
        $stmt_verificar->close();
    }
    
   
    if (empty($errores)) {
        try {
            
            $sql = "UPDATE inventario 
                    SET nombre = ?, descripcion = ?, unidad = ?, stockActual = ?, costoUnitario = ?, idCategoria = ? 
                    WHERE idInventario = ?";
            
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta de actualización: " . $conn->error);
            }
            
            $stmt->bind_param("sssidii", $nombre, $descripcion, $unidad, $stockActual, $costoUnitario, $idCategoria, $idInventario);
            
            if ($stmt->execute()) {
               
                header("Location: ../modificarInventario.php?success=1");
                exit();
            } else {
                throw new Exception("Error al ejecutar la actualización: " . $stmt->error);
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $errores[] = "Error al actualizar el insumo: " . $e->getMessage();
        }
    }
    
    
    if (!empty($errores)) {
        $error_string = implode("|", $errores);
        header("Location: ../modificarInventario.php?error=" . urlencode($error_string));
        exit();
    }
} else {
    
    header("Location: ../modificarInventario.php");
    exit();
}

$conn->close();
?>