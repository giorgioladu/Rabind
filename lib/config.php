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
define('RADIUS_DB_USER', 'radius_user');
define('RADIUS_DB_PASS', 'radius_password');

/* =========================
   RABIND APPLICATION DATABASE
   ========================= */

define('APP_DB_HOST', 'localhost');
define('APP_DB_NAME', 'rabind');
define('APP_DB_USER', 'rabind_user');
define('APP_DB_PASS', 'rabind_password');

/* =========================
   Application Settings
   ========================= */

define('APP_NAME', 'RaBind');
define('APP_VERSION', '0.1.0 BETA');
define('APP_TAGLINE', 'Lightweight RADIUS Control Panel');
define('APP_ENV', 'production');
define('APP_MAINTENANCE', false);
define('APP_DEBUG', false);

define('RADIUS_NAS_IP','192.168.lan.ip');
define('RADIUS_NAS_PORT','3799');
define('RADIUS_SECRET','secretradius');

define('SITE_WIFI_SSID', 'Nome_site_WiFi');
define('SITE_WIFI_PASSWORD', 'Password_Wifi');

/* =========================
   Security
   ========================= */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
// ini_set('session.cookie_secure', 1); // abilita con HTTPS
