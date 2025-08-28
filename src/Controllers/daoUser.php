<?php
/**
 * daoUser.php
 * Controller para la gestión completa de usuarios del sistema
 * 
 * PROPÓSITO:
 * - Gestionar perfiles de usuario (ver, editar información)
 * - Proporcionar API para sincronización con otros sistemas
 * - Manejar registro de nuevos empleados (si es necesario)
 * - Administrar plantas y departamentos
 * - Generar reportes de usuarios y actividad
 * 
 * RUTAS QUE MANEJA:
 * - GET /user/profile -> Perfil del usuario actual
 * - POST /user/update -> Actualizar información del usuario
 * - GET /api/user/{id} -> API para otros sistemas (obtener info de usuario)
 * - POST /api/users/sync -> API para sincronización masiva
 * - GET /admin/users -> Gestión de usuarios (solo admin)
 * - POST /admin/users/create -> Crear nuevo usuario
 * 
 * INTEGRACIONES:
 * - Proporciona APIs para que otros sistemas sincronicen usuarios
 * - Permite consultas cross-system para reportes unificados
 * - Maneja permisos y roles para diferentes aplicaciones
 */

// Incluir la conexión a la base de datos usando ruta relativa
// Verificar si ya está cargada la conexión
if (!class_exists('LocalConector')) {
    require_once __DIR__ . '/../../db/db.php';
}

// Iniciar sesiones para verificar autenticación
session_start();

class daoUser {
    
    private $conexion;
    
    // Lista de plantas disponibles en Grammer
    private $plantas_disponibles = [
        'Matriz' => 'Oficinas Centrales',
        'Planta Norte' => 'Planta de Producción Norte',  
        'Planta Sur' => 'Planta de Producción Sur',
        'Almacén Central' => 'Centro de Distribución',
        'I+D' => 'Investigación y Desarrollo'
    ];
    
    /**
     * Constructor - Establece la conexión a la base de datos
     * 
     * ¿QUÉ HACE? Inicializa la conexión usando LocalConector
     * ¿CÓMO? Crea una instancia del LocalConector y establece conexión
     * ¿PARA QUÉ? Para gestionar usuarios y proporcionar APIs a otros sistemas
     */
    public function __construct() {
        $conector = new LocalConector();
        $this->conexion = $conector->conectar();
    }
    
    /**
     * Muestra el perfil del usuario actual
     * 
     * ¿QUÉ HACE? Presenta la información del usuario logueado
     * ¿CÓMO? Obtiene datos de sesión y BD, renderiza vista de perfil
     * ¿PARA QUÉ? Para que el usuario vea y edite su información personal
     */
    public function mostrarPerfil() {
        // Verificar autenticación
        if (!$this->usuarioEstaAutenticado()) {
            header('Location: /login');
            exit;
        }
        
        // Obtener información completa del usuario desde BD
        $usuario_id = $_SESSION['usuario_id'];
        $usuario = $this->obtenerUsuarioCompleto($usuario_id);
        
        if (!$usuario) {
            $this->responderError('Usuario no encontrado', 404);
            return;
        }
        
        // Obtener estadísticas del usuario (logins, sistemas conectados, etc.)
        $estadisticas = $this->obtenerEstadisticasUsuario($usuario_id);
        
        // Obtener aplicaciones que ha autorizado
        $aplicaciones_conectadas = $this->obtenerAplicacionesConectadas($usuario_id);
        
        // Datos para la vista
        $datos_vista = [
            'usuario' => $usuario,
            'plantas_disponibles' => $this->plantas_disponibles,
            'estadisticas' => $estadisticas,
            'aplicaciones' => $aplicaciones_conectadas,
            'mensaje_exito' => $_SESSION['mensaje_exito'] ?? null,
            'mensaje_error' => $_SESSION['mensaje_error'] ?? null
        ];
        
        // Limpiar mensajes después de mostrarlos
        unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);
        
        // Incluir la vista del perfil
        include __DIR__ . '/../Views/user_profile.php';
    }
    
    /**
     * Actualiza la información del usuario
     * 
     * ¿QUÉ HACE? Procesa cambios en el perfil del usuario
     * ¿CÓMO? Valida datos, actualiza BD, notifica a sistemas conectados
     * ¿PARA QUÉ? Para mantener información actualizada en todos los sistemas
     */
    public function actualizarPerfil() {
        // Solo aceptar peticiones POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderError('Método no permitido', 405);
            return;
        }
        
        // Verificar autenticación
        if (!$this->usuarioEstaAutenticado()) {
            header('Location: /login');
            exit;
        }
        
        $usuario_id = $_SESSION['usuario_id'];
        
        // Obtener y validar datos del formulario
        $nombre = $this->sanitizarInput($_POST['name'] ?? '');
        $planta = $this->sanitizarInput($_POST['planta'] ?? '');
        $password_actual = $_POST['current_password'] ?? '';
        $password_nuevo = $_POST['new_password'] ?? '';
        $password_confirmar = $_POST['confirm_password'] ?? '';
        
        // Validaciones básicas
        if (empty($nombre)) {
            $_SESSION['mensaje_error'] = 'El nombre es requerido';
            header('Location: /user/profile');
            exit;
        }
        
        // Validar planta
        if (!array_key_exists($planta, $this->plantas_disponibles)) {
            $_SESSION['mensaje_error'] = 'Planta seleccionada no válida';
            header('Location: /user/profile');
            exit;
        }
        
        // Obtener usuario actual para validaciones
        $usuario_actual = $this->obtenerUsuarioCompleto($usuario_id);
        
        // Si está cambiando contraseña, validar
        if (!empty($password_nuevo)) {
            if (empty($password_actual)) {
                $_SESSION['mensaje_error'] = 'Debe proporcionar su contraseña actual';
                header('Location: /user/profile');
                exit;
            }
            
            // Verificar contraseña actual
            if (!password_verify($password_actual, $usuario_actual['password'])) {
                $_SESSION['mensaje_error'] = 'Contraseña actual incorrecta';
                header('Location: /user/profile');
                exit;
            }
            
            // Validar nueva contraseña
            if (strlen($password_nuevo) < 6) {
                $_SESSION['mensaje_error'] = 'La nueva contraseña debe tener al menos 6 caracteres';
                header('Location: /user/profile');
                exit;
            }
            
            if ($password_nuevo !== $password_confirmar) {
                $_SESSION['mensaje_error'] = 'Las contraseñas no coinciden';
                header('Location: /user/profile');
                exit;
            }
        }
        
        // Actualizar información en la base de datos
        $actualizado = $this->actualizarUsuarioEnBD($usuario_id, [
            'name' => $nombre,
            'planta' => $planta,
            'password' => !empty($password_nuevo) ? password_hash($password_nuevo, PASSWORD_DEFAULT) : null
        ]);
        
        if (!$actualizado) {
            $_SESSION['mensaje_error'] = 'Error al actualizar la información';
            header('Location: /user/profile');
            exit;
        }
        
        // Actualizar variables de sesión
        $_SESSION['usuario_nombre'] = $nombre;
        
        // Notificar a sistemas conectados sobre los cambios (opcional)
        $this->notificarCambiosASistemasConectados($usuario_id, [
            'name' => $nombre,
            'planta' => $planta
        ]);
        
        // Log de seguridad
        error_log("Perfil actualizado para usuario ID: $usuario_id");
        
        $_SESSION['mensaje_exito'] = 'Perfil actualizado correctamente';
        header('Location: /user/profile');
        exit;
    }
    
    /**
     * API para obtener información de usuario (para otros sistemas)
     * 
     * ¿QUÉ HACE? Proporciona datos de usuario en formato JSON
     * ¿CÓMO? Valida token OAuth, consulta BD, retorna JSON
     * ¿PARA QUÉ? Para que otros sistemas puedan sincronizar información de usuarios
     */
    public function obtenerUsuarioAPI($user_id) {
        // Verificar que la petición incluya token de autorización válido
        if (!$this->validarTokenAPI()) {
            $this->responderErrorAPI('Token de autorización requerido', 401);
            return;
        }
        
        // Validar ID de usuario
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->responderErrorAPI('ID de usuario inválido', 400);
            return;
        }
        
        // Obtener información del usuario
        $usuario = $this->obtenerUsuarioCompleto($user_id);
        
        if (!$usuario) {
            $this->responderErrorAPI('Usuario no encontrado', 404);
            return;
        }
        
        // Preparar respuesta (sin información sensible)
        $response = [
            'id' => $usuario['id'],
            'email' => $usuario['email'],
            'name' => $usuario['name'],
            'planta' => $usuario['planta'],
            'created_at' => $usuario['created_at'],
            'updated_at' => $usuario['updated_at'],
            'active' => true
        ];
        
        // Log de auditoría
        error_log("API: Información de usuario $user_id consultada");
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    /**
     * API para sincronización masiva de usuarios (para otros sistemas)
     * 
     * ¿QUÉ HACE? Permite a otros sistemas obtener info de múltiples usuarios
     * ¿CÓMO? Recibe lista de IDs, retorna array con información de todos
     * ¿PARA QUÉ? Para sincronizaciones eficientes en lotes
     */
    public function sincronizarUsuariosAPI() {
        // Solo aceptar peticiones POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderErrorAPI('Método no permitido', 405);
            return;
        }
        
        // Verificar token de autorización
        if (!$this->validarTokenAPI()) {
            $this->responderErrorAPI('Token de autorización requerido', 401);
            return;
        }
        
        // Obtener datos JSON de la petición
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['user_ids'])) {
            $this->responderErrorAPI('Se requiere array de user_ids', 400);
            return;
        }
        
        $user_ids = $input['user_ids'];
        
        // Validar que sea un array y no tenga más de 100 elementos
        if (!is_array($user_ids) || count($user_ids) > 100) {
            $this->responderErrorAPI('user_ids debe ser array con máximo 100 elementos', 400);
            return;
        }
        
        // Obtener información de todos los usuarios solicitados
        $usuarios = $this->obtenerMultiplesUsuarios($user_ids);
        
        // Preparar respuesta
        $response = [
            'users' => $usuarios,
            'total' => count($usuarios),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Log de auditoría  
        error_log("API: Sincronización masiva de " . count($user_ids) . " usuarios solicitada");
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    /**
     * Panel de administración de usuarios (solo para administradores)
     * 
     * ¿QUÉ HACE? Muestra lista completa de usuarios para gestión
     * ¿CÓMO? Verifica permisos admin, consulta BD, muestra vista administrativa
     * ¿PARA QUÉ? Para que administradores gestionen usuarios del sistema
     */
    public function mostrarAdminUsuarios() {
        // Verificar autenticación
        if (!$this->usuarioEstaAutenticado()) {
            header('Location: /login');
            exit;
        }
        
        // Verificar permisos de administrador
        if (!$this->usuarioEsAdmin()) {
            $this->responderError('Acceso denegado. Se requieren permisos de administrador.', 403);
            return;
        }
        
        // Obtener parámetros de filtrado y paginación
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per_page = 20;
        $filtro_planta = $_GET['planta'] ?? '';
        $buscar = $_GET['search'] ?? '';
        
        // Obtener lista de usuarios con filtros
        $usuarios = $this->obtenerUsuariosPaginados($page, $per_page, $filtro_planta, $buscar);
        $total_usuarios = $this->contarUsuarios($filtro_planta, $buscar);
        $total_paginas = ceil($total_usuarios / $per_page);
        
        // Obtener estadísticas generales
        $estadisticas_generales = $this->obtenerEstadisticasGenerales();
        
        // Datos para la vista
        $datos_vista = [
            'usuarios' => $usuarios,
            'page' => $page,
            'total_paginas' => $total_paginas,
            'total_usuarios' => $total_usuarios,
            'plantas_disponibles' => $this->plantas_disponibles,
            'filtro_planta' => $filtro_planta,
            'buscar' => $buscar,
            'estadisticas' => $estadisticas_generales,
            'mensaje_exito' => $_SESSION['mensaje_exito'] ?? null,
            'mensaje_error' => $_SESSION['mensaje_error'] ?? null
        ];
        
        // Limpiar mensajes
        unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);
        
        // Incluir vista de administración
        include __DIR__ . '/../Views/admin_users.php';
    }
    
    // ===============================================
    // MÉTODOS AUXILIARES PRIVADOS
    // ===============================================
    
    /**
     * Verifica si un usuario está autenticado
     */
    private function usuarioEstaAutenticado() {
        return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
    }
    
    /**
     * Verifica si el usuario actual tiene permisos de administrador
     * 
     * ¿QUÉ HACE? Determina si el usuario puede acceder a funciones administrativas
     * ¿CÓMO? Verifica email o role específico en BD
     * ¿PARA QUÉ? Proteger endpoints administrativos
     */
    private function usuarioEsAdmin() {
        // Por ahora, solo el admin@grammer.com es administrador
        // En producción podrías tener una tabla de roles más sofisticada
        $email_usuario = $_SESSION['usuario_email'] ?? '';
        return $email_usuario === 'admin@grammer.com';
    }
    
    /**
     * Obtiene información completa de un usuario desde la BD
     */
    private function obtenerUsuarioCompleto($user_id) {
        $query = "SELECT id, email, password, name, planta, created_at, updated_at FROM users WHERE id = ? LIMIT 1";
        $stmt = $this->conexion->prepare($query);
        
        if (!$stmt) {
            error_log("Error preparing user query: " . $this->conexion->error);
            return false;
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Obtiene información de múltiples usuarios (para API de sincronización)
     */
    private function obtenerMultiplesUsuarios($user_ids) {
        if (empty($user_ids)) {
            return [];
        }
        
        // Crear placeholders para prepared statement
        $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
        $query = "SELECT id, email, name, planta, created_at, updated_at FROM users WHERE id IN ($placeholders)";
        
        $stmt = $this->conexion->prepare($query);
        
        if (!$stmt) {
            error_log("Error preparing multiple users query: " . $this->conexion->error);
            return [];
        }
        
        // Bind parameters dinámicamente
        $types = str_repeat('i', count($user_ids));
        $stmt->bind_param($types, ...$user_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $usuarios = [];
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
        
        return $usuarios;
    }
    
    /**
     * Actualiza información de usuario en la base de datos
     */
    private function actualizarUsuarioEnBD($user_id, $datos) {
        $campos = [];
        $valores = [];
        $types = '';
        
        foreach ($datos as $campo => $valor) {
            if ($valor !== null) {
                $campos[] = "$campo = ?";
                $valores[] = $valor;
                $types .= 's';
            }
        }
        
        if (empty($campos)) {
            return true; // No hay nada que actualizar
        }
        
        $query = "UPDATE users SET " . implode(', ', $campos) . " WHERE id = ?";
        $valores[] = $user_id;
        $types .= 'i';
        
        $stmt = $this->conexion->prepare($query);
        
        if (!$stmt) {
            error_log("Error preparing update query: " . $this->conexion->error);
            return false;
        }
        
        $stmt->bind_param($types, ...$valores);
        return $stmt->execute();
    }
    
    /**
     * Obtiene estadísticas del usuario (logins, aplicaciones conectadas, etc.)
     */
    private function obtenerEstadisticasUsuario($user_id) {
        // Consultar tokens activos para ver aplicaciones conectadas
        $query_tokens = "SELECT COUNT(*) as tokens_activos FROM oauth_access_tokens WHERE user_id = ? AND expires_at > NOW()";
        $stmt = $this->conexion->prepare($query_tokens);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tokens = $stmt->get_result()->fetch_assoc();
        
        return [
            'aplicaciones_conectadas' => $tokens['tokens_activos'] ?? 0,
            'ultimo_login' => $_SESSION['login_timestamp'] ?? null,
            'cuenta_activa' => true
        ];
    }
    
    /**
     * Obtiene aplicaciones que el usuario ha autorizado
     */
    private function obtenerAplicacionesConectadas($user_id) {
        $query = "SELECT DISTINCT c.name, c.id, MAX(t.created_at) as ultimo_acceso 
                  FROM oauth_access_tokens t 
                  JOIN oauth_clients c ON t.client_id = c.id 
                  WHERE t.user_id = ? AND t.expires_at > NOW() 
                  GROUP BY c.id, c.name";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $aplicaciones = [];
        while ($row = $result->fetch_assoc()) {
            $aplicaciones[] = $row;
        }
        
        return $aplicaciones;
    }
    
    /**
     * Valida token de API para peticiones externas
     * 
     * ¿QUÉ HACE? Verifica que la petición tenga autorización válida
     * ¿CÓMO? Busca Bearer token y lo valida contra oauth_access_tokens
     * ¿PARA QUÉ? Para proteger APIs que consumen otros sistemas
     */
    private function validarTokenAPI() {
        $headers = apache_request_headers();
        
        if (!isset($headers['Authorization'])) {
            return false;
        }
        
        $auth_header = $headers['Authorization'];
        if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return false;
        }
        
        $token = $matches[1];
        
        // Validar token en la base de datos
        $query = "SELECT id FROM oauth_access_tokens WHERE id = ? AND expires_at > NOW() LIMIT 1";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    /**
     * Notifica cambios a sistemas conectados (webhook opcional)
     * 
     * ¿QUÉ HACE? Informa a otros sistemas cuando cambia info de usuario
     * ¿CÓMO? Envía peticiones HTTP a endpoints de notificación registrados
     * ¿PARA QUÉ? Para mantener sincronizados todos los sistemas automáticamente
     */
    private function notificarCambiosASistemasConectados($user_id, $cambios) {
        // Esta función podrías implementarla para notificar automáticamente
        // a tus otros sistemas cuando cambien datos importantes
        
        // Por ejemplo, si tienes webhooks registrados:
        $webhooks = [
            // 'https://pedidos.grammer.com/api/webhook/user-updated',
            // 'https://crm.grammer.com/api/webhook/user-updated'
        ];
        
        $payload = [
            'user_id' => $user_id,
            'changes' => $cambios,
            'timestamp' => time()
        ];
        
        foreach ($webhooks as $webhook_url) {
            // Enviar notificación asíncrona (en producción usarías cola de trabajos)
            $this->enviarNotificacionAsincrona($webhook_url, $payload);
        }
    }
    
    /**
     * Envía notificación asíncrona a webhook
     */
    private function enviarNotificacionAsincrona($url, $payload) {
        // Implementación simplificada - en producción usarías cURL asíncrono o cola
        // exec("curl -X POST -H 'Content-Type: application/json' -d '" . json_encode($payload) . "' $url > /dev/null 2>&1 &");
    }
    
    /**
     * Sanitiza input del usuario
     */
    private function sanitizarInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Obtiene usuarios con paginación y filtros (para administración)
     */
    private function obtenerUsuariosPaginados($page, $per_page, $filtro_planta = '', $buscar = '') {
        $offset = ($page - 1) * $per_page;
        
        // Construir WHERE dinámicamente
        $where_clauses = [];
        $params = [];
        $types = '';
        
        if (!empty($filtro_planta)) {
            $where_clauses[] = "planta = ?";
            $params[] = $filtro_planta;
            $types .= 's';
        }
        
        if (!empty($buscar)) {
            $where_clauses[] = "(name LIKE ? OR email LIKE ?)";
            $params[] = "%$buscar%";
            $params[] = "%$buscar%";
            $types .= 'ss';
        }
        
        $where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);
        
        $query = "SELECT id, email, name, planta, created_at, updated_at 
                  FROM users $where_sql 
                  ORDER BY created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $params[] = $per_page;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $usuarios = [];
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
        
        return $usuarios;
    }
    
    /**
     * Cuenta total de usuarios con filtros aplicados
     */
    private function contarUsuarios($filtro_planta = '', $buscar = '') {
        $where_clauses = [];
        $params = [];
        $types = '';
        
        if (!empty($filtro_planta)) {
            $where_clauses[] = "planta = ?";
            $params[] = $filtro_planta;
            $types .= 's';
        }
        
        if (!empty($buscar)) {
            $where_clauses[] = "(name LIKE ? OR email LIKE ?)";
            $params[] = "%$buscar%";
            $params[] = "%$buscar%";
            $types .= 'ss';
        }
        
        $where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);
        $query = "SELECT COUNT(*) as total FROM users $where_sql";
        
        if (empty($params)) {
            $result = $this->conexion->query($query);
        } else {
            $stmt = $this->conexion->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }
    
    /**
     * Obtiene estadísticas generales del sistema
     */
    private function obtenerEstadisticasGenerales() {
        // Total de usuarios
        $result = $this->conexion->query("SELECT COUNT(*) as total_usuarios FROM users");
        $total_usuarios = $result->fetch_assoc()['total_usuarios'];
        
        // Usuarios por planta
        $result = $this->conexion->query("SELECT planta, COUNT(*) as cantidad FROM users GROUP BY planta");
        $usuarios_por_planta = [];
        while ($row = $result->fetch_assoc()) {
            $usuarios_por_planta[$row['planta']] = $row['cantidad'];
        }
        
        // Aplicaciones OAuth registradas
        $result = $this->conexion->query("SELECT COUNT(*) as total_apps FROM oauth_clients");
        $total_apps = $result->fetch_assoc()['total_apps'];
        
        // Tokens activos
        $result = $this->conexion->query("SELECT COUNT(*) as tokens_activos FROM oauth_access_tokens WHERE expires_at > NOW()");
        $tokens_activos = $result->fetch_assoc()['tokens_activos'];
        
        return [
            'total_usuarios' => $total_usuarios,
            'usuarios_por_planta' => $usuarios_por_planta,
            'total_aplicaciones' => $total_apps,
            'tokens_activos' => $tokens_activos
        ];
    }
    
    /**
     * Responde con error HTML
     */
    private function responderError($mensaje, $codigo = 400) {
        http_response_code($codigo);
        echo "<h1>Error</h1><p>$mensaje</p>";
        exit;
    }
    
    /**
     * Responde con error de API en formato JSON
     */
    private function responderErrorAPI($mensaje, $codigo = 400) {
        http_response_code($codigo);
        header('Content-Type: application/json');
        echo json_encode(['error' => true, 'mensaje' => $mensaje]);
        exit;
    }
}

$ruta = strtok($_SERVER['REQUEST_URI'], '?');
$metodo = $_SERVER['REQUEST_METHOD'];

$userController = new daoUser();

switch(true) {
    case $ruta === '/user/profile':
        if ($metodo === 'GET') {
            $userController->mostrarPerfil();
        }
        break;
        
    case $ruta === '/user/update':
        if ($metodo === 'POST') {
            $userController->actualizarPerfil();
        }
        break;
        
    case preg_match('/^\/api\/user\/(\d+)$/', $ruta, $matches):
        if ($metodo === 'GET') {
            $userController->obtenerUsuarioAPI($matches[1]);
        }
        break;
        
    case $ruta === '/api/users/sync':
        if ($metodo === 'POST') {
            $userController->sincronizarUsuariosAPI();
        }
        break;
        
    case $ruta === '/admin/users':
        if ($metodo === 'GET') {
            $userController->mostrarAdminUsuarios();
        }
        break;
        
    default:
        // Ruta no encontrada
        http_response_code(404);
        echo "Página no encontrada";
        break;
}
?>