<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Usuarios - Sistema Grammer</title>
    
    <!-- CSS Centralizado de Grammer -->
    <link rel="stylesheet" href="../../public/css/styles.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- 
    ¿QUÉ HACE ESTA VISTA?
    - Muestra un panel para la gestión de usuarios del sistema.
    - Permite a los administradores ver, buscar y filtrar usuarios.
    - Proporciona una interfaz para realizar acciones administrativas (ej. editar, desactivar).
    - Se integra con el controlador daoUser.php, método mostrarPanelAdmin().
    
    ¿CÓMO FUNCIONA?
    - Recibe un array de usuarios ($usuarios) desde el controlador.
    - Renderiza una tabla HTML con la información de cada usuario.
    - Utiliza JavaScript para implementar filtros dinámicos sin recargar la página.
    - Los enlaces de acción (ej. "Editar") apuntarían a otras rutas manejadas por el Front Controller.
    
    ¿PARA QUÉ?
    - Para centralizar la administración de cuentas de usuario.
    - Para facilitar el mantenimiento del sistema (altas, bajas, cambios).
    - Para proporcionar a los administradores las herramientas necesarias para su rol.
    -->
    
    <style>
        /* Estilos específicos para el panel de administración */
        .admin-container {
            max-width: var(--max-width, 1200px);
            margin: 0 auto;
            padding: var(--spacing-xl);
            min-height: 100vh;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-xl);
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .admin-header h1 {
            color: var(--grammer-blue);
            margin: 0;
        }

        .filters-container {
            display: flex;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
            flex-wrap: wrap;
        }

        .search-input, .filter-select {
            padding: var(--spacing-sm) var(--spacing-md);
            border: 2px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color var(--transition-normal);
        }
        .search-input:focus, .filter-select:focus {
            outline: none;
            border-color: var(--grammer-blue);
        }

        .users-table-container {
            overflow-x: auto;
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .users-table th, .users-table td {
            padding: var(--spacing-md) var(--spacing-lg);
            border-bottom: 1px solid var(--gray-200);
        }

        .users-table th {
            background-color: var(--gray-50);
            color: var(--gray-600);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
        }
        
        .users-table tr:last-child td {
            border-bottom: none;
        }

        .users-table tr:hover {
            background-color: var(--gray-100);
        }

        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--grammer-light-blue);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: var(--spacing-md);
        }

        .user-name {
            font-weight: 600;
            color: var(--gray-800);
        }
        .user-email {
            font-size: 0.9rem;
            color: var(--gray-500);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-activo {
            background-color: #d1fae5; /* Verde claro */
            color: #065f46; /* Verde oscuro */
        }
        .status-inactivo {
            background-color: #fee2e2; /* Rojo claro */
            color: #991b1b; /* Rojo oscuro */
        }

        .action-buttons .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
            margin-right: var(--spacing-xs);
        }
        
        .no-results {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--gray-500);
        }

        /* 🆕 Estilos para el panel OAuth */
        .tab-container {
            margin-bottom: var(--spacing-xl);
        }

        .tab-buttons {
            display: flex;
            border-bottom: 2px solid var(--gray-200);
            margin-bottom: var(--spacing-lg);
        }

        .tab-button {
            padding: var(--spacing-md) var(--spacing-lg);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-600);
            border-bottom: 2px solid transparent;
            transition: all var(--transition-normal);
        }

        .tab-button.active {
            color: var(--grammer-blue);
            border-bottom-color: var(--grammer-blue);
        }

        .tab-button:hover {
            color: var(--grammer-blue);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .oauth-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .oauth-table th, .oauth-table td {
            padding: var(--spacing-md) var(--spacing-lg);
            border-bottom: 1px solid var(--gray-200);
        }

        .oauth-table th {
            background-color: var(--gray-50);
            color: var(--gray-600);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        .oauth-table tr:hover {
            background-color: var(--gray-100);
        }

        .client-id-badge {
            background: var(--grammer-light-blue);
            color: var(--white);
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.8rem;
        }

        .secret-hidden {
            font-family: monospace;
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        .oauth-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }

        .stat-card {
            background: var(--white);
            padding: var(--spacing-lg);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--grammer-blue);
            margin-bottom: var(--spacing-xs);
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php
    // Verificar permisos de administrador
    require_once __DIR__ . '/../Controllers/SessionValidator.php';
    SessionValidator::requerirAdmin();
    ?>
    
    <div class="admin-container">
        <!-- Encabezado del Panel -->
        <header class="admin-header">
            <h1><i class="fas fa-users-cog"></i> Panel de Administración</h1>
            <a href="/admin/users/new" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Usuario
            </a>
        </header>

        <!-- 🆕 Tabs de Navegación -->
        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-button active" data-tab="users">
                    <i class="fas fa-users"></i> Usuarios
                </button>
                <button class="tab-button" data-tab="oauth">
                    <i class="fas fa-key"></i> Clientes OAuth
                </button>
            </div>

            <!-- 📊 Tab de Usuarios (contenido existente) -->
            <div id="users-tab" class="tab-content active">
                <!-- Filtros y Búsqueda -->
                <div class="section-card">
                    <div class="filters-container">
                        <input type="text" id="searchInput" class="search-input" placeholder="Buscar por nombre o email...">
                        <select id="plantFilter" class="filter-select">
                            <option value="">Todas las Plantas</option>
                            <option value="Querétaro">Querétaro</option>
                            <option value="Puebla">Puebla</option>
                            <option value="Monterrey">Monterrey</option>
                        </select>
                        <select id="statusFilter" class="filter-select">
                            <option value="">Todos los Estados</option>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>

                <!-- Tabla de Usuarios (contenido existente) -->
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Planta</th>
                                <th>Estado</th>
                                <th>Último Login</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="5" class="no-results">No se encontraron usuarios.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="user-name"><?= htmlspecialchars($usuario['nombre']) ?></div>
                                                    <div class="user-email"><?= htmlspecialchars($usuario['email']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($usuario['planta']) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($usuario['estado']) ?>">
                                                <?= htmlspecialchars($usuario['estado']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) ?></td>
                                        <td class="action-buttons">
                                            <a href="/admin/users/edit/<?= $usuario['id'] ?>" class="btn btn-secondary btn-sm">Editar</a>
                                            <a href="/admin/users/view/<?= $usuario['id'] ?>" class="btn btn-tertiary btn-sm">Ver</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                     <div id="noResultsMessage" class="no-results" style="display: none;">
                        No se encontraron usuarios que coincidan con los filtros.
                    </div>
                </div>
            </div>

            <!-- 🔑 Nueva Tab de OAuth -->
            <div id="oauth-tab" class="tab-content">
                <!-- Estadísticas OAuth -->
                <div class="oauth-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="totalClients">3</div>
                        <div class="stat-label">Clientes Registrados</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="activeTokens">--</div>
                        <div class="stat-label">Tokens Activos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="todayLogins">--</div>
                        <div class="stat-label">Logins Hoy</div>
                    </div>
                </div>

                <!-- Tabla de Clientes OAuth -->
                <div class="users-table-container">
                    <table class="oauth-table">
                        <thead>
                            <tr>
                                <th>Cliente ID</th>
                                <th>Nombre</th>
                                <th>Redirect URI</th>
                                <th>Secret</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // 🔥 Aquí obtenemos los clientes desde BD
                            $oauth_clients = obtenerClientesOAuth(); // Función que crearemos
                            
                            if (empty($oauth_clients)): ?>
                                <tr>
                                    <td colspan="6" class="no-results">No hay clientes OAuth registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($oauth_clients as $client): ?>
                                    <tr>
                                        <td>
                                            <span class="client-id-badge"><?= htmlspecialchars($client['id']) ?></span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($client['name']) ?></strong>
                                        </td>
                                        <td>
                                            <span title="<?= htmlspecialchars($client['redirect_uri']) ?>">
                                                <?= substr(htmlspecialchars($client['redirect_uri']), 0, 40) ?>...
                                            </span>
                                        </td>
                                        <td>
                                            <span class="secret-hidden" onclick="toggleSecret(this, '<?= htmlspecialchars($client['secret']) ?>')">
                                                ••••••••••••••••
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-activo">Activo</span>
                                        </td>
                                        <td class="action-buttons">
                                            <button class="btn btn-secondary btn-sm" onclick="regenerateSecret('<?= $client['id'] ?>')">
                                                <i class="fas fa-sync"></i> Regenerar Secret
                                            </button>
                                            <button class="btn btn-tertiary btn-sm" onclick="viewClientDetails('<?= $client['id'] ?>')">
                                                <i class="fas fa-eye"></i> Ver
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 🆕 Formulario para Agregar Cliente -->
                <div class="section-card" style="margin-top: var(--spacing-xl);">
                    <h3><i class="fas fa-plus"></i> Registrar Nuevo Cliente OAuth</h3>
                    <form id="newClientForm" style="display: grid; gap: var(--spacing-md); grid-template-columns: 1fr 1fr;">
                        <input type="text" placeholder="Client ID (ej: app_cliente)" required>
                        <input type="text" placeholder="Nombre de la Aplicación" required>
                        <input type="url" placeholder="Redirect URI" required style="grid-column: 1 / -1;">
                        <button type="submit" class="btn btn-primary" style="grid-column: 1 / -1;">
                            <i class="fas fa-plus"></i> Crear Cliente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        /**
         * JavaScript para el panel de administración
         * * ¿QUÉ HACE?
         * - Añade interactividad a la tabla de usuarios.
         * - Implementa un filtro en tiempo real para la búsqueda, planta y estado.
         * - Muestra u oculta filas de la tabla según los criterios de búsqueda.
         * * ¿CÓMO FUNCIONA?
         * - Agrega 'event listeners' a los campos de input y select.
         * - Cada vez que un filtro cambia, se ejecuta la función filterTable().
         * - La función recorre todas las filas de la tabla y comprueba si coinciden con los valores de los filtros.
         * - Las filas que no coinciden se ocultan (display: 'none') y las que sí, se muestran.
         * * ¿PARA QUÉ?
         * - Para mejorar la experiencia del administrador, permitiéndole encontrar usuarios rápidamente.
         * - Para evitar recargas de página innecesarias, haciendo la interfaz más fluida y moderna.
         */
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const plantFilter = document.getElementById('plantFilter');
            const statusFilter = document.getElementById('statusFilter');
            const tableBody = document.getElementById('usersTableBody');
            const rows = tableBody.getElementsByTagName('tr');
            const noResultsMessage = document.getElementById('noResultsMessage');

            function filterTable() {
                const searchText = searchInput.value.toLowerCase();
                const plantValue = plantFilter.value;
                const statusValue = statusFilter.value;
                let visibleRows = 0;

                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const nameEmailCell = row.cells[0].textContent.toLowerCase();
                    const plantCell = row.cells[1].textContent;
                    const statusCell = row.cells[2].textContent.trim();

                    const matchesSearch = nameEmailCell.includes(searchText);
                    const matchesPlant = plantValue === "" || plantCell === plantValue;
                    const matchesStatus = statusValue === "" || statusCell === statusValue;

                    if (matchesSearch && matchesPlant && matchesStatus) {
                        row.style.display = "";
                        visibleRows++;
                    } else {
                        row.style.display = "none";
                    }
                }
                
                // Mostrar mensaje si no hay resultados
                if (visibleRows === 0) {
                    noResultsMessage.style.display = "block";
                } else {
                    noResultsMessage.style.display = "none";
                }
            }

            // Añadir listeners a los filtros
            searchInput.addEventListener('keyup', filterTable);
            plantFilter.addEventListener('change', filterTable);
            statusFilter.addEventListener('change', filterTable);

            // 🆕 Tab functionality
            document.querySelectorAll('.tab-button').forEach(button => {
                button.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');
                    showTab(tabName, this);
                });
            });

            function showTab(tabName, clickedButton) {
                // Ocultar todas las tabs
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('active');
                });

                // Mostrar tab seleccionada
                document.getElementById(tabName + '-tab').classList.add('active');
                clickedButton.classList.add('active');
            }
        });

        // 🆕 Funciones para OAuth Panel
        function toggleSecret(element, secret) {
            if (element.textContent === '••••••••••••••••') {
                element.textContent = secret;
                element.style.color = 'var(--grammer-blue)';
            } else {
                element.textContent = '••••••••••••••••';
                element.style.color = 'var(--gray-500)';
            }
        }

        function regenerateSecret(clientId) {
            if (confirm('¿Estás seguro de regenerar el secret? Esto invalidará el secret actual.')) {
                // Aquí harías una petición AJAX para regenerar
                alert(`Secret regenerado para ${clientId} (pendiente implementación)`);
            }
        }

        function viewClientDetails(clientId) {
            // Mostrar modal con detalles del cliente
            alert(`Mostrar detalles de ${clientId} (pendiente implementación)`);
        }

        // Event listener para nuevo cliente
        document.getElementById('newClientForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Formulario de nuevo cliente (pendiente implementación)');
        });
    </script>

<?php
function obtenerClientesOAuth() {
    try {
        require_once __DIR__ . '/../../db/db.php';
        $conector = new LocalConector();
        $conexion = $conector->conectar();
        
        $query = "SELECT id, name, redirect_uri, secret, created_at FROM oauth_clients ORDER BY created_at DESC";
        $result = $conexion->query($query);
        
        $clientes = [];
        while ($row = $result->fetch_assoc()) {
            $clientes[] = $row;
        }
        
        return $clientes;
    } catch (Exception $e) {
        error_log("Error obteniendo clientes OAuth: " . $e->getMessage());
        return [];
    }
}

// Ejecutar inserción si no hay datos
$clientes_existentes = obtenerClientesOAuth();
if (empty($clientes_existentes)) {
    insertarClientesPrueba();
}
?>
