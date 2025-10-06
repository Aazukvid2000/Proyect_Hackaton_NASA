<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configuración de la base de datos
$host = 'mysql';
$dbname = 'nasa_research_db';
$username = 'nasauser';
$password = 'nasapass123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? 'all';

switch($action) {
    case 'search':
        buscarArticulos($pdo);
        break;
    case 'all':
        obtenerTodosArticulos($pdo);
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
}

function buscarArticulos($pdo) {
    $termino = $_GET['q'] ?? '';
    $categoria = $_GET['categoria'] ?? '';
    $orden = $_GET['orden'] ?? 'relevancia';
    
    // Registrar búsqueda
    if (!empty($termino)) {
        $stmt = $pdo->prepare("INSERT INTO busquedas (termino_busqueda) VALUES (?)");
        $stmt->execute([$termino]);
    }
    
    // Construir la consulta
    $sql = "SELECT DISTINCT a.*, c.nombre as categoria_nombre,
            GROUP_CONCAT(DISTINCT k.palabra SEPARATOR ', ') as keywords";
    
    if (!empty($termino)) {
        $sql .= ", MATCH(a.titulo, a.resumen, a.contenido) AGAINST (? IN NATURAL LANGUAGE MODE) as score";
    }
    
    $sql .= " FROM articulos a 
            LEFT JOIN categorias c ON a.categoria_id = c.id
            LEFT JOIN articulos_keywords ak ON a.id = ak.articulo_id
            LEFT JOIN keywords k ON ak.keyword_id = k.id";
    
    $params = [];
    $conditions = [];
    
    if (!empty($termino)) {
        $conditions[] = "(MATCH(a.titulo, a.resumen, a.contenido) AGAINST (? IN NATURAL LANGUAGE MODE)
                        OR a.titulo LIKE ?
                        OR a.resumen LIKE ?
                        OR k.palabra LIKE ?)";
        $params[] = $termino;
        $likeTermino = "%$termino%";
        $params[] = $likeTermino;
        $params[] = $likeTermino;
        $params[] = $likeTermino;
    }
    
    if (!empty($categoria)) {
        $conditions[] = "a.categoria_id = ?";
        $params[] = $categoria;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " GROUP BY a.id";
    
    // Ordenamiento
    switch($orden) {
        case 'recientes':
            $sql .= " ORDER BY a.fecha_publicacion DESC";
            break;
        case 'vistas':
            $sql .= " ORDER BY a.vistas DESC";
            break;
        case 'relevancia':
        default:
            if (!empty($termino)) {
                $sql .= " ORDER BY score DESC, a.relevancia_score DESC";
            } else {
                $sql .= " ORDER BY a.relevancia_score DESC";
            }
    }
    
    $sql .= " LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Actualizar vistas
    if (!empty($resultados)) {
        $ids = array_column($resultados, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $updateStmt = $pdo->prepare("UPDATE articulos SET vistas = vistas + 1 WHERE id IN ($placeholders)");
        $updateStmt->execute($ids);
        
        // Actualizar contador de resultados en búsquedas
        if (!empty($termino)) {
            $pdo->prepare("UPDATE busquedas SET resultados_encontrados = ? WHERE id = LAST_INSERT_ID()")
                ->execute([count($resultados)]);
        }
    }
    
    echo json_encode($resultados);
}

function obtenerTodosArticulos($pdo) {
    $sql = "SELECT a.*, c.nombre as categoria_nombre,
            GROUP_CONCAT(DISTINCT k.palabra SEPARATOR ', ') as keywords
            FROM articulos a 
            LEFT JOIN categorias c ON a.categoria_id = c.id
            LEFT JOIN articulos_keywords ak ON a.id = ak.articulo_id
            LEFT JOIN keywords k ON ak.keyword_id = k.id
            GROUP BY a.id
            ORDER BY a.relevancia_score DESC, a.fecha_publicacion DESC
            LIMIT 50";
    
    $stmt = $pdo->query($sql);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($resultados);
}
?>