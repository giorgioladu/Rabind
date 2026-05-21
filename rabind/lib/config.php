<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * RaBind - Configuration File
 */

if (session_status() === PHP_SESSION_NONE)
 {
    session_start();
      // GENERA IL TOKEN SE NON ESISTE
    if (empty($_SESSION['csrf_token'])) {
             $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/* =========================
   RADIUS DATABASE
   ========================= */

define('RADIUS_DB_HOST', 'localhost');
define('RADIUS_DB_NAME', 'radius');
define('RADIUS_DB_USER', 'radius');
define('RADIUS_DB_PASS', '');

/* =========================
   RABIND APPLICATION DATABASE
   ========================= */

define('APP_DB_HOST', 'localhost');
define('APP_DB_NAME', 'rabind');
define('APP_DB_USER', 'rabind');
define('APP_DB_PASS', '');

/* =========================
   Application Settings
   ========================= */

define('APP_NAME', 'RaBind');
define('APP_VERSION', '0.6.0');
define('APP_TAGLINE', 'Lightweight RADIUS Control Panel');
define('APP_ENV', 'production');
define('APP_MAINTENANCE', false);
define('APP_DEBUG', false);
define('BASE_URL', '/rabind/');


define('RADIUS_NAS_IP','192.168.88.1');
define('RADIUS_NAS_PORT','3799');
define('RADIUS_SECRET','');

define('SITE_WIFI_SSID', '');
define('SITE_WIFI_PASSWORD', '');

/* =========================
   Security
   ========================= */
define('SESSION_TIMEOUT', 1800); // 30 minuti
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
// ini_set('session.cookie_secure', 1); // abilita con HTTPS

//debug
//error_reporting(E_ALL);
//ini_set('display_errors', 'On');

?>