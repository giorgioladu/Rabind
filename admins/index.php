<?php
require_once __DIR__ . '/../lib/auth.php';
requireAuth();

require_once __DIR__ . '/../lib/db.php';

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/menu.php';

// Messaggi feedback
$message = $_GET['msg'] ?? null;
$error   = $_GET['error'] ?? null;

// Recupero admin loggato (adatta al tuo auth)
$currentAdminId = $_SESSION['admin_id'] ?? null;

// DELETE (via GET per ora, poi lo miglioriamo)
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    if ($id === $currentAdminId) {
        header("Location: index.php?error=Non puoi eliminare te stesso");
        exit;
    }

    $stmt = $appDb->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: index.php?msg=Admin eliminato");
    exit;
}

// LISTA
$stmt = $appDb->query("SELECT id, username, created_at FROM admins ORDER BY id DESC");
$admins = $stmt->fetchAll();
?>

<div class="container">
    <h2>Amministratori</h2>

    <a href="create.php" class="btn btn-success">+ Nuovo Admin</a>

    <br><br>

    <!-- Messaggi -->
    <?php if ($message): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Creato</th>
                <th width="180">Azioni</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($admins)): ?>
            <tr>
                <td colspan="4">Nessun amministratore presente</td>
            </tr>
        <?php else: ?>
            <?php foreach ($admins as $a): ?>
                <tr>
                    <td><?= $a['id'] ?></td>
                    <td><?= htmlspecialchars($a['username']) ?></td>
                    <td><?= $a['created_at'] ?></td>
                    <td>
                        <a href="edit.php?id=<?= $a['id'] ?>" class="btn btn-primary btn-sm">
                            Modifica
                        </a>

                        <?php if ($a['id'] != $currentAdminId): ?>
                           <form method="post" action="delete.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                 <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <button type="submit"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Eliminare questo admin?')">
                                    Elimina
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">Tu</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>