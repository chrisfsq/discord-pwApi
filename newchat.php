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
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 28800); // Define o timeout para 8 horas

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

    // Número máximo de tentativas de reconexão
    $maxTentativas = 3;
    $tentativa = 0;

    do {
        // Verifica se a conexão está ativa, se não, reconecta
        if (!mysqli_ping($conn)) {
            $conn = conectarMySQL();
        }

        // Executa a consulta SQL
        $result = mysqli_query($conn, $sql);
        
        // Se a consulta foi bem-sucedida, retorna o resultado
        if ($result !== false) {
            return $result;
        }
        
        // Se a consulta falhou, tenta reconectar e executar novamente
        $tentativa++;
    } while ($tentativa < $maxTentativas);

    // Se todas as tentativas falharem, emite um erro
    die("Erro na consulta após $maxTentativas tentativas: " . mysqli_error($conn));
}

// Função para periodicamente verificar a conexão
function manterConexaoAtiva() {
    global $conn;

    // Verifica a cada 30 minutos (1800 segundos)
    $intervalo = 1800;
    while (true) {
        sleep($intervalo);
        if (!mysqli_ping($conn)) {
            $conn = conectarMySQL();
        }
    }
}

// Inicia uma thread para manter a conexão ativa
if (function_exists('pcntl_fork')) {
    $pid = pcntl_fork();
    if ($pid == -1) {
        die('Erro ao criar o processo filho');
    } elseif ($pid == 0) {
        manterConexaoAtiva();
        exit(0);
    }
}

$discord = new Discord([
    'token' => $config['discord']['token'],
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MESSAGES,
]);

$ultimaMensagem = [];

$discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use (&$ultimaMensagem) {
    global $config;
    $channel = $message->channel;
    $author = $message->author;
    $discordUserId = $author->id;
    $account_id = getAccountId($discordUserId);
    
    if (!isUserLinked($discordUserId)) {
        return;
    }

    // Verifica se a mensagem é do canal específico que você quer monitorar
    if ($message->channel_id == '1237088317284028548') {
        // Verifica se o item específico está no inventário do personagem principal
        $itemId = $config['item_chat'];
        $itemName = $config['item_chat_name'];
        if (!itemExistsInRoleInventory($account_id, $itemId)) {
            $author->sendMessage("O item **$itemId - $itemName** não está presente no inventário, é necessário possuir este item no inventário do seu **personagem principal (Primeiro personagem criado na conta)**, para poder enviar mensagem.");
            return;
        }

        // Verifica se o intervalo necessário já passou
        $intervalo = 10; // Intervalo em segundos
        if (isset($ultimaMensagem[$discordUserId]) && time() - $ultimaMensagem[$discordUserId] < $intervalo) {
            $author->sendMessage("Por favor, aguarde $intervalo segundos entre cada mensagem.");
            return;
        }

        // Atualiza a hora da última mensagem
        $ultimaMensagem[$discordUserId] = time();

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
