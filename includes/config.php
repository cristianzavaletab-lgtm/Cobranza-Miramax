<?php
/**
 * Archivo de configuración unificado para la aplicación
 * Carga variables de entorno automáticamente
 */

// Cargar archivo .env si existe (desarrollo local)
if (file_exists(__DIR__ . '/../.env')) {
    $env_file = file_get_contents(__DIR__ . '/../.env');
    $env_lines = explode("\n", $env_file);
    
    foreach ($env_lines as $line) {
        $line = trim($line);
        // Ignorar comentarios y líneas vacías
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Parsear línea KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // No sobrescribir variables ya definidas
            if (!getenv($key)) {
                putenv("{$key}={$value}");
            }
        }
    }
}

// Configuración de la aplicación
define('APP_NAME', getenv('APP_NAME') ?: 'MIRAMAX');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', getenv('APP_DEBUG') === 'true' ? true : false);

// Configuración de base de datos
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'sistema_cobranza');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');

// Rutas
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH . '/uploads/');
define('UPLOAD_MAX_SIZE', 5242880); // 5MB

// Seguridad
define('SESSION_TIMEOUT', getenv('SESSION_TIMEOUT') ?: 1800);

// Configurar session
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
session_set_cookie_params(['lifetime' => SESSION_TIMEOUT]);

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/error.log');
}

// Asegurar que la carpeta de logs existe
if (!is_dir(BASE_PATH . '/logs')) {
    mkdir(BASE_PATH . '/logs', 0755, true);
}

?>
