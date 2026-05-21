<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * SNIPPET — Bottone "Kick" sicuro
 * ─────────────────────────────────────────────────────────────────────
 * Da inserire nelle tabelle di dashboard.php e online_users.php
 * al posto del vecchio:
 *
 *   <a href="user_disconnect.php?u=<?= urlencode($u['username']) ?>"
 *      class="btn btn-xs btn-danger">Kick</a>
 *
 * Il form è inline (display:inline) così non rompe il layout della tabella.
 * Il token CSRF viene letto da $_SESSION['csrf_token'] che deve essere
 * già generato da config.php/auth.php all'avvio della sessione.
 *
 * NOTA: il confirm() JavaScript è client-side e non è una misura
 * di sicurezza — serve solo come UX per evitare click accidentali.
 * La vera protezione è il token CSRF server-side.
 */
?>

<!-- ── Bottone Kick (sicuro) ── -->
<form method="post"
      action="user_disconnect.php"
      style="display:inline;"
      onsubmit="return confirm('Disconnettere <?= htmlspecialchars($u[\'username\'], ENT_QUOTES) ?>?');">

    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
    <input type="hidden" name="username"   value="<?= htmlspecialchars($u['username'],          ENT_QUOTES) ?>">

    <button type="submit" class="btn btn-xs btn-danger">
        <i class="bi bi-x-circle"></i> Kick
    </button>

</form>

<?php
/**
 * GESTIONE FEEDBACK in dashboard.php / online_users.php
 * ─────────────────────────────────────────────────────────────────────
 * Aggiungere dopo <?php requireAuth(); ?> e prima dell'HTML,
 * oppure nel template header.php se vuoi i flash globali.
 *
 * Legge il parametro ?success= o ?error= aggiunto dal redirect
 * di user_disconnect.php e mostra un alert Bootstrap.
 */

$feedbackMessages = [
    // successi
    'disconnected'      => ['success', 'Utente disconnesso con successo.'],
    // errori
    'invalid_user'      => ['danger',  'Username non valido.'],
    'user_not_found'    => ['danger',  'Utente non trovato nel sistema.'],
    'no_active_session' => ['warning', 'Nessuna sessione attiva per questo utente.'],
];

$feedbackKey = $_GET['success'] ?? $_GET['error'] ?? null;

if ($feedbackKey && isset($feedbackMessages[$feedbackKey])):
    [$type, $message] = $feedbackMessages[$feedbackKey];
?>
<div class="alert alert-<?= $type ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
