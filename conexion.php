<?php
$host = "localhost";
$user = "root"; // tu usuario de MySQL
$pass = "";     // tu contraseña de MySQL
$db   = "cafeherencia";

$conn = new mysqli($host, $user, $pass, $db);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
