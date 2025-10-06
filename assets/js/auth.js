/**
 * NASA BIOSCIENCE PLATFORM - AUTHENTICATION SCRIPT
 * Manejo de login y registro de usuarios
 */

// ============================================================
// CAMBIO DE TABS
// ============================================================
function switchTab(tab) {
    const tabs = document.querySelectorAll('.tab');
    const forms = document.querySelectorAll('.form-section');
    
    tabs.forEach(t => t.classList.remove('active'));
    forms.forEach(f => f.classList.remove('active'));
    
    if (tab === 'login') {
        tabs[0].classList.add('active');
        document.getElementById('loginForm').classList.add('active');
    } else {
        tabs[1].classList.add('active');
        document.getElementById('registerForm').classList.add('active');
    }
    
    // Limpiar alertas
    hideAlert();
}

// ============================================================
// SELECTOR DE ROL
// ============================================================
function initRoleSelector() {
    document.querySelectorAll('.role-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.role-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input[type="radio"]').checked = true;
        });
    });
}

// ============================================================
// MANEJO DE LOGIN
// ============================================================
async function handleLogin(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const loadingBox = document.getElementById('loadingBox');
    const submitBtn = e.target.querySelector('.submit-btn');
    
    // Validación básica
    const email = formData.get('email');
    const password = formData.get('password');
    
    if (!email || !password) {
        showAlert('error', '❌ Por favor completa todos los campos');
        return;
    }
    
    // Mostrar loading
    loadingBox.style.display = 'block';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('backend/api/auth.php?action=login', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        loadingBox.style.display = 'none';
        submitBtn.disabled = false;
        
        if (result.success) {
            showAlert('success', '✅ ' + result.message);
            
            // Guardar datos en sessionStorage
            sessionStorage.setItem('usuario', JSON.stringify(result.usuario));
            
            // Redirigir al dashboard después de 1 segundo
            setTimeout(() => {
                window.location.href = 'ai_dashboard.html';
            }, 1000);
        } else {
            showAlert('error', '❌ ' + result.message);
        }
        
    } catch (error) {
        loadingBox.style.display = 'none';
        submitBtn.disabled = false;
        showAlert('error', '❌ Error al conectar con el servidor. Verifica tu conexión.');
        console.error('Error en login:', error);
    }
}

// ============================================================
// MANEJO DE REGISTRO
// ============================================================
async function handleRegister(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const loadingBox = document.getElementById('loadingBox');
    const submitBtn = e.target.querySelector('.submit-btn');
    
    // Validar campos obligatorios
    const nombreCompleto = formData.get('nombre_completo');
    const email = formData.get('email');
    const password = formData.get('password');
    const passwordConfirm = formData.get('password_confirm');
    
    if (!nombreCompleto || !email || !password || !passwordConfirm) {
        showAlert('error', '❌ Por favor completa todos los campos obligatorios');
        return;
    }
    
    // Validar email
    if (!isValidEmail(email)) {
        showAlert('error', '❌ Por favor ingresa un email válido');
        return;
    }
    
    // Validar contraseña
    if (password.length < 6) {
        showAlert('error', '❌ La contraseña debe tener al menos 6 caracteres');
        return;
    }
    
    // Validar que las contraseñas coincidan
    if (password !== passwordConfirm) {
        showAlert('error', '❌ Las contraseñas no coinciden');
        return;
    }
    
    // Mostrar loading
    loadingBox.style.display = 'block';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('backend/api/auth.php?action=registrar', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        loadingBox.style.display = 'none';
        submitBtn.disabled = false;
        
        if (result.success) {
            if (result.requiere_aprobacion) {
                showAlert('info', '📋 ' + result.message + ' Te notificaremos por email cuando sea aprobada.');
            } else {
                showAlert('success', '✅ ' + result.message + ' Ahora puedes iniciar sesión.');
                
                // Cambiar a tab de login después de 2 segundos
                setTimeout(() => {
                    switchTab('login');
                    // Pre-llenar el email
                    document.getElementById('login_email').value = formData.get('email');
                }, 2000);
            }
            
            e.target.reset();
            // Resetear selector de rol
            document.querySelectorAll('.role-option').forEach(o => o.classList.remove('selected'));
            document.querySelector('.role-option:first-child').classList.add('selected');
        } else {
            showAlert('error', '❌ ' + result.message);
        }
        
    } catch (error) {
        loadingBox.style.display = 'none';
        submitBtn.disabled = false;
        showAlert('error', '❌ Error al conectar con el servidor. Verifica tu conexión.');
        console.error('Error en registro:', error);
    }
}

// ============================================================
// MOSTRAR/OCULTAR ALERTAS
// ============================================================
function showAlert(type, message) {
    const alertBox = document.getElementById('alertBox');
    alertBox.className = `alert ${type}`;
    alertBox.textContent = message;
    alertBox.style.display = 'block';
    
    // Scroll al inicio para ver la alerta
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Auto-ocultar después de 8 segundos (excepto para info)
    if (type !== 'info') {
        setTimeout(() => {
            hideAlert();
        }, 8000);
    }
}

function hideAlert() {
    const alertBox = document.getElementById('alertBox');
    alertBox.style.display = 'none';
}

// ============================================================
// VALIDACIONES
// ============================================================
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// ============================================================
// TOGGLE PASSWORD VISIBILITY (opcional)
// ============================================================
function togglePasswordVisibility(inputId, iconElement) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        iconElement.textContent = '🙈';
    } else {
        input.type = 'password';
        iconElement.textContent = '👁️';
    }
}

// ============================================================
// VALIDACIÓN EN TIEMPO REAL (opcional)
// ============================================================
function initRealTimeValidation() {
    const emailInputs = document.querySelectorAll('input[type="email"]');
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !isValidEmail(this.value)) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#e0e0e0';
            }
        });
    });
    
    // Validar longitud de contraseña
    const passwordInput = document.getElementById('reg_password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            if (this.value.length > 0 && this.value.length < 6) {
                this.style.borderColor = '#ffc107';
            } else if (this.value.length >= 6) {
                this.style.borderColor = '#28a745';
            } else {
                this.style.borderColor = '#e0e0e0';
            }
        });
    }
    
    // Validar que las contraseñas coincidan
    const passwordConfirm = document.getElementById('reg_password_confirm');
    if (passwordConfirm) {
        passwordConfirm.addEventListener('input', function() {
            const password = document.getElementById('reg_password').value;
            if (this.value && this.value !== password) {
                this.style.borderColor = '#dc3545';
            } else if (this.value === password) {
                this.style.borderColor = '#28a745';
            } else {
                this.style.borderColor = '#e0e0e0';
            }
        });
    }
}

// ============================================================
// INICIALIZACIÓN AL CARGAR LA PÁGINA
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar selector de rol
    initRoleSelector();
    
    // Inicializar validación en tiempo real (opcional)
    initRealTimeValidation();
    
    // Detectar si hay un parámetro en la URL para mostrar registro
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'register') {
        switchTab('register');
    }
    
    console.log('🚀 Sistema de autenticación NASA Bioscience cargado');
});