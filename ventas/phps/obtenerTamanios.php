<?php
// obtenerTamanios.php
require_once('../../conexion.php'); 

header('Content-Type: application/json');

$baseName = isset($_GET['name']) ? trim($_GET['name']) : '';
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : 0;

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión."]);
    exit();
}

if (empty($baseName)) {
    echo json_encode([]);
    exit();
}

// Lista blanca de tamaños válidos para validar el sufijo
$tamanosValidos = [
    'Chico', 'Grande', 'Mediano', 'Pequeño', 
    'CH', 'G', 'M', 'Gde', 
    'Vaso', 'Frappé', 'Caliente', 'Frío', 
    'Individual', 'Estándar', '' 
];

// Consulta: Busca productos que EMPIECEN con el nombre base y sean de la misma CATEGORÍA
$sql = "SELECT idProducto, nombre, precioVenta FROM productos WHERE nombre LIKE ? AND idCategoria = ? ORDER BY precioVenta ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => $conn->error]);
    exit();
}

$baseNameSearch = $baseName . "%"; // 'Moka%'
$stmt->bind_param("si", $baseNameSearch, $categoryId);
$stmt->execute();
$resultado = $stmt->get_result();

$variants = [];

while ($row = $resultado->fetch_assoc()) {
    $nombreCompleto = $row['nombre'];
    
    // Obtenemos lo que sobra del nombre (el sufijo)
    // Ej: "Capuccino Moka" (sobra "Moka") vs "Capuccino Grande" (sobra "Grande")
    $resto = trim(str_ireplace($baseName, '', $nombreCompleto));
    
    // Validamos si el resto es un tamaño válido
    $esValido = false;
    // Si el nombre es idéntico al base, es válido (producto único)
    if ($resto === '') {
        $esValido = true;
    } else {
        foreach ($tamanosValidos as $tamano) {
            if (strcasecmp($resto, $tamano) == 0) { 
                $esValido = true;
                break;
            }
        }
    }
    
    if ($esValido) {
        $variants[] = $row;
    }
}

$stmt->close();
$conn->close();

echo json_encode($variants);
?>