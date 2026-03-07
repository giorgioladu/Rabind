<?php

require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/templates/header.php';

/* DELETE */
if (isset($_GET['delete'])) {
    $stmt = $radiusDb->prepare("DELETE FROM radgroupcheck WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: radgroupcheck.php");
    exit;
}

/* INSERT / UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id        = $_POST['id'] ?? null;
    $groupname = $_POST['groupname'];
    $attribute = $_POST['attribute'];
    $op        = $_POST['op'];
    $value     = $_POST['value'];

    if ($id) {
        $stmt = $radiusDb->prepare("
            UPDATE radgroupcheck
            SET groupname = ?, attribute = ?, op = ?, value = ?
            WHERE id = ?
        ");
        $stmt->execute([$groupname, $attribute, $op, $value, $id]);
    } else {
        $stmt = $radiusDb->prepare("
            INSERT INTO radgroupcheck (groupname, attribute, op, value)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$groupname, $attribute, $op, $value]);
    }

    header("Location: radgroupcheck.php");
    exit;
}

/* LOAD DATA */
$stmt = $radiusDb->query("
    SELECT * 
    FROM radgroupcheck
    ORDER BY groupname ASC, attribute ASC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Raggruppa per groupname */
$groups = [];
foreach ($rows as $r) {
    $groups[$r['groupname']][] = $r;
}
?>

<?php include "header.php"; ?>

<div class="container-fluid mt-4">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">radgroupcheck</h4>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#editModal">
        + Nuovo Attributo
    </button>
</div>

<?php foreach ($groups as $groupName => $items): ?>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-light fw-bold">
        <?= htmlspecialchars($groupName) ?>
        <span class="badge bg-secondary float-end">
            <?= count($items) ?> attributi
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Attribute</th>
                    <th>Op</th>
                    <th>Value</th>
                    <th width="150">Azioni</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['attribute']) ?></td>
                    <td>
                        <span class="badge bg-info text-dark">
                            <?= $r['op'] ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($r['value']) ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm"
                            onclick='openEdit(<?= json_encode($r) ?>)'>
                            Edit
                        </button>
                        <a href="?delete=<?= $r['id'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Eliminare attributo?')">
                           Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endforeach; ?>

</div>

<!-- MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Regola Group Check</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="form-id">
        
        <div class="mb-3 text-center bg-light p-2 border rounded">
            <small class="d-block mb-2 fw-bold text-muted">Quick Check Controls:</small>
            <div class="d-flex flex-wrap gap-1 justify-content-center">
                <button type="button" class="btn btn-xs btn-outline-success" onclick="setPreset('Simultaneous-Use', ':=', '1')">1 Solo Disp.</button>
                <button type="button" class="btn btn-xs btn-outline-danger" onclick="setPreset('Expiration', ':=', 'Jan 01 2026 00:00:00')">Data Scadenza</button>
                <button type="button" class="btn btn-xs btn-outline-dark" onclick="setPreset('Auth-Type', ':=', 'Reject')">Blocca Gruppo</button>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold">Nome Gruppo</label>
            <input type="text" name="groupname" id="form-group" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Attributo</label>
            <input type="text" name="attribute" id="form-attribute" class="form-control" list="check-attrs" required>
            <datalist id="check-attrs">
                <option value="Simultaneous-Use">Numero max dispositivi</option>
                <option value="Expiration">Data Fine Validità</option>
                <option value="Max-All-Session">Tempo totale acquistato (sec)</option>
                <option value="Auth-Type">Tipo Autenticazione (es. Reject)</option>
            </datalist>
        </div>

        <div class="row">
            <div class="col-4">
                <label class="form-label fw-bold">Operatore</label>
                <select name="op" id="form-op" class="form-select">
                    <option value="==">== (Controlla)</option>
                    <option value=":=">:= (Sovrascrivi)</option>
                    <option value="!=">!= (Diverso)</option>
                </select>
            </div>
            <div class="col-8">
                <label class="form-label fw-bold">Valore</label>
                <input type="text" name="value" id="form-value" class="form-control" required>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success w-100">Applica Regola</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(data){
    document.getElementById('form-id').value = data.id;
    document.getElementById('form-group').value = data.groupname;
    document.getElementById('form-attribute').value = data.attribute;
    document.getElementById('form-op').value = data.op;
    document.getElementById('form-value').value = data.value;

    var modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

function applyPreset(attr, op, val) {
    document.getElementById('form-attribute').value = attr;
    document.getElementById('form-op').value = op;
    document.getElementById('form-value').value = val;
    
    // Piccolo feedback visivo
    const input = document.getElementById('form-attribute');
    input.classList.add('is-valid');
    setTimeout(() => input.classList.remove('is-valid'), 1000);
}

// Gestione bottone cancella
document.getElementById('clearAttr').addEventListener('click', function() {
    document.getElementById('form-attribute').value = '';
    document.getElementById('form-attribute').focus();
});

function setPreset(attr, op, val) {
    document.getElementById('form-attribute').value = attr;
    document.getElementById('form-op').value = op;
    document.getElementById('form-value').value = val;
    
    // Evidenzia brevemente i campi per confermare l'inserimento
    const fields = ['form-attribute', 'form-value'];
    fields.forEach(f => {
        let el = document.getElementById(f);
        el.style.backgroundColor = '#e8f4fd';
        setTimeout(() => el.style.backgroundColor = '', 500);
    });
}
</script>

<?php include "footer.php"; ?>
