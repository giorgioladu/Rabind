<?php
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