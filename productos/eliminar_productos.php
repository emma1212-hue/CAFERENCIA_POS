<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Productos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/productos.css">
    <style>
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .product-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
            margin-bottom: 20px;
            display: none;
        }
        .product-detail {
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        .product-row {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .product-row:hover {
            background-color: #f8f9fa;
        }
        .product-row.selected {
            background-color: #ffe6e6;
            border-left: 4px solid #dc3545;
        }
        .price-highlight {
            font-weight: 600;
            color: var(--primary-dark);
        }
        .form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-info {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-info:hover {
            background-color: #138496;
        }
    </style>
</head>
<body>
    <div class="main-container">
      
        <?php
        if (isset($_GET['success'])) {
            echo '<div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Producto eliminado correctamente
                  </div>';
        }
        
        if (isset($_GET['error'])) {
            $errors = explode("|", $_GET['error']);
            foreach ($errors as $error) {
                echo '<div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($error) . '
                      </div>';
            }
        }
        ?>

      
        <div class="form-section">
            <div class="form-panel">
                <div class="form-header">
                    <h1>Eliminar Producto</h1>
                    <p>Seleccione un producto de la tabla para eliminarlo</p>
                </div>

                <div class="product-info" id="productInfo">
                    <h3><i class="fas fa-exclamation-triangle"></i> Producto Seleccionado para Eliminar</h3>
                    <div class="product-detail">
                        <strong>ID:</strong> <span id="infoId"></span>
                    </div>
                    <div class="product-detail">
                        <strong>Nombre:</strong> <span id="infoNombre"></span>
                    </div>
                    <div class="product-detail">
                        <strong>Descripción:</strong> <span id="infoDescripcion"></span>
                    </div>
                    <div class="product-detail">
                        <strong>Precio:</strong> <span id="infoPrecio"></span>
                    </div>
                    <div class="product-detail">
                        <strong>Categoría:</strong> <span id="infoCategoria"></span>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="location.href='../indexhome.php'">
                             Volver
                        </button>
                        <button type="button" class="btn btn-danger" id="btnEliminar" onclick="confirmarEliminacion()">
                            Eliminar Producto
                        </button>
                        <button type="button" class="btn-info" onclick="location.href='eliminar_insumos.php'">
                             Gestionar Insumos
                        </button>
                    </div>
                </div>

                <div class="product-info" id="noSelectionInfo">
                    <h3><i class="fas fa-info-circle"></i> Eliminar Producto</h3>
                    <p>Seleccione un producto de la tabla para poder eliminarlo. Esta acción no se puede deshacer.</p>
                    <div class="form-actions" style="margin-top: 15px;">
                        <button type="button" class="btn btn-secondary" onclick="location.href='../indexhome.php'">
                            Volver
                        </button>
                        <button type="button" class="btn-info" onclick="location.href='eliminar_insumos.php'">
                            <i class="fas fa-warehouse"></i> Gestionar Insumos
                        </button>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="table-section">
            <div class="table-panel">
                <div class="table-header">
                    <h2><i class="fas fa-boxes"></i> Productos Registrados</h2>
                </div>

                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Precio</th>
                                <th>Categoría</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            include '../conexion.php';
                            
                            $sql = "SELECT p.idProducto, p.nombre, p.descripcion, p.precioVenta, c.nombre as categoria 
                                    FROM productos p 
                                    INNER JOIN categorias c ON p.idCategoria = c.idCategoria 
                                    ORDER BY p.idProducto";
                            $result = $conn->query($sql);
                            
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $nombreEscapado = htmlspecialchars($row['nombre'], ENT_QUOTES);
                                    $descripcionEscapada = htmlspecialchars($row['descripcion'], ENT_QUOTES);
                                    $categoriaEscapada = htmlspecialchars($row['categoria'], ENT_QUOTES);
                                    
                                    echo "<tr class='product-row' onclick='seleccionarProducto({$row['idProducto']}, \"{$nombreEscapado}\", \"{$descripcionEscapada}\", \"{$row['precioVenta']}\", \"{$categoriaEscapada}\")'>
                                            <td><strong>{$row['idProducto']}</strong></td>
                                            <td>{$row['nombre']}</td>
                                            <td>{$row['descripcion']}</td>
                                            <td class='price-highlight'>$" . number_format($row['precioVenta'], 2) . "</td>
                                            <td>{$row['categoria']}</td>
                                          </tr>";
                                }
                            } else {
                                echo '<tr class="empty-row">
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <i class="fas fa-box-open"></i>
                                                <h3>No hay productos registrados</h3>
                                                <p>No hay productos para eliminar</p>
                                            </div>
                                        </td>
                                      </tr>';
                            }
                            
                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        let productoSeleccionado = null;

        
        function seleccionarProducto(id, nombre, descripcion, precio, categoria) {
            
            document.querySelectorAll('.product-row').forEach(row => {
                row.classList.remove('selected');
            });
            
            
            event.currentTarget.classList.add('selected');
            
           
            document.getElementById('infoId').textContent = id;
            document.getElementById('infoNombre').textContent = nombre;
            document.getElementById('infoDescripcion').textContent = descripcion;
            document.getElementById('infoPrecio').textContent = '$' + parseFloat(precio).toFixed(2);
            document.getElementById('infoCategoria').textContent = categoria;
            
            
            document.getElementById('productInfo').style.display = 'block';
            document.getElementById('noSelectionInfo').style.display = 'none';
            
            
            productoSeleccionado = id;
        }

        
        function confirmarEliminacion() {
            if (!productoSeleccionado) {
                alert('Por favor seleccione un producto primero');
                return;
            }

            const nombre = document.getElementById('infoNombre').textContent;
            const precio = document.getElementById('infoPrecio').textContent;
            
            if (confirm(`¿Está seguro que desea eliminar el producto?\n\nNombre: ${nombre}\nPrecio: ${precio}\n\nEsta acción no se puede deshacer.`)) {
                eliminarProducto(productoSeleccionado);
            }
        }

       
        function eliminarProducto(id) {
            const formData = new FormData();
            formData.append('idProducto', id);

            fetch('php/eliminar_productos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    
                    window.location.href = 'eliminar_productos.php?success=1';
                } else {
                    alert('Error al eliminar producto: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al eliminar producto');
            });
        }

        
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 3000);
            });
        });
    </script>
</body>
</html>