-- ============================================================
-- AGREGAR SISTEMA DE USUARIOS A nasa_research_db
-- Ejecutar este SQL en tu base de datos EXISTENTE
-- ============================================================

USE nasa_research_db;

-- Tabla de roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar roles
INSERT INTO roles (nombre, descripcion) VALUES
('lector', 'Usuario que puede consultar y leer publicaciones'),
('investigador', 'Investigador que puede subir publicaciones'),
('admin', 'Administrador con acceso completo')
ON DUPLICATE KEY UPDATE id=id;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    institucion VARCHAR(255),
    afiliacion VARCHAR(255),
    biografia TEXT,
    foto_perfil VARCHAR(500),
    rol_id INT NOT NULL DEFAULT 1,
    estado ENUM('activo', 'inactivo', 'pendiente') DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
    FOREIGN KEY (rol_id) REFERENCES roles(id),
    INDEX idx_email (email),
    INDEX idx_rol (rol_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de actividad de usuarios (opcional pero útil)
CREATE TABLE IF NOT EXISTS actividad_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo_actividad ENUM('login', 'logout', 'subida', 'descarga', 'busqueda', 'lectura') NOT NULL,
    articulo_id INT,
    detalles JSON,
    fecha_actividad TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (articulo_id) REFERENCES articulos(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_tipo (tipo_actividad),
    INDEX idx_fecha (fecha_actividad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear usuario administrador por defecto
-- Contraseña: admin123 (CAMBIAR INMEDIATAMENTE)
INSERT INTO usuarios (nombre_completo, email, password_hash, institucion, rol_id, estado)
VALUES (
    'Administrador',
    'admin@nasa.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'NASA',
    3,
    'activo'
) ON DUPLICATE KEY UPDATE id=id;

-- Verificar instalación
SELECT 'Tablas de usuarios creadas exitosamente' as status;
SELECT COUNT(*) as total_roles FROM roles;
SELECT COUNT(*) as total_usuarios FROM usuarios;