/**
 * NASA BIO RESEARCH PLATFORM - AUTH CHECK
 * Verifica si el usuario está autenticado y muestra opciones según su rol
 */

// ============================================================
// VERIFICAR AUTENTICACIÓN AL CARGAR LA PÁGINA
// ============================================================
document.addEventListener('DOMContentLoaded', async function() {
    await verificarSesion();
});

async function verificarSesion() {
    try {
        const response = await fetch('backend/auth.php?action=verificar');
        const data = await response.json();
        
        if (data.success) {
            // Usuario autenticado
            mostrarOpcionesAutenticado(data.usuario);
        } else {
            // Usuario no autenticado
            mostrarOpcionesNoAutenticado();
        }
    } catch (error) {
        console.error('Error verificando sesión:', error);
        mostrarOpcionesNoAutenticado();
    }
}

function mostrarOpcionesAutenticado(usuario) {
    // Ocultar botón de login
    const loginBtn = document.getElementById('loginBtn');
    if (loginBtn) loginBtn.style.display = 'none';
    
    // Mostrar botón de logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.style.display = 'inline-block';
        logoutBtn.innerHTML = `🚪 Salir (${usuario.nombre.split(' ')[0]})`;
    }
    
    // Si es investigador, mostrar botón de subir publicación
    if (usuario.rol === 'investigador' || usuario.rol === 'admin') {
        const uploadBtn = document.getElementById('uploadBtn');
        if (uploadBtn) uploadBtn.style.display = 'inline-block';
    }
}

function mostrarOpcionesNoAutenticado() {
    // Mostrar botón de login
    const loginBtn = document.getElementById('loginBtn');
    if (loginBtn) loginBtn.style.display = 'inline-block';
    
    // Ocultar botón de logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) logoutBtn.style.display = 'none';
    
    // Ocultar botón de subir publicación
    const uploadBtn = document.getElementById('uploadBtn');
    if (uploadBtn) uploadBtn.style.display = 'none';
}

async function cerrarSesion() {
    try {
        const response = await fetch('backend/auth.php?action=logout');
        const data = await response.json();
        
        if (data.success) {
            alert('Sesión cerrada exitosamente');
            window.location.reload();
        }
    } catch (error) {
        console.error('Error cerrando sesión:', error);
        alert('Error al cerrar sesión');
    }
}