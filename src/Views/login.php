<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Autenticación Grammer</title>
    
    <!-- CSS Centralizado de Grammer -->
    <link rel="stylesheet" href="/assets/css/styles.css">
    
    <!-- 
    ¿QUÉ HACE ESTA VISTA?
    - Presenta el formulario de login corporativo de Grammer
    - Maneja mensajes de error y éxito dinámicamente
    - Diseño responsive usando sistema centralizado de CSS
    - Integración con daoLogin.php para procesar credenciales
    - Branding corporativo profesional con variables Grammer
    
    ¿CÓMO FUNCIONA?
    - Formulario POST hacia /login (manejado por daoLogin.php)
    - JavaScript para validaciones del lado del cliente
    - CSS centralizado con variables corporativas --grammer-blue, etc.
    - Mensajes de error/éxito desde PHP via $_SESSION
    
    ¿PARA QUÉ?
    - Punto de entrada único para todos los empleados de Grammer
    - Primera impresión profesional del sistema de auth
    - Experiencia de usuario consistente con otros sistemas Grammer
    -->
</head>
<body class="auth-page">
    <div class="auth-container">
        <!-- Panel de Branding -->
        <div class="auth-brand">
            <div class="brand-logo">GRAMMER</div>
            <div class="brand-subtitle">Sistema de Autenticación Corporativo</div>
            <ul class="brand-features">
                <li>Acceso único a todas las aplicaciones</li>
                <li>Seguridad empresarial avanzada</li>
                <li>Control de acceso centralizado</li>
                <li>Experiencia de usuario unificada</li>
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
                            👁️
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
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    togglePassword.textContent = '🙈';
                } else {
                    passwordInput.type = 'password';
                    togglePassword.textContent = '👁️';
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
                e.preventDefault();
                
                // Validaciones antes del envío
                if (!validateForm()) {
                    return;
                }
                
                // Estado de loading
                setLoadingState(true);
                
                // Enviar formulario
                submitForm();
            });
            
            function validateForm() {
                let isValid = true;
                
                // Validar email
                const email = emailInput.value.trim().toLowerCase();
                if (!email) {
                    showFieldError(emailInput, 'El correo es requerido');
                    isValid = false;
                } else if (!isValidEmail(email)) {
                    showFieldError(emailInput, 'Formato de correo inválido');
                    isValid = false;
                } else if (!email.endsWith('@grammer.com')) {
                    showFieldError(emailInput, 'Solo se permiten correos @grammer.com');
                    isValid = false;
                } else {
                    clearFieldError(emailInput);
                }
                
                // Validar contraseña
                const password = passwordInput.value;
                if (!password) {
                    showFieldError(passwordInput, 'La contraseña es requerida');
                    isValid = false;
                } else if (password.length < 3) {
                    showFieldError(passwordInput, 'La contraseña es muy corta');
                    isValid = false;
                } else {
                    clearFieldError(passwordInput);
                }
                
                return isValid;
            }
            
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
            
            function showFieldError(field, message) {
                clearFieldError(field);
                
                field.style.borderColor = 'var(--danger)';
                field.style.backgroundColor = '#fed7d7';
                
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
                field.style.backgroundColor = '';
                
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
            
            function submitForm() {
                // Crear FormData para envío
                const formData = new FormData(loginForm);
                
                // Enviar con fetch para mejor control
                fetch('/login', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.redirected) {
                        // Redirección exitosa (login correcto)
                        window.location.href = response.url;
                    } else {
                        // Error - recargar página para mostrar mensaje
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error en login:', error);
                    setLoadingState(false);
                    alert('Error de conexión. Por favor, inténtalo de nuevo.');
                });
            }
            
            // Limpiar errores al empezar a escribir
            [emailInput, passwordInput].forEach(input => {
                input.addEventListener('input', function() {
                    clearFieldError(this);
                });
            });
        });
    </script>
</body>
</html>
<body>
    <div class="login-container">
        <!-- Panel de Branding -->
        <div class="login-brand">
            <div class="brand-logo">GRAMMER</div>
            <div class="brand-subtitle">Sistema de Autenticación Corporativo</div>
            <ul class="brand-features">
                <li>Acceso único a todas las aplicaciones</li>
                <li>Seguridad empresarial avanzada</li>
                <li>Control de acceso centralizado</li>
                <li>Experiencia de usuario unificada</li>
            </ul>
        </div>
        
        <!-- Formulario de Login -->
        <div class="login-form-section">
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
            <form class="login-form" method="POST" action="/login" id="loginForm">
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
                            👁️
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="login-button" id="loginBtn">
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
         * 
         * ¿QUÉ HACE?
         * - Validaciones del lado del cliente
         * - Toggle para mostrar/ocultar contraseña
         * - Loading state durante envío del formulario
         * - Validación de dominio @grammer.com en tiempo real
         * 
         * ¿CÓMO FUNCIONA?
         * - Event listeners para interacciones del usuario
         * - Validación de formato de email y dominio
         * - Estados visuales dinámicos (loading, errores)
         * 
         * ¿PARA QUÉ?
         * - Reducir errores antes de enviar al servidor
         * - Feedback inmediato al usuario
         * - Experiencia más fluida y profesional
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
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    togglePassword.textContent = '🙈';
                } else {
                    passwordInput.type = 'password';
                    togglePassword.textContent = '👁️';
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
                e.preventDefault();
                
                // Validaciones antes del envío
                if (!validateForm()) {
                    return;
                }
                
                // Estado de loading
                setLoadingState(true);
                
                // Enviar formulario
                submitForm();
            });
            
            function validateForm() {
                let isValid = true;
                
                // Validar email
                const email = emailInput.value.trim().toLowerCase();
                if (!email) {
                    showFieldError(emailInput, 'El correo es requerido');
                    isValid = false;
                } else if (!isValidEmail(email)) {
                    showFieldError(emailInput, 'Formato de correo inválido');
                    isValid = false;
                } else if (!email.endsWith('@grammer.com')) {
                    showFieldError(emailInput, 'Solo se permiten correos @grammer.com');
                    isValid = false;
                } else {
                    clearFieldError(emailInput);
                }
                
                // Validar contraseña
                const password = passwordInput.value;
                if (!password) {
                    showFieldError(passwordInput, 'La contraseña es requerida');
                    isValid = false;
                } else if (password.length < 3) {
                    showFieldError(passwordInput, 'La contraseña es muy corta');
                    isValid = false;
                } else {
                    clearFieldError(passwordInput);
                }
                
                return isValid;
            }
            
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
            
            function showFieldError(field, message) {
                clearFieldError(field);
                
                field.style.borderColor = '#e53e3e';
                field.style.backgroundColor = '#fed7d7';
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error';
                errorDiv.style.color = '#e53e3e';
                errorDiv.style.fontSize = '0.85rem';
                errorDiv.style.marginTop = '5px';
                errorDiv.textContent = message;
                
                field.parentNode.appendChild(errorDiv);
            }
            
            function clearFieldError(field) {
                field.style.borderColor = '#e1e5e9';
                field.style.backgroundColor = '#fafbfc';
                
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
            
            function submitForm() {
                // Crear FormData para envío
                const formData = new FormData(loginForm);
                
                // Enviar con fetch para mejor control
                fetch('/login', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.redirected) {
                        // Redirección exitosa (login correcto)
                        window.location.href = response.url;
                    } else {
                        // Error - recargar página para mostrar mensaje
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error en login:', error);
                    setLoadingState(false);
                    alert('Error de conexión. Por favor, inténtalo de nuevo.');
                });
            }
            
            // Limpiar errores al empezar a escribir
            [emailInput, passwordInput].forEach(input => {
                input.addEventListener('input', function() {
                    clearFieldError(this);
                });
            });
        });
    </script>
</body>
</html>