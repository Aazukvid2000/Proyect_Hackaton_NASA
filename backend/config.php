<?php
/**
 * Archivo de Configuración
 * NASA Research Platform - CONFIGURACIÓN PARA DOCKER
 */

// ============================================================
// CONFIGURACIÓN DE LA BASE DE DATOS - DOCKER
// ============================================================
define('DB_HOST', 'mysql');  // Nombre del servicio en docker-compose
define('DB_USER', 'nasauser');
define('DB_PASS', 'nasapass123');
define('DB_NAME', 'nasa_research_db');

// ============================================================
// CONFIGURACIÓN DE LA APLICACIÓN
// ============================================================
define('SITE_NAME', 'NASA Research Platform');
define('SITE_URL', 'http://localhost');
define('UPLOAD_PATH', __DIR__ . '/uploads/publicaciones/');
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB

// ============================================================
// CONFIGURACIÓN DE SESIONES
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    session_start();
}

// ============================================================
// ZONA HORARIA
// ============================================================
date_default_timezone_set('America/Mexico_City');

// ============================================================
// CONFIGURACIÓN DE ERRORES
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php-errors.log');

// ============================================================
// CONEXIÓN A LA BASE DE DATOS
// ============================================================
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error al establecer charset: " . $conn->error);
    }
    
} catch (Exception $e) {
    error_log($e->getMessage());
    
    if (php_sapi_name() === 'cli') {
        die("Error de conexión a la base de datos\n");
    } else {
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'message' => 'Error de conexión a la base de datos'
        ]));
    }
}

// Crear conexión PDO también (para ai_search.php)
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Error PDO: " . $e->getMessage());
}

// ============================================================
// FUNCIONES AUXILIARES
// ============================================================

function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $conn->real_escape_string($data);
}

function isAuthenticated() {
    return isset($_SESSION['usuario_id']);
}

function isInvestigador() {
    return isset($_SESSION['rol']) && ($_SESSION['rol'] === 'investigador' || $_SESSION['rol'] === 'admin');
}

function isAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: /login.html');
        exit;
    }
}

function requireInvestigador() {
    requireAuth();
    if (!isInvestigador()) {
        http_response_code(403);
        die(json_encode([
            'success' => false,
            'message' => 'Acceso denegado. Se requieren permisos de investigador.'
        ]));
    }
}

function logActivity($mensaje, $nivel = 'info') {
    $logFile = __DIR__ . '/logs/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $userId = $_SESSION['usuario_id'] ?? 'anónimo';
    $logMessage = "[$timestamp] [$nivel] [Usuario: $userId] $mensaje\n";
    
    error_log($logMessage, 3, $logFile);
}

function createDirectories() {
    $directories = [
        __DIR__ . '/uploads',
        __DIR__ . '/uploads/publicaciones',
        __DIR__ . '/logs'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            @mkdir($dir, 0777, true); // @ suprime warnings
        }
    }
}

createDirectories();

// ============================================================
// CONSTANTES ÚTILES
// ============================================================
define('CATEGORIAS', [
    1 => 'Flora',
    2 => 'Fauna'
]);

define('ROLES', [
    'lector' => 'Lector',
    'investigador' => 'Investigador',
    'admin' => 'Administrador'
]);
?>