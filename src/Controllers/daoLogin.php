<?php
/**
 * daoLogin.php
 * Controller principal para el manejo de autenticación de usuarios
 * 
 * PROPÓSITO:
 * - Mostrar la página de inicio de sesión
 * - Procesar las credenciales de login
 * - Validar que solo usuarios con email @grammer.com puedan acceder
 * - Manejar sesiones de usuario
 * - Procesar logout
 * 
 * RUTAS QUE MANEJA:
 * - GET /login -> Muestra formulario de login
 * - POST /login -> Procesa credenciales
 * - POST /logout -> Cierra sesión del usuario
 * - GET / -> Página principal (dashboard)
 */

// Incluir la conexión a la base de datos usando ruta relativa
// Verificar si ya está cargada la conexión
if (!class_exists('LocalConector')) {
    require_once __DIR__ . '/../../db/db.php';
}

// Iniciar sesiones para manejar el estado de autenticación del usuario
session_start();

// Debugging temporal - remover en producción
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

class daoLogin {
    
    private $conexion;
    
    /**
     * Constructor - Establece la conexión a la base de datos
     * 
     * ¿QUÉ HACE? Inicializa la conexión usando LocalConector
     * ¿CÓMO? Crea una instancia del LocalConector y establece conexión
     * ¿PARA QUÉ? Para poder consultar la tabla de usuarios y validar credenciales
     */
    public function __construct() {
        $conector = new LocalConector();
        $this->conexion = $conector->conectar();
    }
    
    /**
     * Muestra la página de inicio de sesión
     * 
     * ¿QUÉ HACE? Renderiza el formulario de login
     * ¿CÓMO? Incluye la vista correspondiente o genera HTML
     * ¿PARA QUÉ? Para que el usuario pueda ingresar sus credenciales
     */
    public function mostrarLogin() {
        // Si el usuario ya está logueado, redirigir al dashboard
        if ($this->usuarioEstaAutenticado()) {
            header('Location: /dashboard');
            exit;
        }
        
        // Incluir la vista de login
        include __DIR__ . '/../Views/login.php';
    }
    
    /**
     * Procesa las credenciales de login enviadas por POST
     * 
     * ¿QUÉ HACE? Valida email, contraseña y dominio corporativo
     * ¿CÓMO? Consulta la BD, verifica hash de contraseña, valida dominio
     * ¿PARA QUÉ? Para autenticar usuarios y crear sesiones seguras
     */
    public function procesarLogin() {
        // FORZAR RESPUESTA JSON si viene del fetch
        $forceJson = $this->esAjax() || 
                     (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        
        try {
            // Verificar que sea una petición POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->responderError('Método no permitido', 405, $forceJson);
                return;
            }
            
            // Obtener y sanitizar datos del formulario
            $email = $this->sanitizarInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Validaciones básicas
            if (empty($email) || empty($password)) {
                $this->responderError('Email y contraseña son requeridos', 400, $forceJson);
                return;
            }
            
            // Validar que el email sea del dominio @grammer.com
            if (!$this->validarDominioGrammer($email)) {
                $this->responderError('Solo se permiten emails corporativos @grammer.com', 403, $forceJson);
                return;
            }
            
            // Buscar usuario en la base de datos
            $usuario = $this->buscarUsuarioPorEmail($email);
            
            if (!$usuario) {
                $this->responderError('Usuario no encontrado en el sistema', 404, $forceJson);
                return;
            }
            
            // Verificar la contraseña usando password_verify
            if (!password_verify($password, $usuario['password'])) {
                $this->responderError('Contraseña incorrecta', 401, $forceJson);
                return;
            }
            
            // ✅ Login exitoso - Crear sesión
            $this->crearSesionUsuario($usuario);
            
            // Responder según el tipo de petición
            if ($forceJson) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(200);
                
                $response = [
                    'success' => true,
                    'error' => false,
                    'mensaje' => 'Login exitoso',
                    'redirect' => $_SESSION['redirect_after_login'] ?? '/dashboard',
                    'usuario' => [
                        'email' => $usuario['email'],
                        'nombre' => $usuario['name']
                    ]
                ];
                
                echo json_encode($response);
                exit;
            } else {
                // Redirección tradicional
                $redirect = $_SESSION['redirect_after_login'] ?? '/dashboard';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit;
            }
            
        } catch (Exception $e) {
            $this->responderError('Error interno del servidor', 500, $forceJson);
        }
    }
    
    /**
     * Cierra la sesión del usuario
     * 
     * ¿QUÉ HACE? Destruye la sesión y limpia cookies
     * ¿CÓMO? Usa session_destroy() y limpia variables de sesión
     * ¿PARA QUÉ? Para cerrar sesión de manera segura
     */
    public function procesarLogout() {
        // Limpiar todas las variables de sesión
        $_SESSION = array();
        
        // Destruir la cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
        
        // Redirigir a la página de login con mensaje
        header('Location: /login?mensaje=logout_exitoso');
        exit;
    }
    
    /**
     * Muestra el dashboard principal
     * 
     * ¿QUÉ HACE? Renderiza la página principal después del login
     * ¿CÓMO? Verifica autenticación y muestra vista correspondiente
     * ¿PARA QUÉ? Para dar bienvenida al usuario autenticado
     */
    public function mostrarDashboard() {
        // Verificar que el usuario esté autenticado
        if (!$this->usuarioEstaAutenticado()) {
            // Guardar la URL solicitada para redirigir después del login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: /login');
            exit;
        }
        
        // Obtener información del usuario para mostrar en dashboard
        $usuario = $this->obtenerUsuarioActual();
        
        // Incluir la vista del dashboard
        include __DIR__ . '/../Views/dashboard.php';
    }
    
    // ===============================================
    // MÉTODOS AUXILIARES PRIVADOS
    // ===============================================
    
    /**
     * Verifica si un usuario está autenticado
     * 
     * ¿QUÉ HACE? Revisa si existe una sesión válida
     * ¿CÓMO? Verifica variables de sesión
     * ¿PARA QUÉ? Para proteger rutas que requieren autenticación
     */
    private function usuarioEstaAutenticado() {
        return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
    }
    
    /**
     * Valida que un email pertenezca al dominio @grammer.com
     * 
     * ¿QUÉ HACE? Verifica el dominio del email
     * ¿CÓMO? Usa filter_var y strpos para validar formato y dominio
     * ¿PARA QUÉ? Para asegurar que solo empleados de Grammer accedan
     */
    private function validarDominioGrammer($email) {
        // Primero validar que sea un email válido
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Verificar que termine en @grammer.com
        return str_ends_with(strtolower($email), '@grammer.com');
    }
    
    /**
     * Busca un usuario en la base de datos por email
     * 
     * ¿QUÉ HACE? Consulta la tabla users por email
     * ¿CÓMO? Usa prepared statements para evitar SQL injection
     * ¿PARA QUÉ? Para obtener datos del usuario para autenticación
     */
    private function buscarUsuarioPorEmail($email) {
        try {
            $query = "SELECT id, email, password, name FROM users WHERE email = ? LIMIT 1";
            $stmt = $this->conexion->prepare($query);
            
            if (!$stmt) {
                error_log("Error preparing statement: " . $this->conexion->error);
                throw new Exception("Error en la consulta de base de datos");
            }
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_assoc();
            
        } catch (Exception $e) {
            error_log("Error en buscarUsuarioPorEmail: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Crea una sesión segura para el usuario autenticado
     * 
     * ¿QUÉ HACE? Establece variables de sesión con datos del usuario
     * ¿CÓMO? Almacena datos esenciales en $_SESSION
     * ¿PARA QUÉ? Para mantener al usuario logueado entre páginas
     */
    private function crearSesionUsuario($usuario) {
        // Regenerar ID de sesión por seguridad (previene session fixation)
        session_regenerate_id(true);
        
        // Establecer variables de sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_nombre'] = $usuario['name'];
        $_SESSION['login_timestamp'] = time();
        
        // Log de seguridad
        error_log("Login exitoso para usuario: " . $usuario['email']);
    }
    
    /**
     * Obtiene información del usuario actualmente logueado
     * 
     * ¿QUÉ HACE? Retorna datos del usuario desde la sesión
     * ¿CÓMO? Lee variables de $_SESSION
     * ¿PARA QUÉ? Para mostrar información personalizada en vistas
     */
    private function obtenerUsuarioActual() {
        if (!$this->usuarioEstaAutenticado()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['usuario_id'],
            'email' => $_SESSION['usuario_email'],
            'nombre' => $_SESSION['usuario_nombre'],
            'login_timestamp' => $_SESSION['login_timestamp']
        ];
    }
    
    /**
     * Sanitiza input del usuario para prevenir XSS
     * 
     * ¿QUÉ HACE? Limpia datos de entrada del usuario
     * ¿CÓMO? Usa htmlspecialchars y trim
     * ¿PARA QUÉ? Para prevenir ataques XSS y limpiar datos
     */
    private function sanitizarInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Verifica si la petición es AJAX
     */
    private function esAjax() {
        // Múltiples formas de detectar AJAX
        return (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
             strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
            (!empty($_SERVER['CONTENT_TYPE']) && 
             strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
            (!empty($_POST['ajax']) && $_POST['ajax'] == '1') ||
            (isset($_GET['format']) && $_GET['format'] === 'json')
        );
    }
    
    /**
     * Responde con un error y termina la ejecución
     */
    private function responderError($mensaje, $codigo = 400, $forceJson = null) {
        if ($forceJson === null) {
            $forceJson = $this->esAjax();
        }
        
        if ($forceJson) {
            http_response_code($codigo);
            header('Content-Type: application/json; charset=utf-8');
            
            $response = [
                'success' => false,
                'error' => true,
                'mensaje' => $mensaje,
                'codigo' => $codigo
            ];
            
            echo json_encode($response);
            exit;
        }
        
        // Si es petición normal, redirigir con mensaje de error
        $_SESSION['error_login'] = $mensaje;
        header('Location: /login');
        exit;
    }
}

// ===============================================
// LÓGICA DE ROUTING INTERNA
// ===============================================

// El front controller ya determinó que esta es una petición para login
$ruta = $_SERVER['REQUEST_URI'];
$metodo = $_SERVER['REQUEST_METHOD'];
$ruta = strtok($ruta, '?'); // Limpiar query string

// Crear instancia del controller
$loginController = new daoLogin();

// Manejar la ruta específica
if (strpos($ruta, '/src/Controllers/daoLogin.php') !== false) {
    // Es una llamada directa al controlador
    if ($metodo === 'POST') {
        $loginController->procesarLogin();
    } else {
        $loginController->mostrarLogin();
    }
} else {
    // Routing normal
    switch($ruta) {
        case '/login':
            if ($metodo === 'GET') {
                $loginController->mostrarLogin();
            } elseif ($metodo === 'POST') {
                $loginController->procesarLogin();
            }
            break;
            
        case '/logout':
            if ($metodo === 'POST') {
                $loginController->procesarLogout();
            }
            break;
            
        case '/':
        case '/dashboard':
            $loginController->mostrarDashboard();
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => true, 'mensaje' => 'Ruta no encontrada']);
            break;
    }
}
?>