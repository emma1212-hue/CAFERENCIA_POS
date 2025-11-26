<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Usuarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/eliminar.css">
</head>
<body>
    <div class="main-container">
      
        <?php
        if (isset($_GET['success'])) {
            echo '<div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Usuario eliminado correctamente
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
                    <h1>Eliminar Usuario</h1>
                    <p>Seleccione un usuario de la tabla para eliminarlo</p>
                </div>

                <div class="user-info" id="userInfo" style="display: none;">
                    <h3><i class="fas fa-exclamation-triangle"></i> Usuario Seleccionado para Eliminar</h3>
                    <div class="user-detail">
                        <strong>ID:</strong> <span id="infoId"></span>
                    </div>
                    <div class="user-detail">
                        <strong>Nombre:</strong> <span id="infoNombre"></span>
                    </div>
                    <div class="user-detail">
                        <strong>Usuario:</strong> <span id="infoUsuario"></span>
                    </div>
                    <div class="user-detail">
                        <strong>Rol:</strong> <span id="infoRol"></span>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="location.href='../indexHome.php'">
                            <i class="fas fa-arrow-left"></i> Volver
                        </button>
                        <button type="button" class="btn btn-danger" id="btnEliminar" onclick="confirmarEliminacion()">
                            <i class="fas fa-trash"></i> Eliminar Usuario
                        </button>
                    </div>
                </div>

                <div class="user-info" id="noSelectionInfo">
                    <h3><i class="fas fa-info-circle"></i> Eliminar</h3>
                    <p>Seleccione un usuario de la tabla para poder eliminarlo. Esta acción no se puede deshacer.</p>
                </div>
            </div>
        </div>

        
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
                                    echo "<tr class='user-row' onclick='seleccionarUsuario({$row['idUsuario']}, \"{$row['nombre']}\", \"{$row['nombreDeUsuario']}\", \"{$rolDisplay}\")'>
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
                                                <p>No hay usuarios para eliminar</p>
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
        let usuarioSeleccionado = null;

        
        function seleccionarUsuario(id, nombre, usuario, rol) {
            
            document.querySelectorAll('.user-row').forEach(row => {
                row.classList.remove('selected');
            });
            
            
            event.currentTarget.classList.add('selected');
            
           
            document.getElementById('infoId').textContent = id;
            document.getElementById('infoNombre').textContent = nombre;
            document.getElementById('infoUsuario').textContent = usuario;
            document.getElementById('infoRol').textContent = rol;
            
            
            document.getElementById('userInfo').style.display = 'block';
            document.getElementById('noSelectionInfo').style.display = 'none';
            
            
            usuarioSeleccionado = id;
        }

        
        function confirmarEliminacion() {
            if (!usuarioSeleccionado) {
                alert('Por favor seleccione un usuario primero');
                return;
            }

            const nombre = document.getElementById('infoNombre').textContent;
            const usuario = document.getElementById('infoUsuario').textContent;
            
            if (confirm(`¿Está seguro que desea eliminar al usuario?\n\nNombre: ${nombre}\nUsuario: ${usuario}\n\nEsta acción no se puede deshacer.`)) {
                eliminarUsuario(usuarioSeleccionado);
            }
        }

       
        function eliminarUsuario(id) {
            const formData = new FormData();
            formData.append('idUsuario', id);

            fetch('php/eliminar_usuario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    
                    window.location.href = 'eliminarUsuarios.php?success=1';
                } else {
                    alert('Error al eliminar usuario: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al eliminar usuario');
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