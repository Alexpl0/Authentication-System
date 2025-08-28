<?php
/**
 * app.php - Configuración General del Sistema
 * Archivo: config/app.php
 * 
 * PROPÓSITO:
 * - Reemplaza variables de entorno (.env) con configuración PHP pura
 * - Define constantes globales de la aplicación
 * - Configura parámetros de OAuth 2.0 y seguridad
 * - Establece configuración de base de datos (complementa db.php)
 * 
 * ¿QUÉ HACE?
 * - Define versión de la app y metadatos
 * - Configura URLs base y rutas importantes
 * - Establece parámetros de tokens OAuth (duración, etc.)
 * - Define configuración de seguridad y sesiones
 * - Lista clientes OAuth autorizados
 * 
 * ¿CÓMO FUNCIONA?
 * - Se carga desde index.php al inicio de cada petición
 * - Define constantes con define() para uso global
 * - Configura arrays asociativos para configuraciones complejas
 * - No usa variables de entorno externas
 * 
 * ¿PARA QUÉ?
 * - Centralizar toda la configuración en un lugar
 * - Eliminar dependencia de archivos .env
 * - Configuración fácil de modificar y mantener
 * - Valores por defecto seguros para producción
 */

// =====================================================================
// INFORMACIÓN DE LA APLICACIÓN
// =====================================================================

define('APP_NAME', 'Sistema de Autenticación Grammer');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production'); // development, testing, production
define('APP_DEBUG', false); // Cambiar a true solo en desarrollo
define('APP_CHARSET', 'UTF-8');
define('APP_LOCALE', 'es');

// =====================================================================
// CONFIGURACIÓN DE URLs Y DOMINIOS
// =====================================================================

// URL base de la aplicación (ajustar según tu dominio)
define('APP_URL', 'https://auth.grammer.com');

// En desarrollo local, usar:
// define('APP_URL', 'http://localhost/grammer-auth/public');

// URLs importantes
define('LOGIN_URL', APP_URL . '/login');
define('DASHBOARD_URL', APP_URL . '/dashboard');
define('OAUTH_AUTHORIZE_URL', APP_URL . '/oauth/authorize');
define('OAUTH_TOKEN_URL', APP_URL . '/oauth/token');
define('OAUTH_USER_URL', APP_URL . '/oauth/user');

// Dominio corporativo permitido
define('GRAMMER_EMAIL_DOMAIN', '@grammer.com');

// =====================================================================
// CONFIGURACIÓN OAUTH 2.0 Y TOKENS
// =====================================================================

// Duraciones de tokens (en segundos)
define('AUTHORIZATION_CODE_LIFETIME', 600);    // 10 minutos
define('ACCESS_TOKEN_LIFETIME', 3600);         // 1 hora
define('REFRESH_TOKEN_LIFETIME', 604800);      // 7 días

// Configuración de tokens
define('TOKEN_ALGORITHM', 'HS256');
define('TOKEN_ISSUER', 'grammer-auth-system');

// Longitud de tokens (en bytes - se convertirá a hex)
define('TOKEN_LENGTH', 32); // 64 caracteres hex

// =====================================================================
// CONFIGURACIÓN DE SEGURIDAD
// =====================================================================

// Configuración de sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', APP_ENV === 'production' ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_lifetime', 0); // Hasta que se cierre el navegador

// Configuración de cookies
define('COOKIE_DOMAIN', '.grammer.com'); // Para subdominios
define('COOKIE_SECURE', APP_ENV === 'production');
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Strict');

// Headers de seguridad adicionales
$SECURITY_HEADERS = [
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Content-Security-Policy' => "default-src 'self'; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; img-src 'self' data:; script-src 'self' 'unsafe-inline'",
    'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()'
];

// Aplicar headers de seguridad en producción
if (APP_ENV === 'production') {
    foreach ($SECURITY_HEADERS as $header => $value) {
        header("{$header}: {$value}");
    }
}

// =====================================================================
// CLIENTES OAUTH PREDEFINIDOS
// =====================================================================

/**
 * Lista de aplicaciones autorizadas para usar OAuth
 * En un sistema más avanzado, esto estaría en la base de datos
 * 
 * Estructura:
 * 'client_id' => [
 *     'name' => 'Nombre mostrado al usuario',
 *     'secret' => 'secret_key_para_validacion',
 *     'redirect_uris' => ['url1', 'url2'], // URLs autorizadas
 *     'scopes' => ['scope1', 'scope2'],    // Permisos disponibles
 *     'trusted' => true/false              // Si es aplicación de confianza
 * ]
 */
$OAUTH_CLIENTS = [
    'intranet_client' => [
        'name' => 'Intranet Grammer',
        'secret' => 'intranet_secret_key_grammer_2025',
        'redirect_uris' => [
            'https://intranet.grammer.com/oauth/callback',
            'https://intranet.grammer.com/auth/callback'
        ],
        'scopes' => ['read_user', 'read_email'],
        'trusted' => true
    ],
    
    'crm_client' => [
        'name' => 'CRM Grammer',
        'secret' => 'crm_secret_key_grammer_2025',
        'redirect_uris' => [
            'https://crm.grammer.com/oauth/callback'
        ],
        'scopes' => ['read_user', 'read_email'],
        'trusted' => true
    ],
    
    'pedidos_client' => [
        'name' => 'Sistema de Pedidos',
        'secret' => 'pedidos_secret_key_grammer_2025',
        'redirect_uris' => [
            'https://pedidos.grammer.com/oauth/callback'
        ],
        'scopes' => ['read_user', 'read_email'],
        'trusted' => true
    ]
];

// Hacer accesible globalmente
$GLOBALS['OAUTH_CLIENTS'] = $OAUTH_CLIENTS;

// =====================================================================
// SCOPES OAUTH DISPONIBLES
// =====================================================================

$OAUTH_SCOPES = [
    'read_user' => [
        'name' => 'Ver información básica del perfil',
        'description' => 'Nombre completo, planta de trabajo y fecha de registro',
        'icon' => '👤'
    ],
    'read_email' => [
        'name' => 'Ver dirección de correo electrónico', 
        'description' => 'Tu correo corporativo @grammer.com',
        'icon' => '📧'
    ]
    // Se pueden agregar más scopes en el futuro:
    // 'write_profile' => [...],
    // 'read_calendar' => [...],
    // etc.
];

$GLOBALS['OAUTH_SCOPES'] = $OAUTH_SCOPES;

// =====================================================================
// CONFIGURACIÓN DE BASE DE DATOS (COMPLEMENTO A db.php)
// =====================================================================

// Estas constantes complementan la clase LocalConector
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');
define('DB_PREFIX', ''); // Prefijo para tablas (si se necesita)

// Configuración de conexión
define('DB_TIMEOUT', 30);
define('DB_PERSISTENT', false);

// =====================================================================
// CONFIGURACIÓN DE LOGGING
// =====================================================================

define('LOG_LEVEL', APP_DEBUG ? 'debug' : 'error');
define('LOG_PATH', __DIR__ . '/../logs/');
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB por archivo

// Crear directorio de logs si no existe
if (!file_exists(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// =====================================================================
// CONFIGURACIÓN DE EMAIL (PARA FUTURAS NOTIFICACIONES)
// =====================================================================

define('MAIL_FROM_ADDRESS', 'noreply@grammer.com');
define('MAIL_FROM_NAME', 'Sistema de Autenticación Grammer');
define('MAIL_ADMIN', 'it@grammer.com');

// =====================================================================
// PLANTAS DISPONIBLES EN GRAMMER
// =====================================================================

$PLANTAS_GRAMMER = [
    'Matriz' => 'Oficinas Centrales',
    'Planta Norte' => 'Planta de Producción Norte',
    'Planta Sur' => 'Planta de Producción Sur', 
    'Almacén Central' => 'Centro de Distribución',
    'I+D' => 'Investigación y Desarrollo',
    'Ventas' => 'Departamento de Ventas',
    'Administración' => 'Área Administrativa'
];

$GLOBALS['PLANTAS_GRAMMER'] = $PLANTAS_GRAMMER;

// =====================================================================
// FUNCIONES AUXILIARES GLOBALES
// =====================================================================

/**
 * Función para obtener configuración de cliente OAuth
 * 
 * @param string $client_id ID del cliente
 * @return array|null Configuración del cliente o null si no existe
 */
function getOAuthClient($client_id) {
    return $GLOBALS['OAUTH_CLIENTS'][$client_id] ?? null;
}

/**
 * Función para validar scope OAuth
 * 
 * @param string $scope Scope a validar
 * @return bool True si el scope es válido
 */
function isValidScope($scope) {
    return isset($GLOBALS['OAUTH_SCOPES'][$scope]);
}

/**
 * Función para obtener URL completa
 * 
 * @param string $path Ruta relativa
 * @return string URL completa
 */
function url($path = '') {
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Función para verificar si estamos en desarrollo
 * 
 * @return bool True si estamos en desarrollo
 */
function isDevelopment() {
    return APP_ENV === 'development';
}

/**
 * Función para log personalizado
 * 
 * @param string $message Mensaje a loguear
 * @param string $level Nivel de log (error, info, debug)
 */
function customLog($message, $level = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $log_line = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    $log_file = LOG_PATH . "app_" . date('Y-m-d') . ".log";
    file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
}

// =====================================================================
// CONFIGURACIÓN FINAL
// =====================================================================

// Configurar timezone
date_default_timezone_set('America/Mexico_City');

// Configurar locale para fechas en español
setlocale(LC_TIME, 'es_MX.UTF-8', 'es_ES.UTF-8', 'spanish');

// Log de inicio de aplicación
if (APP_DEBUG) {
    customLog("Aplicación iniciada - Versión: " . APP_VERSION, 'info');
}

// Variables globales útiles
$GLOBALS['APP_START_TIME'] = microtime(true);
$GLOBALS['APP_MEMORY_START'] = memory_get_usage();

// ¡Configuración completada!
?>