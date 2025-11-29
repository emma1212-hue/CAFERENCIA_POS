<?php

require('../../conexion.php');
require('fpdf/fpdf.php'); // Asegúrate de que la ruta sea correcta

session_start();

if (!isset($_SESSION['usuario'])) {
    die("Acceso denegado");
}

$idVenta = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idVenta == 0) {
    die("ID de venta no válido");
}

// 1. Obtener Datos de la Venta (Encabezado)
$sqlVenta = "SELECT v.*, u.nombreDeUsuario 
             FROM ventas v 
             LEFT JOIN usuarios u ON v.idUsuario = u.idUsuario 
             WHERE v.idVenta = ?";
$stmt = $conn->prepare($sqlVenta);
$stmt->bind_param("i", $idVenta);
$stmt->execute();
$resVenta = $stmt->get_result();
$venta = $resVenta->fetch_assoc();

if (!$venta) {
    die("Venta no encontrada");
}

// 2. Obtener Detalles (Productos)
$sqlDetalle = "SELECT * FROM ventasdetalle WHERE idVenta = ?";
$stmt2 = $conn->prepare($sqlDetalle);
$stmt2->bind_param("i", $idVenta);
$stmt2->execute();
$resDetalle = $stmt2->get_result();

// --- CONFIGURACIÓN DEL PDF (TAMAÑO TICKET 80mm) ---
// El alto (200) es inicial, FPDF no soporta rollo infinito nativo fácilmente, 
// pero para visualización está bien. Para impresión real se ajusta solo.
$pdf = new FPDF('P', 'mm', array(80, 200)); 
$pdf->AddPage();
$pdf->SetMargins(4, 4, 4); // Márgenes pequeños (4mm)
$pdf->SetAutoPageBreak(true, 2); // Salto de página automático

// --- ENCABEZADO ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(72, 5, utf8_decode('CAFÉRENCIA'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(72, 4, utf8_decode('Av. Vicente Guerrero #47'), 0, 1, 'C');
$pdf->Cell(72, 4, utf8_decode('Centro, Iguala, Gro.'), 0, 1, 'C');
$pdf->Cell(72, 4, utf8_decode('Tel: 733 135 3321'), 0, 1, 'C');
$pdf->Ln(2);

// Datos del Ticket
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(15, 4, 'Folio:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(57, 4, $venta['idVenta'], 0, 1, 'L');

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(15, 4, 'Fecha:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(57, 4, date('d/m/Y H:i', strtotime($venta['fechaVenta'])), 0, 1, 'L');

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(15, 4, 'Cajero:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 8);
// Si usas el nombre de usuario de la sesión o DB
$cajero = $venta['nombreDeUsuario'] ?? 'Cajero'; 
$pdf->Cell(57, 4, utf8_decode($cajero), 0, 1, 'L');

$pdf->Ln(2);
$pdf->Cell(72, 0, '', 'T'); // Línea separadora
$pdf->Ln(2);

// --- TABLA DE PRODUCTOS ---
// Encabezados
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(10, 4, 'Cant', 0, 0, 'C');
$pdf->Cell(42, 4, utf8_decode('Producto'), 0, 0, 'L');
$pdf->Cell(20, 4, 'Importe', 0, 1, 'R');
$pdf->Ln(1);

$pdf->SetFont('Arial', '', 7);

while ($row = $resDetalle->fetch_assoc()) {
    $importe = $row['precioUnitario'] * $row['cantidad'];
    
    // Cantidad
    $pdf->Cell(10, 4, $row['cantidad'], 0, 0, 'C');
    
    // Nombre del producto (MultiCell para que baje si es largo)
    // Guardamos la posición actual X e Y
    $currentX = $pdf->GetX();
    $currentY = $pdf->GetY();
    
    // Imprimimos el nombre con ancho 42
    $pdf->MultiCell(42, 4, utf8_decode($row['nombre_producto']), 0, 'L');
    
    // Obtenemos la nueva posición Y después del nombre
    $newY = $pdf->GetY();
    
    // Imprimimos el importe a la derecha, volviendo a la altura inicial
    $pdf->SetXY($currentX + 42, $currentY);
    $pdf->Cell(20, 4, '$' . number_format($importe, 2), 0, 1, 'R');
    
    // Si hay observaciones (modificadores), las ponemos abajo en gris o cursiva
    if (!empty($row['observaciones'])) {
        $pdf->SetFont('Arial', 'I', 6);
        $pdf->SetX(14); // Sangría
        $pdf->MultiCell(58, 3, utf8_decode('(' . $row['observaciones'] . ')'), 0, 'L');
        $pdf->SetFont('Arial', '', 7); // Restaurar fuente
        // Ajustamos Y si es necesario
        $newY = $pdf->GetY();
    }
    
    // Movemos el cursor a la nueva línea más baja para el siguiente producto
    $pdf->SetY($newY);
}

$pdf->Ln(2);
$pdf->Cell(72, 0, '', 'T'); // Línea separadora
$pdf->Ln(2);

// --- TOTALES ---
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(45, 5, 'TOTAL:', 0, 0, 'R');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(27, 5, '$' . number_format($venta['totalVenta'], 2), 0, 1, 'R');

$pdf->SetFont('Arial', '', 8);
$pdf->Cell(45, 5, 'Pago (' . $venta['tipoPago'] . '):', 0, 0, 'R');
// Aquí podrías poner el monto recibido si lo guardaste en DB
$pdf->Cell(27, 5, '$' . number_format($venta['totalVenta'], 2), 0, 1, 'R'); 

$pdf->Ln(5);

// --- PIE DE PÁGINA ---
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(72, 4, utf8_decode('¡Gracias por su preferencia!'), 0, 1, 'C');
$pdf->Cell(72, 4, utf8_decode('Síguenos en @caferencia_iguala'), 0, 1, 'C');
$pdf->Ln(5);
//$pdf->SetFont('Arial', 'I', 7);
//$pdf->Cell(72, 4, 'Software desarrollado por TuNombre', 0, 1, 'C');

// Salida del PDF
$pdf->Output('I', 'Ticket_' . $idVenta . '.pdf'); // 'I' lo muestra en el navegador, 'D' lo descarga
?>