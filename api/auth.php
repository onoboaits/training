<?php
/**
 * API de Autenticación - MyPyMEs Training
 * Endpoints: register, login, logout, verify
 */

require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = getDB();

    switch ($action) {
        case 'register':
            if ($method !== 'POST') {
                handleError('Método no permitido', 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar campos requeridos
            $required = ['name', 'email', 'password', 'phone', 'businessType', 'country', 'province', 'city'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    handleError("El campo {$field} es requerido", 400);
                }
            }
            
            // Validar email
            if (!isValidEmail($data['email'])) {
                handleError('Email inválido', 400);
            }
            
            // Verificar si el email ya existe
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                handleError('El email ya está registrado', 409);
            }
            
            // Validar longitud de contraseña
            if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
                handleError('La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres', 400);
            }
            
            // Hash de la contraseña
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            
            // Insertar usuario
            $stmt = $db->prepare("
                INSERT INTO users (name, email, password, phone, business_type, country, province, city)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                sanitize($data['name']),
                sanitize($data['email']),
                $hashedPassword,
                sanitize($data['phone']),
                sanitize($data['businessType']),
                sanitize($data['country']),
                sanitize($data['province']),
                sanitize($data['city'])
            ]);
            
            $userId = $db->lastInsertId();
            
            // Crear sesión
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $data['email'];
            
            jsonResponse([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'user' => [
                    'id' => $userId,
                    'name' => $data['name'],
                    'email' => $data['email']
                ]
            ], 201);
            break;

        case 'login':
            if ($method !== 'POST') {
                handleError('Método no permitido', 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['email']) || empty($data['password'])) {
                handleError('Email y contraseña son requeridos', 400);
            }
            
            // Buscar usuario
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([sanitize($data['email'])]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($data['password'], $user['password'])) {
                handleError('Credenciales inválidas', 401);
            }
            
            // Crear sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            
            // Obtener progreso del usuario
            $progress = getUserProgress($db, $user['id']);
            
            jsonResponse([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'businessType' => $user['business_type'],
                    'country' => $user['country'],
                    'province' => $user['province'],
                    'city' => $user['city'],
                    'examPassed' => (bool)$user['exam_passed'],
                    'progress' => $progress['progress'],
                    'ratings' => $progress['ratings'],
                    'completedModules' => $progress['completedModules'],
                    'certificates' => $progress['certificates']
                ]
            ]);
            break;

        case 'logout':
            session_destroy();
            jsonResponse([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente'
            ]);
            break;

        case 'verify':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['authenticated' => false], 401);
            }
            
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                jsonResponse(['authenticated' => false], 401);
            }
            
            $progress = getUserProgress($db, $user['id']);
            
            jsonResponse([
                'authenticated' => true,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'businessType' => $user['business_type'],
                    'country' => $user['country'],
                    'province' => $user['province'],
                    'city' => $user['city'],
                    'examPassed' => (bool)$user['exam_passed'],
                    'progress' => $progress['progress'],
                    'ratings' => $progress['ratings'],
                    'completedModules' => $progress['completedModules'],
                    'certificates' => $progress['certificates']
                ]
            ]);
            break;

        case 'forgot-password':
            if ($method !== 'POST') {
                handleError('Método no permitido', 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['email'])) {
                handleError('Email requerido', 400);
            }
            
            // Buscar usuario
            $stmt = $db->prepare("SELECT id, name, email FROM users WHERE email = ?");
            $stmt->execute([sanitize($data['email'])]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Por seguridad, no revelar si el email existe o no
                jsonResponse([
                    'success' => true,
                    'message' => 'Si el email existe, recibirás instrucciones'
                ]);
                break;
            }
            
            // Generar token único
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Guardar token (puedes crear una tabla password_resets o usar sessions)
            $stmt = $db->prepare("
                INSERT INTO password_resets (email, token, expires_at) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
            ");
            $stmt->execute([$user['email'], $token, $expiry, $token, $expiry]);
            
            // Enviar email
            $resetLink = BASE_URL . "reset-password.php?token=" . $token;
            $subject = 'Recuperación de Contraseña - MyPyMEs';
            $message = "
                Hola {$user['name']},
                
                Has solicitado recuperar tu contraseña en MyPyMés Training.
                
                Haz clic en el siguiente enlace para crear una nueva contraseña:
                {$resetLink}
                
                Este enlace expira en 1 hora.
                
                Si no solicitaste esto, ignora este email.
                
                Saludos,
                Equipo MyPyMés
            ";
            
            $headers = "From: " . SMTP_FROM . "\r\n";
            mail($user['email'], $subject, $message, $headers);
            
            jsonResponse([
                'success' => true,
                'message' => 'Se ha enviado un email con instrucciones'
            ]);
            break;

        default:
            handleError('Acción no válida', 400);
    }

} catch (Exception $e) {
    error_log('Auth API Error: ' . $e->getMessage());
    handleError('Error interno del servidor', 500);
}

// Función para obtener el progreso completo del usuario
function getUserProgress($db, $userId) {
    // Obtener progreso de temas
    $stmt = $db->prepare("SELECT module_id, topic_id FROM user_progress WHERE user_id = ?");
    $stmt->execute([$userId]);
    $progressRows = $stmt->fetchAll();
    
    $progress = [];
    foreach ($progressRows as $row) {
        $progress["{$row['module_id']}-{$row['topic_id']}"] = true;
    }
    
    // Obtener calificaciones
    $stmt = $db->prepare("SELECT module_id, topic_id, rating FROM content_ratings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $ratingRows = $stmt->fetchAll();
    
    $ratings = [];
    foreach ($ratingRows as $row) {
        $ratings["{$row['module_id']}-{$row['topic_id']}"] = (int)$row['rating'];
    }
    
    // Obtener módulos completados
    $stmt = $db->prepare("SELECT module_id FROM completed_modules WHERE user_id = ?");
    $stmt->execute([$userId]);
    $completedModules = array_column($stmt->fetchAll(), 'module_id');
    
    // Obtener certificados
    $stmt = $db->prepare("SELECT certificate_type, module_id, score, DATE_FORMAT(issued_at, '%d/%m/%Y') as date FROM certificates WHERE user_id = ?");
    $stmt->execute([$userId]);
    $certificateRows = $stmt->fetchAll();
    
    $certificates = [];
    foreach ($certificateRows as $cert) {
        $certificates[] = [
            'type' => $cert['certificate_type'],
            'module' => $cert['module_id'],
            'score' => $cert['score'] ? (float)$cert['score'] : null,
            'date' => $cert['date']
        ];
    }
    
    return [
        'progress' => $progress,
        'ratings' => $ratings,
        'completedModules' => $completedModules,
        'certificates' => $certificates
    ];
}
?>