<?php

// Configurações do banco de dados
$dbHost = 'localhost'; // Host do banco de dados
$dbName = 'pw'; // Nome do banco de dados
$dbUser = 'admin'; // Nome de usuário do banco de dados
$dbPass = 'migHyMPrd76v'; // Senha do banco de dados

// Conecta ao MySQL
$conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

// Verifica a conexão
if (!$conn) {
    die("Erro ao conectar ao banco de dados: " . mysqli_connect_error());
}

// Biblioteca DiscordPHP
require __DIR__ . '/vendor/autoload.php';

use Discord\Discord;
use Discord\WebSockets\Event;

// Token do seu bot do Discord
$discordToken = 'MTIzNjQxMTk5MTc0MDkwNzY5Mg.G4OxCb.1qWiqMSqSWkpqDHAUdxIAYTrOcsdfIM0v7b3CU';

$discord = new Discord([
    'token' => $discordToken,
]);

$discord->on(Event::MESSAGE_CREATE, function ($message, $discord) {
    global $conn;

    $content = $message->content;
    $author = $message->author;
    $channel = $message->channel;

    echo "Mensagem recebida: $content" . PHP_EOL;

    // Verifica se a mensagem começa com o comando !vincular
    if (strpos($content, '!vincular') === 0) {
        // Separa o comando e o nome de usuário digitado pelo usuário
        $parts = explode(' ', $content);
        $discordId = $author->id; // Use o ID do Discord em vez do nome de usuário
        $gameUsername = isset($parts[1]) ? $parts[1] : null;

        if (!$gameUsername) {
            // Se o usuário não fornecer um nome de usuário do jogo, envie uma mensagem de erro
            $channel->sendMessage("Por favor, forneça o nome de usuário do jogo. Exemplo: !vincular seu_login_do_jogo");
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

        // Verifica se o nome de usuário do jogo existe na tabela de usuários
        $query = "SELECT * FROM users WHERE name = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $gameUsername);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $gameUser = mysqli_fetch_assoc($result);

        if (!$gameUser) {
            // Se o usuário do jogo não existir, envie uma mensagem de erro
            $channel->sendMessage("Usuário do jogo não encontrado. Verifique se você digitou corretamente.");
            return;
        }

        // Insere o ID do Discord na coluna "discord_id" na linha correspondente na tabela de usuários
        $query = "UPDATE users SET discord_id = ? WHERE name = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $discordId, $gameUsername);
        mysqli_stmt_execute($stmt);

        // Envie uma mensagem confirmando que o usuário foi vinculado com sucesso
        $channel->sendMessage("Sua conta do Discord foi vinculada com sucesso à conta do jogo: " . $gameUsername);
    } elseif ($content === '!verificar') {
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

try {
    $discord->run();
} catch (Exception $e) {
    echo "Erro ao executar o bot: ", $e->getMessage(), PHP_EOL;
}

// Fecha a conexão com o MySQL
mysqli_close($conn);

?>
