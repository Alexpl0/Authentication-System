/**
 * dashboard.js - JavaScript para dashboard principal
 * 
 * PROPÓSITO:
 * - Manejar interacciones del dashboard
 * - Confirmación de logout
 * - Actualizaciones en tiempo real
 * - Animaciones de entrada
 */

document.addEventListener('DOMContentLoaded', function() {
    // Verificar que el usuario esté autenticado antes de cargar el dashboard
    verificarAutenticacion();
    
    // Actualizar hora actual cada minuto
    setInterval(updateCurrentTime, 60000);
    
    // Confirmación de logout
    const logoutForm = document.querySelector('form[action*="logout"]');
    if (logoutForm) {
        logoutForm.addEventListener('submit', function(e) {
            if (!confirm('¿Estás seguro de cerrar sesión?')) {
                e.preventDefault();
            }
        });
    }
    
    // Animación de entrada para las cards 
    const cards = document.querySelectorAll('.section-card, .app-card-dashboard');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease-out';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Efecto de hover mejorado para app cards
    const appCards = document.querySelectorAll('.app-card-dashboard');
    appCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(-2px) scale(1)';
        });
    });
    
    // Log de interacciones para analytics
    document.querySelectorAll('.app-card-dashboard').forEach(card => {
        card.addEventListener('click', function() {
            const appName = this.querySelector('.app-name')?.textContent;
            console.log('Dashboard: Acceso a aplicación', appName);
        });
    });
    
    // Cargar estadísticas en tiempo real
    cargarEstadisticas();
});

async function verificarAutenticacion() {
    try {
        const response = await GrammerUtils.fetch('/api/session-status');
        
        if (!response || !response.ok) {
            GrammerUtils.showMessage('Sesión no válida. Redirigiendo al login...', 'error');
            setTimeout(() => {
                window.location.href = GrammerUtils.buildUrl('/login');
            }, 2000);
        }
    } catch (error) {
        console.warn('No se pudo verificar la sesión:', error);
    }
}

async function cargarEstadisticas() {
    try {
        const response = await GrammerUtils.fetch('/api/user/stats');
        
        if (response && response.ok) {
            const stats = await response.json();
            actualizarEstadisticas(stats);
        }
    } catch (error) {
        console.warn('No se pudieron cargar las estadísticas:', error);
    }
}

function actualizarEstadisticas(stats) {
    // Actualizar contadores en el dashboard
    const elementos = {
        'aplicaciones_conectadas': stats.aplicaciones_conectadas,
        'ultimo_login': stats.ultimo_login,
        'tokens_activos': stats.tokens_activos
    };
    
    Object.entries(elementos).forEach(([id, valor]) => {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = valor;
        }
    });
}

function updateCurrentTime() {
    const timeElement = document.querySelector('.stat-number');
    if (timeElement && timeElement.textContent.includes(':')) {
        timeElement.textContent = new Date().toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

// Detectar si el usuario está inactivo
let inactivityTimer;
function resetInactivityTimer() {
    clearTimeout(inactivityTimer);
    inactivityTimer = setTimeout(() => {
        console.log('Usuario inactivo detectado');
        GrammerUtils.showMessage('Has estado inactivo. Tu sesión podría expirar pronto.', 'warning');
    }, 30 * 60 * 1000); // 30 minutos
}

// Eventos para detectar actividad
['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
    document.addEventListener(event, resetInactivityTimer, true);
});

resetInactivityTimer();