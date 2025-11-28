<?php
// obtenerTamanios.php
// Endpoint AJAX para obtener todas las variantes (tamaños) de un producto base.

// RUTA DE INCLUSIÓN CORREGIDA: Subir dos niveles para llegar a la raíz donde está conexion.php
require_once('../../conexion.php'); 

header('Content-Type: application/json');

// --- 1. Obtener y Limpiar el Nombre Base ---
// El JavaScript envía el parámetro como 'name', así que lo esperamos como 'name'.
$baseName = isset($_GET['name']) ? trim($_GET['name']) : '';

// 1. CHEQUEO BÁSICO DE CONEXIÓN
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión a la base de datos."]);
    exit();
}

if (empty($baseName)) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Nombre base del producto no proporcionado."]);
    $conn->close();
    exit();
}

// --- 2. Ejecución de la consulta preparada ---
$variants = [];
// Consulta SQL usando LIKE para encontrar todas las variantes (Chico, Grande)
$sql = "SELECT idProducto, nombre, precioVenta FROM productos WHERE nombre LIKE ? ORDER BY precioVenta ASC";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(["error" => "Error preparando la consulta: " . $conn->error]);
    $conn->close();
    exit();
}

// Preparamos el parámetro de búsqueda para el LIKE
$baseNameSearch = "%" . $baseName . "%";
$stmt->bind_param("s", $baseNameSearch);
$stmt->execute();
$resultado = $stmt->get_result();

// --- 3. Procesar Resultados ---
if ($resultado && $resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        $variants[] = $row;
    }
}

$stmt->close();
$conn->close();

// --- 4. Devolver los Datos en Formato JSON ---
echo json_encode($variants);

?>