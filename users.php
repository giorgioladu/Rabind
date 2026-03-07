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
SELECT r.username, r.type, r.notes, r.created_at, GROUP_CONCAT(DISTINCT ra.callingstationid) AS mac, GROUP_CONCAT(DISTINCT rug.groupname) AS user_groups FROM rabind.rabind_users r LEFT JOIN radius.radacct ra ON r.username = ra.username LEFT JOIN radius.radusergroup rug ON r.username = rug.username GROUP BY r.username ORDER BY r.created_at DESC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   FUNZIONE BADGE
========================= */

function userBadgeClass($type){
    return match($type){
        'disabled' => 'bg-danger',
        'active' => 'bg-success',
        default => 'bg-secondary'
    };
}

?>

<div class="d-flex justify-content-between mb-4">
    <h4>👤 Gestione Utenti</h4>

    <button class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#addUserModal">
        + Nuovo Utente
    </button>
</div>


<table class="table table-hover table-bordered align-middle">

<thead class="table-dark">
<tr>
<th>Username / MAC</th>
<th width="80">Gruppo</th>
<th width="80">Stato</th>
<th width="260">Note</th>
<th width="360">Azioni</th>
</tr>
</thead>

<tbody>

<?php foreach($users as $u): ?>

<tr>

<td>
<a href="user_log.php?u=<?= urlencode($u['username']) ?>" class="fw-bold text-decoration-none text-primary">
<?= htmlspecialchars($u['username']) ?>
</a>
&nbsp;&middot;&nbsp;
<small class="text-muted">
 <a href="#" onclick="showUserDetails('<?= htmlspecialchars($u['username']) ?>')">
<?= htmlspecialchars($u['mac']) ?: '-' ?>
 </a>
</small>
</td>


<td>
<?php if(!empty($u['user_groups'])): ?>
    <?php foreach(explode(',', $u['user_groups']) as $g): ?>
        <span class="badge bg-info text-dark">
            <?= htmlspecialchars($g) ?>
        </span>
    <?php endforeach; ?>
<?php else: ?>
    -
<?php endif; ?>
</td>


<td>
<span class="badge <?= userBadgeClass($u['type']) ?>">
<?= htmlspecialchars($u['type']) ?>
</span>
</td>


<td>
<?= htmlspecialchars($u['notes'] ?? '') ?>
</td>


<td>

<!-- Toggle Enable / Disable -->
<a href="user_disable.php?u=<?= urlencode(htmlspecialchars($u['username'])) ?>"
class="btn <?= $u['type']==='disabled' ? 'btn-success' : 'btn-secondary' ?> btn-sm">
<?= $u['type']==='disabled' ? 'Enable' : 'Disable' ?>
</a>

<!-- Reset MAC -->
<a href="user_reset_mac.php?u=<?= urlencode(htmlspecialchars($u['username'])) ?>"
class="btn btn-warning btn-sm">
Reset MAC
</a>

<!-- Reset Traffico -->
<a href="user_reset_traffic.php?u=<?= urlencode(htmlspecialchars($u['username'])) ?>"
class="btn btn-dark btn-sm"
onclick="return confirm('Azzerare traffico consumato?')">
Reset Traffic
</a>

<!-- Delete -->
<a href="user_delete.php?u=<?= urlencode(htmlspecialchars($u['username'])) ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Eliminare definitivamente?')">
Delete
</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>


<!-- =========================
   MODAL DETTAGLI UTENTE
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
   MODAL CREAZIONE UTENTE
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
<input type="number"
name="quantity"
class="form-control"
value="1"
min="1"
max="20">
</div>

<div class="mb-3">
<label>Password</label>
<input type="text"
name="password"
class="form-control"
required>
</div>

<div class="mb-3">
<label>Tipo</label>
<select name="type" class="form-control">
<option value="active">Active</option>
<option value="disabled">Disabled</option>
</select>
</div>

<div class="mb-3">
<label>Profilo Banda</label>
<select name="profile" class="form-control">
<option value="Guest">Guest</option>
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
<button class="btn btn-success">
Salva
</button>
</div>

</form>

</div>
</div>
</div>

<script src="js/user_modal.js"></script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
