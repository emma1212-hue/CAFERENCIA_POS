<?php
$host = "localhost";
$user = "root"; 
$pass = "";     
//$db   = "cafeherencia";
$db = "caferencia"; //  Por si tienen otro nombre como yo jiji

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
