<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';

if(!isset($_GET['u'])){
    header("Location: users.php");
    exit;
}

$username = $_GET['u'];

try{

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

header("Location: users.php");
exit;
?>