<?php
/**
 * Configuración de Base de Datos
 * Le Salon de Lumière - Restaurant Management System
 */

// Cargar variables de entorno
require_once __DIR__ . '/env_loader.php';

// Configuración de Base de Datos
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'crud_proyecto'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Configuración de la Aplicación
define('APP_NAME', env('APP_NAME', 'Le Salon de Lumière'));
define('APP_URL', env('APP_URL', 'http://localhost/PRY_PROYECTO'));
define('BASE_PATH', __DIR__ . '/..');

// Rutas
define('MODELS_PATH', BASE_PATH . '/models');
define('VIEWS_PATH', BASE_PATH . '/views');
define('CONTROLLERS_PATH', BASE_PATH . '/controllers');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('UPLOADS_PATH', BASE_PATH . '/public/uploads');

// Configuración de Sesión
define('SESSION_LIFETIME', 3600); // 1 hora

// Zona Horaria
date_default_timezone_set('America/Guayaquil');

// Modo Debug
define('DEBUG_MODE', env('DEBUG_MODE', true));

// Configuración de Errores
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
