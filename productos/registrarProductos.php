<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/productos.css">
    <style>
        .validation-message {
            margin-top: 5px;
            font-size: 0.85rem;
            padding: 5px;
            border-radius: 4px;
            display: none;
        }
        
        .validation-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .validation-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .validation-checking {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <div class="main-container">
        
        <?php
        if (isset($_GET['success'])) {
            echo '<div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Producto guardado correctamente
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

        <!-- Formulario de Registro de Productos -->
        <div class="form-section">
            <div class="form-panel">
                <div class="form-header">
                    <h1>Registrar Nuevo Producto</h1>
                    <p>Complete los campos para agregar un producto al sistema</p>
                </div>

                <form action="php/guardar_productos.php" method="POST" class="user-form" id="productoForm">
                    
                    <div class="form-row">
                        <div class="form-column-left">
                            <div class="form-group">
                                <label for="nombre" class="required">
                                    <i class="fas fa-tag"></i> Nombre del Producto
                                </label>
                                <input type="text" id="nombre" name="nombre" placeholder="Ej: Café Americano" required 
                                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                                <div id="nombreValidation" class="validation-message"></div>
                            </div>
                        </div>
                        
                        <div class="form-column-right">
                            <div class="form-group">
                                <label for="precioVenta" class="required">
                                    <i class="fas fa-dollar-sign"></i> Precio de Venta
                                </label>
                                <input type="number" id="precioVenta" name="precioVenta" step="0.01" min="0" placeholder="Ej: 25.50" required
                                       value="<?php echo isset($_POST['precioVenta']) ? htmlspecialchars($_POST['precioVenta']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    
                    <div class="form-row">
                        <div class="form-column-left">
                            <div class="form-group">
                                <label for="descripcion" class="required">
                                    <i class="fas fa-align-left"></i> Descripción
                                </label>
                                <textarea id="descripcion" name="descripcion" placeholder="Descripción detallada del producto..." required><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-column-right">
                            <div class="form-group">
                                <label for="idCategoria" class="required">
                                    <i class="fas fa-list"></i> Categoría
                                </label>
                                <div class="category-select-wrapper">
                                    <select id="idCategoria" name="idCategoria" required>
                                        <option value="">Seleccione una categoría</option>
                                        <?php
                                        include '../conexion.php';
                                        
                                        $sql_categorias = "SELECT idCategoria, nombre FROM categorias ORDER BY nombre";
                                        $result_categorias = $conn->query($sql_categorias);
                                        
                                        if ($result_categorias->num_rows > 0) {
                                            while($categoria = $result_categorias->fetch_assoc()) {
                                                $selected = (isset($_POST['idCategoria']) && $_POST['idCategoria'] == $categoria['idCategoria']) ? 'selected' : '';
                                                echo "<option value='{$categoria['idCategoria']}' $selected>{$categoria['nombre']}</option>";
                                            }
                                        }
                                        $conn->close();
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                  
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="location.href='../indexhome.php'">
                            Volver
                        </button>
                        
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            Guardar Producto
                        </button>

                        <button type="button" class="btn btn-info" onclick="location.href='inventario.php'">
                             Gestionar Insumos
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de Productos -->
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
                                    echo "<tr>
                                            <td>{$row['idProducto']}</td>
                                            <td>{$row['nombre']}</td>
                                            <td>{$row['descripcion']}</td>
                                            <td>$" . number_format($row['precioVenta'], 2) . "</td>
                                            <td>{$row['categoria']}</td>
                                          </tr>";
                                }
                            } else {
                                echo '<tr class="empty-row">
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <i class="fas fa-box-open"></i>
                                                <h3>No hay productos registrados</h3>
                                                <p>Los productos que agregues aparecerán aquí</p>
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

            
            const nombreInput = document.getElementById('nombre');
            const nombreValidation = document.getElementById('nombreValidation');
            const submitBtn = document.getElementById('submitBtn');
            let validationTimeout;

            nombreInput.addEventListener('input', function() {
                const nombre = this.value.trim();
                
               
                clearTimeout(validationTimeout);
                
                if (nombre.length === 0) {
                    nombreValidation.style.display = 'none';
                    enableSubmitButton();
                    return;
                }

                
                nombreValidation.textContent = 'Verificando disponibilidad del nombre...';
                nombreValidation.className = 'validation-message validation-checking';
                nombreValidation.style.display = 'block';
                
               
                validationTimeout = setTimeout(() => {
                    verificarNombreProducto(nombre);
                }, 500);
            });

            function verificarNombreProducto(nombre) {
                
                const formData = new FormData();
                formData.append('nombre', nombre);
                formData.append('verificar_nombre', true);

                fetch('php/verificar_productos.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.existe) {
                        nombreValidation.textContent = 'Ya existe un producto con este nombre';
                        nombreValidation.className = 'validation-message validation-error';
                        disableSubmitButton();
                    } else {
                        nombreValidation.textContent = 'Nombre disponible';
                        nombreValidation.className = 'validation-message validation-success';
                        enableSubmitButton();
                    }
                    nombreValidation.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    nombreValidation.textContent = 'Error al verificar el nombre';
                    nombreValidation.className = 'validation-message validation-error';
                    enableSubmitButton(); 
                });
            }

            function disableSubmitButton() {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
                submitBtn.style.cursor = 'not-allowed';
            }

            function enableSubmitButton() {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            }

          
            if (nombreInput.value.trim().length > 0) {
                verificarNombreProducto(nombreInput.value.trim());
            }
        });
    </script>
</body>
</html>