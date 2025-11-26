<?php

include '../../conexion.php';


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verificar_nombre'])) {
   
    $nombre = trim($_POST['nombre']);
    
    
    header('Content-Type: application/json');
    
    if (empty($nombre)) {
        echo json_encode(['existe' => false]);
        exit();
    }
    
    try {
       
        if (!$conn) {
            throw new Exception("Error de conexión a la base de datos");
        }
       
        $sql_verificar = "SELECT idProducto FROM productos WHERE nombre = ?";
        $stmt_verificar = $conn->prepare($sql_verificar);
        
        if (!$stmt_verificar) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $stmt_verificar->bind_param("s", $nombre);
        
        if ($stmt_verificar->execute()) {
            $result_verificar = $stmt_verificar->get_result();
            
            if ($result_verificar->num_rows > 0) {
                echo json_encode(['existe' => true]);
            } else {
                echo json_encode(['existe' => false]);
            }
        } else {
            throw new Exception("Error al ejecutar la consulta: " . $stmt_verificar->error);
        }
        
        $stmt_verificar->close();
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} else {
    
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Solicitud inválida']);
}


if (isset($conn)) {
    $conn->close();
}
?>