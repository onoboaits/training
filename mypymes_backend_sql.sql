-- Base de datos: onoboait_training_mypymes
-- Estructura de tablas para MyPyMEs Training Platform

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    business_type VARCHAR(100),
    country VARCHAR(10),
    province VARCHAR(100),
    city VARCHAR(100),
    exam_passed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de progreso de usuarios (temas leídos)
CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id VARCHAR(100) NOT NULL,
    topic_id VARCHAR(100) NOT NULL,
    completed TINYINT(1) DEFAULT 1,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (user_id, module_id, topic_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de calificaciones de contenido
CREATE TABLE IF NOT EXISTS content_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id VARCHAR(100) NOT NULL,
    topic_id VARCHAR(100) NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rating (user_id, module_id, topic_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de certificados
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    certificate_type ENUM('knowledge', 'certified') NOT NULL,
    module_id VARCHAR(100) DEFAULT NULL,
    score DECIMAL(5,2) DEFAULT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (certificate_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de respuestas de exámenes
CREATE TABLE IF NOT EXISTS exam_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_answer INT NOT NULL,
    is_correct TINYINT(1) NOT NULL,
    exam_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_exam_date (exam_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de módulos completados
CREATE TABLE IF NOT EXISTS completed_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id VARCHAR(100) NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_completed (user_id, module_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de sesiones (opcional, para gestión de sesiones)
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuario demo para pruebas
INSERT INTO users (name, email, password, phone, business_type, country, province, city, exam_passed)
VALUES (
    'Usuario Demo',
    'demo@mypymes.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: demo123
    '0999999999',
    'retail',
    'EC',
    'Guayas',
    'Guayaquil',
    0
)
ON DUPLICATE KEY UPDATE email = email;

-- Vistas útiles para reportes

-- Vista de progreso de usuarios
CREATE OR REPLACE VIEW v_user_progress_summary AS
SELECT 
    u.id,
    u.name,
    u.email,
    COUNT(DISTINCT up.module_id) as modules_started,
    COUNT(up.id) as topics_completed,
    u.exam_passed,
    COUNT(DISTINCT c.id) as certificates_earned
FROM users u
LEFT JOIN user_progress up ON u.id = up.user_id
LEFT JOIN certificates c ON u.id = c.user_id
GROUP BY u.id, u.name, u.email, u.exam_passed;

-- Vista de estadísticas de módulos
CREATE OR REPLACE VIEW v_module_statistics AS
SELECT 
    module_id,
    COUNT(DISTINCT user_id) as users_count,
    AVG(rating) as avg_rating,
    COUNT(DISTINCT topic_id) as topics_count
FROM (
    SELECT module_id, user_id, NULL as rating, topic_id FROM user_progress
    UNION ALL
    SELECT module_id, user_id, rating, topic_id FROM content_ratings
) AS combined
GROUP BY module_id;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email (email),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;