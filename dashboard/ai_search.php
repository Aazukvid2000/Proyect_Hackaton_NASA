<?php
/**
 * AI SEARCH - Búsqueda Inteligente con IA
 * Compatible con estructura nasa_research_db
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);    
    exit();
}

// Configuración
$host = 'mysql';
$dbname = 'nasa_research_db';
$username = 'nasauser';
$password = 'nasapass123';

// API KEY de Claude
define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: 'YOUR_KEY_HERE');


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'ai_search';
$aiProvider = $_GET['ai_provider'] ?? 'claude';

switch($action) {
    case 'ai_search':
        busquedaConIA($pdo);
        break;
    case 'semantic_search':
        busquedaSemantica($pdo);
        break;
    case 'ai_question':
        preguntarDocumento($pdo);
        break;
    case 'generate_summary':
        generarResumen($pdo);
        break;
    case 'get_stats':
        obtenerEstadisticas($pdo);
        break;
    default:
        busquedaSimple($pdo);
}

/**
 * Búsqueda mejorada con IA de Claude
 */
function busquedaConIA($pdo) {
    $queryOriginal = $_POST['query'] ?? $_GET['query'] ?? '';    
    if (empty($queryOriginal)) {
        busquedaSimple($pdo);
        return;
    }
    
    // 1. Mejorar query con Claude
    $queryMejorado = mejorarQueryConClaude($queryOriginal);
    
    // 2. Buscar en base de datos
    $sql = "SELECT a.*, c.nombre as categoria_nombre
            FROM articulos a 
            LEFT JOIN categorias c ON a.categoria_id = c.id
            WHERE a.titulo LIKE ? 
               OR a.resumen LIKE ? 
               OR a.contenido LIKE ?
               OR a.autor LIKE ?
            ORDER BY a.relevancia_score DESC, a.fecha_publicacion DESC
            LIMIT 15";
    
    $searchTerm = "%{$queryMejorado}%";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay resultados con query mejorado, intentar con original
    if (empty($resultados)) {
        $searchTerm = "%{$queryOriginal}%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $queryMejorado = $queryOriginal;
    }
    
    // 3. Analizar resultados con Claude
    $analisis = '';
    if (!empty($resultados)) {
        $analisis = analizarResultadosConClaude($queryOriginal, $resultados);
    }
    
    echo json_encode([
        'success' => true,
        'original_query' => $queryOriginal,
        'improved_query' => $queryMejorado,
        'ai_analysis' => $analisis,
        'provider' => 'claude',
        'results' => $resultados,
        'total' => count($resultados)
    ]);
}

/**
 * Búsqueda semántica (por ahora usa el mismo método que AI search)
 */
function busquedaSemantica($pdo) {
    busquedaConIA($pdo);
}

/**
 * Búsqueda simple sin IA
 */
function busquedaSimple($pdo) {
    $query = $_POST['query'] ?? $_GET['query'] ?? '';
    
    if (empty($query)) {
        // Retornar todos los artículos
        $sql = "SELECT a.*, c.nombre as categoria_nombre
                FROM articulos a 
                LEFT JOIN categorias c ON a.categoria_id = c.id
                ORDER BY a.relevancia_score DESC
                LIMIT 20";
        $stmt = $pdo->query($sql);
    } else {
        $sql = "SELECT a.*, c.nombre as categoria_nombre
                FROM articulos a 
                LEFT JOIN categorias c ON a.categoria_id = c.id
                WHERE a.titulo LIKE ? OR a.resumen LIKE ? OR a.contenido LIKE ?
                ORDER BY a.relevancia_score DESC
                LIMIT 20";
        $searchTerm = "%{$query}%";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    }
    
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'results' => $resultados,
        'total' => count($resultados)
    ]);
}

/**
 * Preguntar sobre documentos con Claude
 */
function preguntarDocumento($pdo) {
    $pregunta = $_POST['question'] ?? '';
    $articuloId = $_POST['article_id'] ?? null;
    
    if (empty($pregunta)) {
        echo json_encode(['success' => false, 'error' => 'Pregunta vacía']);
        return;
    }
    
    // Obtener contexto
    if ($articuloId) {
        $stmt = $pdo->prepare("SELECT * FROM articulos WHERE id = ?");
        $stmt->execute([$articuloId]);
        $articulos = [$stmt->fetch(PDO::FETCH_ASSOC)];
    } else {
        // Buscar artículos relevantes
        $searchTerm = "%{$pregunta}%";
        $stmt = $pdo->prepare("
            SELECT * FROM articulos 
            WHERE titulo LIKE ? OR resumen LIKE ? OR contenido LIKE ?
            LIMIT 5
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $respuesta = preguntarClaude($pregunta, $articulos);
    
    echo json_encode([
        'success' => true,
        'question' => $pregunta,
        'answer' => $respuesta,
        'provider' => 'claude',
        'articles_consulted' => count($articulos)
    ]);
}

/**
 * Generar resumen con Claude
 */
function generarResumen($pdo) {
    $articuloId = $_POST['article_id'] ?? null;
    
    if (!$articuloId) {
        echo json_encode(['success' => false, 'error' => 'ID requerido']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM articulos WHERE id = ?");
    $stmt->execute([$articuloId]);
    $articulo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$articulo) {
        echo json_encode(['success' => false, 'error' => 'Artículo no encontrado']);
        return;
    }
    
    $resumen = generarResumenConClaude($articulo);
    
    echo json_encode([
        'success' => true,
        'article_id' => $articuloId,
        'ai_summary' => $resumen,
        'provider' => 'claude'
    ]);
}

/**
 * Obtener estadísticas de la base de datos
 */
function obtenerEstadisticas($pdo) {
    // Total de artículos
    $totalArticulos = $pdo->query("SELECT COUNT(*) FROM articulos")->fetchColumn();
    
    // Artículos por categoría
    $porCategoria = $pdo->query("
        SELECT c.nombre, COUNT(a.id) as total
        FROM categorias c
        LEFT JOIN articulos a ON c.id = a.categoria_id
        GROUP BY c.id, c.nombre
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Artículos por año
    $porAnio = $pdo->query("
        SELECT YEAR(fecha_publicacion) as anio, COUNT(*) as total
        FROM articulos
        WHERE fecha_publicacion IS NOT NULL
        GROUP BY YEAR(fecha_publicacion)
        ORDER BY anio DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Top 5 autores
    $topAutores = $pdo->query("
        SELECT autor, COUNT(*) as total
        FROM articulos
        WHERE autor IS NOT NULL
        GROUP BY autor
        ORDER BY total DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Promedio de relevancia
    $promedioRelevancia = $pdo->query("
        SELECT AVG(relevancia_score) as promedio
        FROM articulos
    ")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_articulos' => $totalArticulos,
            'por_categoria' => $porCategoria,
            'por_anio' => $porAnio,
            'top_autores' => $topAutores,
            'promedio_relevancia' => round($promedioRelevancia, 2)
        ]
    ]);
}

// ==================== FUNCIONES DE CLAUDE ====================

function mejorarQueryConClaude($query) {
    $prompt = "Eres un asistente de investigación científica de la NASA especializado en biociencia espacial. Mejora esta consulta de búsqueda expandiéndola con términos científicos relevantes y sinónimos. Mantén el contexto de flora y fauna en microgravedad.\n\nConsulta original: \"$query\"\n\nResponde SOLO con los términos de búsqueda mejorados, sin explicaciones adicionales.";
    
    $respuesta = llamarClaude($prompt);
    
    // Si falla o está vacío, retornar query original
    return !empty($respuesta) && strpos($respuesta, 'Error') === false ? $respuesta : $query;
}

function analizarResultadosConClaude($query, $resultados) {
    $top5 = array_slice($resultados, 0, 5);
    $resumen = implode("\n", array_map(function($r) {
        return "- {$r['titulo']} ({$r['autor']}, {$r['fecha_publicacion']})";
    }, $top5));
    
    $prompt = "Eres un experto en biociencia espacial de la NASA. Analiza brevemente (máximo 100 palabras) la relevancia de estos resultados para la consulta del usuario.\n\nConsulta: \"$query\"\n\nResultados encontrados:\n$resumen\n\n¿Qué patrón o insight científico destacarías de estos resultados?";
    
    return llamarClaude($prompt);
}

function preguntarClaude($pregunta, $articulos) {
    $contexto = "Contexto de artículos científicos de NASA:\n\n";
    
    foreach ($articulos as $art) {
        $contexto .= "Título: {$art['titulo']}\n";
        $contexto .= "Autor: {$art['autor']}\n";
        $contexto .= "Resumen: {$art['resumen']}\n";
        $contexto .= "Contenido: " . substr($art['contenido'], 0, 800) . "...\n\n";
    }
    
    $prompt = "Eres un experto en biociencia espacial de la NASA. Responde la siguiente pregunta basándote EXCLUSIVAMENTE en el contexto proporcionado. Si la información no está en el contexto, indica que no tienes esa información.\n\n$contexto\nPregunta del usuario: $pregunta\n\nRespuesta (máximo 150 palabras):";
    
    return llamarClaude($prompt);
}

function generarResumenConClaude($articulo) {
    $prompt = "Eres un experto en biociencia espacial. Genera un resumen conciso y técnico (máximo 120 palabras) de este artículo científico de NASA:\n\nTítulo: {$articulo['titulo']}\nAutor: {$articulo['autor']}\nContenido completo:\n{$articulo['contenido']}\n\nResumen científico:";
    
    return llamarClaude($prompt);
}

function llamarClaude($prompt) {
    $apiKey = CLAUDE_API_KEY;
    
    $data = [
        'model' => 'claude-3-haiku-20240307',
        'max_tokens' => 1024,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];
    
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return $result['content'][0]['text'] ?? 'Sin respuesta de Claude';
    }
    
    return "Error en Claude API (HTTP $httpCode)";
}
?>