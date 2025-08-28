/**
 * oauth-consent.js - JavaScript para página de consentimiento OAuth
 * 
 * PROPÓSITO:
 * - Manejar envío del formulario de consentimiento
 * - Prevenir doble autorización
 * - Validaciones y confirmaciones
 * - Feedback visual durante procesamiento
 */

document.addEventListener('DOMContentLoaded', function() {
    // Verificar autenticación antes de mostrar consentimiento
    verificarAutenticacionOAuth();
    
    const consentForm = document.getElementById('consentForm');
    const authorizeBtn = document.getElementById('authorizeBtn');
    const btnText = document.getElementById('btnText');
    const loadingSpinner = document.getElementById('loadingSpinner');
    
    if (consentForm) {
        consentForm.addEventListener('submit', function(e) {
            const clickedButton = e.submitter;
            const isAuthorizing = clickedButton.value === 'yes';
            
            if (isAuthorizing) {
                // Obtener nombre de la app desde el DOM o atributos data
                const appName = document.querySelector('[data-app-name]')?.dataset.appName || 'esta aplicación';
                
                if (!confirm(`¿Estás seguro de autorizar a ${appName} a acceder a tu información?`)) {
                    e.preventDefault();
                    return;
                }
                
                setLoadingState(true);
                disableAllButtons(true);
            } else {
                disableAllButtons(true);
            }
        });
    }
    
    function setLoadingState(loading) {
        if (loading && btnText && loadingSpinner) {
            loadingSpinner.style.display = 'inline-block';
            btnText.textContent = 'Autorizando...';
        } else if (btnText && loadingSpinner) {
            loadingSpinner.style.display = 'none';
            btnText.innerHTML = '<i class="fas fa-check"></i> Autorizar Aplicación';
        }
    }
    
    function disableAllButtons(disabled) {
        const allButtons = consentForm?.querySelectorAll('button[type="submit"]') || [];
        allButtons.forEach(button => {
            button.disabled = disabled;
        });
    }
    
    // Auto-focus en botón de autorizar para accesibilidad
    if (authorizeBtn) {
        authorizeBtn.focus();
    }
    
    // Prevenir que el usuario salga accidentalmente
    window.addEventListener('beforeunload', function(e) {
        if (!document.querySelector('form[submitted]')) {
            e.returnValue = '¿Estás seguro de salir? La aplicación seguirá esperando tu autorización.';
        }
    });
    
    // Remover advertencia cuando se envía el formulario
    if (consentForm) {
        consentForm.addEventListener('submit', function() {
            this.setAttribute('submitted', 'true');
            window.removeEventListener('beforeunload', arguments.callee);
        });
    }
});

async function verificarAutenticacionOAuth() {
    try {
        const response = await GrammerUtils.fetch('/api/session-status');
        
        if (!response || !response.ok) {
            GrammerUtils.showMessage('Debe iniciar sesión antes de autorizar aplicaciones', 'error');
            setTimeout(() => {
                window.location.href = GrammerUtils.buildUrl('/login');
            }, 2000);
        }
    } catch (error) {
        console.warn('No se pudo verificar la autenticación OAuth:', error);
    }
}