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
 * Invia un pacchetto Disconnect-Request (PoD) al NAS
 * Refactored: Pure PHP (No shell_exec / No radclient)
 */

require_once __DIR__ . '/radius_pod.php';

function radiusDisconnect($nas_ip, $nas_port, $secret, $coa_data)
{
    // Sanitizzazione e validazione rigorosa dei parametri di rete
    $nas_ip = filter_var(trim($nas_ip), FILTER_VALIDATE_IP);
    $nas_port = filter_var($nas_port, FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1, "max_range" => 65535]
    ]);

    // Stringhe pulite
    $secret = (string)$secret;
    $coa_data = (string)$coa_data;

    // Se i parametri di base falliscono la validazione, blocca subito l'esecuzione
    if (!$nas_ip || !$nas_port || empty($secret) || empty($coa_data)) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("RADIUS COA ERROR: Parametri di chiamata non validi o malevoli.");
        }
        return "ERROR: Parametri non validi";
    }

    // Eseguiamo la disconnessione tramite la libreria PHP nativa
    $output = sendRadiusPod($nas_ip, $nas_port, $secret, $coa_data);

    // Log dell'output in un file se il debug è attivo
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log("RADIUS COA TARGET: $nas_ip:$nas_port");
        error_log("RADIUS COA DATA: $coa_data");
        error_log("RADIUS COA RESULT: $output");
    }

    return $output;
}