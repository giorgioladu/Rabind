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

require_once __DIR__ . '/lib/auth.php';
requireAuth();
require_once __DIR__ . '/lib/db.php';
// Includi la logica CoA per disconnettere l'utente subito dopo il reset
require_once __DIR__ . '/lib/coa.php';

/* =========================
   VALIDAZIONE INPUT
========================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users.php");
    exit;
}
requireCsrf($_POST['csrf_token'] ?? null); // Funzione in auth.php[cite: 3]

$username = $_POST['u'] ?? '';
if (!$username) {
    header("Location: users.php?error=missing_user");
    exit;
}

try{

        $stmt = $radiusDb->prepare("SELECT acctsessionid, framedipaddress FROM radacct WHERE username = ? AND acctstoptime IS NULL LIMIT 1");
        $stmt->execute([$username]);
        $session = $stmt->fetch();

        if ($session) {
            $coa = sprintf("User-Name=%s, Acct-Session-Id=%s, Framed-IP-Address=%s", $username, $session['acctsessionid'], $session['framedipaddress']);
            radiusDisconnect(RADIUS_NAS_IP, RADIUS_NAS_PORT, RADIUS_SECRET, $coa);
        }

    /* Radius tables */
    $tables = [
        "radcheck",
        "radreply",
        "radusergroup",
        "radacct"
    ];

    foreach($tables as $table){

        $stmt = $radiusDb->prepare("
            DELETE FROM $table
            WHERE username = ?
        ");

        $stmt->execute([$username]);
    }

    /* RaBind table */
    $stmt = $appDb->prepare("
        DELETE FROM rabind_users
        WHERE username = ?
    ");

    $stmt->execute([$username]);

}
catch(Exception $e){
    die("Delete error");
}

header("Location: users.php?success=delete");
exit;
?>
