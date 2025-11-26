<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Insumos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/productos.css">
    <style>
        .search-form {
            background: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        .search-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        .btn-search {
            background: var(--primary-dark);
            color: var(--white);
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-search:hover {
            background: #352a24;
        }
        .btn-info {
            background: var(--primary-medium);
            color: var(--white);
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-info:hover {
            background: #8a7866;
        }
        .search-results {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-top: 10px;
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
            flex-direction: column;
            gap: 10px;
        }
        .form-actions button {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="main-container">
        
        <div class="search-form">
            <div class="form-header">
                <h1><i class="fas fa-search"></i> Consultar Insumos</h1>
                <p>Busque insumos por ID o nombre</p>
            </div>

            <form method="GET" action="" class="search-form">
                <div class="search-grid">
                    <div class="form-group">
                        <label for="buscarId">
                            <i class="fas fa-hashtag"></i> Buscar por ID
                        </label>
                        <input type="number" id="buscarId" name="buscarId" 
                               placeholder="Ej: 1, 2, 3..." 
                               min="1"
                               value="<?php echo isset($_GET['buscarId']) ? htmlspecialchars($_GET['buscarId']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="buscarNombre">
                            <i class="fas fa-tag"></i> Buscar por Nombre
                        </label>
                        <input type="text" id="buscarNombre" name="buscarNombre" 
                               placeholder="Ej: Café en grano" 
                               value="<?php echo isset($_GET['buscarNombre']) ? htmlspecialchars($_GET['buscarNombre']) : ''; ?>">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <button type="button" class="btn-info" onclick="location.href='consultar_productos.php'">
                            Gestionar Productos
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="location.href='../indexHome.php'">
                             Volver
                        </button>
                    </div>
                </div>
            </form>

            <?php
            include '../conexion.php';
            
          
            $buscarId = isset($_GET['buscarId']) ? trim($_GET['buscarId']) : '';
            $buscarNombre = isset($_GET['buscarNombre']) ? trim($_GET['buscarNombre']) : '';
            
           
            $sql = "SELECT i.idInventario, i.nombre, i.descripcion, i.unidad, 
                           i.stockActual, i.costoUnitario, c.nombre as categoria 
                    FROM inventario i 
                    INNER JOIN categorias c ON i.idCategoria = c.idCategoria 
                    WHERE 1=1";
            $params = [];
            $types = '';
            
            if (!empty($buscarId)) {
                $sql .= " AND i.idInventario = ?";
                $params[] = $buscarId;
                $types .= 'i';
            }
            
            if (!empty($buscarNombre)) {
                $sql .= " AND i.nombre LIKE ?";
                $params[] = "%$buscarNombre%";
                $types .= 's';
            }
            
            $sql .= " ORDER BY i.idInventario";
            
            // Preparar y ejecutar consulta
            $stmt = $conn->prepare($sql);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $totalResultados = $result->num_rows;
            
   
            if (!empty($buscarId) || !empty($buscarNombre)) {
                echo "<div class='search-results'>";
                echo "<i class='fas fa-info-circle'></i> ";
                if ($totalResultados > 0) {
                    echo "Se encontraron <strong>$totalResultados</strong> insumo(s)";
                } else {
                    echo "No se encontraron resultados";
                }
                
                if (!empty($buscarId)) {
                    echo " para el ID: <strong>'$buscarId'</strong>";
                }
                if (!empty($buscarNombre)) {
                    echo " para el nombre: <strong>'$buscarNombre'</strong>";
                }
                echo "</div>";
            }
            ?>
        </div>

        <!-- Tabla de Resultados -->
        <div class="table-section">
            <div class="table-panel">
                <div class="table-header">
                    <h2><i class="fas fa-warehouse"></i> Resultados de la Búsqueda</h2>
                    <div class="table-stats">
                        <?php echo $totalResultados; ?> insumo(s)
                    </div>
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
                            if ($totalResultados > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>
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
                                                <i class="fas fa-search"></i>
                                                <h3>No se encontraron insumos</h3>
                                                <p>Intente con otros términos de búsqueda</p>
                                            </div>
                                        </td>
                                      </tr>';
                            }
                            
                            $stmt->close();
                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    
        function limpiarBusqueda() {
            window.location.href = 'consultar_insumos.php';
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

            document.getElementById('buscarId').focus();
        });


        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.querySelector('.btn-search').click();
            }
        });
    </script>
</body>
</html>