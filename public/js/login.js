/**
 * login.js - JavaScript para página de login
 * 
 * PROPÓSITO:
 * - Manejar validaciones del formulario de login
 * - Envío AJAX del formulario para mejor UX
 * - Toggle de contraseña visible/oculta
 * - Validación en tiempo real de email corporativo
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const loginBtn = document.getElementById('loginBtn');
    const btnText = document.getElementById('btnText');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const togglePassword = document.getElementById('togglePassword');
    
    // Toggle para mostrar/ocultar contraseña
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const icon = togglePassword.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
    }
    
    // Validación de email en tiempo real
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const email = emailInput.value.toLowerCase();
            
            if (email && !GrammerUtils.validateCorporateEmail(email)) {
                showFieldError(emailInput, 'Debe usar su correo corporativo @grammer.com');
            } else {
                clearFieldError(emailInput);
            }
        });
    }
    
    // Manejo del envío del formulario
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return;
            }
            
            setLoadingState(true);
            
            try {
                const formData = new FormData(loginForm);
                
                const response = await fetch(GrammerUtils.buildUrl(CONFIG.ENDPOINTS.LOGIN), {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                // Verificar si la respuesta es JSON válida
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Respuesta del servidor no es JSON válido');
                }
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    GrammerUtils.showMessage('Login exitoso. Redirigiendo...', 'success');
                    setTimeout(() => {
                        window.location.href = result.redirect || GrammerUtils.buildUrl('/dashboard');
                    }, 1000);
                } else {
                    // Manejar errores específicos
                    handleLoginError(result, response.status);
                    setLoadingState(false);
                }
                
            } catch (error) {
                console.error('Error en login:', error);
                GrammerUtils.showMessage('Error de conexión o respuesta inválida', 'error');
                setLoadingState(false);
            }
        });
    }
    
    function handleLoginError(result, status) {
        let mensaje = 'Error desconocido';
        
        if (result && result.mensaje) {
            mensaje = result.mensaje;
        } else {
            switch (status) {
                case 400:
                    mensaje = 'Datos de entrada inválidos';
                    break;
                case 401:
                    mensaje = 'Contraseña incorrecta';
                    break;
                case 403:
                    mensaje = 'Solo emails @grammer.com están permitidos';
                    break;
                case 404:
                    mensaje = 'Usuario no encontrado';
                    break;
                case 500:
                    mensaje = 'Error interno del servidor';
                    break;
            }
        }
        
        GrammerUtils.showMessage(mensaje, 'error');
    }
    
    function validateForm() {
        let isValid = true;
        
        const email = emailInput.value.trim().toLowerCase();
        if (!email) {
            showFieldError(emailInput, 'El correo es requerido');
            isValid = false;
        } else if (!GrammerUtils.validateCorporateEmail(email)) {
            showFieldError(emailInput, 'Solo se permiten correos @grammer.com');
            isValid = false;
        } else {
            clearFieldError(emailInput);
        }
        
        const password = passwordInput.value;
        if (!password) {
            showFieldError(passwordInput, 'La contraseña es requerida');
            isValid = false;
        } else if (password.length < CONFIG.VALIDATION.MIN_PASSWORD_LENGTH) {
            showFieldError(passwordInput, `La contraseña debe tener al menos ${CONFIG.VALIDATION.MIN_PASSWORD_LENGTH} caracteres`);
            isValid = false;
        } else {
            clearFieldError(passwordInput);
        }
        
        return isValid;
    }
    
    function showFieldError(field, message) {
        clearFieldError(field);
        field.style.borderColor = 'var(--danger)';
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.color = 'var(--danger)';
        errorDiv.style.fontSize = '0.85rem';
        errorDiv.style.marginTop = '5px';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }
    
    function clearFieldError(field) {
        field.style.borderColor = '';
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }
    
    function setLoadingState(loading) {
        if (loading) {
            loginBtn.disabled = true;
            loadingSpinner.style.display = 'inline-block';
            btnText.textContent = 'Autenticando...';
        } else {
            loginBtn.disabled = false;
            loadingSpinner.style.display = 'none';
            btnText.textContent = 'Iniciar Sesión';
        }
    }
    
    // Limpiar errores al escribir
    [emailInput, passwordInput].forEach(input => {
        if (input) {
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        }
    });
});