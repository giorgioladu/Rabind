<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $baseUsername = trim($_POST['username']);
    $passwordBase = trim($_POST['password']);
    $type = $_POST['type'] ?? 'temporary';
    $typeProfile = $_POST['profile'] ?? 'basic';
    $notes = $_POST['notes'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 1);

    /* Array utenti creati */
    $createdUsers = [];

    if ($baseUsername === '' || $passwordBase === '') {
        die("Dati non validi");
    }

    for ($i = 0; $i < $quantity; $i++) {

        /* =========================
           Username e password
        ========================= */

        if ($i == 0) {
            $username = $baseUsername;
            $password = $passwordBase;
        } else {
            $suffix = str_pad($i, 2, "0", STR_PAD_LEFT);
            $username = $baseUsername . $suffix;
            $password = $passwordBase;
        }

        /* =========================
           RaBind DB
        ========================= */

        $stmt = $appDb->prepare("
            INSERT INTO rabind_users
            (username, type, notes)
            VALUES (?, ?, ?)
        ");

        try {
            $stmt->execute([$username, $type, $notes]);
        } catch (Exception $e) {
            continue;
        }

        /* =========================
           FreeRADIUS DB
        ========================= */

        /* Password */
        $stmt = $radiusDb->prepare("
            INSERT INTO radcheck
            (username, attribute, op, value)
            VALUES (?, 'Cleartext-Password', ':=', ?)
        ");

        $stmt->execute([$username, $password]);

        /* Simultaneous Use */
        $stmt = $radiusDb->prepare("
            INSERT INTO radcheck
            (username, attribute, op, value)
            VALUES (?, 'Simultaneous-Use', ':=', '1')
        ");

        $stmt->execute([$username]);

        $stmt = $radiusDb->prepare("
            INSERT IGNORE INTO radusergroup (username, groupname)
            VALUES (?, ?)  ");

        $stmt->execute([$username, $typeProfile]);

        /* Salviamo per stampa */
        $createdUsers[] = $username;
    }

    /* =========================
       Redirect stampa
    ========================= */

    if (!empty($createdUsers)) {
        header("Location: print_users.php?users=" . urlencode(implode(",", $createdUsers)));
        exit;
    }

    header("Location: users.php");
    exit;
}
