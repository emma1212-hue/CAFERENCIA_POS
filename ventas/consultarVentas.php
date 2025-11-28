<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../indexLogin.php");
    exit();
}

$rol = $_SESSION['rol'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <title>Consultar Ventas | Cafetería</title>
    <link rel="stylesheet" href="../homepage/css/styleshome.css">
    <link rel="stylesheet" href="css/ventasStyle.css">
    <link rel="stylesheet" href="css/consultarVentas.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <button class="icon-btn menu-btn" onclick="window.location.href='../indexhome.php'">←</button>
            <div>
                <h1>
                    <svg class="title-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                        <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></circle>
                        <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    Consultar Ventas
                </h1>
                <p class="subtitle">Este es un apartado para consultar las ventas realizadas</p>
            </div>
        </div>

        <div class="controls">
            <div class="filters-panel" role="region" aria-label="Opciones de filtrado">
                <input id="q" class="search-input" type="text" placeholder="Buscar por idVenta o idUsuario" aria-label="Buscar por idVenta o idUsuario">

                <label>
                    Desde: <input id="fromDate" class="filter-input" type="date">
                </label>
                <label>
                    Hasta: <input id="toDate" class="filter-input" type="date">
                </label>
                <select id="tipoPagoFilter" class="filter-input">
                    <option value="">Todos los métodos</option>
                    <option value="Efectivo">Efectivo</option>
                    <option value="Transferencia">Transferencia</option>
                </select>
                <div class="filters-actions">
                    <button class="btn" onclick="applyFilters()" aria-label="Buscar ventas">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="btn-icon" aria-hidden="true" focusable="false">
                            <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"></circle>
                            <path d="M21 21l-4.5-4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        Buscar
                    </button>
                    <button class="btn btn-secondary" onclick="resetFilters()" aria-label="Limpiar filtros">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="btn-icon" aria-hidden="true" focusable="false">
                            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"></path>
                        </svg>
                        Limpiar
                    </button>
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table class="sales-table" id="salesTable">
                <thead>
                    <tr>
                        <th>idVenta</th>
                        <th>fechaVenta</th>
                        <th>totalVenta</th>
                        <th>tipoPago</th>
                        <th>idUsuario</th>
                        <th>idCorte</th>
                    </tr>
                </thead>
                <tbody id="salesBody">
                    <!-- Filas de ejemplo (sin conexión a BD) -->
                    <tr data-fecha="2025-11-10" data-usuario="María López">
                        <td>1001</td>
                        <td>2025-11-10</td>
                        <td>$45.50</td>
                        <td>Efectivo</td>
                        <td>12</td>
                        <td>3</td>
                    </tr>
                    <tr data-fecha="2025-11-12" data-usuario="Carlos Ruiz">
                        <td>1002</td>
                        <td>2025-11-12</td>
                        <td>$23.00</td>
                        <td>Transferencia</td>
                        <td>8</td>
                        <td>3</td>
                    </tr>
                    <tr data-fecha="2025-11-13" data-usuario="Ana Gómez">
                        <td>1003</td>
                        <td>2025-11-13</td>
                        <td>$12.75</td>
                        <td>Transferencia</td>
                        <td>5</td>
                        <td>4</td>
                    </tr>
                    <tr data-fecha="2025-11-14" data-usuario="Luis Pérez">
                        <td>1004</td>
                        <td>2025-11-14</td>
                        <td>$60.00</td>
                        <td>Efectivo</td>
                        <td>3</td>
                        <td>4</td>
                    </tr>
                    <tr data-fecha="2025-11-15" data-usuario="Sofía Díaz">
                        <td>1005</td>
                        <td>2025-11-15</td>
                        <td>$8.50</td>
                        <td>Transferencia</td>
                        <td>7</td>
                        <td>5</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Vista móvil: tarjetas -->
        <div class="cards" id="cardsContainer">
            <!-- Cards generadas por JS a partir de la tabla -->
        </div>

    </div>

    <script>
        // Generar tarjetas móviles a partir de las filas de la tabla
        function buildCards() {
            const tbody = document.getElementById('salesBody');
            const cards = document.getElementById('cardsContainer');
            cards.innerHTML = '';
            Array.from(tbody.querySelectorAll('tr')).forEach(row => {
                const cols = row.querySelectorAll('td');
                const card = document.createElement('div');
                card.className = 'sale-card';
                const usuarioNombre = row.getAttribute('data-usuario') || '';
                card.innerHTML = `
                    <div class="card-row"><div class="card-label">idVenta</div><div>${cols[0].textContent}</div></div>
                    <div class="card-row"><div class="card-label">fecha</div><div>${cols[1].textContent}</div></div>
                    <div class="card-row"><div class="card-label">total</div><div>${cols[2].textContent}</div></div>
                    <div class="card-row"><div class="card-label">tipoPago</div><div>${cols[3].textContent}</div></div>
                    <div class="card-row"><div class="card-label">usuario</div><div>${usuarioNombre || cols[4].textContent}</div></div>
                    <div class="card-row"><div class="card-label">idUsuario</div><div>${cols[4].textContent}</div></div>
                    <div class="card-row"><div class="card-label">idCorte</div><div>${cols[5].textContent}</div></div>
                `;
                cards.appendChild(card);
            });
        }

        // Filtrado simple en el front
        function applyFilters() {
            const q = document.getElementById('q').value.toLowerCase();
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;
            const tipo = document.getElementById('tipoPagoFilter').value;

            const rows = document.querySelectorAll('#salesBody tr');
            rows.forEach(row => {
                const cols = row.querySelectorAll('td');
                const id = cols[0].textContent.toLowerCase();
                const fecha = row.getAttribute('data-fecha');
                const idUsuario = cols[4].textContent.toLowerCase();

                let visible = true;

                if (q) {
                    // Buscar únicamente por idVenta o idUsuario
                    const hay = id.includes(q) || idUsuario.includes(q);
                    if (!hay) visible = false;
                }

                // filtro por tipo de pago si se selecciona
                if (tipo) {
                    const tipoPago = cols[3].textContent.toLowerCase();
                    if (tipo.toLowerCase() !== tipoPago) visible = false;
                }

                if (from && fecha < from) visible = false;
                if (to && fecha > to) visible = false;

                row.style.display = visible ? '' : 'none';
            });

            buildCards();
        }

        function resetFilters() {
            document.getElementById('q').value = '';
            document.getElementById('fromDate').value = '';
            document.getElementById('toDate').value = '';
            document.getElementById('tipoPagoFilter').value = '';
            applyFilters();
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', () => {
            buildCards();
        });
    </script>
</body>
</html>