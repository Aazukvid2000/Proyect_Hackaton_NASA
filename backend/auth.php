<?php
/**
 * Sistema de Autenticación
 * ADAPTADO para nasa_research_db
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Usar config.php que ya tiene la conexión y session_start()
require_once __DIR__ . '/config.php';

class AuthSystem {
    private $conn;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * Registrar nuevo usuario en tabla usuarios
     */
    public function registrar($datos) {
        try {
            $this->validarDatosRegistro($datos);
            
            if ($this->emailExiste($datos['email'])) {
                throw new Exception('El email ya está registrado');
            }
            
            $passwordHash = password_hash($datos['password'], PASSWORD_BCRYPT);
            
            // Determinar rol: 1=lector, 2=investigador, 3=admin
            $rolId = $datos['rol'] === 'investigador' ? 2 : 1;
            $estado = $rolId === 2 ? 'pendiente' : 'activo';
            
            $stmt = $this->conn->prepare("
                INSERT INTO usuarios 
                (nombre_completo, email, password_hash, institucion, afiliacion, rol_id, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "sssssis",
                $datos['nombre_completo'],
                $datos['email'],
                $passwordHash,
                $datos['institucion'],
                $datos['afiliacion'],
                $rolId,
                $estado
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Error al registrar usuario');
            }
            
            $usuarioId = $this->conn->insert_id;
            
            $mensaje = $rolId === 2 
                ? 'Registro exitoso. Tu cuenta de investigador está pendiente de aprobación.'
                : 'Registro exitoso. Ya puedes iniciar sesión.';
            
            return [
                'success' => true,
                'message' => $mensaje,
                'usuario_id' => $usuarioId,
                'requiere_aprobacion' => $rolId === 2
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Iniciar sesión
     */
    public function login($email, $password) {
        try {
            $sql = "
                SELECT u.id, u.nombre_completo, u.email, u.password_hash, 
                       u.institucion, u.afiliacion, u.foto_perfil, u.estado, 
                       u.created_at AS fecha_creacion, u.ultimo_acceso,
                       r.nombre as rol
                FROM usuarios u
                JOIN roles r ON u.rol_id = r.id
                WHERE u.email = ?
            ";

            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) throw new Exception('Error interno al preparar la consulta de login');
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Email o contraseña incorrectos');
            }
            
            $usuario = $result->fetch_assoc();
            
            if (!password_verify($password, $usuario['password_hash'])) {
                throw new Exception('Email o contraseña incorrectos');
            }
            
            if ($usuario['estado'] === 'inactivo') {
                throw new Exception('Tu cuenta ha sido desactivada. Contacta al administrador.');
            }
            
            if ($usuario['estado'] === 'pendiente') {
                throw new Exception('Tu cuenta está pendiente de aprobación.');
            }
            
            // Crear sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre_completo'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['rol'] = $usuario['rol'];
            $_SESSION['institucion'] = $usuario['institucion'];
            $_SESSION['afiliacion'] = $usuario['afiliacion'];
            $_SESSION['foto_perfil'] = $usuario['foto_perfil'];
            $_SESSION['estado'] = $usuario['estado'];
            $_SESSION['fecha_creacion'] = $usuario['fecha_creacion'] ?? $usuario['created_at'] ?? null;
            
            // Actualizar último acceso
            $this->actualizarUltimoAcceso($usuario['id']);
            
            // Determinar redirección según rol
            $redirect = '/index.html';
            if ($usuario['rol'] === 'admin') {
                $redirect = '/dashboard/admin.html';
            } elseif ($usuario['rol'] === 'investigador') {
                $redirect = '/index.html';
            }
            
            return [
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'redirect' => $redirect,
                'usuario' => [
                    'id' => $usuario['id'],
                    'nombre' => $usuario['nombre_completo'],
                    'email' => $usuario['email'],
                    'rol' => $usuario['rol'],
                    'institucion' => $usuario['institucion'],
                    'afiliacion' => $usuario['afiliacion'],
                    'estado' => $usuario['estado'],
                    'fecha_creacion' => $usuario['fecha_creacion'],
                    'ultimo_acceso' => $usuario['ultimo_acceso'],
                    'foto_perfil' => $usuario['foto_perfil']
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        session_destroy();
        return [
            'success' => true,
            'message' => 'Sesión cerrada exitosamente'
        ];
    }
    
    /**
     * Verificar sesión activa
     */
    public function verificarSesion() {
        if (!isset($_SESSION['usuario_id'])) {
            return [
                'success' => false,
                'message' => 'No hay sesión activa'
            ];
        }
        
        // Obtener datos actualizados de la BD - ❌ AQUÍ ESTABA EL ERROR
        $stmt = $this->conn->prepare("
            SELECT u.id, u.nombre_completo, u.email, u.institucion, 
                   u.afiliacion, u.foto_perfil, u.estado, 
                   u.created_at AS fecha_creacion, u.ultimo_acceso,
                   r.nombre as rol
            FROM usuarios u
            JOIN roles r ON u.rol_id = r.id
            WHERE u.id = ?
        ");
        
        if (!$stmt) return ['success' => false, 'message' => 'Error interno al preparar la consulta de sesión'];
        
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }
        
        $usuario = $result->fetch_assoc();
        
        return [
            'success' => true,
            'usuario' => [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre_completo'],
                'email' => $usuario['email'],
                'rol' => $usuario['rol'],
                'institucion' => $usuario['institucion'],
                'afiliacion' => $usuario['afiliacion'],
                'estado' => $usuario['estado'],
                'fecha_creacion' => $usuario['fecha_creacion'],
                'ultimo_acceso' => $usuario['ultimo_acceso'],
                'foto_perfil' => $usuario['foto_perfil']
            ]
        ];
    }
    
    // MÉTODOS PRIVADOS
    
    private function validarDatosRegistro($datos) {
        if (empty($datos['nombre_completo'])) {
            throw new Exception('El nombre completo es obligatorio');
        }
        
        if (empty($datos['email']) || !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        if (empty($datos['password']) || strlen($datos['password']) < 6) {
            throw new Exception('La contraseña debe tener al menos 6 caracteres');
        }
        
        if (isset($datos['password_confirm']) && $datos['password'] !== $datos['password_confirm']) {
            throw new Exception('Las contraseñas no coinciden');
        }
    }
    
    private function emailExiste($email) {
        $stmt = $this->conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    
    private function actualizarUltimoAcceso($usuarioId) {
        $stmt = $this->conn->prepare("
            UPDATE usuarios 
            SET ultimo_acceso = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
    }
}

// Procesar peticiones
$auth = new AuthSystem($conn);
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'registrar':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'nombre_completo' => $_POST['nombre_completo'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'password_confirm' => $_POST['password_confirm'] ?? '',
                'institucion' => $_POST['institucion'] ?? '',
                'afiliacion' => $_POST['afiliacion'] ?? '',
                'rol' => $_POST['rol'] ?? 'lector'
            ];
            
            $resultado = $auth->registrar($datos);
            echo json_encode($resultado);
        } else {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        }
        break;
        
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $resultado = $auth->login($email, $password);
            echo json_encode($resultado);
        } else {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        }
        break;
        
    case 'logout':
        $resultado = $auth->logout();
        echo json_encode($resultado);
        break;
        
    case 'verificar':
        $resultado = $auth->verificarSesion();
        echo json_encode($resultado);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida'
        ]);
}
?>