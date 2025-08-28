<?php
/**
 * index.php - Front Controller Principal
 * Archivo: public/index.php
 * 
 * PROPÓSITO:
 * - Reemplaza completamente a Composer y su autoloader
 * - Maneja todas las rutas del sistema de autenticación
 * - Carga controllers dinámicamente usando require_once
 * - Implementa router simple pero potente con PHP puro
 * - Gestiona configuración global de la aplicación
 * 
 * ¿QUÉ HACE?
 * - Actúa como punto de entrada único para todas las peticiones
 * - Inicializa configuración y sesiones
 * - Enruta peticiones a los controllers apropiados (dao*.php)
 * - Maneja errores 404 y otros códigos de estado
 * - Establece headers de seguridad básicos
 * 
 * ¿CÓMO FUNCIONA?
 * - Analiza $_SERVER['REQUEST_URI'] para determinar la ruta
 * - Usa switch/case para mapear rutas a controllers
 * - Incluye archivos PHP necesarios con require_once
 * - Los controllers manejan su lógica propia y sus vistas
 * 
 * ¿PARA QUÉ?
 * - Eliminar dependencia de Composer completamente
 * - Control total sobre el routing y carga de archivos
 * - Simplicidad y rapidez en el despliegue
 * - Compatibilidad con cualquier hosting PHP básico
 */

// =====================================================================
// CONFIGURACIÓN INICIAL
// =====================================================================

// Iniciar buffer de salida para mejor manejo de headers
ob_start();

// Iniciar sesiones globalmente
session_start();

// Configurar zona horaria (ajustar según ubicación)
date_default_timezone_set('America/Mexico_City');

// Configurar manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 0); // En producción cambiar a 0
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Headers de seguridad básicos
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// =====================================================================
// CARGA DE CONFIGURACIÓN Y CLASES BASE
// =====================================================================

// Cargar configuración de la aplicación
require_once __DIR__ . '/../config/app.php';

// Cargar conexión a base de datos
require_once __DIR__ . '/../db/db.php';

// =====================================================================
// FUNCIONES AUXILIARES
// =====================================================================

/**
 * Función para cargar un controller dinámicamente
 * 
 * @param string $controller_name Nombre del controller (ej: 'daoLogin')
 * @return bool True si se cargó correctamente
 */
function loadController($controller_name) {
    $controller_path = __DIR__ . "/../src/Controllers/{$controller_name}.php";
    
    if (file_exists($controller_path)) {
        require_once $controller_path;
        return true;
    }
    
    // Log error si el controller no existe
    error_log("Controller no encontrado: {$controller_path}");
    return false;
}

/**
 * Función para cargar una vista directamente
 * 
 * @param string $view_name Nombre de la vista (ej: 'login')
 * @param array $data Datos a pasar a la vista
 */
function loadView($view_name, $data = []) {
    $view_path = __DIR__ . "/../src/Views/{$view_name}.php";
    
    if (file_exists($view_path)) {
        // Extraer variables para la vista
        extract($data);
        
        // Cargar la vista
        include $view_path;
        return true;
    }
    
    error_log("Vista no encontrada: {$view_path}");
    return false;
}

/**
 * Función para manejar errores HTTP
 * 
 * @param int $code Código de error HTTP
 * @param string $message Mensaje personalizado
 */
function handleError($code = 404, $message = 'Página no encontrada') {
    http_response_code($code);
    
    // En lugar de una vista de error compleja, mostrar mensaje simple
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Error {$code} - Sistema Grammer</title>
        <link rel='stylesheet' href='/assets/css/styles.css'>
    </head>
    <body class='auth-page'>
        <div style='max-width: 500px; width: 100%; background: white; padding: 2rem; border-radius: 12px; text-align: center; box-shadow: var(--shadow-lg);'>
            <h1 style='color: var(--grammer-blue); margin-bottom: 1rem;'>Error {$code}</h1>
            <p style='color: var(--gray-600); margin-bottom: 2rem;'>{$message}</p>
            <a href='/login' class='btn btn-primary'>← Volver al Inicio</a>
        </div>
    </body>
    </html>";
    exit;
}

// =====================================================================
// ROUTER PRINCIPAL
// =====================================================================

// Obtener la ruta de la petición
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Limpiar query string y normalizar ruta
$route = strtok($request_uri, '?');
$route = rtrim($route, '/') ?: '/';

// Log de todas las peticiones (para debugging)
error_log("Request: {$request_method} {$route}");

// =====================================================================
// DEFINICIÓN DE RUTAS
// =====================================================================

switch ($route) {
    
    // ===== RUTAS DE AUTENTICACIÓN =====
    case '/login':
        if (loadController('daoLogin')) {
            // El controller daoLogin ya maneja GET y POST internamente
            exit; // El controller ya procesó todo
        }
        break;
        
    case '/logout':
        if ($request_method === 'POST' && loadController('daoLogin')) {
            // El controller maneja el logout
            exit;
        } else {
            // Si es GET, redirigir a POST logout automáticamente
            echo "<form id='logoutForm' method='POST' action='/logout' style='display:none;'></form>
                  <script>document.getElementById('logoutForm').submit();</script>";
            exit;
        }
        break;
    
    // ===== RUTAS OAUTH 2.0 =====
    case '/oauth/authorize':
        if (loadController('daoOAuth')) {
            exit; // Controller maneja GET y POST
        }
        break;
        
    case '/oauth/token':
        if ($request_method === 'POST' && loadController('daoOAuth')) {
            exit;
        }
        break;
        
    case '/oauth/user':
        if ($request_method === 'GET' && loadController('daoOAuth')) {
            exit;
        }
        break;
    
    // ===== RUTAS DE USUARIO =====
    case '/user/profile':
        if ($request_method === 'GET' && loadController('daoUser')) {
            exit;
        }
        break;
        
    case '/user/update':
        if ($request_method === 'POST' && loadController('daoUser')) {
            exit;
        }
        break;
    
    // ===== RUTAS DE API =====
    case (preg_match('/^\/api\/user\/(\d+)$/', $route, $matches) ? true : false):
        if ($request_method === 'GET' && loadController('daoUser')) {
            // Pasar el ID del usuario extraído de la ruta
            $_GET['user_id'] = $matches[1];
            exit;
        }
        break;
        
    case '/api/users/sync':
        if ($request_method === 'POST' && loadController('daoUser')) {
            exit;
        }
        break;
    
    // ===== RUTAS DE ADMINISTRACIÓN =====
    case '/admin/users':
        if ($request_method === 'GET' && loadController('daoUser')) {
            exit;
        }
        break;
    
    // ===== RUTAS PRINCIPALES =====
    case '/':
    case '/dashboard':
        if (loadController('daoLogin')) {
            // El método mostrarDashboard() maneja la autenticación
            exit;
        }
        break;
    
    // ===== ASSETS ESTÁTICOS =====
    case (preg_match('/^\/assets\/(.+)$/', $route, $matches) ? true : false):
        // Servir archivos estáticos (CSS, JS, imágenes)
        $file_path = __DIR__ . "/assets/" . $matches[1];
        
        if (file_exists($file_path)) {
            // Determinar Content-Type basado en extensión
            $ext = pathinfo($file_path, PATHINFO_EXTENSION);
            $content_types = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'ico' => 'image/x-icon'
            ];
            
            $content_type = $content_types[$ext] ?? 'application/octet-stream';
            header("Content-Type: {$content_type}");
            
            // Cache headers para assets
            header('Cache-Control: public, max-age=31536000'); // 1 año
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            
            readfile($file_path);
            exit;
        }
        break;
    
    // ===== NUEVOS ENDPOINTS DE API =====
    case '/api/session-status':
        require_once __DIR__ . '/../src/Controllers/SessionValidator.php';
        SessionValidator::verificarEstadoSesion();
        break;
        
    case '/api/admin/verify':
        require_once __DIR__ . '/../src/Controllers/SessionValidator.php';
        SessionValidator::verificarPermisosAdmin();
        break;
    
    // ===== RUTAS ESPECIALES =====
    case '/health':
        // Endpoint para verificar que el sistema esté funcionando
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'system' => 'Grammer Auth System',
            'version' => APP_VERSION
        ]);
        exit;
        break;
    
    // ===== RUTA POR DEFECTO (404) =====
    default:
        // Verificar si es una petición para un asset que no existe
        if (strpos($route, '/assets/') === 0) {
            handleError(404, 'Archivo no encontrado');
        }
        
        // Para cualquier otra ruta, mostrar 404
        handleError(404, 'La página solicitada no existe en el sistema de autenticación Grammer');
        break;
}

// Si llegamos aquí, significa que no se encontró la ruta
handleError(404, 'Ruta no válida');

// =====================================================================
// LIMPIEZA FINAL
// =====================================================================

// Enviar buffer de salida
ob_end_flush();
?>