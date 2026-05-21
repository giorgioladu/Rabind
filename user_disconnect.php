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
 * user_disconnect.php
 * Esegue il CoA Disconnect-Request verso MikroTik per un utente attivo.
 *
 * Sicurezza implementata:
 *  1. requireAuth()         — solo admin autenticati
 *  2. Solo POST             — niente kick via link GET/URL diretta
 *  3. CSRF token            — usa requireCsrf() da auth.php
 *  4. Validazione username  — solo caratteri ammessi (alphanumerico + . - _)
 *  5. Verifica esistenza    — l'utente deve esistere in rabind_users
 *  6. Sessione attiva       — deve esserci una riga aperta in radacct
 *  7. Nessun dato sensibile — il CoA fallisce silenziosamente con redirect
 */

require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/coa.php';

/* ─── 1. Solo POST ──────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

/* ─── 2. CSRF ───────────────────────────────────────────────────────── */
requireCsrf($_POST['csrf_token'] ?? null);

/* ─── 3. Validazione username ───────────────────────────────────────── */
$username = trim($_POST['username'] ?? '');

// Ammessi: lettere, cifre, punto, trattino, underscore — max 64 caratteri
// Stessa regex che dovresti usare anche in user_create.php
if ($username === '' || !preg_match('/^[a-zA-Z0-9._\-]{1,64}$/', $username)) {
    header("Location: dashboard.php?error=invalid_user");
    exit;
}

/* ─── 4. L'utente deve esistere in rabind_users (DB locale) ────────── */
$stmt = $appDb->prepare("SELECT id FROM rabind_users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
if (!$stmt->fetch()) {
    // Username non presente nel nostro DB: potrebbe essere un tentativo
    // di kickare un utente RADIUS non gestito da Rabind
    header("Location: dashboard.php?error=user_not_found");
    exit;
}

/* ─── 5. Sessione attiva in radacct ─────────────────────────────────── */
$stmt = $radiusDb->prepare("
    SELECT acctsessionid, framedipaddress
    FROM   radacct
    WHERE  username     = ?
      AND  acctstoptime IS NULL
    LIMIT  1
");
$stmt->execute([$username]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    // Nessuna sessione aperta: niente CoA da inviare
    header("Location: dashboard.php?error=no_active_session");
    exit;
}

/* ─── 6. CoA Disconnect-Request ─────────────────────────────────────── */
$coa = sprintf(
    "User-Name=%s, Acct-Session-Id=%s, Framed-IP-Address=%s",
    $username,
    $session['acctsessionid'],
    $session['framedipaddress']
);

radiusDisconnect(
    RADIUS_NAS_IP,
    RADIUS_NAS_PORT,
    RADIUS_SECRET,
    $coa
);

/* ─── 7. Redirect con feedback ──────────────────────────────────────── */
header("Location: dashboard.php?success=disconnected");
exit;