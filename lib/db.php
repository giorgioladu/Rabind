<?php
require_once __DIR__ . '/config.php';

/* =========================
   Radius DB Connection
   ========================= */

try {
    $radiusDb = new PDO(
        "mysql:host=" . RADIUS_DB_HOST . ";dbname=" . RADIUS_DB_NAME . ";charset=utf8mb4",
        RADIUS_DB_USER,
        RADIUS_DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Radius DB connection error.");
}

/* =========================
   RaBind App DB Connection
   ========================= */

try {
    $appDb = new PDO(
        "mysql:host=" . APP_DB_HOST . ";dbname=" . APP_DB_NAME . ";charset=utf8mb4",
        APP_DB_USER,
        APP_DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("App DB connection error.");
}
