<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Autenticación Grammer</title>
    
    <!-- CSS Centralizado de Grammer -->
    <link rel="stylesheet" href="../../public/css/styles.css">
    
    <!-- 
    ¿QUÉ HACE ESTA VISTA?
    - Página principal después del login exitoso
    - Muestra información personalizada del usuario
    - Lista aplicaciones conectadas y disponibles
    - Panel de estadísticas y accesos rápidos
    - Integra con daoLogin.php método mostrarDashboard()
    
    ¿CÓMO FUNCIONA?
    - Recibe datos del usuario desde $_SESSION (establecida en daoLogin.php)
    - Muestra información personalizada por planta y rol
    - Enlaces a otras secciones del sistema (perfil, admin)
    - JavaScript para funcionalidades interactivas
    
    ¿PARA QUÉ?
    - Bienvenida personalizada al sistema de autenticación
    - Hub central para acceso a otras aplicaciones
    - Información de estado del usuario y sistema
    - Experiencia profesional post-login
    -->
    
    <style>
        /* Estilos específicos para dashboard */
        .dashboard-container {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: var(--spacing-xl);
            min-height: 100vh;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--grammer-blue) 0%, var(--grammer-light-blue) 100%);
            color: var(--white);
            padding: var(--spacing-2xl);
            border-radius: var(--border-radius-lg);
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-lg);
        }
        
        .user-welcome {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-lg);
        }
        
        .user-avatar-large {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin-right: var(--spacing-lg);
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        
        .welcome-text h1 {
            margin: 0 0 var(--spacing-sm) 0;
            font-size: 2.5rem;
            color: var(--white);
        }
        
        .welcome-text p {
            margin: 0;
            font-size: 1.1rem;
            opacity: 0.9;
            color: var(--white);
        }
        
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            margin-top: var(--spacing-lg);
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: var(--spacing-lg);
            border-radius: var(--border-radius);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: var(--spacing-xs);
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }
        
        .main-content {
            space-y: var(--spacing-xl);
        }
        
        .sidebar-content {
            space-y: var(--spacing-lg);
        }
        
        .section-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            margin-bottom: var(--spacing-xl);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-lg);
        }
        
        .section-icon {
            width: 40px;
            height: 40px;
            background: var(--grammer-accent);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.2rem;
            margin-right: var(--spacing-md);
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--grammer-blue);
            margin: 0;
        }
        
        .apps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: var(--spacing-lg);
        }
        
        .app-card-dashboard {
            background: var(--gray-50);
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-lg);
            text-decoration: none;
            color: inherit;
            transition: all var(--transition-normal);
            display: block;
        }
        
        .app-card-dashboard:hover {
            border-color: var(--grammer-accent);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
            text-decoration: none;
        }
        
        .app-header {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-md);
        }
        
        .app-icon-small {
            width: 40px;
            height: 40px;
            background: var(--grammer-blue);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1rem;
            margin-right: var(--spacing-md);
        }
        
        .app-name {
            font-weight: 600;
            color: var(--grammer-blue);
            margin: 0;
        }
        
        .app-description {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin: 0;
        }
        
        .app-status {
            display: flex;
            align-items: center;
            margin-top: var(--spacing-sm);
            font-size: 0.8rem;
        }
        
        .status-connected {
            color: var(--success);
        }
        
        .status-available {
            color: var(--gray-500);
        }
        
        .quick-actions {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: var(--spacing-sm) var(--spacing-md);
            background: var(--grammer-light-blue);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all var(--transition-normal);
            display: inline-flex;
            align-items: center;
        }
        
        .action-btn:hover {
            background: var(--grammer-blue);
            transform: translateY(-1px);
            color: var(--white);
            text-decoration: none;
        }
        
        .action-btn-icon {
            margin-right: var(--spacing-xs);
        }
        
        .recent-activity {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: var(--spacing-md) 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 32px;
            height: 32px;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: var(--spacing-md);
            font-size: 0.8rem;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            color: var(--gray-700);
            font-size: 0.9rem;
            margin: 0 0 var(--spacing-xs) 0;
        }
        
        .activity-time {
            color: var(--gray-500);
            font-size: 0.8rem;
            margin: 0;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .user-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: var(--spacing-md);
            }
            
            .dashboard-header {
                padding: var(--spacing-lg);
            }
            
            .user-welcome {
                flex-direction: column;
                text-align: center;
            }
            
            .user-avatar-large {
                margin-right: 0;
                margin-bottom: var(--spacing-md);
            }
            
            .welcome-text h1 {
                font-size: 2rem;
            }
            
            .user-stats {
                grid-template-columns: 1fr;
            }
            
            .apps-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header de Bienvenida -->
        <div class="dashboard-header">
            <div class="user-welcome">
                <div class="user-avatar-large">
                    <?= strtoupper(substr($usuario['nombre'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="welcome-text">
                    <h1>¡Bienvenido, <?= htmlspecialchars(explode(' ', $usuario['nombre'] ?? 'Usuario')[0]) ?>!</h1>
                    <p>
                        <?= htmlspecialchars($usuario['email'] ?? '') ?> • 
                        Planta: <?= htmlspecialchars($usuario['planta'] ?? 'No definida') ?>
                    </p>
                </div>
            </div>
            
            <div class="user-stats">
                <div class="stat-card">
                    <div class="stat-number"><?= $estadisticas['aplicaciones_conectadas'] ?? 0 ?></div>
                    <div class="stat-label">Aplicaciones Conectadas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $ultimo_login = $estadisticas['ultimo_login'] ?? null;
                        if ($ultimo_login) {
                            echo date('H:i', $ultimo_login);
                        } else {
                            echo 'Ahora';
                        }
                        ?>
                    </div>
                    <div class="stat-label">Último Acceso</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">✅</div>
                    <div class="stat-label">Cuenta Activa</div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-content">
            <!-- Contenido Principal -->
            <div class="main-content">
                <!-- Aplicaciones Disponibles -->
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-icon">🏢</div>
                        <h2 class="section-title">Aplicaciones Corporativas</h2>
                    </div>
                    
                    <div class="apps-grid">
                        <!-- Intranet Grammer -->
                        <a href="https://intranet.grammer.com" class="app-card-dashboard" target="_blank">
                            <div class="app-header">
                                <div class="app-icon-small">🏠</div>
                                <h3 class="app-name">Intranet Grammer</h3>
                            </div>
                            <p class="app-description">Portal interno con noticias, documentos y recursos corporativos</p>
                            <div class="app-status status-connected">
                                ✅ Conectada con SSO
                            </div>
                        </a>
                        
                        <!-- Sistema de Pedidos -->
                        <a href="https://pedidos.grammer.com" class="app-card-dashboard" target="_blank">
                            <div class="app-header">
                                <div class="app-icon-small">📦</div>
                                <h3 class="app-name">Sistema de Pedidos</h3>
                            </div>
                            <p class="app-description">Gestión de pedidos, inventario y logística</p>
                            <div class="app-status status-connected">
                                ✅ Conectada con SSO
                            </div>
                        </a>
                        
                        <!-- CRM Grammer -->
                        <a href="https://crm.grammer.com" class="app-card-dashboard" target="_blank">
                            <div class="app-header">
                                <div class="app-icon-small">🤝</div>
                                <h3 class="app-name">CRM Grammer</h3>
                            </div>
                            <p class="app-description">Gestión de clientes y relaciones comerciales</p>
                            <div class="app-status status-available">
                                🔗 Disponible para conectar
                            </div>
                        </a>
                        
                        <!-- Sistema de Recursos Humanos -->
                        <div class="app-card-dashboard" style="opacity: 0.6;">
                            <div class="app-header">
                                <div class="app-icon-small">👥</div>
                                <h3 class="app-name">Recursos Humanos</h3>
                            </div>
                            <p class="app-description">Gestión de personal, nóminas y vacaciones</p>
                            <div class="app-status status-available">
                                🚧 En desarrollo
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Acciones Rápidas -->
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-icon">⚡</div>
                        <h2 class="section-title">Acciones Rápidas</h2>
                    </div>
                    
                    <div class="quick-actions">
                        <a href="/user/profile" class="action-btn">
                            <span class="action-btn-icon">👤</span>
                            Editar Perfil
                        </a>
                        <a href="/user/profile#applications" class="action-btn">
                            <span class="action-btn-icon">🔗</span>
                            Gestionar Conexiones
                        </a>
                        <?php if (($usuario['email'] ?? '') === 'admin@grammer.com'): ?>
                            <a href="/admin/users" class="action-btn">
                                <span class="action-btn-icon">👨‍💼</span>
                                Panel Admin
                            </a>
                        <?php endif; ?>
                        <a href="mailto:it@grammer.com" class="action-btn">
                            <span class="action-btn-icon">🆘</span>
                            Soporte IT
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="sidebar-content">
                <!-- Actividad Reciente -->
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-icon">📊</div>
                        <h3 class="section-title">Actividad Reciente</h3>
                    </div>
                    
                    <ul class="recent-activity">
                        <li class="activity-item">
                            <div class="activity-icon">🔐</div>
                            <div class="activity-content">
                                <p class="activity-text">Inicio de sesión exitoso</p>
                                <p class="activity-time">Ahora mismo</p>
                            </div>
                        </li>
                        
                        <?php if (!empty($aplicaciones)): ?>
                            <?php foreach (array_slice($aplicaciones, 0, 3) as $app): ?>
                                <li class="activity-item">
                                    <div class="activity-icon">🔗</div>
                                    <div class="activity-content">
                                        <p class="activity-text">Acceso a <?= htmlspecialchars($app['name']) ?></p>
                                        <p class="activity-time"><?= date('d/m/Y H:i', strtotime($app['ultimo_acceso'])) ?></p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <li class="activity-item">
                            <div class="activity-icon">✅</div>
                            <div class="activity-content">
                                <p class="activity-text">Cuenta verificada</p>
                                <p class="activity-time"><?= date('d/m/Y', strtotime($usuario['login_timestamp'] ?? 'now')) ?></p>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <!-- Información del Sistema -->
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-icon">ℹ️</div>
                        <h3 class="section-title">Información</h3>
                    </div>
                    
                    <div style="space-y: var(--spacing-md);">
                        <div>
                            <strong>Sistema:</strong><br>
                            <small>Autenticación Grammer v1.0</small>
                        </div>
                        <div style="margin-top: var(--spacing-md);">
                            <strong>Soporte:</strong><br>
                            <small><a href="mailto:it@grammer.com" class="help-link">it@grammer.com</a></small>
                        </div>
                        <div style="margin-top: var(--spacing-md);">
                            <strong>Documentación:</strong><br>
                            <small><a href="#" class="help-link">Guía de usuario</a></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer con Logout -->
        <div class="section-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="margin: 0; color: var(--gray-600);">
                        Sesión iniciada como <strong><?= htmlspecialchars($usuario['nombre'] ?? 'Usuario') ?></strong>
                    </p>
                    <small style="color: var(--gray-500);">
                        IP: <?= $_SERVER['REMOTE_ADDR'] ?> • 
                        Navegador: <?= substr($_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido', 0, 50) ?>...
                    </small>
                </div>
                
                <form method="POST" action="/logout" style="margin: 0;">
                    <button type="submit" class="btn btn-secondary">
                        🚪 Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        /**
         * JavaScript para dashboard interactivo
         * 
         * ¿QUÉ HACE?
         * - Mejora la experiencia de usuario con interacciones dinámicas
         * - Maneja confirmaciones de logout
         * - Actualiza estadísticas en tiempo real
         * - Efectos visuales y animaciones suaves
         * 
         * ¿CÓMO FUNCIONA?
         * - Event listeners para clicks y hover
         * - Actualizaciones periódicas de estado
         * - Animaciones CSS activadas por JavaScript
         * 
         * ¿PARA QUÉ?
         * - Dashboard más profesional e interactivo
         * - Feedback inmediato al usuario
         * - Experiencia moderna similar a aplicaciones web actuales
         */
        
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar hora actual cada minuto
            setInterval(updateCurrentTime, 60000);
            
            // Confirmación de logout
            const logoutForm = document.querySelector('form[action="/logout"]');
            if (logoutForm) {
                logoutForm.addEventListener('submit', function(e) {
                    if (!confirm('¿Estás seguro de cerrar sesión?')) {
                        e.preventDefault();
                    }
                });
            }
            
            // Animación de entrada para las cards 
            const cards = document.querySelectorAll('.section-card, .app-card-dashboard');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Efecto de hover mejorado para app cards
            const appCards = document.querySelectorAll('.app-card-dashboard');
            appCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-2px) scale(1)';
                });
            });
            
            // Log de interacciones para analytics
            document.querySelectorAll('.app-card-dashboard').forEach(card => {
                card.addEventListener('click', function() {
                    const appName = this.querySelector('.app-name')?.textContent;
                    console.log('Dashboard: Acceso a aplicación', appName);
                });
            });
        });
        
        function updateCurrentTime() {
            // Actualizar la hora en las estadísticas si es necesario
            const timeElement = document.querySelector('.stat-number');
            if (timeElement && timeElement.textContent.includes(':')) {
                timeElement.textContent = new Date().toLocaleTimeString('es-ES', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }
        
        // Detectar si el usuario está inactivo (opcional)
        let inactivityTimer;
        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                console.log('Usuario inactivo detectado');
                // Aquí podrías implementar auto-logout después de X tiempo
            }, 30 * 60 * 1000); // 30 minutos
        }
        
        // Eventos para detectar actividad
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetInactivityTimer, true);
        });
        
        resetInactivityTimer();
    </script>
</body>
</html>