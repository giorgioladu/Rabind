<?php
/**
 * RaBind - Configuration File
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   RADIUS DATABASE
   ========================= */

define('RADIUS_DB_HOST', 'localhost');
define('RADIUS_DB_NAME', 'radius');
define('RADIUS_DB_USER', 'radius');
define('RADIUS_DB_PASS', 'secret_database_radius_password');

/* =========================
   RABIND APPLICATION DATABASE
   ========================= */

define('APP_DB_HOST', 'localhost');
define('APP_DB_NAME', 'rabind');
define('APP_DB_USER', 'rabind');
define('APP_DB_PASS', 'secret_database_rabind_password');

/* =========================
   Application Settings
   ========================= */

define('APP_NAME', 'RaBind');
define('APP_VERSION', '0.3.5');
define('APP_TAGLINE', 'Lightweight RADIUS Control Panel');
define('APP_ENV', 'production');
define('APP_MAINTENANCE', false);
define('APP_DEBUG', false);
define('BASE_URL', '/rabind');


define('RADIUS_NAS_IP','192.168.88.1');
define('RADIUS_NAS_PORT','3799');
define('RADIUS_SECRET','secret_radius_shared_password');

define('SITE_WIFI_SSID', 'Wifi_SSID');
define('SITE_WIFI_PASSWORD', 'wifi_password');

/* =========================
   Security
   ========================= */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
// ini_set('session.cookie_secure', 1); // abilita con HTTPS
// Genera un CSRF Token se non esiste

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
