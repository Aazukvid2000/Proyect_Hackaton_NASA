<?php
// backend/auth_middleware.php
session_start();

function requireAdmin() {
    // Verificar que existe una sesión activa
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'No autorizado. Por favor inicia sesión.',
            'redirect' => '/login.html'
        ]);
        exit();
    }
    
    // Verificar que el usuario sea administrador
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Acceso denegado. Necesitas privilegios de administrador.'
        ]);
        exit();
    }
    
    return true;
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'nombre' => $_SESSION['nombre_completo'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'rol' => $_SESSION['rol'] ?? ''
    ];
}
?>