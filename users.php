<?php

require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/menu.php';


/* =========================
   LISTA UTENTI
========================= */

$stmt = $appDb->query("
SELECT r.username,
       r.type,
       r.notes,
       r.created_at,
       GROUP_CONCAT(DISTINCT rad.value) AS mac
FROM rabind_users r
LEFT JOIN radius.radcheck rad
ON r.username = rad.username
AND rad.attribute = 'Calling-Station-Id'
GROUP BY r.username
");

$users = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between mb-3">
    <h4>Gestione Utenti</h4>

    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
        + Nuovo Utente
    </button>
</div>

<table class="table table-hover shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>Username / MAC</th>
            <th>Tipo</th>
            <th>Note</th>
            <th>Azioni</th>
        </tr>
    </thead>

    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td>
                <a href="#"
                onclick="showUserDetails('<?= $u['username'] ?>')">
                <?= htmlspecialchars($u['username']) ?>
                </a>
                <br>
                <small class="text-muted">
                <?= $u['mac'] ?: '-' ?>
                </small>
            </td>

            <td>
                <span class="badge bg-secondary">
                    <?php echo $u['type']; ?>
                </span>
            </td>

            <td><?php echo htmlspecialchars($u['notes']); ?></td>

                <td>

                <?php if($u['type'] === 'disabled'): ?>

                <span class="btn btn-secondary btn-sm disabled">
                Disabled
                </span>

                <?php else: ?>

                <a href="user_disable.php?u=<?= urlencode($u['username']) ?>"
                class="btn btn-warning btn-sm">
                Disable
                </a>

                <?php endif; ?>


                <a href="user_reset_mac.php?u=<?= urlencode($u['username']) ?>"
                class="btn btn-warning btn-sm">
                Reset MAC
                </a>


                <a href="user_delete.php?u=<?= urlencode($u['username']) ?>"
                class="btn btn-danger btn-sm"
                onclick="return confirm('Delete user permanently?')">
                Delete
                </a>

                </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- =========================
   DATI UTENTE
========================= -->
<div class="modal fade" id="userModal">
<div class="modal-dialog modal-lg">
<div class="modal-content">

<div class="modal-header">
<h5 class="modal-title">User Details</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<div id="userDetailsContent"></div>

</div>

</div>
</div>
</div>

<!-- =========================
   MODALE AGGIUNTA UTENTE
========================= -->

<div class="modal fade" id="addUserModal">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header">
<h5 class="modal-title">Nuovo Utente</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<form method="post" action="user_create.php">

<div class="modal-body">

<div class="mb-3">
<label>Username</label>
<input name="username" class="form-control" required>
</div>

<div class="mb-3">
<label>Numero utenti</label>
<input type="number" name="quantity" class="form-control" value="1" min="1" max="10">
</div>

<div class="mb-3">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<div class="mb-3">
<label>Tipo</label>
<select name="type" class="form-control">
<option value="fixed">Fisso</option>
<option value="temporary">Temporaneo</option>
</select>
</div>

<div class="mb-3">
<label>Profilo Banda</label>
<select name="profile" class="form-control">
<option value="basic">Basic</option>
<option value="medium">Medium</option>
<option value="high">High</option>
</select>
</div>

<div class="mb-3">
<label>Note</label>
<textarea name="notes" class="form-control"></textarea>
</div>

</div>

<div class="modal-footer">
<button class="btn btn-success">Salva</button>
</div>

</form>

</div>
</div>
</div>


 <script src="js/user_modal.js"></script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
