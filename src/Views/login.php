<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Autenticación Grammer</title>
    
    <!-- CSS Centralizado de Grammer -->
    <link rel="stylesheet" href="../../public/css/styles.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .brand-logo-image {
            width: 120px;
            height: auto;
            margin-bottom: var(--spacing-md);
            filter: brightness(0) invert(1); /* Hace el logo blanco si es oscuro */
        }
    </style>
    
    <!-- 
    ¿QUÉ HACE ESTA VISTA?
    - Presenta el formulario de login corporativo de Grammer.
    - Maneja mensajes de error y éxito dinámicamente.
    - Diseño responsive usando sistema centralizado de CSS.
    - Integración con daoLogin.php para procesar credenciales.
    - Branding corporativo profesional con variables Grammer.
    
    ¿CÓMO FUNCIONA?
    - Formulario POST hacia /login (manejado por daoLogin.php).
    - JavaScript para validaciones del lado del cliente.
    - CSS centralizado con variables corporativas --grammer-blue, etc.
    - Mensajes de error/éxito desde PHP via $_SESSION.
    
    ¿PARA QUÉ?
    - Punto de entrada único para todos los empleados de Grammer.
    - Primera impresión profesional del sistema de auth.
    - Experiencia de usuario consistente con otros sistemas Grammer.
    -->
</head>
<body class="auth-page">
    <div class="auth-container">
        <!-- Panel de Branding -->
        <div class="auth-brand">
            <img src="../../../public/images/imagen.png" alt="Grammer Logo" class="brand-logo-image">
            <div class="brand-logo">GRAMMER</div>
            <div class="brand-subtitle">Sistema de Autenticación Corporativo</div>
            <ul class="brand-features">
                <li><i class="fas fa-key"></i> Acceso único a todas las aplicaciones</li>
                <li><i class="fas fa-shield-alt"></i> Seguridad empresarial avanzada</li>
                <li><i class="fas fa-cogs"></i> Control de acceso centralizado</li>
                <li><i class="fas fa-user-friends"></i> Experiencia de usuario unificada</li>
            </ul>
        </div>
        
        <!-- Formulario de Login -->
        <div class="auth-form-section">
            <div class="form-header">
                <h1 class="form-title">Iniciar Sesión</h1>
                <p class="form-subtitle">Accede con tu correo corporativo @grammer.com</p>
            </div>
            
            <!-- Mensajes de Error/Éxito desde PHP -->
            <?php if (isset($_SESSION['error_login'])): ?>
                <div class="message message-error">
                    <?= htmlspecialchars($_SESSION['error_login']) ?>
                </div>
                <?php unset($_SESSION['error_login']); ?>
            <?php endif; ?>
            
            <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'logout_exitoso'): ?>
                <div class="message message-success">
                    Sesión cerrada correctamente. ¡Hasta pronto!
                </div>
            <?php endif; ?>
            
            <!-- Formulario Principal -->
            <form class="form-grammer" method="POST" action="/login" id="loginForm">
                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="tu.email@grammer.com"
                        required
                        autocomplete="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="password-field">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Tu contraseña corporativa"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full btn-large" id="loginBtn">
                    <span class="loading-spinner" id="loadingSpinner"></span>
                    <span id="btnText">Iniciar Sesión</span>
                </button>
            </form>
            
            <div class="form-footer">
                ¿Problemas para acceder? 
                <a href="mailto:it@grammer.com" class="help-link">Contacta IT Support</a>
            </div>
        </div>
    </div>
    
    <script>
        /**
         * JavaScript para mejorar la experiencia de usuario
         * Usando el sistema de diseño centralizado de Grammer
         */
        
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const togglePassword = document.getElementById('togglePassword');
            
            // Toggle para mostrar/ocultar contraseña
            togglePassword.addEventListener('click', function() {
                const icon = togglePassword.querySelector('i');
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    passwordInput.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            });
            
            // Validación de email en tiempo real
            emailInput.addEventListener('blur', function() {
                const email = emailInput.value.toLowerCase();
                
                if (email && !email.endsWith('@grammer.com')) {
                    showFieldError(emailInput, 'Debe usar su correo corporativo @grammer.com');
                } else {
                    clearFieldError(emailInput);
                }
            });
            
            // Manejo del envío del formulario
            loginForm.addEventListener('submit', function(e) {
                // Se previene el envío tradicional para manejarlo con JS/Fetch
                // y dar una experiencia de "Single Page Application"
                e.preventDefault(); 
                
                if (!validateForm()) {
                    return;
                }
                
                setLoadingState(true);
                
                // Simulación de envío, en un caso real se enviaría el formulario
                // Para este proyecto, dejamos que el form se envíe de forma nativa
                // si la validación es correcta.
                // submitForm(); // Descomentar si se usa Fetch
                
                // Si la validación JS pasa, se envía el formulario de verdad.
                loginForm.submit();
            });
            
            function validateForm() {
                let isValid = true;
                
                const email = emailInput.value.trim().toLowerCase();
                if (!email) {
                    showFieldError(emailInput, 'El correo es requerido');
                    isValid = false;
                } else if (!email.endsWith('@grammer.com')) {
                    showFieldError(emailInput, 'Solo se permiten correos @grammer.com');
                    isValid = false;
                } else {
                    clearFieldError(emailInput);
                }
                
                const password = passwordInput.value;
                if (!password) {
                    showFieldError(passwordInput, 'La contraseña es requerida');
                    isValid = false;
                } else {
                    clearFieldError(passwordInput);
                }
                
                return isValid;
            }
            
            function showFieldError(field, message) {
                clearFieldError(field);
                field.style.borderColor = 'var(--danger)';
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error';
                errorDiv.style.color = 'var(--danger)';
                errorDiv.style.fontSize = '0.85rem';
                errorDiv.style.marginTop = '5px';
                errorDiv.textContent = message;
                field.parentNode.appendChild(errorDiv);
            }
            
            function clearFieldError(field) {
                field.style.borderColor = '';
                const existingError = field.parentNode.querySelector('.field-error');
                if (existingError) {
                    existingError.remove();
                }
            }
            
            function setLoadingState(loading) {
                if (loading) {
                    loginBtn.disabled = true;
                    loadingSpinner.style.display = 'inline-block';
                    btnText.textContent = 'Autenticando...';
                } else {
                    loginBtn.disabled = false;
                    loadingSpinner.style.display = 'none';
                    btnText.textContent = 'Iniciar Sesión';
                }
            }
            
            [emailInput, passwordInput].forEach(input => {
                input.addEventListener('input', function() {
                    clearFieldError(this);
                });
            });
        });
    </script>
</body>
</html>
