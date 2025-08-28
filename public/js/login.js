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
                
                if (response.ok) {
                    const result = await response.json();
                    
                    if (result.success) {
                        GrammerUtils.showMessage('Login exitoso. Redirigiendo...', 'success');
                        setTimeout(() => {
                            window.location.href = result.redirect || GrammerUtils.buildUrl('/dashboard');
                        }, 1000);
                    } else {
                        GrammerUtils.showMessage(result.mensaje || 'Error en el login', 'error');
                        setLoadingState(false);
                    }
                } else {
                    const errorData = await response.json();
                    GrammerUtils.showMessage(errorData.mensaje || 'Error del servidor', 'error');
                    setLoadingState(false);
                }
                
            } catch (error) {
                console.error('Error en login:', error);
                GrammerUtils.showMessage('Error de conexión', 'error');
                setLoadingState(false);
            }
        });
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