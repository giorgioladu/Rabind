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

// Definiamo dopo quanto tempo una sessione è considerata "stale" (es. 2 ore)
$threshold = "2 HOUR";

try {
    // Chiudiamo le sessioni che non hanno stoptime e sono troppo vecchie
    // Usiamo acctupdatetime se presente, altrimenti acctstarttime
    $sql = "UPDATE radacct
            SET acctstoptime = NOW(),
                acctterminatecause = 'Stale-Cleanup'
            WHERE acctstoptime IS NULL
            AND acctstarttime < (NOW() - INTERVAL $threshold)";

    $stmt = $radiusDb->prepare($sql);
    $stmt->execute();
    $count = $stmt->rowCount();

    header("Location: online_users.php?msg=cleaned&count=$count");
} catch (Exception $e) {
    die("Errore durante la pulizia: " . $e->getMessage());
}

?>