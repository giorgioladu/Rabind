<?php
/**
 * Invia un pacchetto Disconnect-Request (PoD) al NAS
 */
function radiusDisconnect($nas_ip, $nas_port, $secret, $coa_data)
{
    // Sanitizzazione minima per sicurezza shell
    $nas_ip = escapeshellarg($nas_ip);
    $nas_port = escapeshellarg($nas_port);
    $secret = escapeshellarg($secret);

    // Costruiamo il comando radclient
    // Usiamo 'echo' per passare i dati via pipe a radclient
    $cmd = "echo " . escapeshellarg($coa_data) . " | radclient -x $nas_ip:$nas_port disconnect $secret 2>&1";

    // Eseguiamo e catturiamo l'output per debug
    $output = shell_exec($cmd);

    // Opzionale: Loggare l'output in un file se il debug è attivo
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log("RADIUS COA CMD: $cmd");
        error_log("RADIUS COA RESULT: $output");
    }

    return $output;
}
?>
