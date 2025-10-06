<?php
// backend/api/admin_users.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config.php';
//require_once __DIR__ . '/../auth_middleware.php';

// Verificar autenticación y rol de administrador
//requireAdmin();

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_pending':
            getPendingUsers($conn);
            break;
        case 'approve':
            approveUser($conn);
            break;
        case 'reject':
            rejectUser($conn);
            break;
        case 'get_all':
            getAllUsers($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getPendingUsers($conn) {
    $sql = "SELECT u.id, u.nombre_completo, u.email, u.institucion, u.afiliacion, 
                   u.created_at, r.nombre as rol_nombre
            FROM usuarios u
            LEFT JOIN roles r ON u.rol_id = r.id
            WHERE u.estado = 'pendiente'
            ORDER BY u.created_at DESC";
    
    $result = $conn->query($sql);
    $users = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode(['success' => true, 'users' => $users]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al obtener usuarios']);
    }
}

function getAllUsers($conn) {
    $sql = "SELECT u.id, u.nombre_completo, u.email, u.institucion, u.afiliacion, 
                   u.estado, u.created_at, u.ultimo_acceso, r.nombre as rol_nombre
            FROM usuarios u
            LEFT JOIN roles r ON u.rol_id = r.id
            ORDER BY u.created_at DESC";
    
    $result = $conn->query($sql);
    $users = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode(['success' => true, 'users' => $users]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al obtener usuarios']);
    }
}

function approveUser($conn) {
    $userId = $_POST['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
        return;
    }
    
    $sql = "UPDATE usuarios SET estado = 'activo' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    
    if ($stmt->execute()) {
        // Aquí podrías enviar un email al usuario notificando la aprobación
        echo json_encode(['success' => true, 'message' => 'Usuario aprobado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al aprobar usuario']);
    }
}

function rejectUser($conn) {
    $userId = $_POST['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
        return;
    }
    
    // Puedes optar por eliminar el usuario o marcarlo como rechazado
    $sql = "DELETE FROM usuarios WHERE id = ?";
    // O usar: UPDATE usuarios SET estado = 'inactivo' WHERE id = ?
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Usuario rechazado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al rechazar usuario']);
    }
}
?>