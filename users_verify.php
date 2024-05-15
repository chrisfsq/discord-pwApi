<?php

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

// Biblioteca DiscordPHP
require __DIR__ . '/vendor/autoload.php';

use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Discord\Parts\Channel\Message;

// Token do seu bot do Discord
$discordToken = 'MTIzNjQxMTk5MTc0MDkwNzY5Mg.G4OxCb.1qWiqMSqSWkpqDHAUdxIAYTrOcsdfIM0v7b3CU';

$discord = new Discord([
    'token' => $discordToken,
]);

$discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use (&$userState) {
    // Se a mensagem não for de um servidor, saia
    if ($message->channel->guild) {
        return;
    }
    
    global $conn;

    $content = $message->content;
    $author = $message->author;
    $channel = $message->channel;

    echo "Mensagem recebida: $content" . PHP_EOL;

    if (strpos($content, '!vincular') === 0) {
        // Separa o comando, o nome de usuário e o e-mail digitado pelo usuário
        $parts = explode(' ', $content);
        $discordId = $author->id; // Use o ID do Discord em vez do nome de usuário
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
        mysqli_stmt_bind_param($stmt, "s", $discordId); // Use o ID do Discord para vincular
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
        mysqli_stmt_bind_param($stmt, "sss", $discordId, $gameUsername, $email);
        mysqli_stmt_execute($stmt);
    
        // Envie uma mensagem confirmando que o usuário foi vinculado com sucesso
        $channel->sendMessage("Sua conta do Discord foi vinculada com sucesso à conta do jogo: " . $gameUsername);
    }
     elseif ($content === '!verificar') {
        // Primeiro, vamos obter o ID do Discord do usuário
        $discordUserId = $author->id;
        echo "ID do Discord: $discordUserId" . PHP_EOL;
    
        // Agora, vamos verificar se o ID do Discord está na tabela de usuários
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
        } else {

            $channel->sendMessage("Você não está vinculado a uma conta. Use !vincular para vincular sua conta.");
        }
    }
    
});