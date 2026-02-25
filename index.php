<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';

if (defined('APP_MAINTENANCE') && APP_MAINTENANCE === true) {
    die("RaBind in manutenzione");
}

if (isLoggedIn()) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}

exit;
