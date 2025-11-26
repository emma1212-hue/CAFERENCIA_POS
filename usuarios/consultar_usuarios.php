<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Usuarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
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
        .btn-clear {
            background: var(--primary-light);
            color: var(--text-dark);
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-clear:hover {
            background: #978a7e;
        }
        .search-results {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        
        <div class="search-form">
            <div class="form-header">
                <h1> Consultar Usuarios</h1>
                <p>Busque usuarios por nombre o nombre de usuario</p>
            </div>

            <form method="GET" action="" class="search-form">
                <div class="search-grid">
                    <div class="form-group">
                        <label for="buscarNombre">
                            <i class="fas fa-user"></i> Buscar por Nombre
                        </label>
                        <input type="text" id="buscarNombre" name="buscarNombre" 
                               placeholder="Ej: Emmanuel" 
                               value="<?php echo isset($_GET['buscarNombre']) ? htmlspecialchars($_GET['buscarNombre']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="buscarUsuario">
                            <i class="fas fa-at"></i> Buscar por Usuario
                        </label>
                        <input type="text" id="buscarUsuario" name="buscarUsuario" 
                               placeholder="Ej: emmadev" 
                               value="<?php echo isset($_GET['buscarUsuario']) ? htmlspecialchars($_GET['buscarUsuario']) : ''; ?>">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <button type="button" class="btn-clear" onclick="location.href='../indexHome.php'">
                             Volver
                        </button>
                    </div>
                </div>
            </form>

            <?php
            include '../conexion.php';
            
           
            $buscarNombre = isset($_GET['buscarNombre']) ? trim($_GET['buscarNombre']) : '';
            $buscarUsuario = isset($_GET['buscarUsuario']) ? trim($_GET['buscarUsuario']) : '';
            
            
            $sql = "SELECT idUsuario, nombre, nombreDeUsuario, password, rolUsuario FROM usuarios WHERE 1=1";
            $params = [];
            $types = '';
            
            if (!empty($buscarNombre)) {
                $sql .= " AND nombre LIKE ?";
                $params[] = "%$buscarNombre%";
                $types .= 's';
            }
            
            if (!empty($buscarUsuario)) {
                $sql .= " AND nombreDeUsuario LIKE ?";
                $params[] = "%$buscarUsuario%";
                $types .= 's';
            }
            
            $sql .= " ORDER BY idUsuario";
            
            
            $stmt = $conn->prepare($sql);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $totalResultados = $result->num_rows;
            
            
            if (!empty($buscarNombre) || !empty($buscarUsuario)) {
                echo "<div class='search-results'>";
                echo "<i class='fas fa-info-circle'></i> ";
                if ($totalResultados > 0) {
                    echo "Se encontraron <strong>$totalResultados</strong> usuario(s)";
                } else {
                    echo "No se encontraron resultados";
                }
                
                if (!empty($buscarNombre)) {
                    echo " para el nombre: <strong>'$buscarNombre'</strong>";
                }
                if (!empty($buscarUsuario)) {
                    echo " para el usuario: <strong>'$buscarUsuario'</strong>";
                }
                echo "</div>";
            }
            ?>
        </div>

      
        <div class="table-section">
            <div class="table-panel">
                <div class="table-header">
                    <h2><i class="fas fa-users"></i> Resultados de la Búsqueda</h2>
                    <div class="table-stats">
                        <?php echo $totalResultados; ?> usuario(s)
                    </div>
                </div>

                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Password</th>
                                <th>Rol</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($totalResultados > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $rolDisplay = ($row["rolUsuario"] == "admin") ? "Administrador" : "Trabajador";
                                    $rolClass = ($row["rolUsuario"] == "admin") ? "admin" : "usuario";
                                    echo "<tr>
                                            <td>{$row['idUsuario']}</td>
                                            <td>{$row['nombre']}</td>
                                            <td>{$row['nombreDeUsuario']}</td>
                                            <td>********</td>
                                            <td>
                                                <span class='role-badge {$rolClass}'>
                                                    {$rolDisplay}
                                                </span>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo '<tr class="empty-row">
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <i class="fas fa-search"></i>
                                                <h3>No se encontraron usuarios</h3>
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
            window.location.href = 'consultarUsuarios.php';
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