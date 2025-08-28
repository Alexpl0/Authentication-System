<?php
/**
 * daoOAuth.php  
 * Controller para manejar el protocolo OAuth 2.0 completo
 * 
 * PROPÓSITO:
 * - Implementar el servidor OAuth 2.0 que actúa como Identity Provider
 * - Manejar el flujo Authorization Code Grant (el más seguro)
 * - Permitir que aplicaciones externas (Intranet, CRM) autentiquen usuarios
 * - Generar y validar tokens de acceso de manera segura
 * - Controlar permisos y scopes de las aplicaciones cliente
 * 
 * RUTAS QUE MANEJA:
 * - GET /oauth/authorize -> Página de consentimiento OAuth  
 * - POST /oauth/authorize -> Procesa el consentimiento del usuario
 * - POST /oauth/token -> Intercambia authorization_code por access_token
 * - GET /oauth/user -> Retorna info del usuario con token válido
 * 
 * FLUJO OAuth 2.0 Authorization Code Grant:
 * 1. App cliente redirige usuario a /oauth/authorize
 * 2. Usuario se autentica (manejado por daoLogin.php)
 * 3. Usuario otorga consentimiento
 * 4. Sistema genera authorization_code y redirige de vuelta
 * 5. App intercambia code por access_token en /oauth/token
 * 6. App usa access_token para obtener datos en /oauth/user
 */

// Incluir la conexión a la base de datos usando ruta relativa
// Verificar si ya está cargada la conexión
if (!class_exists('LocalConector')) {
    require_once __DIR__ . '/../../db/db.php';
}

// Iniciar sesiones para verificar que el usuario esté autenticado
session_start();

class daoOAuth {
    
    private $conexion;
    
    // Configuración OAuth
    private $authorization_code_lifetime = 600; // 10 minutos para codes
    private $access_token_lifetime = 3600; // 1 hora para access tokens  
    private $refresh_token_lifetime = 604800; // 7 días para refresh tokens
    
    /**
     * Constructor - Establece la conexión a la base de datos
     * 
     * ¿QUÉ HACE? Inicializa la conexión usando LocalConector
     * ¿CÓMO? Crea una instancia del LocalConector y establece conexión
     * ¿PARA QUÉ? Para consultar clientes OAuth, generar tokens y validar permisos
     */
    public function __construct() {
        $conector = new LocalConector();
        $this->conexion = $conector->conectar();
    }
    
    /**
     * Muestra la página de autorización OAuth (paso 1 del flujo)
     * 
     * ¿QUÉ HACE? Presenta al usuario la solicitud de consentimiento de una app
     * ¿CÓMO? Valida parámetros OAuth, verifica cliente, muestra página de consentimiento  
     * ¿PARA QUÉ? Para que el usuario autorice a la app acceder a su información
     * 
     * Parámetros esperados:
     * - client_id: ID de la aplicación cliente (ej: "intranet_client")
     * - redirect_uri: URL donde enviar de vuelta al usuario
     * - response_type: Debe ser "code" (Authorization Code Grant)
     * - scope: Permisos solicitados (ej: "read_user")
     * - state: Valor anti-CSRF proporcionado por el cliente
     */
    public function mostrarAutorizacion() {
        // Verificar que el usuario esté autenticado
        if (!$this->usuarioEstaAutenticado()) {
            // Guardar los parámetros OAuth para después del login
            $_SESSION['oauth_params'] = $_GET;
            $_SESSION['redirect_after_login'] = '/oauth/authorize?' . $_SERVER['QUERY_STRING'];
            header('Location: /login');
            exit;
        }
        
        // Obtener y validar parámetros OAuth requeridos
        $client_id = $_GET['client_id'] ?? '';
        $redirect_uri = $_GET['redirect_uri'] ?? '';
        $response_type = $_GET['response_type'] ?? '';
        $scope = $_GET['scope'] ?? 'read_user';
        $state = $_GET['state'] ?? '';
        
        // Validar parámetros obligatorios
        if (empty($client_id) || empty($redirect_uri) || empty($response_type)) {
            $this->responderError('Parámetros OAuth incompletos. Se requiere client_id, redirect_uri y response_type.');
            return;
        }
        
        // Solo soportamos Authorization Code Grant por seguridad
        if ($response_type !== 'code') {
            $this->responderError('response_type no soportado. Use "code" para Authorization Code Grant.');
            return;
        }
        
        // Validar que el cliente esté registrado en nuestra base de datos
        $cliente = $this->obtenerClientePorId($client_id);
        if (!$cliente) {
            $this->responderError('Cliente OAuth no válido o no registrado.');
            return;
        }
        
        // Validar que la redirect_uri coincida con la registrada (seguridad crítica)
        if (!$this->validarRedirectUri($redirect_uri, $cliente['redirect_uri'])) {
            $this->responderError('redirect_uri no autorizada para este cliente.');
            return;
        }
        
        // Validar scopes solicitados
        $scopes_validos = $this->validarScopes($scope);
        if (empty($scopes_validos)) {
            $this->responderError('Scopes solicitados no válidos.');
            return;
        }
        
        // Obtener información del usuario actual
        $usuario = $this->obtenerUsuarioActual();
        
        // Preparar datos para la vista de consentimiento
        $datos_consentimiento = [
            'cliente' => $cliente,
            'usuario' => $usuario,
            'scopes' => $scopes_validos,
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'scope' => $scope,
            'state' => $state
        ];
        
        // Mostrar página de consentimiento
        include __DIR__ . '/../Views/oauth_consent.php';
    }
    
    /**
     * Procesa el consentimiento del usuario (paso 2 del flujo)
     * 
     * ¿QUÉ HACE? Genera authorization_code si el usuario autoriza la app
     * ¿CÓMO? Crea código temporal, lo almacena en BD, redirige de vuelta al cliente
     * ¿PARA QUÉ? Para completar el primer paso del intercambio OAuth
     */
    public function procesarAutorizacion() {
        // Solo aceptar peticiones POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderError('Método no permitido', 405);
            return;
        }
        
        // Verificar que el usuario esté autenticado
        if (!$this->usuarioEstaAutenticado()) {
            header('Location: /login');
            exit;
        }
        
        // Obtener parámetros del formulario
        $client_id = $_POST['client_id'] ?? '';
        $redirect_uri = $_POST['redirect_uri'] ?? '';
        $scope = $_POST['scope'] ?? '';
        $state = $_POST['state'] ?? '';
        $user_consent = $_POST['authorize'] ?? '';
        
        // Verificar si el usuario autorizó o denegó
        if ($user_consent !== 'yes') {
            // Usuario denegó el acceso - redirigir con error
            $error_url = $redirect_uri . '?error=access_denied&error_description=Usuario+denegó+autorización';
            if (!empty($state)) {
                $error_url .= '&state=' . urlencode($state);
            }
            header('Location: ' . $error_url);
            exit;
        }
        
        // Re-validar cliente (por seguridad, nunca confiar solo en el frontend)
        $cliente = $this->obtenerClientePorId($client_id);
        if (!$cliente || !$this->validarRedirectUri($redirect_uri, $cliente['redirect_uri'])) {
            $this->responderError('Cliente o redirect_uri no válidos.');
            return;
        }
        
        // Generar authorization code único y temporal
        $authorization_code = $this->generarAuthorizationCode();
        
        // Almacenar el authorization code en la base de datos
        $code_almacenado = $this->almacenarAuthorizationCode(
            $authorization_code,
            $client_id,
            $this->obtenerUsuarioId(),
            $redirect_uri,
            $scope
        );
        
        if (!$code_almacenado) {
            $this->responderError('Error interno al generar código de autorización.');
            return;
        }
        
        // Redirigir de vuelta al cliente con el authorization code
        $success_url = $redirect_uri . '?code=' . urlencode($authorization_code);
        if (!empty($state)) {
            $success_url .= '&state=' . urlencode($state);
        }
        
        // Log de seguridad
        error_log("Authorization code generado para cliente: $client_id, usuario: " . $this->obtenerUsuarioId());
        
        header('Location: ' . $success_url);
        exit;
    }
    
    /**
     * Intercambia authorization code por access token (paso 3 del flujo)
     * 
     * ¿QUÉ HACE? Valida el code y genera access_token + refresh_token
     * ¿CÓMO? Verifica code en BD, valida cliente, genera tokens JWT o únicos
     * ¿PARA QUÉ? Para que la app cliente obtenga tokens para hacer peticiones
     */
    public function intercambiarToken() {
        // Solo aceptar peticiones POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderErrorOAuth('invalid_request', 'Solo se permiten peticiones POST', 405);
            return;
        }
        
        // Obtener parámetros de la petición (pueden venir por POST o headers)
        $grant_type = $_POST['grant_type'] ?? '';
        $code = $_POST['code'] ?? '';
        $redirect_uri = $_POST['redirect_uri'] ?? '';
        $client_id = $_POST['client_id'] ?? '';
        $client_secret = $_POST['client_secret'] ?? '';
        
        // Validar grant_type (solo soportamos authorization_code)
        if ($grant_type !== 'authorization_code') {
            $this->responderErrorOAuth('unsupported_grant_type', 'Solo se soporta authorization_code grant');
            return;
        }
        
        // Validar parámetros obligatorios
        if (empty($code) || empty($redirect_uri) || empty($client_id) || empty($client_secret)) {
            $this->responderErrorOAuth('invalid_request', 'Faltan parámetros requeridos: code, redirect_uri, client_id, client_secret');
            return;
        }
        
        // Validar credenciales del cliente
        $cliente = $this->validarCredencialesCliente($client_id, $client_secret);
        if (!$cliente) {
            $this->responderErrorOAuth('invalid_client', 'Credenciales de cliente inválidas');
            return;
        }
        
        // Buscar y validar el authorization code
        $codigo_info = $this->obtenerAuthorizationCode($code);
        if (!$codigo_info) {
            $this->responderErrorOAuth('invalid_grant', 'Código de autorización inválido o expirado');
            return;
        }
        
        // Verificar que el código pertenece a este cliente
        if ($codigo_info['client_id'] !== $client_id) {
            $this->responderErrorOAuth('invalid_grant', 'Código de autorización no pertenece a este cliente');
            return;
        }
        
        // Verificar que la redirect_uri coincide
        if ($codigo_info['redirect_uri'] !== $redirect_uri) {
            $this->responderErrorOAuth('invalid_grant', 'redirect_uri no coincide con la autorización original');
            return;
        }
        
        // Verificar que el código no haya expirado
        if (strtotime($codigo_info['expires_at']) < time()) {
            $this->responderErrorOAuth('invalid_grant', 'Código de autorización expirado');
            return;
        }
        
        // ✅ Todo válido - generar tokens
        $access_token = $this->generarAccessToken();
        $refresh_token = $this->generarRefreshToken();
        
        // Almacenar access token en BD
        $token_almacenado = $this->almacenarAccessToken(
            $access_token,
            $codigo_info['user_id'],
            $client_id,
            $codigo_info['scope']
        );
        
        // Almacenar refresh token en BD
        $refresh_almacenado = $this->almacenarRefreshToken($refresh_token, $access_token);
        
        if (!$token_almacenado || !$refresh_almacenado) {
            $this->responderErrorOAuth('server_error', 'Error interno al generar tokens');
            return;
        }
        
        // Invalidar el authorization code (solo se puede usar una vez)
        $this->invalidarAuthorizationCode($code);
        
        // Responder con los tokens en formato OAuth estándar
        $response = [
            'access_token' => $access_token,
            'token_type' => 'Bearer',
            'expires_in' => $this->access_token_lifetime,
            'refresh_token' => $refresh_token,
            'scope' => $codigo_info['scope']
        ];
        
        // Log de seguridad
        error_log("Access token generado para cliente: $client_id, usuario: " . $codigo_info['user_id']);
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    /**
     * Proporciona información del usuario autenticado (paso 4 del flujo)
     * 
     * ¿QUÉ HACE? Retorna datos del usuario si el token es válido
     * ¿CÓMO? Valida Bearer token, consulta BD, retorna info permitida por scope
     * ¿PARA QUÉ? Para que las apps clientes obtengan información del usuario logueado
     */
    public function obtenerInfoUsuario() {
        // Obtener el access token del header Authorization
        $access_token = $this->extraerBearerToken();
        
        if (empty($access_token)) {
            $this->responderErrorOAuth('invalid_request', 'Token de acceso requerido', 401);
            return;
        }
        
        // Validar el access token
        $token_info = $this->validarAccessToken($access_token);
        if (!$token_info) {
            $this->responderErrorOAuth('invalid_token', 'Token de acceso inválido o expirado', 401);
            return;
        }
        
        // Obtener información del usuario desde la BD
        $usuario_info = $this->obtenerUsuarioPorId($token_info['user_id']);
        if (!$usuario_info) {
            $this->responderErrorOAuth('server_error', 'Usuario no encontrado', 500);
            return;
        }
        
        // Filtrar información según los scopes del token
        $response = $this->filtrarInfoUsuarioPorScopes($usuario_info, $token_info['scope']);
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // ===============================================
    // MÉTODOS AUXILIARES PRIVADOS
    // ===============================================
    
    /**
     * Verifica si un usuario está autenticado
     * 
     * ¿QUÉ HACE? Verifica si hay una sesión de usuario válida
     * ¿CÓMO? Consulta variables de sesión establecidas por daoLogin.php
     * ¿PARA QUÉ? OAuth requiere que el usuario esté logueado antes del consentimiento
     */
    private function usuarioEstaAutenticado() {
        return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
    }
    
    /**
     * Obtiene ID del usuario actualmente logueado
     */
    private function obtenerUsuarioId() {
        return $_SESSION['usuario_id'] ?? null;
    }
    
    /**
     * Obtiene información completa del usuario actual desde sesión
     */
    private function obtenerUsuarioActual() {
        if (!$this->usuarioEstaAutenticado()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['usuario_id'],
            'email' => $_SESSION['usuario_email'],
            'nombre' => $_SESSION['usuario_nombre']
        ];
    }
    
    /**
     * Busca un cliente OAuth en la base de datos por su ID
     * 
     * ¿QUÉ HACE? Consulta la tabla oauth_clients por client_id
     * ¿CÓMO? Prepared statement para evitar SQL injection
     * ¿PARA QUÉ? Validar que la aplicación esté registrada en nuestro sistema
     */
    private function obtenerClientePorId($client_id) {
        $query = "SELECT id, secret, name, redirect_uri FROM oauth_clients WHERE id = ? LIMIT 1";
        $stmt = $this->conexion->prepare($query);
        
        if (!$stmt) {
            error_log("Error preparing client query: " . $this->conexion->error);
            return false;
        }
        
        $stmt->bind_param("s", $client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Valida que la redirect_uri sea exactamente igual a la registrada
     * 
     * ¿QUÉ HACE? Compara URIs de redirección para prevenir ataques
     * ¿CÓMO? Comparación exacta de strings (crítico para seguridad)
     * ¿PARA QUÉ? Prevenir que atacantes redirijan tokens a sus servidores
     */
    private function validarRedirectUri($uri_solicitada, $uri_registrada) {
        // En OAuth, la redirect_uri debe coincidir EXACTAMENTE
        return $uri_solicitada === $uri_registrada;
    }
    
    /**
     * Valida y procesa los scopes solicitados
     * 
     * ¿QUÉ HACE? Verifica que los permisos solicitados sean válidos
     * ¿CÓMO? Compara contra lista de scopes permitidos
     * ¿PARA QUÉ? Controlar qué información puede acceder cada aplicación
     */
    private function validarScopes($scope_string) {
        $scopes_disponibles = [
            'read_user' => 'Ver información básica del usuario',
            'read_email' => 'Ver dirección de correo electrónico'
        ];
        
        $scopes_solicitados = explode(' ', trim($scope_string));
        $scopes_validos = [];
        
        foreach ($scopes_solicitados as $scope) {
            if (array_key_exists($scope, $scopes_disponibles)) {
                $scopes_validos[$scope] = $scopes_disponibles[$scope];
            }
        }
        
        return $scopes_validos;
    }
    
    /**
     * Genera un authorization code único y seguro
     * 
     * ¿QUÉ HACE? Crea código temporal de autorización
     * ¿CÓMO? Usa random_bytes() para máxima entropía
     * ¿PARA QUÉ? Para el intercambio seguro en el flujo OAuth
     */
    private function generarAuthorizationCode() {
        return bin2hex(random_bytes(32)); // 64 caracteres hexadecimales
    }
    
    /**
     * Genera un access token único y seguro
     */
    private function generarAccessToken() {
        return bin2hex(random_bytes(32)); // 64 caracteres hexadecimales
    }
    
    /**
     * Genera un refresh token único y seguro
     */
    private function generarRefreshToken() {
        return bin2hex(random_bytes(32)); // 64 caracteres hexadecimales
    }
    
    /**
     * Almacena authorization code en base de datos (tabla temporal)
     * 
     * ¿QUÉ HACE? Guarda el código con tiempo de expiración corto
     * ¿CÓMO? INSERT con timestamp de expiración calculado
     * ¿PARA QUÉ? Para validar el código en el paso de intercambio por token
     */
    private function almacenarAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $scope) {
        // Crear tabla temporal si no existe (en producción esto estaría en migration)
        $this->crearTablaAuthorizationCodes();
        
        $expires_at = date('Y-m-d H:i:s', time() + $this->authorization_code_lifetime);
        
        $query = "INSERT INTO oauth_authorization_codes (code, client_id, user_id, redirect_uri, scope, expires_at) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($query);
        
        if (!$stmt) {
            error_log("Error preparing auth code storage: " . $this->conexion->error);
            return false;
        }
        
        $stmt->bind_param("ssssss", $code, $client_id, $user_id, $redirect_uri, $scope, $expires_at);
        return $stmt->execute();
    }
    
    /**
     * Crea tabla temporal para authorization codes (helper para desarrollo)
     */
    private function crearTablaAuthorizationCodes() {
        $query = "CREATE TABLE IF NOT EXISTS oauth_authorization_codes (
            code VARCHAR(255) PRIMARY KEY,
            client_id VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            redirect_uri TEXT NOT NULL,
            scope VARCHAR(500) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES oauth_clients(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->conexion->query($query);
    }
    
    /**
     * Responde con error OAuth estándar en formato JSON
     * 
     * ¿QUÉ HACE? Envía respuesta de error siguiendo RFC OAuth 2.0
     * ¿CÓMO? JSON con campos error, error_description, código HTTP apropiado
     * ¿PARA QUÉ? Para que clientes OAuth puedan manejar errores correctamente
     */
    private function responderErrorOAuth($error_code, $description, $http_code = 400) {
        http_response_code($http_code);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => $error_code,
            'error_description' => $description
        ]);
        exit;
    }
    
    /**
     * Obtiene información de un authorization code desde la BD
     */
    private function obtenerAuthorizationCode($code) {
        $query = "SELECT code, client_id, user_id, redirect_uri, scope, expires_at FROM oauth_authorization_codes WHERE code = ? LIMIT 1";
        $stmt = $this->conexion->prepare($query);
        
        if (!$stmt) {
            error_log("Error preparing auth code query: " . $this->conexion->error);
            return false;
        }
        
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Valida credenciales de cliente (client_id + client_secret)
     */
    private function validarCredencialesCliente($client_id, $client_secret) {
        $query = "SELECT id, secret, name, redirect_uri FROM oauth_clients WHERE id = ? AND secret = ? LIMIT 1";
        $stmt = $this->conexion->prepare($query);
        
        if (!$stmt) {
            error_log("Error preparing client validation: " . $this->conexion->error);
            return false;
        }
        
        $stmt->bind_param("ss", $client_id, $client_secret);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Almacena access token en la base de datos
     */
    private function almacenarAccessToken($token, $user_id, $client_id, $scope) {
        $expires_at = date('Y-m-d H:i:s', time() + $this->access_token_lifetime);
        
        $query = "INSERT INTO oauth_access_tokens (id, user_id, client_id, expires_at) VALUES (?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($query);
        
        if (!$stmt) {
            error_log("Error preparing access token storage: " . $this->conexion->error);
            return false;
        }
        
        $stmt->bind_param("siss", $token, $user_id, $client_id, $expires_at);
        return $stmt->execute();
    }
    
    /**
     * Almacena refresh token en la base de datos
     */
    private function almacenarRefreshToken($refresh_token, $access_token) {
        $expires_at = date('Y-m-d H:i:s', time() + $this->refresh_token_lifetime);
        
        $query = "INSERT INTO oauth_refresh_tokens (id, access_token_id, expires_at) VALUES (?, ?, ?)";
        $stmt = $this->conexion->prepare($query);
        
        if (!$stmt) {
            error_log("Error preparing refresh token storage: " . $this->conexion->error);
            return false;
        }
        
        $stmt->bind_param("sss", $refresh_token, $access_token, $expires_at);
        return $stmt->execute();
    }
    
    /**
     * Invalida un authorization code (solo se puede usar una vez)
     */
    private function invalidarAuthorizationCode($code) {
        $query = "DELETE FROM oauth_authorization_codes WHERE code = ?";
        $stmt = $this->conexion->prepare($query);
        
        if (!$stmt) {
            error_log("Error preparing auth code deletion: " . $this->conexion->error);
            return false;
        }
        
        $stmt->bind_param("s", $code);
        return $stmt->execute();
    }
    
    /**
     * Extrae Bearer token del header Authorization
     * 
     * ¿QUÉ HACE? Obtiene token del header "Authorization: Bearer xxxx"
     * ¿CÓMO? Parsea el header HTTP Authorization
     * ¿PARA QUÉ? Para validar tokens en peticiones API
     */
    private function extraerBearerToken() {
        $headers = apache_request_headers();
        
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Valida un access token y retorna su información
     */
    private function validarAccessToken($access_token) {
        $query = "SELECT id, user_id, client_id, expires_at FROM oauth_access_tokens WHERE id = ? AND expires_at > NOW() LIMIT 1";
        $stmt = $this->conexion->prepare($query);
        
        if (!$stmt) {
            error_log("Error preparing token validation: " . $this->conexion->error);
            return false;
        }
        
        $stmt->bind_param("s", $access_token);
        $stmt->execute();
        $result = $stmt->get_result();
        $token_info = $result->fetch_assoc();
        
        if ($token_info) {
            // Agregar scope por defecto (en un sistema completo estaría en la BD)
            $token_info['scope'] = 'read_user read_email';
        }
        
        return $token_info;
    }
    
    /**
     * Obtiene información de usuario por ID desde la base de datos
     */
    private function obtenerUsuarioPorId($user_id) {
        $query = "SELECT id, email, name, created_at FROM users WHERE id = ? LIMIT 1";
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
     * Filtra información del usuario según los scopes del token
     * 
     * ¿QUÉ HACE? Retorna solo la info permitida por los permisos del token
     * ¿CÓMO? Mapea scopes a campos específicos del usuario
     * ¿PARA QUÉ? Para controlar exactamente qué datos puede ver cada app
     */
    private function filtrarInfoUsuarioPorScopes($usuario_info, $scopes) {
        $scopes_array = explode(' ', trim($scopes));
        $response = [];
        
        // Siempre incluir ID (necesario para identificar al usuario)
        $response['id'] = $usuario_info['id'];
        
        // Mapear scopes a información específica
        if (in_array('read_user', $scopes_array)) {
            $response['name'] = $usuario_info['name'];
        }
        
        if (in_array('read_email', $scopes_array)) {
            $response['email'] = $usuario_info['email'];
        }
        
        // Siempre incluir cuando se registró el usuario
        $response['member_since'] = $usuario_info['created_at'];
        
        return $response;
    }
    
    /**
     * Responde con error HTML básico
     */
    private function responderError($mensaje, $codigo = 400) {
        http_response_code($codigo);
        echo "<h1>Error OAuth</h1><p>$mensaje</p>";
        exit;
    }
}

// El front controller maneja el routing, nosotros solo ejecutamos
$ruta = strtok($_SERVER['REQUEST_URI'], '?');
$metodo = $_SERVER['REQUEST_METHOD'];

$oauthController = new daoOAuth();

switch($ruta) {
    case '/oauth/authorize':
        if ($metodo === 'GET') {
            $oauthController->mostrarAutorizacion();
        } elseif ($metodo === 'POST') {
            $oauthController->procesarAutorizacion();
        }
        break;
        
    case '/oauth/token':
        if ($metodo === 'POST') {
            $oauthController->intercambiarToken();
        }
        break;
        
    case '/oauth/user':
        if ($metodo === 'GET') {
            $oauthController->obtenerInfoUsuario();
        }
        break;
        
    default:
        // Ruta no encontrada
        http_response_code(404);
        echo "Endpoint OAuth no encontrado";
        break;
}
?>