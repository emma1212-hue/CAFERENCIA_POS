<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Insumos</title>
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
        .insumo-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
            margin-bottom: 20px;
            display: none;
        }
        .insumo-detail {
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        .insumo-row {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .insumo-row:hover {
            background-color: #f8f9fa;
        }
        .insumo-row.selected {
            background-color: #ffe6e6;
            border-left: 4px solid #dc3545;
        }
        .price-highlight {
            font-weight: 600;
            color: var(--primary-dark);
        }
        .stock-highlight {
            font-weight: 600;
            color: #28a745;
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
                    <i class="fas fa-check-circle"></i> Insumo eliminado correctamente
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
                    <h1> Eliminar Insumo</h1>
                    <p>Seleccione un insumo de la tabla para eliminarlo</p>
                </div>

                <div class="insumo-info" id="insumoInfo">
                    <h3><i class="fas fa-exclamation-triangle"></i> Insumo Seleccionado para Eliminar</h3>
                    <div class="insumo-detail">
                        <strong>ID:</strong> <span id="infoId"></span>
                    </div>
                    <div class="insumo-detail">
                        <strong>Nombre:</strong> <span id="infoNombre"></span>
                    </div>
                    <div class="insumo-detail">
                        <strong>Descripción:</strong> <span id="infoDescripcion"></span>
                    </div>
                    <div class="insumo-detail">
                        <strong>Unidad:</strong> <span id="infoUnidad"></span>
                    </div>
                    <div class="insumo-detail">
                        <strong>Stock Actual:</strong> <span id="infoStock"></span>
                    </div>
                    <div class="insumo-detail">
                        <strong>Costo Unitario:</strong> <span id="infoCosto"></span>
                    </div>
                    <div class="insumo-detail">
                        <strong>Categoría:</strong> <span id="infoCategoria"></span>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="location.href='../indexHome.php'">
                             Volver
                        </button>
                        <button type="button" class="btn btn-danger" id="btnEliminar" onclick="confirmarEliminacion()">
                             Eliminar Insumo
                        </button>
                        <button type="button" class="btn-info" onclick="location.href='eliminar_productos.php'">
                             Gestionar Productos
                        </button>
                    </div>
                </div>

                <div class="insumo-info" id="noSelectionInfo">
                    <h3><i class="fas fa-info-circle"></i> Eliminar Insumo</h3>
                    <p>Seleccione un insumo de la tabla para poder eliminarlo. Esta acción no se puede deshacer.</p>
                    <div class="form-actions" style="margin-top: 15px;">
                        <button type="button" class="btn btn-secondary" onclick="location.href='../indexHome.php'">
                            <i class="fas fa-arrow-left"></i> Volver
                        </button>
                        <button type="button" class="btn-info" onclick="location.href='eliminar_productos.php'">
                            <i class="fas fa-boxes"></i> Gestionar Productos
                        </button>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="table-section">
            <div class="table-panel">
                <div class="table-header">
                    <h2><i class="fas fa-warehouse"></i> Insumos Registrados</h2>
                </div>

                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Unidad</th>
                                <th>Stock</th>
                                <th>Costo Unitario</th>
                                <th>Categoría</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            include '../conexion.php';
                            
                            $sql = "SELECT i.idInventario, i.nombre, i.descripcion, i.unidad, 
                                           i.stockActual, i.costoUnitario, c.nombre as categoria 
                                    FROM inventario i 
                                    INNER JOIN categorias c ON i.idCategoria = c.idCategoria 
                                    ORDER BY i.idInventario";
                            $result = $conn->query($sql);
                            
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $nombreEscapado = htmlspecialchars($row['nombre'], ENT_QUOTES);
                                    $descripcionEscapada = htmlspecialchars($row['descripcion'], ENT_QUOTES);
                                    $unidadEscapada = htmlspecialchars($row['unidad'], ENT_QUOTES);
                                    $categoriaEscapada = htmlspecialchars($row['categoria'], ENT_QUOTES);
                                    
                                    echo "<tr class='insumo-row' onclick='seleccionarInsumo({$row['idInventario']}, \"{$nombreEscapado}\", \"{$descripcionEscapada}\", \"{$unidadEscapada}\", \"{$row['stockActual']}\", \"{$row['costoUnitario']}\", \"{$categoriaEscapada}\")'>
                                            <td><strong>{$row['idInventario']}</strong></td>
                                            <td>{$row['nombre']}</td>
                                            <td>{$row['descripcion']}</td>
                                            <td>{$row['unidad']}</td>
                                            <td class='stock-highlight'>{$row['stockActual']}</td>
                                            <td class='price-highlight'>$" . number_format($row['costoUnitario'], 2) . "</td>
                                            <td>{$row['categoria']}</td>
                                          </tr>";
                                }
                            } else {
                                echo '<tr class="empty-row">
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="fas fa-warehouse"></i>
                                                <h3>No hay insumos registrados</h3>
                                                <p>No hay insumos para eliminar</p>
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
        let insumoSeleccionado = null;

        
        function seleccionarInsumo(id, nombre, descripcion, unidad, stock, costo, categoria) {
            
            document.querySelectorAll('.insumo-row').forEach(row => {
                row.classList.remove('selected');
            });
            
            
            event.currentTarget.classList.add('selected');
            
           
            document.getElementById('infoId').textContent = id;
            document.getElementById('infoNombre').textContent = nombre;
            document.getElementById('infoDescripcion').textContent = descripcion;
            document.getElementById('infoUnidad').textContent = unidad;
            document.getElementById('infoStock').textContent = stock;
            document.getElementById('infoCosto').textContent = '$' + parseFloat(costo).toFixed(2);
            document.getElementById('infoCategoria').textContent = categoria;
            
            
            document.getElementById('insumoInfo').style.display = 'block';
            document.getElementById('noSelectionInfo').style.display = 'none';
            
            
            insumoSeleccionado = id;
        }

        
        function confirmarEliminacion() {
            if (!insumoSeleccionado) {
                alert('Por favor seleccione un insumo primero');
                return;
            }

            const nombre = document.getElementById('infoNombre').textContent;
            const stock = document.getElementById('infoStock').textContent;
            const costo = document.getElementById('infoCosto').textContent;
            
            if (confirm(`¿Está seguro que desea eliminar el insumo?\n\nNombre: ${nombre}\nStock: ${stock}\nCosto: ${costo}\n\nEsta acción no se puede deshacer.`)) {
                eliminarInsumo(insumoSeleccionado);
            }
        }

       
        function eliminarInsumo(id) {
            const formData = new FormData();
            formData.append('idInventario', id);

            fetch('php/eliminar_insumos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    
                    window.location.href = 'eliminar_insumos.php?success=1';
                } else {
                    alert('Error al eliminar insumo: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al eliminar insumo');
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