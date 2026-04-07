<?php
require_once __DIR__ . '/../lib/auth.php';
requireAuth();

require_once __DIR__ . '/../lib/db.php';

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/menu.php';

// Validazione ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=ID non valido");
    exit;
}

$id = (int) $_GET['id'];

// Recupero admin
$stmt = $appDb->prepare("SELECT id, username FROM admins WHERE id = ?");
$stmt->execute([$id]);
$admin = $stmt->fetch();

if (!$admin) {
    header("Location: index.php?error=Admin non trovato");
    exit;
}

// Messaggi errore
$error = $_GET['error'] ?? null;
?>

<div class="container">
    <h2>Modifica Amministratore</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="save.php">
        <input type="hidden" name="id" value="<?= $admin['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="form-group">
            <label>Username</label>
            <input type="text"
                   name="username"
                   class="form-control"
                   required
                   maxlength="50"
                   value="<?= htmlspecialchars($_GET['username'] ?? $admin['username']) ?>">
        </div>

        <div class="form-group">
            <label>Password (lascia vuota per non modificarla)</label>
            <input type="password"
                   name="password"
                   class="form-control">
        </div>

        <br>

        <button type="submit" class="btn btn-primary">Aggiorna</button>
        <a href="index.php" class="btn btn-secondary">Annulla</a>
    </form>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>