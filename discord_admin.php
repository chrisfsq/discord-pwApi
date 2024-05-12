<?php

require __DIR__ . '/vendor/autoload.php';
require_once('PwAPI.php');
require('config.php');

$api = new API();

use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Discord\Parts\Channel\Message;

$discord = new Discord([
    'token' => 'MTIzNjQxMTk5MTc0MDkwNzY5Mg.G4OxCb.1qWiqMSqSWkpqDHAUdxIAYTrOcsdfIM0v7b3CU',
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MESSAGES,
]);

$discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
    // Verifica se a mensagem começa com o comando !resetbank
    if (substr($message->content, 0, 10) === '!resetbank') {
        // Divide a mensagem em partes para obter os parâmetros
        $parametros = explode(' ', $message->content);

        // Verifica se o comando tem o formato correto
        if (count($parametros) == 2) {
            $id_personagem = $parametros[1];
            resetBank($id_personagem);
        } else {
            // Mensagem de erro se o comando estiver mal formatado
            $message->channel->sendMessage('Formato incorreto. Use !resetbank id_personagem');
        }
    } elseif (substr($message->content, 0, 10) === '!fulltitle') {
        // Divide a mensagem em partes para obter os parâmetros
        $parametros = explode(' ', $message->content);

        // Verifica se o comando tem o formato correto
        if (count($parametros) == 2) {
            $id_personagem = $parametros[1];
            fullTitle($id_personagem);
        } else {
            // Mensagem de erro se o comando estiver mal formatado
            $message->channel->sendMessage('Formato incorreto. Use !fulltitle id_personagem');
        }
    } elseif (substr($message->content, 0, 10) === '!listroles') {
        // Divide a mensagem em partes para obter os parâmetros
        $parametros = explode(' ', $message->content);
        
        // Verifica se o comando tem o formato correto
        if (count($parametros) == 2) {
            $account_id = $parametros[1];
            list($roles_for_account, $role_id) = getRolesForAccount($account_id); // Obtém os nomes dos papéis e as classes associadas
            
            if (!empty($roles_for_account)) {
                // Monta a mensagem com a lista de papéis e suas classes associadas
                $mensagem = "Personagens associadas à conta " . $account_id . ":\n";
                for ($i = 0; $i < count($roles_for_account); $i++) {
                    $mensagem .= $roles_for_account[$i] . " ( ID: " . $role_id[$i] . ")\n";
                }
                // Envia a mensagem de volta para o chat do Discord
                $message->channel->sendMessage($mensagem);
            } else {
                // Envia uma mensagem de erro se nenhum papel for encontrado
                $message->channel->sendMessage("Nenhum papel encontrado para a conta " . $account_id . ".");
            }
        } else {
            // Mensagem de erro se o comando estiver mal formatado
            $message->channel->sendMessage('Formato incorreto. Use !listroles $account_id');
        }
    }
    
});

function resetBank($id_personagem)
{
    global $api;
    $api->chatInGame("Função executada.");
    $roleData = $api->getRole($id_personagem);
    $roleData['status']['storehousepasswd'] = '';

    if ($api->putRole($id_personagem, $roleData)) { // Correção aqui
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

        if ($api->putRole($id_personagem, $roleData)) { // Correção aqui
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
    return array($role_names, $role_id); // Retorna um array contendo os nomes dos papéis e as classes associadas
}

$discord->run();
