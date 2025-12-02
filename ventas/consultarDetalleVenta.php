<?php
// AJUSTA LA RUTA DE CONEXION SI ES NECESARIO
include '../conexion.php'; 

if (!isset($_POST['idVenta'])) { die("Error: ID faltante"); }

$idVenta = (int)$_POST['idVenta'];

// Consulta SQL corregida para obtener descuento directo
$sql = "SELECT 
            vd.cantidad, 
            vd.precioUnitario, 
            vd.descuento, 
            vd.idProducto, 
            p.idCategoria,
            p.nombre 
        FROM ventasdetalle vd
        JOIN productos p ON vd.idProducto = p.idProducto
        WHERE vd.idVenta = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idVenta);
$stmt->execute();
$res = $stmt->get_result();

$totalDescuentoGlobal = 0;

if ($res->num_rows > 0): ?>
    <table class="detail-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio</th>
                <th>Desc.</th> <!-- Nueva Columna -->
                <th>Importe</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $res->fetch_assoc()): 
                // Cálculos
                $precioUnitario = (float)$row['precioUnitario'];
                $cantidad = (int)$row['cantidad'];
                $descuentoLinea = (float)$row['descuento'];
                
                $subtotalBruto = $precioUnitario * $cantidad;
                $importeNeto = $subtotalBruto - $descuentoLinea;
                
                // Acumular descuento total
                $totalDescuentoGlobal += $descuentoLinea;
            ?>
            <tr data-product-id="<?php echo $row['idProducto']; ?>" data-category-id="<?php echo $row['idCategoria']; ?>">
                <td style="text-align:left;"><?php echo htmlspecialchars($row['nombre']); ?></td>
                <td style="text-align:center;"><?php echo $cantidad; ?></td>
                <td>$<?php echo number_format($precioUnitario, 2); ?></td>
                
                <!-- Columna Descuento -->
                <td style="color:#c0392b; font-size:0.9em; text-align:center;">
                    <?php echo ($descuentoLinea > 0) ? '-$' . number_format($descuentoLinea, 2) : '--'; ?>
                </td>
                
                <!-- Columna Importe Final -->
                <td style="font-weight:bold;">$<?php echo number_format($importeNeto, 2); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Mostrar Descuento Total al final de la tabla (Antes del Total General del Modal) -->
    <?php if ($totalDescuentoGlobal > 0): ?>
        <div style="text-align:right; margin-top:15px; padding-top:10px; border-top:1px dashed #ccc; color:#7d6a59;">
            Ahorro Total: <span style="color:#c0392b; font-weight:bold;">-$<?php echo number_format($totalDescuentoGlobal, 2); ?></span>
        </div>
    <?php endif; ?>

<?php else: ?>
    <p style="text-align:center; padding:20px;">No se encontraron detalles para esta venta.</p>
<?php endif; 

$stmt->close();
$conn->close();
?>