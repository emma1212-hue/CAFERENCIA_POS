<?php
// AJUSTA LA RUTA DE CONEXION SI ES NECESARIO
include '../conexion.php'; 

if (!isset($_POST['idVenta'])) { die("Error: ID faltante"); }

$idVenta = (int)$_POST['idVenta'];

// Consulta SQL uniendo 'ventasdetalle' con 'productos'
// Se usa (vd.cantidad * vd.precioUnitario) como subtotal.
$sql = "SELECT 
            vd.cantidad, 
            vd.precioUnitario, vd.descuento, vd.idProducto,
            (vd.cantidad * vd.precioUnitario) as subtotal,
            p.nombre 
        FROM ventasdetalle vd
        JOIN productos p ON vd.idProducto = p.idProducto
        WHERE vd.idVenta = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idVenta);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0): ?>
    <table class="detail-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $res->fetch_assoc()): ?>
            <tr data-product-id="<?php echo $row['idProducto']; ?>" data-discount="<?php echo $row['descuento']; ?>">
                <td style="text-align:left;"><?php echo htmlspecialchars($row['nombre']); ?></td>
                <td style="text-align:center;"><?php echo $row['cantidad']; ?></td>
                <td>$<?php echo number_format($row['precioUnitario'], 2); ?></td>
                <td style="font-weight:bold;">$<?php echo number_format($row['subtotal'], 2); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p style="text-align:center; padding:20px;">No se encontraron detalles para esta venta.</p>
<?php endif; 

$stmt->close();
$conn->close();
?>