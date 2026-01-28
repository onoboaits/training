<?php
/**
 * MyPyMEs Training Platform - Archivo de Configuración
 * Domain: training.onoboaits.com/mypymes
 */

// Configuración de la base de datos
define('DB_HOST', '69.10.38.124');
define('DB_NAME', 'onoboait_try');
define('DB_USER', 'onoboait_try'); // Cambia esto por tu usuario MySQL
define('DB_PASS', 'Tr@1n1ng2026'); // Cambia esto por tu contraseña MySQL
define('DB_CHARSET', 'utf8mb4');

// Configuración de email
define('SMTP_HOST', 'mail.onoboaits.com'); // O smtp.gmail.com si usas Gmail
define('SMTP_PORT', 587); // 465 para SSL, 587 para TLS
define('SMTP_USER', 'training@onoboaits.com');
define('SMTP_PASS', 'Tr@1n1ng2026'); // Contraseña del email
define('SMTP_FROM', 'training@onoboaits.com');
define('SMTP_FROM_NAME', 'MyPyMEs Training');
define('SMTP_ENCRYPTION', 'tls'); // 'tls' o 'ssl'

// URLs del sistema
define('BASE_URL', 'https://training.onoboaits.com/mypymes/');
define('API_URL', BASE_URL . 'api/');

// Configuración de seguridad
define('JWT_SECRET', 'eWOdRNwRfz4xAy5OW2uJBYgfOYNbejXzZ2UF8BjQcnb'); // Genera una clave segura
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 días en segundos
define('PASSWORD_MIN_LENGTH', 6);

// Configuración de la aplicación
define('APP_NAME', 'MyPyMEs Training');
define('APP_ENV', 'production'); // 'development' o 'production'
define('DEBUG_MODE', false); // Cambiar a false en producción

// Zona horaria
date_default_timezone_set('America/Guayaquil');

// Configuración de errores
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// CORS - Permitir solicitudes desde tu dominio
header('Access-Control-Allow-Origin: https://training.onoboaits.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Conexión a la base de datos
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            if (DEBUG_MODE) {
                die('Error de conexión a la base de datos: ' . $e->getMessage());
            } else {
                die('Error de conexión a la base de datos. Por favor, contacte al administrador.');
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // Prevenir clonación
    private function __clone() {}

    // Prevenir deserialización
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Función helper para obtener conexión
function getDB() {
    return Database::getInstance()->getConnection();
}

// Función para responder JSON
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Función para manejar errores
function handleError($message, $code = 400) {
    jsonResponse(['error' => true, 'message' => $message], $code);
}

// Función para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Función para sanitizar datos
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Iniciar sesión PHP si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>