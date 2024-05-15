<?php

require __DIR__ . '/vendor/autoload.php';
require_once('PwAPI.php');
require('config.php');

$api = new API();

use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Discord\Parts\Channel\Message;

$dbHost = 'localhost';
$dbName = 'pw'; 
$dbUser = 'admin'; 
$dbPass = 'migHyMPrd76v'; 

function conectarMySQL() {
    global $dbHost, $dbUser, $dbPass, $dbName;
    $conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

    if (!$conn) {
        die("Erro ao conectar ao banco de dados: " . mysqli_connect_error());
    }

    return $conn;
}


$conn = conectarMySQL();

function executarConsulta($sql) {
    global $conn;

    if (!mysqli_ping($conn)) {
        $conn = conectarMySQL();
    }

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die("Erro na consulta: " . mysqli_error($conn));
    }

    return $result;
}

$discord = new Discord([
    'token' => 'MTIzNjQxMTk5MTc0MDkwNzY5Mg.G4OxCb.1qWiqMSqSWkpqDHAUdxIAYTrOcsdfIM0v7b3CU',
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MESSAGES,
]);

$userState = []; 

$discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use (&$userState) {
    if ($message->channel->guild) {
        return;
    }

    $channel = $message->channel;
    $author = $message->author;
    $discordUserId = $author->id;

    // Verifica se o usuário está no meio de selecionar um personagem
    if (isset($userState[$discordUserId]) && $userState[$discordUserId] === 'selecting_character') {
        $content = $message->content;
        list($account_id, $roles_for_account, $role_ids) = $_SESSION['user_data'][$discordUserId];

        // Verifica se a mensagem é do mesmo usuário e se é um número válido
        if (is_numeric($content) && $content > 0 && $content <= count($roles_for_account)) {
            $selected_role_index = (int)$content - 1;
            $selected_role_id = $role_ids[$selected_role_index];

            // Verifica se o item específico está no inventário do personagem
            $itemId = 11208; // ID do item a ser verificado
            if (!itemExistsInRoleInventory($selected_role_id, $itemId)) {
                $channel->sendMessage("O item com ID $itemId não está presente no inventário do personagem.");

                // Limpa o estado do usuário
                unset($userState[$discordUserId]);
                unset($_SESSION['user_data'][$discordUserId]);
                unset($_SESSION['last_command'][$discordUserId]);

                return;
            }

            // Executa a função correspondente com o personagem selecionado
            if (substr($_SESSION['last_command'][$discordUserId], 0, 10) === '!fulltitle') {
                fullTitle($selected_role_id);
                $channel->sendMessage("Full titulo aplica com sucesso no personagem selecionado ID: $selected_role_id");
            } elseif (substr($_SESSION['last_command'][$discordUserId], 0, 10) === '!resetbank') {
                resetBank($selected_role_id);
                $channel->sendMessage("Reset de senha do banqueiro aplica com sucesso no persoganem selecionado ID: $selected_role_id");
            }

            // Limpa o estado do usuário
            unset($userState[$discordUserId]);
            unset($_SESSION['user_data'][$discordUserId]);
            unset($_SESSION['last_command'][$discordUserId]);
        } else {
            // Retorna erro se o número do personagem não for válido
            $channel->sendMessage("Número de personagem inválido. Tente novamente.");
        }
    } elseif (substr($message->content, 0, 10) === '!fulltitle' || substr($message->content, 0, 10) === '!resetbank') {
        // Verifica se o usuário está vinculado a uma conta
        if (!isUserLinked($discordUserId)) {
            $channel->sendMessage("Você não está vinculado a uma conta. Use !vincular para vincular sua conta.");
            return;
        }

        // Obtém a lista de personagens associados à conta
        $account_id = getAccountId($discordUserId);
        list($roles_for_account, $role_ids) = getRolesForAccount($account_id);

        if (empty($roles_for_account)) {
            $channel->sendMessage("Nenhum personagem encontrado para a conta " . $account_id . ".");
            return;
        }

        // Monta a mensagem com a lista de personagens e suas IDs associadas
        $mensagem = "Personagens associados à conta " . $account_id . ":\n";
        for ($i = 0; $i < count($roles_for_account); $i++) {
            $mensagem .= $i + 1 . ". " . $roles_for_account[$i] . " ( ID: " . $role_ids[$i] . ")\n";
        }
        $mensagem .= "Digite o número do personagem que você deseja usar: ";

        // Envia a mensagem com a lista de personagens
        $channel->sendMessage($mensagem);

        // Define o estado do usuário para 'selecting_character'
        $userState[$discordUserId] = 'selecting_character';
        $_SESSION['user_data'][$discordUserId] = [$account_id, $roles_for_account, $role_ids];
        $_SESSION['last_command'][$discordUserId] = $message->content;
    } elseif (substr($message->content, 0, 10) === '!listroles') {
        // Obtém o ID do Discord do usuário que enviou a mensagem
        $discordUserId = $author->id;

        // Verifica se o usuário está vinculado a uma conta
        if (!isUserLinked($discordUserId)) {
            $channel->sendMessage("Você não está vinculado a uma conta. Use !vincular para vincular sua conta.");
            return;
        }

        // Consulta o banco de dados para obter o ID da conta do jogo vinculada ao ID do Discord
        $account_id = getAccountId($discordUserId);
        list($roles_for_account, $role_id) = getRolesForAccount($account_id); // Obtém os nomes dos papéis e os IDs associados

        if (!empty($roles_for_account)) {
            // Monta a mensagem com a lista de papéis e suas IDs associadas
            $mensagem = "Personagens associadas à conta " . $account_id . ":\n";
            for ($i = 0; $i < count($roles_for_account); $i++) {
                $mensagem .= $roles_for_account[$i] . " ( ID: " . $role_id[$i] . ")\n";
            }
            // Envia a mensagem de volta para o chat do Discord
            $channel->sendMessage($mensagem);
        } else {
            // Envia uma mensagem de erro se nenhum papel for encontrado
            $channel->sendMessage("Nenhum papel encontrado para a conta " . $account_id . ".");
        }
    }
});



function resetBank($id_personagem)
{
    global $api;
    $api->chatInGame("Função executada.");
    $roleData = $api->getRole($id_personagem);
    $roleData['status']['storehousepasswd'] = '';

    if ($api->putRole($id_personagem, $roleData)) {
        echo 'success';
    } else {
        echo 'system error';
    }
}

function fullTitle($id_personagem)
{
    global $api;
    $api->chatInGame("Função executada.");
    $roleData = $api->getRole($id_personagem);

    if ($roleData['status']['title_data'] != '8405cc0000000e050d058c050f05150516051705180519051a051b051c051d0583051e051f05200521052205230524052505840526052705280529052a052b052c052d052e052f0530053105320533053405350536053705380539053a053b053c053d053e053f054005410542054305850544058705450546054705480549054a0586054b054c054d054e054f058805500551055205530554055505560557055805590589055a055b055c055d055e055f0560056105620563056405650566056705680569056a056b056c056d056e056f05700571057205730574057505760577058b0578058a0579057a057b057c057d057e057f058005810582058d058e058f051106140615061606170618061d0629062b063e06610662066306640666066706680669066a066b066c066d066e066f067006710672067306740675067606770678067a067b067c067d067e067f0680068106820683068406850686068706880689068a068b068c068d068e068f06900691069206930694069b069c069f06a006a106a206a306a406a506a606a706a806a906aa06ab06ac06ad06ae0600000000') {
        $roleData['status']['title_data'] = '8405cc0000000e050d058c050f05150516051705180519051a051b051c051d0583051e051f05200521052205230524052505840526052705280529052a052b052c052d052e052f0530053105320533053405350536053705380539053a053b053c053d053e053f054005410542054305850544058705450546054705480549054a0586054b054c054d054e054f058805500551055205530554055505560557055805590589055a055b055c055d055e055f0560056105620563056405650566056705680569056a056b056c056d056e056f05700571057205730574057505760577058b0578058a0579057a057b057c057d057e057f058005810582058d058e058f051106140615061606170618061d0629062b063e06610662066306640666066706680669066a066b066c066d066e066f067006710672067306740675067606770678067a067b067c067d067e067f0680068106820683068406850686068706880689068a068b068c068d068e068f06900691069206930694069b069c069f06a006a106a206a306a406a506a606a706a806a906aa06ab06ac06ad06ae0600000000';

        if ($api->putRole($id_personagem, $roleData)) {
            echo 'success';
        } else {
            echo 'system error';
        }
    }
}

function getRolesForAccount($account_id)
{
    global $api;
    $roles = $api->getRoles($account_id);
    $role_names = array();
    foreach ($roles['roles'] as $role) {
        $role_names[] = $role['name'];
        $role_id[] = $role['id'];
    }
    return array($role_names, $role_id); 
}

function isUserLinked($discordUserId)
{
    global $conn;
    $query = "SELECT ID FROM users WHERE discord_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $discordUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) !== null;
}

function getAccountId($discordUserId)
{
    global $conn;
    $query = "SELECT ID FROM users WHERE discord_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $discordUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    return $user ? $user['ID'] : null;
}

function itemExistsInRoleInventory($roleId, $itemId)
{
    global $api;
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

$discord->run();
