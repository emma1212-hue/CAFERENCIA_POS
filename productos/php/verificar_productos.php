<?php
// productos/php/verificar_productos.php

include '../../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verificar_nombre'])) {
    $nombre = trim($_POST['nombre']);
    
    
    $sql = "SELECT idProducto FROM productos WHERE nombre = ? AND status = 'activo'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $stmt->store_result();
    
   
    echo json_encode(['existe' => $stmt->num_rows > 0]);
    
    $stmt->close();
    $conn->close();
    exit();
}

// Si no es una verificación de nombre, devolver false
echo json_encode(['existe' => false]);
?>