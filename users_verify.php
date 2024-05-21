<?php

require_once('./api/PwAPI.php');
require('./configs/config.php');

$api = new API();

// Função para conectar ao MySQL
function conectarMySQL()
{
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
function executarConsulta($sql)
{
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

// Biblioteca DiscordPHP
require __DIR__ . '/vendor/autoload.php';

use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Discord\Parts\Channel\Message;

$discord = new Discord([
    'token' => $config['discord']['token'],
]);

$discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
    // Se a mensagem não for de um servidor, saia
    if ($message->channel->guild) {
        return;
    }

    global $conn;

    $content = $message->content;
    $author = $message->author;
    $channel = $message->channel;
    $discordUserId = $author->id; // ID do Discord do usuário

    echo "Mensagem recebida: $content" . PHP_EOL;

    if (strpos($content, '!vincular') === 0) {
        vincularUsuario($content, $discordUserId, $channel);
    } elseif ($content === '!verificar') {
        verificarVinculacao($discordUserId, $channel);
    } elseif ($content === '!listroles') {
        listarPersonagens($discordUserId, $channel);
    }
});

// Função para vincular um usuário
function vincularUsuario($content, $discordUserId, $channel)
{
    global $conn, $api;

    // Separa o comando, o nome de usuário e o e-mail digitado pelo usuário
    $parts = explode(' ', $content);
    $gameUsername = isset($parts[1]) ? $parts[1] : null;
    $email = isset($parts[2]) ? $parts[2] : null;

    if (!$gameUsername || !$email) {
        // Se o usuário não fornecer um nome de usuário do jogo ou e-mail, envie uma mensagem de erro
        $channel->sendMessage("Por favor, forneça o nome de usuário do jogo e o e-mail. Exemplo: !vincular seu_login_do_jogo seu_email");
        return;
    }

    // Verifica se o usuário já está vinculado
    $query = "SELECT * FROM users WHERE discord_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $discordUserId); // Use o ID do Discord para vincular
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existingUser = mysqli_fetch_assoc($result);

    if ($existingUser) {
        // Se o usuário já estiver vinculado, envie uma mensagem informando que ele já está vinculado
        $channel->sendMessage("Você já está vinculado a uma conta.");
        return;
    }

    // Verifica se o nome de usuário do jogo e o e-mail existem na tabela de usuários
    $query = "SELECT * FROM users WHERE name = ? AND email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $gameUsername, $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $gameUser = mysqli_fetch_assoc($result);

    if (!$gameUser) {
        // Se o usuário do jogo não existir, envie uma mensagem de erro
        $channel->sendMessage("Usuário do jogo ou e-mail não encontrado. Verifique se você digitou corretamente.");
        return;
    }

    if ($gameUser['discord_id']) {
        // Se a conta do jogo já estiver vinculada, envie uma mensagem informando que ela já está vinculada
        $channel->sendMessage("A conta do jogo já está vinculada a outro usuário.");
        return;
    }

    // Insere o ID do Discord na coluna "discord_id" na linha correspondente na tabela de usuários
    $query = "UPDATE users SET discord_id = ? WHERE name = ? AND email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sss", $discordUserId, $gameUsername, $email);
    mysqli_stmt_execute($stmt);

    // Obtém os personagens associados à conta
    $roles = $api->getRoles($gameUser['ID']);

    if (!isset($roles['roles']) || !is_array($roles['roles'])) {
        $channel->sendMessage("Não foi possível obter os personagens. Tente novamente mais tarde.");
        return;
    }

    // Insere os personagens na tabela de personagens
    foreach ($roles['roles'] as $role) {
        $query = "INSERT INTO characters (user_id, character_id, character_name) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iis", $gameUser['ID'], $role['id'], $role['name']);
        mysqli_stmt_execute($stmt);
    }

    // Envie uma mensagem confirmando que o usuário foi vinculado com sucesso
    $channel->sendMessage("Sua conta do Discord foi vinculada com sucesso à conta do jogo: " . $gameUsername);

    // Envia uma mensagem com a lista de personagens vinculados
    $mensagem = "Personagens associados à conta do jogo:\n";
    foreach ($roles['roles'] as $index => $role) {
        $mensagem .= ($index + 1) . ". " . $role['name'] . " (ID: " . $role['id'] . ")\n";
    }
    $channel->sendMessage($mensagem);
}

// Função para verificar a vinculação de um usuário e atualizar os personagens
function verificarVinculacao($discordUserId, $channel)
{
    global $conn, $api;

    // Verifica se o ID do Discord está na tabela de usuários
    $query = "SELECT * FROM users WHERE discord_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $discordUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $numRows = mysqli_num_rows($result);
    echo "Linhas retornadas: $numRows" . PHP_EOL;
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        // Se o usuário for encontrado, podemos enviar uma mensagem com informações
        $channel->sendMessage("Você está vinculado à conta do jogo: " . $user['name']);

        // Obtém os personagens associados à conta
        $roles = $api->getRoles($user['ID']);

        if (!isset($roles['roles']) || !is_array($roles['roles'])) {
            $channel->sendMessage("Não foi possível obter os personagens. Tente novamente mais tarde.");
            return;
        }

        // Atualiza os personagens na tabela de personagens
        $accountId = $user['ID'];
        $deleteQuery = "DELETE FROM characters WHERE user_id = ?";
        $deleteStmt = mysqli_prepare($conn, $deleteQuery);
        mysqli_stmt_bind_param($deleteStmt, "i", $accountId);
        mysqli_stmt_execute($deleteStmt);

        foreach ($roles['roles'] as $role) {
            $insertQuery = "INSERT INTO characters (user_id, character_id, character_name) VALUES (?, ?, ?)";
            $insertStmt = mysqli_prepare($conn, $insertQuery);
            mysqli_stmt_bind_param($insertStmt, "iis", $accountId, $role['id'], $role['name']);
            mysqli_stmt_execute($insertStmt);
        }

        // Envia uma mensagem com a lista de personagens atualizados
        $mensagem = "Personagens associados à sua conta foram atualizados:\n";
        foreach ($roles['roles'] as $index => $role) {
            $mensagem .= ($index + 1) . ". " . $role['name'] . " (ID: " . $role['id'] . ")\n";
        }
        $channel->sendMessage($mensagem);
    } else {
        $channel->sendMessage("Você não está vinculado a uma conta. Use !vincular para vincular sua conta.");
    }
}


// Função para listar personagens de um usuário vinculado
function listarPersonagens($discordUserId, $channel)
{
    global $conn;

    if (!isUserLinked($discordUserId)) {
        $channel->sendMessage("Você não está vinculado a uma conta. Use !vincular para vincular sua conta.");
        return;
    }

    $account_id = getAccountId($discordUserId);

    // Obtém a lista de personagens da tabela de personagens
    $query = "SELECT character_name, character_id FROM characters WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $account_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        $channel->sendMessage("Erro ao obter a lista de personagens.");
        return;
    }

    $numRows = mysqli_num_rows($result);

    if ($numRows === 0) {
        $channel->sendMessage("Nenhum personagem encontrado para a sua conta.");
        return;
    }

    // Monta a mensagem com a lista de personagens e suas IDs associadas
    $mensagem = "Personagens associados à sua conta:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        $mensagem .= $row['character_name'] . " (ID: " . $row['character_id'] . ")\n";
    }

    // Envia a mensagem com a lista de personagens
    $channel->sendMessage($mensagem);
}

// Função para verificar se o usuário está vinculado a uma conta
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

// Função para obter o ID da conta do usuário
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

$discord->run();
