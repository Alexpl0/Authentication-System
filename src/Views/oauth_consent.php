<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorización de Aplicación - Sistema de Autenticación Grammer</title>
    
    <!-- CSS Centralizado de Grammer -->
    <link rel="stylesheet" href="../../../public/css/styles.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- 
    ¿QUÉ HACE ESTA VISTA?
    - Muestra solicitud de autorización OAuth de una aplicación cliente
    - Presenta permisos específicos que la app solicita
    - Permite al usuario autorizar o denegar el acceso
    - Integra con daoOAuth.php para procesar la decisión del usuario
    - Implementa flujo OAuth 2.0 Authorization Code Grant
    
    ¿CÓMO FUNCIONA?
    - Recibe datos de $datos_consentimiento desde daoOAuth.php
    - Muestra información de la app (nombre, permisos solicitados)
    - Formulario POST hacia /oauth/authorize con decisión del usuario
    - JavaScript para mejorar UX y validaciones
    
    ¿PARA QUÉ?
    - Paso crucial del flujo OAuth donde usuario otorga consentimiento
    - Transparencia sobre qué datos compartirá con cada aplicación
    - Control granular de permisos por aplicación
    - Cumplimiento con estándares de privacidad y seguridad
    -->
    
    <style>
        /* Estilos específicos para la página de consentimiento */
        .consent-container {
            max-width: 600px;
            width: 100%;
        }
        
        .app-info {
            background: var(--white);
            border: 2px solid var(--grammer-light-blue);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
            text-align: center;
            box-shadow: var(--shadow-md);
        }
        
        .app-icon-large {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, var(--grammer-blue) 0%, var(--grammer-light-blue) 100%);
            border-radius: var(--border-radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 2rem;
            margin: 0 auto var(--spacing-lg);
            box-shadow: var(--shadow-md);
        }
        
        .consent-question {
            font-size: 1.3rem;
            color: var(--grammer-blue);
            font-weight: 600;
            margin-bottom: var(--spacing-md);
        }
        
        .app-name {
            color: var(--grammer-accent);
            font-weight: bold;
        }
        
        .user-info {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--grammer-blue);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: bold;
            margin-right: var(--spacing-md);
        }
        
        .user-details h4 {
            margin: 0;
            color: var(--grammer-blue);
        }
        
        .user-details p {
            margin: 0;
            color: var(--gray-600);
            font-size: 0.9rem;
        }
        
        .permissions-section {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-sm);
        }
        
        .permissions-title {
            color: var(--grammer-blue);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: var(--spacing-lg);
            display: flex;
            align-items: center;
        }
        
        .permissions-title::before {
            content: '';
            margin-right: var(--spacing-sm);
        }
        
        .permissions-title i {
            margin-right: var(--spacing-sm);
        }
        
        .permission-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: var(--spacing-md);
            padding: var(--spacing-md);
            background: var(--gray-50);
            border-radius: var(--border-radius);
            border-left: 4px solid var(--grammer-accent);
        }
        
        .permission-icon {
            color: var(--grammer-accent);
            font-size: 1.2rem;
            margin-right: var(--spacing-md);
            margin-top: 2px;
        }
        
        .permission-details h5 {
            margin: 0 0 var(--spacing-xs) 0;
            color: var(--grammer-blue);
            font-size: 0.95rem;
        }
        
        .permission-details p {
            margin: 0;
            color: var(--gray-600);
            font-size: 0.85rem;
        }
        
        .consent-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-md);
            margin-top: var(--spacing-xl);
        }
        
        .btn-deny {
            background: var(--white);
            color: var(--gray-600);
            border: 2px solid var(--gray-300);
        }
        
        .btn-deny:hover {
            background: var(--gray-100);
            color: var(--gray-700);
            border-color: var(--gray-400);
            transform: translateY(-1px);
        }
        
        .security-note {
            background: var(--background);
            border: 1px solid var(--grammer-light-blue);
            border-radius: var(--border-radius);
            padding: var(--spacing-md);
            margin-top: var(--spacing-lg);
            font-size: 0.85rem;
            color: var(--grammer-blue);
        }
        
        .security-note::before {
            content: '🛡️';
            margin-right: var(--spacing-sm);
        }
        
        .cancel-link {
            display: block;
            text-align: center;
            margin-top: var(--spacing-lg);
            color: var(--gray-500);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .cancel-link:hover {
            color: var(--grammer-blue);
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .consent-actions {
                grid-template-columns: 1fr;
            }
            
            .app-info {
                padding: var(--spacing-lg);
            }
            
            .permissions-section {
                padding: var(--spacing-lg);
            }
        }
    </style>
</head>
<body class="auth-page">
    <div class="consent-container">
        <!-- Información de la Aplicación -->
        <div class="app-info">
            <div class="app-icon-large">
                <?= strtoupper(substr($datos_consentimiento['cliente']['name'], 0, 2)) ?>
            </div>
            
            <div class="consent-question">
                ¿Autorizar a <span class="app-name"><?= htmlspecialchars($datos_consentimiento['cliente']['name']) ?></span> a acceder a tu información?
            </div>
            
            <p style="color: var(--gray-600); margin: 0;">
                Esta aplicación podrá acceder a los permisos que selecciones a continuación.
            </p>
        </div>
        
        <!-- Información del Usuario -->
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($datos_consentimiento['usuario']['nombre'], 0, 1)) ?>
            </div>
            <div class="user-details">
                <h4><?= htmlspecialchars($datos_consentimiento['usuario']['nombre']) ?></h4>
                <p><?= htmlspecialchars($datos_consentimiento['usuario']['email']) ?></p>
            </div>
        </div>
        
        <!-- Permisos Solicitados -->
        <div class="permissions-section">
            <div class="permissions-title">
                Permisos solicitados
            </div>
            
            <?php 
            $scope_descriptions = [
                'read_user' => [
                    'title' => 'Ver información básica del perfil',
                    'description' => 'Nombre completo, planta de trabajo y fecha de registro',
                    'icon' => 'fas fa-user'
                ],
                'read_email' => [
                    'title' => 'Ver dirección de correo electrónico',
                    'description' => 'Tu correo corporativo @grammer.com',
                    'icon' => 'fas fa-envelope'
                ]
            ];
            ?>
            
            <?php foreach ($datos_consentimiento['scopes'] as $scope => $description): ?>
                <?php $scope_info = $scope_descriptions[$scope] ?? ['title' => $description, 'description' => 'Acceso a este permiso', 'icon' => 'fas fa-key']; ?>
                <div class="permission-item">
                    <div class="permission-icon"><i class="<?= $scope_info['icon'] ?>"></i></div>
                    <div class="permission-details">
                        <h5><?= htmlspecialchars($scope_info['title']) ?></h5>
                        <p><?= htmlspecialchars($scope_info['description']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Formulario de Decisión -->
        <form method="POST" action="/oauth/authorize" id="consentForm">
            <!-- Parámetros OAuth hidden -->
            <input type="hidden" name="client_id" value="<?= htmlspecialchars($datos_consentimiento['client_id']) ?>">
            <input type="hidden" name="redirect_uri" value="<?= htmlspecialchars($datos_consentimiento['redirect_uri']) ?>">
            <input type="hidden" name="scope" value="<?= htmlspecialchars($datos_consentimiento['scope']) ?>">
            <input type="hidden" name="state" value="<?= htmlspecialchars($datos_consentimiento['state']) ?>">
            
            <div class="consent-actions">
                <button type="submit" name="authorize" value="no" class="btn btn-deny btn-full">
                    <i class="fas fa-times"></i> Denegar Acceso
                </button>
                <button type="submit" name="authorize" value="yes" class="btn btn-primary btn-full" id="authorizeBtn">
                    <span class="loading-spinner" id="loadingSpinner"></span>
                    <span id="btnText"><i class="fas fa-check"></i> Autorizar Aplicación</span>
                </button>
            </div>
            
            <div class="security-note">
                <i class="fas fa-shield-alt"></i>
                <strong>Nota de seguridad:</strong> Solo autoriza aplicaciones en las que confíes. 
                Puedes revocar estos permisos en cualquier momento desde tu perfil de usuario.
            </div>
        </form>
        
        <!-- Link de cancelación -->
        <a href="/dashboard" class="cancel-link"><i class="fas fa-arrow-left"></i> Cancelar y volver al dashboard</a>
    </div>
    
    <script>
        /**
         * JavaScript para página de consentimiento OAuth
         * 
         * ¿QUÉ HACE?
         * - Maneja envío del formulario con estado de loading
         * - Previene doble-click en botones de autorización
         * - Valida que se haya seleccionado una opción
         * - Mejora UX con feedback visual
         * 
         * ¿CÓMO FUNCIONA?
         * - Event listeners en botones de autorizar/denegar
         * - Estados de loading durante procesamiento
         * - Validaciones antes de envío
         * 
         * ¿PARA QUÉ?
         * - Evitar autorizaciones accidentales múltiples
         * - Feedback claro al usuario sobre el proceso
         * - Experiencia fluida en el flujo OAuth crítico
         */
        
        document.addEventListener('DOMContentLoaded', function() {
            const consentForm = document.getElementById('consentForm');
            const authorizeBtn = document.getElementById('authorizeBtn');
            const btnText = document.getElementById('btnText');
            const loadingSpinner = document.getElementById('loadingSpinner');
            
            // Manejo del envío del formulario
            consentForm.addEventListener('submit', function(e) {
                // Determinar cuál botón fue clickeado
                const clickedButton = e.submitter;
                const isAuthorizing = clickedButton.value === 'yes';
                
                if (isAuthorizing) {
                    // Confirmar autorización para aplicaciones críticas
                    const appName = '<?= addslashes($datos_consentimiento['cliente']['name']) ?>';
                    
                    if (!confirm(`¿Estás seguro de autorizar a ${appName} a acceder a tu información?`)) {
                        e.preventDefault();
                        return;
                    }
                    
                    // Estado de loading solo para autorización
                    setLoadingState(true);
                    
                    // Deshabilitar ambos botones para evitar doble envío
                    disableAllButtons(true);
                } else {
                    // Para denegación, solo deshabilitar botones
                    disableAllButtons(true);
                }
            });
            
            function setLoadingState(loading) {
                if (loading) {
                    loadingSpinner.style.display = 'inline-block';
                    btnText.textContent = 'Autorizando...';
                } else {
                    loadingSpinner.style.display = 'none';
                    btnText.textContent = '✅ Autorizar Aplicación';
                }
            }
            
            function disableAllButtons(disabled) {
                const allButtons = consentForm.querySelectorAll('button[type="submit"]');
                allButtons.forEach(button => {
                    button.disabled = disabled;
                });
            }
            
            // Auto-focus en botón de autorizar para accesibilidad
            authorizeBtn.focus();
            
            // Log de auditoría del lado del cliente
            console.log('OAuth Consent: Solicitud de autorización mostrada', {
                client: '<?= addslashes($datos_consentimiento['cliente']['name']) ?>',
                user: '<?= addslashes($datos_consentimiento['usuario']['email']) ?>',
                scopes: '<?= addslashes($datos_consentimiento['scope']) ?>',
                timestamp: new Date().toISOString()
            });
        });
        
        // Prevenir que el usuario salga accidentalmente
        window.addEventListener('beforeunload', function(e) {
            // Solo mostrar advertencia si hay una decisión pendiente
            e.returnValue = '¿Estás seguro de salir? La aplicación seguirá esperando tu autorización.';
        });
        
        // Remover advertencia cuando se envía el formulario
        document.getElementById('consentForm').addEventListener('submit', function() {
            window.removeEventListener('beforeunload', arguments.callee);
        });
    </script>
</body>
</html>