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

function processLogLine($line = null) {
    global $config;
    if (strpos($line, "type=2") !== false or strpos($line, "type=258") !== false){
        global $api;
        $attacker = explode("=", explode(":", $line)[8])[1];
        $attackerRole = $api->getRoleBase($attacker);
        $attacked = explode("=", explode(":", $line)[6])[1];
        $attackedRole = $api->getRoleBase($attacked);
        $classeJogadorQueMatou = $api->getRoleBase($attacker)['cls'];
        $classeJogadorQueMorreu = $api->getRoleBase($attacked)['cls'];
        $classeStringKill = classesEmote($classeJogadorQueMatou);
        $classeStringDead = classesEmote($classeJogadorQueMorreu);


        $mensagem = ":crossed_swords: - **{$attackerRole['name']}** $classeStringKill matou **{$attackedRole['name']}** $classeStringDead";

        sendMessageToDiscordWebhook($config['discord']['webhook_url'], $mensagem);
    }
}

?>
