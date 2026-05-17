<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';

// 1. Forza metodo POST e valida il CSRF[cite: 6]
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users.php");
    exit;
}

    requireCsrf($_POST['csrf_token'] ?? null); // Funzione in auth.php[cite: 3]


    $baseUsername = trim($_POST['username']);
    $passwordBase = trim($_POST['password']);
    $type = $_POST['type'] ?? 'temporary';
    $typeProfile = $_POST['profile'] ?? 'basic';
    $notes = $_POST['notes'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 1);


        /* ==================================================
           CONTROLLO ESISTENZA UTENTE
        ================================================== */

        // 1. Controlla nel DB RaBind
        $stmtCheck = $appDb->prepare("SELECT id FROM rabind_users WHERE username = ?");
        $stmtCheck->execute([$baseUsername]);

        // 2. Controlla nel DB RADIUS (per sicurezza extra)
        $stmtRadius = $radiusDb->prepare("SELECT id FROM radcheck WHERE username = ?");
        $stmtRadius->execute([$baseUsername]);

        if ($stmtCheck->fetch() || $stmtRadius->fetch()) {
            // Se l'utente esiste in uno dei due DB, blocca tutto
            header("Location: users.php?error=user_exists");
            exit;
        }

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

