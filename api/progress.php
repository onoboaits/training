<?php
/**
 * API de Progreso - MyPyMEs Training
 * Gestión de progreso de usuarios, calificaciones y módulos completados
 */

require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    handleError('No autenticado', 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    $db = getDB();

    switch ($action) {
        case 'mark-read':
            if ($method !== 'POST') {
                handleError('Método no permitido', 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['moduleId']) || empty($data['topicId'])) {
                handleError('moduleId y topicId son requeridos', 400);
            }
            
            // Marcar tema como leído
            $stmt = $db->prepare("
                INSERT INTO user_progress (user_id, module_id, topic_id, completed)
                VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE completed = 1, completed_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                $userId,
                sanitize($data['moduleId']),
                sanitize($data['topicId'])
            ]);
            
            // Verificar si el módulo está completo
            checkModuleCompletion($db, $userId, $data['moduleId']);
            
            jsonResponse([
                'success' => true,
                'message' => 'Tema marcado como leído'
            ]);
            break;

        case 'rate-content':
            if ($method !== 'POST') {
                handleError('Método no permitido', 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['moduleId']) || empty($data['topicId']) || !isset($data['rating'])) {
                handleError('moduleId, topicId y rating son requeridos', 400);
            }
            
            $rating = (int)$data['rating'];
            if ($rating < 1 || $rating > 5) {
                handleError('La calificación debe estar entre 1 y 5', 400);
            }
            
            // Guardar o actualizar calificación
            $stmt = $db->prepare("
                INSERT INTO content_ratings (user_id, module_id, topic_id, rating)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE rating = ?, updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                $userId,
                sanitize($data['moduleId']),
                sanitize($data['topicId']),
                $rating,
                $rating
            ]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Calificación guardada exitosamente'
            ]);
            break;

        case 'get-progress':
            $stmt = $db->prepare("SELECT module_id, topic_id FROM user_progress WHERE user_id = ?");
            $stmt->execute([$userId]);
            $progressRows = $stmt->fetchAll();
            
            $progress = [];
            foreach ($progressRows as $row) {
                $progress["{$row['module_id']}-{$row['topic_id']}"] = true;
            }
            
            jsonResponse([
                'success' => true,
                'progress' => $progress
            ]);
            break;

        case 'get-ratings':
            $stmt = $db->prepare("SELECT module_id, topic_id, rating FROM content_ratings WHERE user_id = ?");
            $stmt->execute([$userId]);
            $ratingRows = $stmt->fetchAll();
            
            $ratings = [];
            foreach ($ratingRows as $row) {
                $ratings["{$row['module_id']}-{$row['topic_id']}"] = (int)$row['rating'];
            }
            
            jsonResponse([
                'success' => true,
                'ratings' => $ratings
            ]);
            break;

        case 'get-completed-modules':
            $stmt = $db->prepare("SELECT module_id FROM completed_modules WHERE user_id = ?");
            $stmt->execute([$userId]);
            $modules = array_column($stmt->fetchAll(), 'module_id');
            
            jsonResponse([
                'success' => true,
                'completedModules' => $modules
            ]);
            break;

        default:
            handleError('Acción no válida', 400);
    }

} catch (Exception $e) {
    error_log('Progress API Error: ' . $e->getMessage());
    handleError('Error interno del servidor', 500);
}

/**
 * Verificar si un módulo está completo y emitir certificado si corresponde
 */
function checkModuleCompletion($db, $userId, $moduleId) {
    // Definir la cantidad de temas por módulo (esto debería coincidir con tu frontend)
    $moduleTopics = [
        'inicio' => 4,
        'gestion-negocio' => 3,
        'usuarios' => 3,
        'contactos' => 4,
        'productos' => 9,
        'compras' => 4,
        'ventas' => 9,
        'facturacion-electronica' => 6,
        'inventario' => 5,
        'reportes' => 7,
        'gastos' => 3,
        'notificaciones' => 3,
        'configuracion' => 5,
        'hardware' => 4
    ];
    
    if (!isset($moduleTopics[$moduleId])) {
        return false;
    }
    
    // Contar temas completados en este módulo
    $stmt = $db->prepare("
        SELECT COUNT(*) as completed_count 
        FROM user_progress 
        WHERE user_id = ? AND module_id = ? AND completed = 1
    ");
    $stmt->execute([$userId, $moduleId]);
    $result = $stmt->fetch();
    
    $completedCount = (int)$result['completed_count'];
    $requiredCount = $moduleTopics[$moduleId];
    
    // Si completó todos los temas del módulo
    if ($completedCount >= $requiredCount) {
        // Verificar si ya está marcado como completo
        $stmt = $db->prepare("SELECT id FROM completed_modules WHERE user_id = ? AND module_id = ?");
        $stmt->execute([$userId, $moduleId]);
        
        if (!$stmt->fetch()) {
            // Marcar módulo como completo
            $stmt = $db->prepare("INSERT INTO completed_modules (user_id, module_id) VALUES (?, ?)");
            $stmt->execute([$userId, $moduleId]);
            
            // Emitir certificado de conocimiento
            $stmt = $db->prepare("
                INSERT INTO certificates (user_id, certificate_type, module_id)
                VALUES (?, 'knowledge', ?)
            ");
            $stmt->execute([$userId, $moduleId]);
            
            return true;
        }
    }
    
    return false;
}
?>