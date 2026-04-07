<?php
require_once __DIR__ . '/../lib/auth.php';
requireAuth();
protectWriteOperations();
require_once __DIR__ . '/../lib/db.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Validazione ID
$id = $_POST['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: index.php?error=ID non valido");
    exit;
}

$id = (int) $id;

// Admin corrente
$currentAdminId = $_SESSION['admin_id'] ?? null;

// Blocco self-delete
if ($id === $currentAdminId) {
    header("Location: index.php?error=Non puoi eliminare te stesso");
    exit;
}

// DELETE
$stmt = $appDb->prepare("DELETE FROM admins WHERE id = ?");
$stmt->execute([$id]);

header("Location: index.php?msg=Admin eliminato");
exit;
?>