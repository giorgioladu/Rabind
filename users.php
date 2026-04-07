<?php

require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/menu.php';

/* =========================
   LISTA UTENTI
========================= */

// Recupero dati con join tra App DB e Radius DB
$stmt = $appDb->query("
SELECT 
    r.username, 
    r.type, 
    r.notes, 
    r.created_at, 
    GROUP_CONCAT(DISTINCT ra.callingstationid) AS mac, 
    GROUP_CONCAT(DISTINCT rug.groupname) AS user_groups 
FROM rabind.rabind_users r 
LEFT JOIN radius.radacct ra ON r.username = ra.username 
LEFT JOIN radius.radusergroup rug ON r.username = rug.username 
GROUP BY r.username 
ORDER BY r.created_at DESC
");

$allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Suddivisione utenti per stato
$activeUsers = array_filter($allUsers, fn($u) => $u['type'] === 'active');
$disabledUsers = array_filter($allUsers, fn($u) => $u['type'] === 'disabled');

/* =========================
   FUNZIONE BADGE & TABELLA
========================= */

function userBadgeClass($type){
    return match($type){
        'disabled' => 'bg-danger',
        'active' => 'bg-success',
        default => 'bg-secondary'
    };
}

/**
 * Funzione per renderizzare la tabella utenti in modo uniforme
 */
function renderUserTable($users) {
    if (empty($users)) {
        echo '<div class="p-5 text-center text-muted">Nessun utente trovato in questa categoria.</div>';
        return;
    }
    ?>
    <table class="table table-hover align-middle mb-0">
        <thead class="table-dark">
            <tr>
				<th>Username / MAC</th>
                <th >Gruppo</th>
				<th >Stato</th>
				<th width="260">Note</th>
				<th class="text-end" width="360">Azioni</th>
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
    <?php
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-people"></i> Gestione Utenti</h4>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus"></i> Nuovo Utente
        </button>
    </div>

    <ul class="nav nav-tabs mb-3" id="userTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fw-bold" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-pane" type="button" role="tab">
                Attivi <span class="badge bg-success ms-1"><?= count($activeUsers) ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold text-danger" id="disabled-tab" data-bs-toggle="tab" data-bs-target="#disabled-pane" type="button" role="tab">
                Disabilitati <span class="badge bg-danger ms-1"><?= count($disabledUsers) ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="userTabsContent">
        <div class="tab-pane fade show active" id="active-pane" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <?php renderUserTable($activeUsers); ?>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="disabled-pane" role="tabpanel">
            <div class="card shadow-sm border-0 border-top border-danger border-3">
                <div class="table-responsive">
                    <?php renderUserTable($disabledUsers); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addUserModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus"></i> Nuovo Utente</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="user_create.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username</label>
                        <input name="username" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Numero utenti</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" max="20">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Password</label>
                            <input type="text" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tipo</label>
                            <select name="type" class="form-control">
                                <option value="active">Active</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Profilo Banda</label>
                            <select name="profile" class="form-control">
                                <option value="Guest">Guest</option>
                                <option value="basic">Basic</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Note</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Salva Utente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
