<?php

require __DIR__ . '/vendor/autoload.php';
require_once('./api/PwAPI.php');
require('./configs/config.php');

$api = new API();

use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Discord\Parts\Channel\Message;

// Função para conectar ao MySQL
function conectarMySQL() {
    global $config;
    $conn = mysqli_connect($config['mysql']['host'], $config['mysql']['user'], $config['mysql']['password'], $config['mysql']['db']);

    // Verifica se a conexão foi bem-sucedida
    if (!$conn) {
        die("Erro ao conectar ao banco de dados: " . mysqli_connect_error());
    }

    return $conn;
}

// Variável global para a conexão
$conn = conectarMySQL();

// Função para executar consultas SQL com reconexão automática
function executarConsulta($sql) {
    global $conn;

    // Verifica se a conexão está ativa, se não, reconecta
    if (!mysqli_ping($conn)) {
        $conn = conectarMySQL();
    }

    // Executa a consulta SQL
    $result = mysqli_query($conn, $sql);

    // Verifica se ocorreu algum erro na execução da consulta
    if (!$result) {
        die("Erro na consulta: " . mysqli_error($conn));
    }

    return $result;
}

$discord = new Discord([
    'token' => $config['discord']['token'],
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MESSAGES,
]);

$discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
    global $config;
    $channel = $message->channel;
    $author = $message->author;
    $discordUserId = $author->id;
    $account_id = getAccountId($discordUserId);
    
    if (!isUserLinked($discordUserId)) {
        return;
    }

    // Verifica se o item específico está no inventário do personagem principal
    $itemId = $config['item_chat']; // ID do item a ser verificado
    if (!itemExistsInRoleInventory($account_id, $itemId)) {
        $channel->sendMessage("O item com ID $itemId não está presente no inventário do PERSONAGEM PRINCIPAL.");
        return;
    }

    // Verifica se a mensagem é do canal específico que você quer monitorar
    
    if ($message->channel_id == '1237088317284028548') {
        // Processa a mensagem conforme necessário
        $conteudo = $message->content;

        // Obtém o nome do autor da mensagem
        $nome = $message->author->username;

        $discordUserId = $author->id;
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
