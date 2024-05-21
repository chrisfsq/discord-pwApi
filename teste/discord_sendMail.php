<?php

require __DIR__ . '/vendor/autoload.php';
require_once('./api/PwAPI.php');

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

    if (substr($message->content, 0, 5) === '!item') {

        $parametros = explode(' ', $message->content);

        if (count($parametros) == 4) {
            $id_personagem = $parametros[1];
            $id_item = $parametros[2];
            $quantidade = $parametros[3];

            enviarParaAPI($id_personagem, $id_item, $quantidade);
        } else {
            $message->channel->sendMessage('Formato incorreto. Use !item id_personagem id_item quantidade');
        }
    }
});


function enviarParaAPI($id_personagem, $id_item, $quantidade)
{
    global $api;

    $item = array(
            'id' => $id_item,
            'pos' => 0,
            'count' => $quantidade,
            'max_count' => 1000,
            'data' => "",
            'proctype' => 8,
            'expire_date' => 0,
            'guid1' => 0,
            'guid2' => 0,
            'mask' => 0
    );

    $api->sendMail($id_personagem, "Prêmio", "Parabéns! Aqui está seu prêmio!", $item, 0);
}

$discord->run();
