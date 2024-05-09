<?php

require('PwAPI.php');

$api = new API(); // Correção no nome da classe

function getUserRoles(){
    global $api;
    $roles = $api->getRoles(1088);

    echo "Lista de personagens da conta:";
        echo "\n - $roles";
}


getUserRoles();

?>
