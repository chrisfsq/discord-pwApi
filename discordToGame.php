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
    // Verifica se a mensagem é do canal específico que você quer monitorar
    if ($message->channel_id == '1237088317284028548') {
        // Processa a mensagem conforme necessário
        $conteudo = $message->content;

        // Obtém o nome do autor da mensagem
        $nome = $message->author->username;

        // Envia os dados para a sua API
        enviarParaAPI($conteudo, $nome);
    }
});

function enviarParaAPI($conteudo, $nome)
{
    global $api;
    global $config;

    if (strpos($conteudo, '<:global:1237095759325827243>') === false) {
        
        $mensagem = "{cor-1}[Discord]$nome: {cor-4} $conteudo";

        $mensagem = cores($mensagem);

        $api->pwchat(0, $mensagem, $config['discordchanel']);

    } else {
        echo "Mensagem contém conteúdo a ser ignorado: $conteudo\n";
    }
};


$discord->run();
