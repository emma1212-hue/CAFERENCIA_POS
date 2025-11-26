<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Usuarios</title>
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
    </style>
</head>
<body>
    <div class="main-container">
       
        <?php
        if (isset($_GET['success'])) {
            echo '<div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Usuario guardado correctamente
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

        <!-- Formulario de Registro -->
        <div class="form-section">
            <div class="form-panel">
                <div class="form-header">
                    <h1>Registrar Nuevo Usuario</h1>
                    <p>Complete los campos para agregar un usuario al sistema</p>
                </div>

                <form action="php/guardar_usuario.php" method="POST" class="user-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre" class="required">
                                <i class="fas fa-user"></i> Nombre Completo
                            </label>
                            <input type="text" id="nombre" name="nombre" placeholder="Ej: Emmanuel Ramírez" required 
                                   value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="nombreDeUsuario" class="required">
                                <i class="fas fa-at"></i> Nombre de Usuario
                            </label>
                            <input type="text" id="nombreDeUsuario" name="nombreDeUsuario" placeholder="Ej: emmadev" required
                                   value="<?php echo isset($_POST['nombreDeUsuario']) ? htmlspecialchars($_POST['nombreDeUsuario']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="password" class="required">
                                <i class="fas fa-lock"></i> Contraseña
                            </label>
                            <div class="password-input-wrapper">
                                <input type="password" id="password" name="password" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirmar_password" class="required">
                                <i class="fas fa-lock"></i> Confirmar Contraseña
                            </label>
                            <div class="password-input-wrapper">
                                <input type="password" id="confirmar_password" name="confirmar_password" placeholder="Repita la contraseña" required>
                            </div>
                            <div class="password-match" id="passwordMatch"></div>
                        </div>

                        <div class="form-group full-width">
                            <label for="rolUsuario" class="required">
                                <i class="fas fa-user-tag"></i> Rol de Usuario
                            </label>
                            <div class="role-options">
                                <div class="role-option">
                                    <input type="radio" id="rol-admin" name="rolUsuario" value="admin" required
                                           <?php echo (isset($_POST['rolUsuario']) && $_POST['rolUsuario'] == 'admin') ? 'checked' : ''; ?>>
                                    <label for="rol-admin" class="role-label">
                                        <i class="fas fa-crown"></i>
                                        <div class="role-info">
                                            <span class="role-title">Administrador</span>
                                            <small>Acceso completo</small>
                                        </div>
                                    </label>
                                </div>
                              
                                <div class="role-option">
                                    <input type="radio" id="rol-usuario" name="rolUsuario" value="usuario" required
                                           <?php echo (isset($_POST['rolUsuario']) && $_POST['rolUsuario'] == 'usuario') ? 'checked' : ''; ?>>
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
<button type="button" class="btn btn-secondary" onclick="location.href='../indexHome.php'">
    Volver
</button>
                        <button type="submit" class="btn btn-primary">
                            Guardar Usuario
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
                            
                          
                            $sql = "SELECT idUsuario, nombre, nombreDeUsuario, password, rolUsuario FROM usuarios ORDER BY idUsuario";
                            $result = $conn->query($sql);
                            
                            if ($result->num_rows > 0) {
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
                                                <i class="fas fa-users-slash"></i>
                                                <h3>No hay usuarios registrados</h3>
                                                <p>Los usuarios que agregues aparecerán aquí</p>
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

    <script src="js/script.js"></script>
</body>
</html>