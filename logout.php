<?php
require_once __DIR__ . '/lib/config.php';

session_start();

/* Distrugge tutte le variabili sessione */
$_SESSION = [];

/* Distrugge il cookie di sessione */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* Distrugge la sessione */
session_destroy();

/* Redirect login */
header("Location: " . BASE_URL . "/login.php");
exit;
?>

