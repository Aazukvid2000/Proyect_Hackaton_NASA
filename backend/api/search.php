<?php
/**
 * API de Búsqueda para NASA Bio Research
 * Conecta con nasa_research_db
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Conexión a la base de datos
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

// Obtener parámetros de búsqueda
$query = $_GET['query'] ?? '';
$category = $_GET['category'] ?? '';
$year = $_GET['year'] ?? '';
$author = $_GET['author'] ?? '';
$sort = $_GET['sort'] ?? 'relevance';

// Construir consulta SQL
$sql = "SELECT 
    a.*,
    c.nombre as categoria_nombre,
    GROUP_CONCAT(DISTINCT k.palabra SEPARATOR ', ') as keywords
    FROM articulos a
    LEFT JOIN categorias c ON a.categoria_id = c.id
    LEFT JOIN articulo_keywords ak ON a.id = ak.articulo_id
    LEFT JOIN keywords k ON ak.keyword_id = k.id
    WHERE 1=1";

$params = [];

// Filtro de búsqueda general
if (!empty($query)) {
    $sql .= " AND (a.titulo LIKE ? OR a.resumen LIKE ? OR a.contenido LIKE ? OR a.autor LIKE ?)";
    $searchTerm = "%$query%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Filtro por categoría
if (!empty($category)) {
    $sql .= " AND c.nombre = ?";
    $params[] = ucfirst($category);
}

// Filtro por año
if (!empty($year)) {
    $sql .= " AND YEAR(a.fecha_publicacion) = ?";
    $params[] = $year;
}

// Filtro por autor
if (!empty($author)) {
    $sql .= " AND a.autor LIKE ?";
    $params[] = "%$author%";
}

$sql .= " GROUP BY a.id";

// Ordenamiento
switch($sort) {
    case 'alphabetical':
        $sql .= " ORDER BY a.titulo ASC";
        break;
    case 'year':
        $sql .= " ORDER BY a.fecha_publicacion DESC";
        break;
    case 'author':
        $sql .= " ORDER BY a.autor ASC";
        break;
    case 'relevance':
    default:
        $sql .= " ORDER BY a.relevancia_score DESC, a.fecha_publicacion DESC";
        break;
}

$sql .= " LIMIT 50";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count' => count($results),
        'results' => $results
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en la búsqueda: ' . $e->getMessage()
    ]);
}
?>