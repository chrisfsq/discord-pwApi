<?php

require_once('./api/PwAPI.php');
require('./configs/config.php');
$api = new API();

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


$argv[1]($argv[2]);

function processChatLine($line = null)
{

    global $api, $config;

    if (strpos($line, "chl=1") !== false) {
        preg_match('/src=(\d+)/', $line, $matches);
        $getRoleId = $matches[1];
        $roleId = $api->getRoleBase($getRoleId);
        $classeJogador = $api->getRoleBase($getRoleId)['cls'];
        $classeEmote = classesEmote($classeJogador);

        preg_match('/msg=([^ ]+)/', $line, $matches);
        $str = $matches[1];
        $msg = base64_decode($str);
        $msg_enconding = 'ISO-8859-1';

        $hora_atual = date("H:i");
        
        $mensagem = iconv($msg_enconding, 'UTF-8', "[$hora_atual] <:global:1237095759325827243> - **{$roleId['name']}** $classeEmote disse: $msg");

        sendMessageToDiscordWebhook($config['discord']['webhook_url'], $mensagem);
    }
}
