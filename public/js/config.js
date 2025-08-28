/**
 * config.js - Configuración Global JavaScript
 * 
 * PROPÓSITO:
 * - Centralizar configuración del frontend
 * - URLs base para peticiones AJAX
 * - Constantes globales del sistema
 * - Configuración de timeouts y reintentos
 */

const CONFIG = {
    // URL base del sistema (ajustar según el entorno)
    BASE_URL: 'https://grammermx.com/Jesus/auth',
    
    // Endpoints principales
    ENDPOINTS: {
        LOGIN: '/src/Controllers/daoLogin.php',
        LOGOUT: '/src/Controllers/daoLogin.php',
        DASHBOARD: '/src/Controllers/daoLogin.php',
        USER_PROFILE: '/src/Controllers/daoUser.php',
        USER_UPDATE: '/src/Controllers/daoUser.php',
        OAUTH_AUTHORIZE: '/src/Controllers/daoOAuth.php',
        OAUTH_TOKEN: '/src/Controllers/daoOAuth.php',
        OAUTH_USER: '/src/Controllers/daoOAuth.php',
        ADMIN_USERS: '/src/Controllers/daoUser.php',
        API_USER: '/src/Controllers/daoUser.php'
    },
    
    // Configuración de peticiones
    REQUEST: {
        TIMEOUT: 10000, // 10 segundos
        RETRY_ATTEMPTS: 3,
        RETRY_DELAY: 1000 // 1 segundo entre reintentos
    },
    
    // Configuración de UI
    UI: {
        LOADING_DELAY: 300, // Delay antes de mostrar loading
        MESSAGE_DURATION: 5000, // Duración de mensajes toast
        ANIMATION_DURATION: 300
    },
    
    // Validaciones
    VALIDATION: {
        EMAIL_DOMAIN: '@grammer.com',
        MIN_PASSWORD_LENGTH: 6,
        SESSION_CHECK_INTERVAL: 300000 // 5 minutos
    }
};

// Funciones de utilidad global
window.GrammerUtils = {
    /**
     * Construye URL completa para endpoint
     */
    buildUrl: function(endpoint, params = {}) {
        let url = CONFIG.BASE_URL + endpoint;
        
        if (Object.keys(params).length > 0) {
            const searchParams = new URLSearchParams(params);
            url += '?' + searchParams.toString();
        }
        
        return url;
    },
    
    /**
     * Realiza petición AJAX con configuración estándar
     */
    fetch: async function(endpoint, options = {}) {
        const url = this.buildUrl(endpoint);
        
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            timeout: CONFIG.REQUEST.TIMEOUT
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, finalOptions);
            
            // Verificar si la respuesta indica que la sesión expiró
            if (response.status === 401 || response.redirected && response.url.includes('/login')) {
                this.handleSessionExpired();
                return null;
            }
            
            return response;
        } catch (error) {
            console.error('Error en petición:', error);
            throw error;
        }
    },
    
    /**
     * Maneja expiración de sesión
     */
    handleSessionExpired: function() {
        alert('Tu sesión ha expirado. Serás redirigido al login.');
        window.location.href = CONFIG.BASE_URL + '/src/Controllers/daoLogin.php';
    },
    
    /**
     * Valida email corporativo
     */
    validateCorporateEmail: function(email) {
        return email.toLowerCase().endsWith(CONFIG.VALIDATION.EMAIL_DOMAIN);
    },
    
    /**
     * Muestra mensaje toast
     */
    showMessage: function(message, type = 'info') {
        // Implementación simple - en producción usar librería de toasts
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'error' ? '#ef4444' : type === 'success' ? '#10b981' : '#3b82f6'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, CONFIG.UI.MESSAGE_DURATION);
    },
    
    /**
     * Verifica estado de sesión periódicamente
     */
    startSessionCheck: function() {
        setInterval(async () => {
            try {
                const response = await this.fetch('/api/session-status');
                if (!response || !response.ok) {
                    this.handleSessionExpired();
                }
            } catch (error) {
                console.warn('Error verificando sesión:', error);
            }
        }, CONFIG.VALIDATION.SESSION_CHECK_INTERVAL);
    }
};

// Inicializar verificación de sesión automáticamente
document.addEventListener('DOMContentLoaded', function() {
    // Solo iniciar en páginas que requieren autenticación
    if (!window.location.pathname.includes('/login')) {
        GrammerUtils.startSessionCheck();
    }
});