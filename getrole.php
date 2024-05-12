<?php

require('PwAPI.php');

function getRolesForAccount($account_id)
{
    $api = new API();
    $roles = $api->getRoles($account_id);
    $role_names = array();
    $role_classes = array(); // Inicializa um array para armazenar os nomes das classes

    // Itera sobre os papéis para obter seus nomes e classes associadas
    foreach ($roles['roles'] as $role) {
        $role_names[] = $role['name'];
        $role_id[] = $role['id'];
    }
    return [$role_names, $role_id]; // Retorna um array contendo os nomes dos papéis e suas classes associadas
}

// Exemplo de uso: listar todos os papéis associados à conta 1088
$account_id = 1088;
list($roles_for_account, $role_id) = getRolesForAccount($account_id);
if (!empty($roles_for_account)) {
    echo "Papéis associados à conta " . $account_id . ":\n";
    for ($i = 0; $i < count($roles_for_account); $i++) {
        echo $roles_for_account[$i] . " - Classe: " . $role_id[$i] . "\n";
    }
} else {
    echo "Nenhum papel encontrado para a conta " . $account_id . ".\n";
}
