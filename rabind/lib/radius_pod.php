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
 * RaBind - Pure PHP RADIUS PoD (Disconnect-Request) Library
 * RFC 5176 / RFC 2865
 */

function generateRadiusPodPacket($coa_data, $secret)
{
    // 1. Identificativi dei tipi di attributo RADIUS standard
    $attr_types = [
        'user-name'         => 1,  // String
        'framed-ip-address' => 8,  // IP Address (4 bytes)
        'acct-session-id'   => 44, // String
    ];

    // 2. Parser della stringa $coa_data (es. "User-Name=test, Acct-Session-Id=123")
    $attributes_bin = '';
    $pairs = explode(',', $coa_data);

    foreach ($pairs as $pair) {
        if (strpos($pair, '=') === false) continue;

        list($key, $val) = explode('=', $pair, 2);
        $key = strtolower(trim($key));
        $val = trim($val);

        if (!isset($attr_types[$key])) {
            continue; // Salta attributi non supportati per sicurezza
        }

        $type = $attr_types[$key];
        $value_bin = '';

        // Codifica il valore in base al tipo di dato
        if ($type === 8) {
            // Framed-IP-Address: converte l'IP in formato binario a 4 byte
            $value_bin = inet_pton($val);
            if ($value_bin === false) continue; // IP non valido, salta
        } else {
            // User-Name e Acct-Session-Id sono stringhe testo
            $value_bin = $val;
        }

        $length = strlen($value_bin) + 2; // Tipo(1) + Lunghezza(1) + Valore
        $attributes_bin .= pack('CC', $type, $length) . $value_bin;
    }

    // 3. Costruzione dell'Header RADIUS
    $code = 40; // Disconnect-Request
    $identifier = rand(0, 255); // Identificativo univoco della richiesta
    $length = 20 + strlen($attributes_bin); // Header(20) + Attributi

    // Request Authenticator iniziale per PoD: 16 byte a zero
    $authenticator_zero = str_repeat("\x00", 16);

    // Costruiamo il pacchetto parziale per calcolare l'MD5
    $partial_packet = pack('CCn', $code, $identifier, $length) . $authenticator_zero . $attributes_bin;

    // Calcolo del Request Authenticator (MD5 di: Packet + Secret)
    $authenticator = md5($partial_packet . $secret, true);

    // Pacchetto finale pronto da spedire
    return pack('CCn', $code, $identifier, $length) . $authenticator . $attributes_bin;
}

/**
 * Invia il pacchetto UDP al NAS e attende la risposta
 */
function sendRadiusPod($nas_ip, $nas_port, $secret, $coa_data)
{
    $packet = generateRadiusPodPacket($coa_data, $secret);

    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if (!$socket) {
        return "ERROR: Impossibile creare il socket UDP";
    }

    // Timeout di 3 secondi per la ricezione (evita che il PHP rimanga appeso se il NAS non risponde)
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 3, 'usec' => 0]);

    // Invio del pacchetto
    $result = @socket_sendto($socket, $packet, strlen($packet), 0, $nas_ip, $nas_port);
    if ($result === false) {
        socket_close($socket);
        return "ERROR: Invio UDP fallito";
    }

    // Lettura del pacchetto di risposta (ACK o NAK)
    $buf = '';
    $from = '';
    $port = 0;

    $recv_res = @socket_recvfrom($socket, $buf, 1024, 0, $from, $port);
    socket_close($socket);

    if ($recv_res === false) {
        return "TIMEOUT: Il NAS non ha risposto entro 3 secondi";
    }

    if (strlen($buf) < 20) {
        return "ERROR: Risposta dal NAS corrotta o troppo corta";
    }

    // Unpack del codice di risposta RADIUS
    $res_data = unpack('Ccode', $buf);
    $res_code = $res_data['code'];

    switch ($res_code) {
        case 41:
            return "Disconnect-ACK";
        case 42:
            return "Disconnect-NAK";
        default:
            return "UNKNOWN RADIUS CODE ($res_code)";
    }
}