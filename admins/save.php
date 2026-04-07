<?php
require_once __DIR__ . '/../lib/auth.php';
requireAuth();
protectWriteOperations();
require_once __DIR__ . '/../lib/db.php';

// Recupero dati
$id       = $_POST['id'] ?? null;
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// =========================
// VALIDAZIONE
// =========================

if ($username === '') {
    header("Location: create.php?error=Username obbligatorio&username=" . urlencode($username));
    exit;
}

// Se INSERT → password obbligatoria
if (!$id && $password === '') {
    header("Location: create.php?error=Password obbligatoria&username=" . urlencode($username));
    exit;
}

// =========================
// CHECK USERNAME DUPLICATO
// =========================

if ($id) {
    $stmt = $appDb->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
    $stmt->execute([$username, $id]);
} else {
    $stmt = $appDb->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
}

if ($stmt->fetch()) {
    $redirect = $id ? "edit.php?id=$id" : "create.php";
    header("Location: $redirect?error=Username già esistente&username=" . urlencode($username));
    exit;
}

// =========================
// INSERT / UPDATE
// =========================

try {

    if ($id) {
        // UPDATE

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $appDb->prepare("
                UPDATE admins
                SET username = ?, password = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $hash, $id]);

        } else {
            $stmt = $appDb->prepare("
                UPDATE admins
                SET username = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $id]);
        }

        header("Location: index.php?msg=Admin aggiornato");

    } else {
        // INSERT

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $appDb->prepare("
            INSERT INTO admins (username, password)
            VALUES (?, ?)
        ");
        $stmt->execute([$username, $hash]);

        header("Location: index.php?msg=Admin creato");
    }

    exit;

} catch (Exception $e) {

    // fallback errore generico
    $redirect = $id ? "edit.php?id=$id" : "create.php";

    header("Location: $redirect?error=Errore durante il salvataggio");
    exit;
}
