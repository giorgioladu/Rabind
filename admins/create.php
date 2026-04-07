<?php
require_once __DIR__ . '/../lib/auth.php';
requireAuth();

require_once __DIR__ . '/../lib/db.php';

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/menu.php';

// Messaggi errore
$error = $_GET['error'] ?? null;
?>

<div class="container">
    <h2>Nuovo Amministratore</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="save.php">

        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="form-group">
            <label>Username</label>
            <input type="text"
                   name="username"
                   class="form-control"
                   required
                   maxlength="50">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password"
                   name="password"
                   class="form-control"
                   required>
        </div>

        <br>

        <button type="submit" class="btn btn-primary">Salva</button>
        <a href="index.php" class="btn btn-secondary">Annulla</a>
    </form>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>