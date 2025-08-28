<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Usuarios - Sistema Grammer</title>
    
    <!-- CSS Centralizado de Grammer -->
    <link rel="stylesheet" href="../../../public/css/styles.css">
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

    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Encabezado del Panel -->
        <header class="admin-header">
            <h1><i class="fas fa-users-cog"></i> Gestión de Usuarios</h1>
            <a href="/admin/users/new" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Usuario
            </a>
        </header>

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

        <!-- Tabla de Usuarios -->
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
        });
    </script>
</body>
</html>
