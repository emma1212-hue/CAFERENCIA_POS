<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: indexLogin.php");
    exit();
}

$rol = $_SESSION['rol'];   
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Cafetería</title>
    <link rel="stylesheet" href="homepage/css/styleshome.css">
</head>
<body>
   
    <div class="container">
      
        <div class="header">
            <div class="header-left">
                <button class="icon-btn menu-btn" onclick="toggleSidebar()">

                </button>
             <h1>CAFERENCIA PUNTO DE VENTA</h1>
            </div>
            <div class="header-icons">


            </div>
        </div>

<!--
        <div class="stats-container">
        
            <div class="stat-card clickable" onclick="handleClick('mas-vendidos')">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"></div>
                <div class="stat-info">
                    <h3>Productos Más Vendidos</h3>
                    <p class="stat-value">Ver Top</p>
                    <span class="stat-change">Café Americano #1</span>
                </div>
            </div>
-->
            <!-- Turno Actual 
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);"></div>
                <div class="stat-info">
                    <h3>Turno Actual</h3>
                    <p class="stat-value" id="currentTime">00:00:00</p>
                    <span class="stat-change">Admin</span>
                </div>
            </div>
-->
            <!-- Productos Bajos en Stock 
            <div class="stat-card clickable" onclick="handleClick('stock-bajo')">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);"></div>
                <div class="stat-info">
                    <h3>Productos Bajos en Stock</h3>
                    <p class="stat-value">2 productos</p>
                    <span class="stat-change alert">Stock bajo</span>
                </div>
            </div>
        </div>-->

        <h2 class="section-title">Accesos Rápidos</h2>
        <div class="dashboard-grid">
            <!-- Tarjeta Ventas -->
            <div class="dashboard-card card-ventas" id="card-ventas" onclick="toggleCardMenu('ventas')">
                <div class="card-main-content">
                    
                    <img src="homepage/img/carrito.png" alt="Ventas" class="card-icon-img">
                    <h2 class="card-title">Ventas</h2>
                    <p class="card-description">Registrar nueva venta</p>
                </div>
                <div class="card-submenu" id="submenu-ventas">
                    <button class="submenu-item">
                        <span class="submenu-icon">➕</span>
                        <span><a href="ventas/ventas.html" style="text-decoration: none; color: inherit;">Nueva Venta</a></span>

                    </button>
                    <button class="submenu-item">
                        <span class="submenu-icon">✏️</span>
                        <span><a href="ventas/modificarVentas.html" style="text-decoration: none; color: inherit;">Modificar Venta</a></span>
                    </button>
                    <button class="submenu-item">
                        <span class="submenu-icon">🔍</span>
                        <span><a href="ventas/consultarVentas.html" style="text-decoration: none; color: inherit;">Consultar Venta</a></span>
                    </button>
                    <button class="submenu-item">
                        <span class="submenu-icon">🖨️</span>
                       <span><a href="ventas/tickets.html" style="text-decoration: none; color: inherit;">Impresión Tickets</a></span>
                    </button>
                </div>
            </div>

            <!-- Tarjeta Productos -->
            <div class="dashboard-card card-productos" id="card-productos" onclick="toggleCardMenu('productos')">
                <div class="card-main-content">
                    
                    <img src="homepage/img/productos.png" alt="Productos" class="card-icon-img">
                    <h2 class="card-title">Productos</h2>
                    <p class="card-description">Gestionar inventario</p>
                </div>
                <div class="card-submenu" id="submenu-productos">
                    <button class="submenu-item">
                        <span class="submenu-icon">➕</span>
                        <span><a href="productos/registrarProductos.php" style="text-decoration: none; color: inherit;">Registrar Producto</a></span>
                    </button>
                    <button class="submenu-item">
                        <span class="submenu-icon">✏️</span>
                        <span><a href="productos/modificar_productos.php" style="text-decoration: none; color: inherit;">Modificar Producto</a></span>
                    </button>
                    <button class="submenu-item">
                        <span class="submenu-icon">🔍</span>
                        <span><a href="productos/consultar_productos.php" style="text-decoration: none; color: inherit;">Consultar Producto</a></span>
                    </button>
                    <button class="submenu-item">
                        <span class="submenu-icon">🗑️</span>
                    <span><a href="productos/eliminar_productos.php" style="text-decoration: none; color: inherit;">Eliminar producto</a></span>
                    </button>
                </div>
            </div>

<?php if ($rol === 'admin'): ?>
    <!-- Tarjeta Usuarios -->
    <div class="dashboard-card card-usuarios" id="card-usuarios" onclick="toggleCardMenu('usuarios')">
        <div class="card-main-content">
            <img src="homepage/img/users.png" alt="Usuarios" class="card-icon-img">
            <h2 class="card-title">Usuarios</h2>
            <p class="card-description">Administrar personal</p>
        </div>

        <div class="card-submenu" id="submenu-usuarios">
            <button class="submenu-item">
                <span class="submenu-icon">➕</span>
                <span><a href="usuarios/agregarUsuarios.php" style="text-decoration: none; color: inherit;">Registrar Usuario</a></span>
            </button>
            <button class="submenu-item">
                <span class="submenu-icon">✏️</span>
                <span><a href="usuarios/modificarUsuarios.php" style="text-decoration: none; color: inherit;">Modificar Usuario</a></span>
            </button>
            <button class="submenu-item">
                <span class="submenu-icon">🔍</span>
                <span><a href="usuarios/consultar_usuarios.php" style="text-decoration: none; color: inherit;">Consultar Usuario</a></span>
            </button>
            <button class="submenu-item">
                <span class="submenu-icon">👤</span>
                <span><a href="usuarios/eliminarUsuarios.php" style="text-decoration: none; color: inherit;"> Dar de Baja</a></span>
            </button>
        </div>
    </div>
<?php endif; ?>


            <!-- Tarjeta Reporte -->
            <div class="dashboard-card card-reporte" id="card-reporte" onclick="toggleCardMenu('reporte')">
                <div class="card-main-content">
                 
                    <img src="homepage/img/cortecaja.png" alt="Reportes" class="card-icon-img">
                    <h2 class="card-title">Reporte / Corte</h2>
                    <p class="card-description">Cierre de caja</p>
                </div>
                <div class="card-submenu" id="submenu-reporte">
                    <button class="submenu-item">
                        <span class="submenu-icon">💰</span>
                        <span><a href="reporte/generarReporte.html" style="text-decoration: none; color: inherit;">Corte de Caja</a></span>
                    </button>
                </div>
            </div>

            <!-- Tarjeta Cerrar Sesión -->
            <div class="dashboard-card card-logout" id="card-logout" onclick="confirmLogout()">
                <div class="card-main-content">
                    <svg class="card-icon" viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <h2 class="card-title">Cerrar Sesión</h2>
                    <p class="card-description">Salir del sistema</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Reloj en tiempo real
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('es-MX', { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('currentTime').textContent = timeString;
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Toggle Sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        // Toggle User Menu
        function toggleUserMenu() {
            alert('Menú de usuario:\n- Perfil\n- Configuración\n- Cerrar sesión');
        }


        function showNotifications() {
            alert('Notificaciones:\n1. Nueva venta registrada\n2. Producto bajo en stock: Leche\n3. Turno por finalizar en 1 hora');
        }

       
        function handleClick(section) {
            console.log('Navegando a:', section);
            const messages = {
                'mas-vendidos': 'Top 10 Productos Más Vendidos',
                'stock-bajo': 'Lista de Productos con Stock Bajo'
            };
            alert(`Accediendo a: ${messages[section] || section}`);
        }

     
        function toggleCardMenu(cardType) {
            const card = document.getElementById(`card-${cardType}`);
            const submenu = document.getElementById(`submenu-${cardType}`);
            
          
            document.querySelectorAll('.dashboard-card').forEach(c => {
                if (c.id !== `card-${cardType}`) {
                    c.classList.remove('expanded');
                }
            });
            
        
            card.classList.toggle('expanded');
        }

     
        function handleSubmenuClick(event, action) {
            event.stopPropagation();
            
            const messages = {
                'nueva-venta': 'Nueva Venta',
                'modificar-venta': 'Modificar Venta',
                'consultar-venta': 'Consultar Venta',
                'impresion-tickets': 'Impresión de Tickets',
                'registrar-producto': 'Registrar Producto',
                'modificar-producto': 'Modificar Producto',
                'consultar-producto': 'Consultar Producto',
                'eliminar-producto': 'Eliminar Producto',
                'registrar-usuario': 'Registrar Nuevo Usuario',
                'modificar-usuario': 'Modificar Usuario',
                'consultar-usuario': 'Consultar Usuario',
                'baja-usuario': 'Dar de Baja Usuario',
                'corte-caja': 'Corte de Caja'
            };
            
            console.log('Acción seleccionada:', action);
            alert(`Accediendo a: ${messages[action]}`);
        }

        // Confirmar cierre de sesión
        function confirmLogout() {
            if (confirm('¿Estás seguro que deseas cerrar sesión?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>