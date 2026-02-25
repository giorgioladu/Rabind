<?php

function radiusDisconnect($nas_ip, $nas_port, $secret, $coa)
{
    $cmd = "echo \"$coa\" | radclient -x $nas_ip:$nas_port disconnect $secret > /dev/null 2>&1 & ";

    shell_exec($cmd);
}

?>
