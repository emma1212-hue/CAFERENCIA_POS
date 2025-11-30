<?php
// ventas/phps/generar_ticket.php

require('../../conexion.php');
require('fpdf/fpdf.php');

session_start();

if (!isset($_SESSION['usuario'])) {
    die("Acceso denegado. Inicie sesión.");
}

$idVenta = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idVenta == 0) {
    die("ID de venta no válido");
}

// 1. OBTENER DATOS DE LA VENTA
$sqlVenta = "SELECT v.*, u.nombre 
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

// 2. OBTENER DETALLES
$sqlDetalle = "SELECT vd.*, p.nombre AS nombre_catalogo
               FROM ventasdetalle vd
               LEFT JOIN productos p ON vd.idProducto = p.idProducto
               WHERE vd.idVenta = ?";
               
$stmt2 = $conn->prepare($sqlDetalle);
$stmt2->bind_param("i", $idVenta);
$stmt2->execute();
$resDetalle = $stmt2->get_result();

// --- CONFIGURACIÓN PDF (80mm) ---
$pdf = new FPDF('P', 'mm', array(80, 200)); 
$pdf->AddPage();
$pdf->SetMargins(4, 4, 4);
$pdf->SetAutoPageBreak(true, 2);

// --- ENCABEZADO CON LOGO ---

// 1. Configuración del Logo
$rutaLogo = '../../homepage/img/logoCaferencia.png'; // <--- VERIFICA ESTA RUTA Y NOMBRE
$anchoLogo = 25; // Ancho en milímetros que quieres que tenga el logo en el papel

// Si el archivo existe, lo ponemos
if (file_exists($rutaLogo)) {
    // Cálculo para CENTRAR: (AnchoPapel - AnchoImagen) / 2
    // (80 - 25) / 2 = 27.5
    $xLogo = (80 - $anchoLogo) / 2;
    
    // Image(archivo, x, y, w)
    $pdf->Image($rutaLogo, $xLogo, 4, $anchoLogo); 
    
    // Movemos el cursor hacia abajo para que el texto no quede encima del logo
    $pdf->Ln(18); // Ajusta este número según la altura de tu logo
}

// --- ENCABEZADO ---

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(72, 5, utf8_decode('CAFÉrencia'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(72, 4, utf8_decode('Av. Vicente Guerrero #47'), 0, 1, 'C');
$pdf->Cell(72, 4, utf8_decode('Centro, Iguala, Gro.'), 0, 1, 'C');
$pdf->Ln(2);

// DATOS
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
$cajero = $venta['nombre'] ?? 'Cajero'; 
$pdf->Cell(57, 4, utf8_decode($cajero), 0, 1, 'L');

$pdf->Ln(2);
$pdf->Cell(72, 0, '', 'T'); 
$pdf->Ln(2);

// --- PRODUCTOS ---
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(8, 4, 'Cant', 0, 0, 'C'); 
$pdf->Cell(44, 4, utf8_decode('Producto'), 0, 0, 'L');
$pdf->Cell(20, 4, 'Importe', 0, 1, 'R');
$pdf->Ln(1);

$pdf->SetFont('Arial', '', 7);

$sumaDescuentos = 0; // Variable para acumular descuento total

while ($row = $resDetalle->fetch_assoc()) {

    // Si guardaste el precioUnitario como el precio FINAL (con descuento ya restado):
    $importeCobrado = $row['precioUnitario'] * $row['cantidad'];
    
    // Obtenemos el descuento de la DB
    $descuentoItem = floatval($row['descuento']);
    $sumaDescuentos += $descuentoItem;

    $nombreProducto = $row['nombre_producto'] ?? $row['nombre_catalogo'] ?? 'Producto';

    // 1. Cantidad
    $pdf->Cell(8, 4, $row['cantidad'], 0, 0, 'C');
    
    // 2. Nombre
    $currentX = $pdf->GetX();
    $currentY = $pdf->GetY();
    
    $pdf->MultiCell(44, 4, utf8_decode($nombreProducto), 0, 'L');
    $newY = $pdf->GetY();
    
    // 3. Importe (Mostramos lo que se cobró realmente)
    $pdf->SetXY($currentX + 44, $currentY);
    $pdf->Cell(20, 4, '$' . number_format($importeCobrado, 2), 0, 1, 'R');
    
    // Movemos cursor abajo del nombre para imprimir detalles extra
    $pdf->SetY($newY); 

    // 4. Observaciones (Leche, Extras)
    if (!empty($row['observaciones'])) {
        $pdf->SetFont('Arial', 'I', 6);
        $pdf->SetX(12); 
        $pdf->MultiCell(60, 3, utf8_decode('(' . $row['observaciones'] . ')'), 0, 'L');
    }

    // 5. MOSTRAR DESCUENTO INDIVIDUAL (Si existe)
    if ($descuentoItem > 0) {
        $pdf->SetFont('Arial', 'B', 6); // Negrita pequeña
        $pdf->SetX(12); 
        // Mostramos el ahorro en negativo
        $pdf->Cell(60, 3, utf8_decode('Desc: -$' . number_format($descuentoItem, 2)), 0, 1, 'L');
    }

    $pdf->SetFont('Arial', '', 7); // Restaurar fuente normal
    $pdf->Ln(1); // Espacio entre productos
}

$pdf->Ln(1); // Espacio mínimo después de la línea
$pdf->Cell(72, 0, '', 'T'); 
$pdf->Ln(1); // Espacio mínimo antes de totales

// --- TOTALES COMPACTOS ---
$totalCobrado = floatval($venta['totalVenta']);
$subtotalReal = $totalCobrado + $sumaDescuentos; 

// 1. Subtotales (Fuente pequeña y altura de celda 3.5)
if ($sumaDescuentos > 0) {
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(45, 3.5, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(27, 3.5, '$' . number_format($subtotalReal, 2), 0, 1, 'R');

    $pdf->Cell(45, 3.5, 'Ahorro:', 0, 0, 'R');
    $pdf->Cell(27, 3.5, '-$' . number_format($sumaDescuentos, 2), 0, 1, 'R');
}

// 2. TOTAL (Un poco más grande, pero pegado a lo anterior)
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(45, 6, 'TOTAL:', 0, 0, 'R'); // Altura 6 para destacar
$pdf->Cell(27, 6, '$' . number_format($totalCobrado, 2), 0, 1, 'R');

// 3. DATOS DE PAGO (Pegados al total, fuente normal)
$pdf->SetFont('Arial', '', 7);

// Renglón Método de Pago
$pdf->Cell(45, 4, utf8_decode('Pago (' . $venta['tipoPago'] . '):'), 0, 0, 'R');
$pdf->Cell(27, 4, '$' . number_format($totalCobrado, 2), 0, 1, 'R'); 

// Renglón Efectivo/Cambio (Si aplica)
if ($venta['tipoPago'] == 'Efectivo' && isset($_GET['recibido'])) {
    $recibido = floatval($_GET['recibido']);
    $cambio = isset($_GET['cambio']) ? floatval($_GET['cambio']) : ($recibido - $totalCobrado);

    $pdf->Cell(45, 4, 'Efectivo:', 0, 0, 'R');
    $pdf->Cell(27, 4, '$' . number_format($recibido, 2), 0, 1, 'R');

    $pdf->SetFont('Arial', 'B', 7); // Negrita para el cambio
    $pdf->Cell(45, 4, 'Cambio:', 0, 0, 'R');
    $pdf->Cell(27, 4, '$' . number_format($cambio, 2), 0, 1, 'R');
}

// --- PIE DE PÁGINA COMPACTO ---
$pdf->Ln(4); // Solo un pequeño espacio antes del pie

$pdf->SetFont('Arial', '', 8);
$pdf->Cell(72, 3.5, utf8_decode('¡Gracias por su preferencia!'), 0, 1, 'C');

$pdf->SetFont('Arial', 'B', 8); // Redes sociales en negrita destacan más
$pdf->Cell(72, 3.5, utf8_decode('@caferencia_iguala'), 0, 1, 'C');

$pdf->Ln(2); // Mínimo espacio final
$pdf->SetFont('Arial', 'I', 6);
//$pdf->SetFont('Arial', 'I', 6);
//$pdf->Cell(72, 4, 'Sistema v1.0', 0, 1, 'C');

$pdf->Output('I', 'Ticket_' . $idVenta . '.pdf'); 
?>