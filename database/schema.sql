-- =====================================================
-- Schema de Base de Datos - Sistema MIRAMAX
-- =====================================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS sistema_cobranza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_cobranza;

-- =====================================================
-- Tabla de Clientes
-- =====================================================
CREATE TABLE IF NOT EXISTS clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dni VARCHAR(8) UNIQUE NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(15),
    deuda_actual DECIMAL(10,2) DEFAULT 0.00,
    estado_pago ENUM('al_día', 'vencido', 'parcial') DEFAULT 'al_día',
    fecha_vencimiento DATE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_dni (dni),
    INDEX idx_estado (estado_pago),
    INDEX idx_vencimiento (fecha_vencimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Tabla de Pagos
-- =====================================================
CREATE TABLE IF NOT EXISTS pagos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('transferencia', 'plin', 'yape', 'otros') NOT NULL,
    numero_operacion VARCHAR(50) UNIQUE,
    comprobante VARCHAR(255),
    estado ENUM('pendiente', 'verificado', 'rechazado') DEFAULT 'pendiente',
    descripcion TEXT,
    fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_verificacion TIMESTAMP NULL,
    verificado_por INT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_cliente (cliente_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Tabla de Usuarios/Administradores
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nombres VARCHAR(100),
    email VARCHAR(100),
    rol ENUM('admin', 'cobrador', 'auditor') DEFAULT 'cobrador',
    activo BOOLEAN DEFAULT TRUE,
    ultimo_acceso TIMESTAMP NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Tabla de Auditoría
-- =====================================================
CREATE TABLE IF NOT EXISTS auditoria (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    accion VARCHAR(100),
    tabla_afectada VARCHAR(50),
    registro_id INT,
    datos_anteriores JSON,
    datos_nuevos JSON,
    ip_address VARCHAR(45),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Tabla de Configuración
-- =====================================================
CREATE TABLE IF NOT EXISTS configuracion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insertar datos iniciales
-- =====================================================

-- Usuario administrador (password: admin123)
INSERT INTO usuarios (usuario, password_hash, nombres, email, rol, activo) 
VALUES ('admin', '$2y$10$eImiTXuWVxfaHNYY0iS8/OPST9/PgBkqquzi.Oy1D3lH1JNdiYgju', 'Administrador', 'admin@miramax.local', 'admin', TRUE)
ON DUPLICATE KEY UPDATE id=id;

-- Usuario cobrador (password: cobrador123)
INSERT INTO usuarios (usuario, password_hash, nombres, email, rol, activo) 
VALUES ('cobrador', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36ajwFUm', 'Cobrador', 'cobrador@miramax.local', 'cobrador', TRUE)
ON DUPLICATE KEY UPDATE id=id;

-- Configuraciones iniciales
INSERT INTO configuracion (clave, valor, tipo) VALUES 
('nombre_empresa', 'MIRAMAX', 'string'),
('email_contacto', 'contacto@miramax.local', 'string'),
('telefono_contacto', '918762620', 'string'),
('activo', 'true', 'boolean')
ON DUPLICATE KEY UPDATE valor=VALUES(valor);

-- =====================================================
-- Vistas útiles
-- =====================================================

-- Vista de clientes con resumen de pagos
CREATE OR REPLACE VIEW v_clientes_estado AS
SELECT 
    c.id,
    c.dni,
    CONCAT(c.nombres, ' ', c.apellidos) as nombre_completo,
    c.email,
    c.telefono,
    c.deuda_actual,
    c.estado_pago,
    c.fecha_vencimiento,
    COUNT(p.id) as total_pagos,
    SUM(CASE WHEN p.estado = 'verificado' THEN p.monto ELSE 0 END) as pagos_verificados,
    SUM(CASE WHEN p.estado = 'pendiente' THEN p.monto ELSE 0 END) as pagos_pendientes
FROM clientes c
LEFT JOIN pagos p ON c.id = p.cliente_id
GROUP BY c.id, c.dni, c.nombres, c.apellidos, c.email, c.telefono, c.deuda_actual, c.estado_pago, c.fecha_vencimiento;

-- Vista de resumen de estadísticas
CREATE OR REPLACE VIEW v_estadisticas_general AS
SELECT 
    (SELECT COUNT(*) FROM clientes WHERE activo = TRUE) as total_clientes,
    (SELECT COUNT(*) FROM clientes WHERE estado_pago IN ('vencido', 'parcial')) as clientes_con_deuda,
    (SELECT SUM(deuda_actual) FROM clientes WHERE activo = TRUE) as deuda_total,
    (SELECT COUNT(*) FROM pagos WHERE estado = 'pendiente') as pagos_pendientes,
    (SELECT COUNT(*) FROM pagos WHERE estado = 'verificado' AND DATE(fecha_pago) = CURDATE()) as pagos_hoy;

-- =====================================================
-- Permisos y privilegios
-- =====================================================

-- Crear usuario específico para la aplicación (opcional)
-- GRANT ALL PRIVILEGES ON sistema_cobranza.* TO 'app_user'@'localhost' IDENTIFIED BY 'app_password_secure';
-- FLUSH PRIVILEGES;
