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
            filter: brightness(0) invert(1);
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
            <img src="../../public/images/imagen.png" alt="Grammer Logo" class="brand-logo-image">
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
            <form class="form-grammer" method="POST" action="https://grammermx.com/Jesus/auth/src/Controllers/daoLogin.php" id="loginForm">
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
    
    <!-- JavaScript Modularizado -->
    <script src="../../public/js/config.js"></script>
    <script src="../../public/js/login.js"></script>
</body>
</html>
