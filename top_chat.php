<?php

require __DIR__ . '/vendor/autoload.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;

// Configurações do seu bot
require('config.php');
require_once('PwAPI.php');

$api = new API();

function rankAnnounce($discord, $config, $api)
{
    $mysqli = new mysqli(
        $config['mysql']['host'],
        $config['mysql']['user'],
        $config['mysql']['password'],
        $config['mysql']['db']
    );

    if ($mysqli->connect_errno) {
        logError($discord, $config['discord']['channel_id'], "Erro ao conectar ao banco de dados: " . $mysqli->connect_error);
        return;
    }

    $consultaRanking = "SELECT DISTINCT KillID, pdl, liga FROM pwrank ORDER BY pdl DESC LIMIT 10";

    $resultado = $mysqli->query($consultaRanking);

    if (!$resultado || $resultado->num_rows === 0) {
        sendMessage($discord, $config['discord']['channel_id'], "Não há dados suficientes para gerar o ranking.");
        return;
    }

    $top3Jogadores = $resultado->fetch_all(MYSQLI_ASSOC);

    $announce = ":crossed_swords: Nosso top 10 melhores jogadores da primeira temporada serão anunciados:";
    sendMessage($discord, $config['discord']['channel_id'], $announce);

    sleep(3);

    foreach ($top3Jogadores as $posicao => $jogador) {
        $classeJogador = $api->getRoleBase($jogador['KillID'])['cls'];
        $classeString = classes($classeJogador);
        $nomeJogador = $api->getRoleBase($jogador['KillID'])['name'];
        $posicaoJogador = $posicao + 1;

        $mensagem = "**#{$posicaoJogador} - {$nomeJogador} ($classeString)** - PdL: {$jogador['pdl']} - Liga: {$jogador['liga']}";
        sendMessage($discord, $config['discord']['channel_id'], $mensagem);

        sleep(2);
    }

    $mysqli->close();
}

function sendMessage($discord, $channelId, $message)
{
    $channel = $discord->getChannel($channelId);
    if ($channel) {
        $channel->sendMessage($message);
    } else {
        logError($discord, $channelId, "Erro ao enviar mensagem para o Discord.");
    }
}

// Função para iniciar o temporizador para executar rankAnnounce a cada 1 minutos
function startTimer($discord, $config, $api) {
    $discord->loop->addPeriodicTimer(60, function() use ($discord, $config, $api) {
        rankAnnounce($discord, $config, $api);
    });
}

function logError($discord, $channelId, $errorMessage)
{
    sendMessage($discord, $channelId, "Erro: " . $errorMessage);
}

$discord = new Discord([
    'token' => $config['discord']['token'],
]);

startTimer($discord, $config, $api);

$discord->run();
