<?php

include '../../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
   
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $unidad = trim($_POST['unidad']);
    $stockActual = $_POST['stockActual'];
    $costoUnitario = $_POST['costoUnitario'];
    $idCategoria = $_POST['idCategoria'];
    
   
    $errores = array();
    
  
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
        try {
            
            if (!$conn) {
                throw new Exception("Error de conexión a la base de datos");
            }
            
           
            $sql_verificar = "SELECT idInventario FROM inventario WHERE nombre = ?";
            $stmt_verificar = $conn->prepare($sql_verificar);
            
            if (!$stmt_verificar) {
                throw new Exception("Error al preparar la consulta de verificación: " . $conn->error);
            }
            
            $stmt_verificar->bind_param("s", $nombre);
            
            if ($stmt_verificar->execute()) {
                $result_verificar = $stmt_verificar->get_result();
                
                if ($result_verificar->num_rows > 0) {
                    $errores[] = "Ya existe un insumo con el nombre: " . htmlspecialchars($nombre);
                }
            } else {
                throw new Exception("Error al ejecutar la consulta de verificación: " . $stmt_verificar->error);
            }
            
            $stmt_verificar->close();
            
        } catch (Exception $e) {
            $errores[] = "Error al verificar el insumo: " . $e->getMessage();
        }
    }
    
    
    if (empty($errores)) {
        try {
           
            $sql_insertar = "INSERT INTO inventario (nombre, descripcion, unidad, stockActual, costoUnitario, idCategoria) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt_insertar = $conn->prepare($sql_insertar);
            
            if (!$stmt_insertar) {
                throw new Exception("Error al preparar la consulta de inserción: " . $conn->error);
            }
            
            
            $stmt_insertar->bind_param("sssidi", $nombre, $descripcion, $unidad, $stockActual, $costoUnitario, $idCategoria);
            
            
            if ($stmt_insertar->execute()) {
               
                header("Location: ../inventario.php?success=Insumo guardado correctamente");
                exit();
            } else {
                throw new Exception("Error al ejecutar la consulta de inserción: " . $stmt_insertar->error);
            }
            
           
            $stmt_insertar->close();
            
        } catch (Exception $e) {
            
            $errores[] = "Error al guardar el insumo: " . $e->getMessage();
        }
    }
    
    
    if (!empty($errores)) {
        $error_string = implode("|", $errores);
        
        
        $datos_formulario = "&nombre=" . urlencode($nombre) . 
                           "&descripcion=" . urlencode($descripcion) . 
                           "&unidad=" . urlencode($unidad) . 
                           "&stockActual=" . urlencode($stockActual) . 
                           "&costoUnitario=" . urlencode($costoUnitario) . 
                           "&idCategoria=" . urlencode($idCategoria);
        
        header("Location: ../inventario.php?error=" . urlencode($error_string) . $datos_formulario);
        exit();
    }
    
} else {
  
    header("Location: ../inventario.php");
    exit();
}


if (isset($conn)) {
    $conn->close();
}
?>