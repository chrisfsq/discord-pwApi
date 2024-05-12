<?php

require_once('PwAPI.php');

function sendMessageToDiscordWebhook($webhookUrl, $messageContent)
{
    try {
        // Inicializa a sessão cURL
        $curl = curl_init($webhookUrl);

        // Configura as opções da solicitação
        $payload = json_encode(['content' => $messageContent]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Executa a solicitação
        $result = curl_exec($curl);

        // Verifica se houve erros
        if ($result === false) {
            throw new Exception('Erro ao enviar mensagem para o Discord via webhook: ' . curl_error($curl));
        }

        // Fecha a sessão cURL
        curl_close($curl);

        return $result;
    } catch (\Exception $e) {
        echo 'Ocorreu um erro: ' . $e->getMessage();
    }
}


$api = new API();

$linha = $argv[1];

if (strpos($linha, "rolelogin") !== false) {
    $player = explode("=", explode(":", $linha)[7])[1];
    if (isset($player)) {
        $player = $api->getRoleBase($player); //id player
        $playerName = $player['name'];
        $data = date("Y-m-d H:i:s");

        $mensagem = "[$data] O jogador **$playerName** entrou no jogo";
        sendMessageToDiscordWebhook('https://discord.com/api/webhooks/1237159935629066323/OtKzJlDC8JUoxpCI88lr96ImlhwjtEtB9r5RLRJsvUUYYA1EygOnnUJrIHuD8BR1GA_k', $mensagem);
    }
}

if (strpos($linha, "rolelogout") !== false) {
    $player = explode("=", explode(":", $linha)[7])[1];
    if (isset($player)) {
        $player = $api->getRoleBase($player);
        $playerName = $player['name'];
        $data = date("Y-m-d H:i:s");

        $mensagem = "[$data] O jogador **$playerName** saiu do jogo";
        sendMessageToDiscordWebhook('https://discord.com/api/webhooks/1237159935629066323/OtKzJlDC8JUoxpCI88lr96ImlhwjtEtB9r5RLRJsvUUYYA1EygOnnUJrIHuD8BR1GA_k', $mensagem);
    }
}

if (strpos($linha, "createrole-success") !== false) {
    $playerId = explode("=", explode(":", $linha)[8])[1];
    if (isset($playerId)) {
        $player = $api->getRoleBase($playerId); // Obtém os detalhes do jogador
        $playerName = $player['name'];
        $playerClass = classes($player['cls']);
        $data = date("Y-m-d H:i:s");

        // Monta a mensagem
        $mensagem = "[$data] O jogador **$playerName** iniciou sua aventura no *Perfect World Supimpa* com a classe *$playerClass*, bem vindo!";

        // Envia a mensagem para o webhook do Discord
        sendMessageToDiscordWebhook('https://discord.com/api/webhooks/1237159935629066323/OtKzJlDC8JUoxpCI88lr96ImlhwjtEtB9r5RLRJsvUUYYA1EygOnnUJrIHuD8BR1GA_k', $mensagem);
    }
}

if (strpos($linha, "faction:type=create") !== false) {
    $guild = explode("=", explode(":", $linha)[8])[1];
    if (isset($guild)) {
        $guild = $api->getFactionDetail($guild);
        $player = $api->getRoleBase($guild['master']);
        $playerName = $player['name'];
        $guildName = $guild['name'];
        $data = date("Y-m-d H:i:s");

        $mensagem = "[$data] O jogador **$playerName** criou a guilda **$guildName**";
        sendMessageToDiscordWebhook('https://discord.com/api/webhooks/1237159935629066323/OtKzJlDC8JUoxpCI88lr96ImlhwjtEtB9r5RLRJsvUUYYA1EygOnnUJrIHuD8BR1GA_k', $mensagem);
    }
}


// Processamento do upgrade de nível
if (strpos($linha, "upgrade") !== false) {
    $levels = explode(',', '90,100,101,102,103,104,105');
    $player = explode("=", explode(":", $linha)[6])[1];
    $role_level = explode("=", explode(":", $linha)[7])[1];
    if (isset($role_level) && is_numeric($role_level) && in_array($role_level, $levels)) {
        $player = $api->getRoleBase($player);
        $playerName = $player['name'];
        $playerClass = classes($player['cls']);
        $data = date("Y-m-d H:i:s");

        $mensagem = "[$data] O jogador **$playerName** atingiu o nível *$role_level* como *$playerClass*";
        sendMessageToDiscordWebhook('https://discord.com/api/webhooks/1237159935629066323/OtKzJlDC8JUoxpCI88lr96ImlhwjtEtB9r5RLRJsvUUYYA1EygOnnUJrIHuD8BR1GA_k', $mensagem);
    }
}
