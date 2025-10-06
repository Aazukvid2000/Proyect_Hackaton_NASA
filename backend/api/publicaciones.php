<?php
/**
 * API para gestionar publicaciones de investigadores
 * backend/publicaciones.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No estás autenticado. Por favor inicia sesión.'
    ]);
    exit;
}

// Verificar que el usuario sea investigador o admin
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['investigador', 'admin'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Solo los investigadores pueden publicar artículos.'
    ]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'publicar':
        publicarArticulo();
        break;
    
    case 'mis_publicaciones':
        obtenerMisPublicaciones();
        break;
    
    case 'editar':
        editarArticulo();
        break;
    
    case 'eliminar':
        eliminarArticulo();
        break;
    
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida'
        ]);
}

function publicarArticulo() {
    global $conn;
    
    try {
        // Validar campos requeridos
        $titulo = trim($_POST['titulo'] ?? '');
        $resumen = trim($_POST['resumen'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $categoria_id = intval($_POST['categoria_id'] ?? 0);
        $url_documento = trim($_POST['url_documento'] ?? '');
        
        // Validaciones
        if (empty($titulo)) {
            throw new Exception('El título es obligatorio');
        }
        
        if (strlen($titulo) > 255) {
            throw new Exception('El título no puede exceder 255 caracteres');
        }
        
        if (empty($resumen)) {
            throw new Exception('El resumen es obligatorio');
        }
        
        if (empty($contenido)) {
            throw new Exception('El contenido es obligatorio');
        }
        
        if (!in_array($categoria_id, [1, 2])) {
            throw new Exception('Debes seleccionar una categoría válida (Flora o Fauna)');
        }
        
        // Validar URL si se proporciona
        if (!empty($url_documento)) {
            if (strlen($url_documento) > 500) {
                throw new Exception('La URL del documento no puede exceder 500 caracteres');
            }
            
            if (!filter_var($url_documento, FILTER_VALIDATE_URL)) {
                throw new Exception('La URL del documento no es válida');
            }
        }
        
        // Obtener el nombre del autor
        $autor = $_SESSION['nombre'] ?? 'Investigador';
        
        // Insertar el artículo
        $stmt = $conn->prepare("
            INSERT INTO articulos 
            (titulo, autor, resumen, contenido, url_documento, fecha_publicacion, categoria_id, relevancia_score)
            VALUES (?, ?, ?, ?, ?, CURDATE(), ?, 5.0)
        ");
        
        $stmt->bind_param(
            "sssssi",
            $titulo,
            $autor,
            $resumen,
            $contenido,
            $url_documento,
            $categoria_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al guardar el artículo: ' . $stmt->error);
        }
        
        $articulo_id = $conn->insert_id;
        
        // Registrar en logs
        logActivity("Artículo publicado: {$titulo} (ID: {$articulo_id})", 'info');
        
        echo json_encode([
            'success' => true,
            'message' => '¡Investigación publicada exitosamente!',
            'articulo_id' => $articulo_id
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function obtenerMisPublicaciones() {
    global $conn;
    
    try {
        $usuario_nombre = $_SESSION['nombre'];
        
        $stmt = $conn->prepare("
            SELECT 
                a.id,
                a.titulo,
                a.autor,
                a.resumen,
                a.contenido,
                a.url_documento,
                a.fecha_publicacion,
                a.relevancia_score,
                c.nombre as categoria_nombre,
                a.created_at,
                a.updated_at
            FROM articulos a
            LEFT JOIN categorias c ON a.categoria_id = c.id
            WHERE a.autor = ?
            ORDER BY a.created_at DESC
        ");
        
        $stmt->bind_param("s", $usuario_nombre);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $publicaciones = [];
        while ($row = $result->fetch_assoc()) {
            $publicaciones[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'publicaciones' => $publicaciones,
            'total' => count($publicaciones)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener publicaciones: ' . $e->getMessage()
        ]);
    }
}

function editarArticulo() {
    global $conn;
    
    try {
        $articulo_id = intval($_POST['id'] ?? 0);
        
        if (!$articulo_id) {
            throw new Exception('ID de artículo inválido');
        }
        
        // Verificar que el artículo pertenece al usuario
        $stmt = $conn->prepare("SELECT autor FROM articulos WHERE id = ?");
        $stmt->bind_param("i", $articulo_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Artículo no encontrado');
        }
        
        $articulo = $result->fetch_assoc();
        
        if ($articulo['autor'] !== $_SESSION['nombre'] && $_SESSION['rol'] !== 'admin') {
            throw new Exception('No tienes permisos para editar este artículo');
        }
        
        // Actualizar el artículo
        $titulo = trim($_POST['titulo'] ?? '');
        $resumen = trim($_POST['resumen'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $categoria_id = intval($_POST['categoria_id'] ?? 0);
        $url_documento = trim($_POST['url_documento'] ?? '');
        
        if (empty($titulo) || empty($resumen) || empty($contenido)) {
            throw new Exception('Todos los campos obligatorios deben estar completos');
        }
        
        $stmt = $conn->prepare("
            UPDATE articulos 
            SET titulo = ?, resumen = ?, contenido = ?, url_documento = ?, categoria_id = ?
            WHERE id = ?
        ");
        
        $stmt->bind_param(
            "sssiii",
            $titulo,
            $resumen,
            $contenido,
            $url_documento,
            $categoria_id,
            $articulo_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al actualizar el artículo');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Artículo actualizado exitosamente'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function eliminarArticulo() {
    global $conn;
    
    try {
        $articulo_id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
        
        if (!$articulo_id) {
            throw new Exception('ID de artículo inválido');
        }
        
        // Verificar que el artículo pertenece al usuario
        $stmt = $conn->prepare("SELECT autor FROM articulos WHERE id = ?");
        $stmt->bind_param("i", $articulo_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Artículo no encontrado');
        }
        
        $articulo = $result->fetch_assoc();
        
        if ($articulo['autor'] !== $_SESSION['nombre'] && $_SESSION['rol'] !== 'admin') {
            throw new Exception('No tienes permisos para eliminar este artículo');
        }
        
        // Eliminar el artículo
        $stmt = $conn->prepare("DELETE FROM articulos WHERE id = ?");
        $stmt->bind_param("i", $articulo_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al eliminar el artículo');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Artículo eliminado exitosamente'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>