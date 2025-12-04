<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Usuarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
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
        .user-row {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .user-row:hover {
            background-color: #f0f0f0;
        }
        .user-row.selected {
            background-color: #e3f2fd;
            border-left: 4px solid var(--primary-medium);
        }
    </style>
</head>
<body>
    <div class="main-container">
        
        <?php
        if (isset($_GET['success'])) {
            echo '<div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Usuario actualizado correctamente
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

        <!-- Formulario de Modificación -->
        <div class="form-section">
            <div class="form-panel">
                <div class="form-header">
                    <h1>Modificar Usuario</h1>
                    <p>Seleccione un usuario de la tabla y modifique la información</p>
                </div>

                <form action="php/actualizar_usuario.php" method="POST" class="user-form" id="formUsuario">
                    <input type="hidden" id="idUsuario" name="idUsuario" value="">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre" class="required">
                                <i class="fas fa-user"></i> Nombre Completo
                            </label>
                            <input type="text" id="nombre" name="nombre" placeholder="Ej: Emmanuel Ramírez" required>
                        </div>

                        <div class="form-group">
                            <label for="nombreDeUsuario" class="required">
                                <i class="fas fa-at"></i> Nombre de Usuario
                            </label>
                            <input type="text" id="nombreDeUsuario" name="nombreDeUsuario" placeholder="Ej: emmadev" required>
                            <div class="availability-status" id="usuarioStatus"></div>
                        </div>

                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> Nueva Contraseña
                            </label>
                            <div class="password-input-wrapper">
                                <input type="password" id="password" name="password" placeholder="Dejar vacío para mantener la actual">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirmar_password">
                                <i class="fas fa-lock"></i> Confirmar Contraseña
                            </label>
                            <div class="password-input-wrapper">
                                <input type="password" id="confirmar_password" name="confirmar_password" placeholder="Repita la nueva contraseña">
                            </div>
                            <div class="password-match" id="passwordMatch"></div>
                        </div>

                        <div class="form-group full-width">
                            <label for="rolUsuario" class="required">
                                <i class="fas fa-user-tag"></i> Rol de Usuario
                            </label>
                            <div class="role-options">
                                <div class="role-option">
                                    <input type="radio" id="rol-admin" name="rolUsuario" value="admin" required>
                                    <label for="rol-admin" class="role-label">
                                        <i class="fas fa-crown"></i>
                                        <div class="role-info">
                                            <span class="role-title">Administrador</span>
                                            <small>Acceso completo</small>
                                        </div>
                                    </label>
                                </div>
                              
                                <div class="role-option">
                                    <input type="radio" id="rol-usuario" name="rolUsuario" value="usuario" required>
                                    <label for="rol-usuario" class="role-label">
                                        <i class="fas fa-user"></i>
                                        <div class="role-info">
                                            <span class="role-title">Trabajador</span>
                                            <small>Acceso básico</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="location.href='../indexhome.php'">
                            Volver
                        </button>
                       
                        <button type="submit" class="btn btn-primary" id="btnGuardar">
                           Actualizar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="table-section">
            <div class="table-panel">
                <div class="table-header">
                    <h2><i class="fas fa-users"></i> Usuarios Registrados</h2>
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
                            include '../conexion.php';
                            
                            $sql = "SELECT idUsuario, nombre, nombreDeUsuario, password, rolUsuario FROM usuarios WHERE status='activo' ORDER BY idUsuario";
                            $result = $conn->query($sql);
                            
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $rolDisplay = ($row["rolUsuario"] == "admin") ? "Administrador" : "Trabajador";
                                    $rolClass = ($row["rolUsuario"] == "admin") ? "admin" : "usuario";
                                    echo "<tr class='user-row' onclick='cargarUsuario({$row['idUsuario']}, \"{$row['nombre']}\", \"{$row['nombreDeUsuario']}\", \"{$row['rolUsuario']}\")'>
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
                                                <i class="fas fa-users-slash"></i>
                                                <h3>No hay usuarios registrados</h3>
                                                <p>Seleccione un usuario para modificarlo</p>
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


 <script src="js/script2.js"></script>
</body>
</html>