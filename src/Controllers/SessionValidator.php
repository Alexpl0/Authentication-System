<?php
/**
 * SessionValidator.php
 * Clase para validar sesiones y permisos de manera centralizada
 * 
 * PROPÓSITO:
 * - Centralizar validación de sesiones
 * - Verificar permisos de administrador
 * - Proporcionar endpoints para verificación AJAX
 * - Manejar expiración de sesiones
 */

class SessionValidator {
    
    /**
     * Verifica si hay una sesión de usuario válida
     */
    public static function verificarSesion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['usuario_id']) && 
               !empty($_SESSION['usuario_id']) && 
               isset($_SESSION['login_timestamp']);
    }
    
    /**
     * Verifica si el usuario actual es administrador
     */
    public static function esAdministrador() {
        if (!self::verificarSesion()) {
            return false;
        }
        
        $email_usuario = $_SESSION['usuario_email'] ?? '';
        
        // Lista de emails administradores (en producción esto podría venir de BD)
        $admins = [
            'admin@grammer.com',
            'it@grammer.com',
            'sistemas@grammer.com'
        ];
        
        return in_array($email_usuario, $admins);
    }
    
    /**
     * Fuerza autenticación o redirige al login
     */
    public static function requerirAutenticacion($redirect_url = '/login') {
        if (!self::verificarSesion()) {
            if (self::esAjax()) {
                self::responderErrorAjax('Sesión requerida', 401);
            } else {
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
                header('Location: ' . $redirect_url);
                exit;
            }
        }
    }
    
    /**
     * Fuerza permisos de administrador
     */
    public static function requerirAdmin($redirect_url = '/dashboard') {
        self::requerirAutenticacion();
        
        if (!self::esAdministrador()) {
            if (self::esAjax()) {
                self::responderErrorAjax('Permisos de administrador requeridos', 403);
            } else {
                header('Location: ' . $redirect_url . '?error=access_denied');
                exit;
            }
        }
    }
    
    /**
     * Verifica si la sesión ha expirado
     */
    public static function verificarExpiracion($duracion_maxima = 8 * 3600) {
        if (!self::verificarSesion()) {
            return true; // Ya no hay sesión
        }
        
        $login_timestamp = $_SESSION['login_timestamp'] ?? 0;
        $tiempo_transcurrido = time() - $login_timestamp;
        
        if ($tiempo_transcurrido > $duracion_maxima) {
            self::destruirSesion();
            return true;
        }
        
        return false;
    }
    
    /**
     * Destruye la sesión actual
     */
    public static function destruirSesion() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = array();
            
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            session_destroy();
        }
    }
    
    /**
     * Endpoint AJAX para verificar estado de sesión
     */
    public static function verificarEstadoSesion() {
        header('Content-Type: application/json');
        
        $es_valida = self::verificarSesion() && !self::verificarExpiracion();
        
        if ($es_valida) {
            echo json_encode([
                'valid' => true,
                'user_id' => $_SESSION['usuario_id'],
                'email' => $_SESSION['usuario_email'],
                'is_admin' => self::esAdministrador(),
                'login_time' => $_SESSION['login_timestamp']
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'valid' => false,
                'message' => 'Sesión expirada o inválida'
            ]);
        }
        exit;
    }
    
    /**
     * Endpoint para verificar permisos de administrador
     */
    public static function verificarPermisosAdmin() {
        header('Content-Type: application/json');
        
        if (!self::verificarSesion()) {
            http_response_code(401);
            echo json_encode(['isAdmin' => false, 'message' => 'No autenticado']);
            exit;
        }
        
        if (!self::esAdministrador()) {
            http_response_code(403);
            echo json_encode(['isAdmin' => false, 'message' => 'Sin permisos de administrador']);
            exit;
        }
        
        echo json_encode([
            'isAdmin' => true,
            'email' => $_SESSION['usuario_email'],
            'permissions' => ['admin', 'users_management', 'oauth_management']
        ]);
        exit;
    }
    
    /**
     * Detecta si es una petición AJAX
     */
    private static function esAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    /**
     * Responde error en formato JSON para AJAX
     */
    private static function responderErrorAjax($mensaje, $codigo = 400) {
        http_response_code($codigo);
        header('Content-Type: application/json');
        echo json_encode(['error' => true, 'mensaje' => $mensaje]);
        exit;
    }
}
?>