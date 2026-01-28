<?php
/**
 * API de Exámenes - MyPyMEs Training
 * Gestión de exámenes y certificados
 */

require_once '../config.php';
require_once '../includes/email.php';

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
        case 'submit':
            if ($method !== 'POST') {
                handleError('Método no permitido', 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['answers']) || !is_array($data['answers'])) {
                handleError('Respuestas del examen requeridas', 400);
            }
            
            // Respuestas correctas (deben coincidir con el frontend)
            $correctAnswers = [
                1 => 1,  // Pregunta 1: opción correcta es índice 1
                2 => 2,  // Pregunta 2: opción correcta es índice 2
                3 => 1,  // Pregunta 3: opción correcta es índice 1
                4 => 1,  // Pregunta 4: opción correcta es índice 1
                5 => 2,  // Pregunta 5: opción correcta es índice 2
                6 => 1,  // Pregunta 6: opción correcta es índice 1
                7 => 2,  // Pregunta 7: opción correcta es índice 2
                8 => 1,  // Pregunta 8: opción correcta es índice 1
                9 => 0,  // Pregunta 9: opción correcta es índice 0
                10 => 1  // Pregunta 10: opción correcta es índice 1
            ];
            
            $correct = 0;
            $total = count($correctAnswers);
            
            // Calcular respuestas correctas y guardar
            $db->beginTransaction();
            
            foreach ($data['answers'] as $questionId => $selectedAnswer) {
                $isCorrect = isset($correctAnswers[$questionId]) && 
                            $correctAnswers[$questionId] === (int)$selectedAnswer;
                
                if ($isCorrect) {
                    $correct++;
                }
                
                // Guardar respuesta
                $stmt = $db->prepare("
                    INSERT INTO exam_answers (user_id, question_id, selected_answer, is_correct)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $questionId, $selectedAnswer, $isCorrect ? 1 : 0]);
            }
            
            $percentage = ($correct / $total) * 100;
            $passed = $percentage >= 70;
            
            if ($passed) {
                // Actualizar usuario como examen aprobado
                $stmt = $db->prepare("UPDATE users SET exam_passed = 1 WHERE id = ?");
                $stmt->execute([$userId]);
                
                // Crear certificado
                $stmt = $db->prepare("
                    INSERT INTO certificates (user_id, certificate_type, score)
                    VALUES (?, 'certified', ?)
                ");
                $stmt->execute([$userId, $percentage]);
                
                // Obtener información del usuario para el email
                $stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                $db->commit();
                
                // Enviar email de certificado
                sendCertificateEmail($user['email'], $user['name'], 'certified', $percentage);
                
                jsonResponse([
                    'success' => true,
                    'passed' => true,
                    'score' => $percentage,
                    'correct' => $correct,
                    'total' => $total,
                    'message' => '¡Felicitaciones! Has aprobado el examen'
                ]);
            } else {
                $db->commit();
                
                jsonResponse([
                    'success' => true,
                    'passed' => false,
                    'score' => $percentage,
                    'correct' => $correct,
                    'total' => $total,
                    'message' => 'Necesitas 70% o más para aprobar'
                ]);
            }
            break;

        case 'get-certificates':
            $stmt = $db->prepare("
                SELECT 
                    certificate_type,
                    module_id,
                    score,
                    DATE_FORMAT(issued_at, '%d/%m/%Y') as date
                FROM certificates
                WHERE user_id = ?
                ORDER BY issued_at DESC
            ");
            $stmt->execute([$userId]);
            $certificates = $stmt->fetchAll();
            
            $formattedCerts = [];
            foreach ($certificates as $cert) {
                $formattedCerts[] = [
                    'type' => $cert['certificate_type'],
                    'module' => $cert['module_id'],
                    'score' => $cert['score'] ? (float)$cert['score'] : null,
                    'date' => $cert['date']
                ];
            }
            
            jsonResponse([
                'success' => true,
                'certificates' => $formattedCerts
            ]);
            break;

        case 'send-certificate-email':
            if ($method !== 'POST') {
                handleError('Método no permitido', 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['certificateType'])) {
                handleError('Tipo de certificado requerido', 400);
            }
            
            // Obtener información del usuario y certificado
            $stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            $stmt = $db->prepare("
                SELECT score 
                FROM certificates 
                WHERE user_id = ? AND certificate_type = ?
                ORDER BY issued_at DESC
                LIMIT 1
            ");
            $stmt->execute([$userId, $data['certificateType']]);
            $cert = $stmt->fetch();
            
            if (!$cert) {
                handleError('Certificado no encontrado', 404);
            }
            
            $score = $cert['score'] ? (float)$cert['score'] : null;
            
            // Enviar email
            $emailSent = sendCertificateEmail($user['email'], $user['name'], $data['certificateType'], $score);
            
            if ($emailSent) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Email enviado exitosamente'
                ]);
            } else {
                handleError('Error al enviar el email', 500);
            }
            break;

        default:
            handleError('Acción no válida', 400);
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Exam API Error: ' . $e->getMessage());
    handleError('Error interno del servidor', 500);
}
?>