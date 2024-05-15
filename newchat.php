<?php

require __DIR__ . '/vendor/autoload.php';
require_once('PwAPI.php');
require('config.php');

use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Discord\Parts\Channel\Message;

$dbHost = 'localhost';
$dbName = 'pw'; 
$dbUser = 'admin'; 
$dbPass = 'migHyMPrd76v'; 

// Função para conectar ao MySQL
function conectarMySQL() {
    global $dbHost, $dbUser, $dbPass, $dbName;
    $conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

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

// Criação do cliente Discord
$discord = new Discord([
    'token' => 'MTIzNjQxMTk5MTc0MDkwNzY5Mg.G4OxCb.1qWiqMSqSWkpqDHAUdxIAYTrOcsdfIM0v7b3CU',
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MESSAGES,
]);

// Evento para lidar com a criação de mensagens
$discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
    global $conn;

    $channel = $message->channel;
    $author = $message->author;
    $discordUserId = $author->id;

    // Verifica se o usuário está vinculado
    if (!isUserLinked($discordUserId)) {
        return;
    }

    // Verifica se o item específico está no inventário do personagem principal
    $itemId = 11208; // ID do item a ser verificado
    if (!itemExistsInRoleInventory($discordUserId, $itemId)) {
        $channel->sendMessage("O item com ID $itemId não está presente no inventário do PERSONAGEM PRINCIPAL.");
        return;
    }

    // Verifica se a mensagem é do canal específico que você quer monitorar
    if ($message->channel_id == '1237088317284028548') {
        // Processa a mensagem conforme necessário
        $conteudo = $message->content;
        $nome = $message->author->username;

        // Envia os dados para a API
        enviarParaAPI($conteudo, $nome);
    }
});

// Função para enviar dados para a API
function enviarParaAPI($conteudo, $nome) {
    global $api, $config;

    if (strpos($conteudo, '<:global:1237095759325827243>') === false) {
        $mensagem = "{cor-1}[Discord]$nome: {cor-4} $conteudo";
        $mensagem = cores($mensagem);
        $api->pwchat(0, $mensagem, $config['discordchanel']);
    } else {
        echo "Mensagem contém conteúdo a ser ignorado: $conteudo\n";
    }
}

// Função para verificar se o usuário está vinculado
function isUserLinked($discordUserId) {
    global $conn;
    $query = "SELECT ID FROM users WHERE discord_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $discordUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) !== null;
}

// Função para obter o ID da conta
function getAccountId($discordUserId) {
    global $conn;
    $query = "SELECT ID FROM users WHERE discord_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $discordUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    return $user ? $user['ID'] : null;
}

// Função para verificar se o item está no inventário do personagem
function itemExistsInRoleInventory($roleId, $itemId) {
    global $api;
    $roleInventory = $api->getRoleInventory($roleId);
    foreach ($roleInventory['inv'] as $item) {
        if ($item['id'] === $itemId) {
            return true;
        }
    }
    return false;
}

// Execução do cliente Discord
$discord->run();
