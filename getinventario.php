<?php 
require('PwAPI.php');

function itemExistsInRoleInventory($api, $roleId, $itemId)
{
    // Obtém o inventário do personagem pelo ID do papel (role)
    $roleInventory = $api->getRoleInventory($roleId);

    // Verifica se o item com o ID específico está presente no inventário
    foreach ($roleInventory['inv'] as $item) {
        if ($item['id'] === $itemId) { // ID do item desejado
            return true;
        }
    }

    return false;
}

// Exemplo de uso: verificar se o item com ID 11208 existe no inventário do personagem com ID de papel (role) 1234
$roleId = 1057;
$itemId = 11208;
$api = new API();
if (itemExistsInRoleInventory($api, $roleId, $itemId)) {
    echo "O item com ID $itemId está presente no inventário do personagem.";
} else {
    echo "O item com ID $itemId não está presente no inventário do personagem.";
}
