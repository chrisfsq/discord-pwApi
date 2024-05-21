<?php

require __DIR__ . '/vendor/autoload.php';
require_once('./api/PwAPI.php');
require('./configs/config.php');

use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Discord\Parts\Channel\Message;

$api = new API();
global $config;

$discord = new Discord([
    'token' => $config['discord']['token'],
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MESSAGES,
]);

$discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use ($api) {
    if (substr($message->content, 0, 11) === '!getfaction') {
        $parametros = explode(' ', $message->content);
        
        if (count($parametros) == 2) {
            $id_guild = $parametros[1];
            $details = getGuildDetails($api, $id_guild);
            if ($details !== false) {
                $response = "Detalhes da Guilda:\n";
                $response .= "ID: " . $details['id'] . "\n";
                $response .= "Nome: " . $details['name'] . "\n";
                $response .= "Nível: " . $details['level'] . "\n";
                $response .= "Mestre da Guilda:\n";
                $response .= "- Nome do Mestre: " . $details['master']['name'] . "\n";
                $response .= "- ID do Mestre: " . $details['master']['roleid'] . "\n";
                $response .= "- Cargo do Mestre: " . $details['master']['role'] . "\n";
                $response .= "Contagem de Membros: " . $details['membercount'] . "\n";
                $response .= "Anúncio: " . $details['announce'] . "\n";
                $response .= "Informações do Sistema: " . $details['sysinfo'] . "\n";
                $response .= "Membros da Guilda:\n";
                foreach ($details['members'] as $member) {
                    $response .= "- Nome do Membro: " . $member['name'] . ", ID do Membro: " . $member['roleid'] . ", Cargo: " . $member['role'] . "\n";
                }
                $message->channel->sendMessage($response);
            } else {
                $message->channel->sendMessage('Erro ao obter os detalhes da guilda.');
            }
        } else {
            $message->channel->sendMessage('Formato incorreto. Use !getfaction id_guilda');
        }
    }
});

function getGuildDetails($api, $id_guild) {
    try {
        $guildDetails = $api->getFactionInfo($id_guild);
        
        if (!$guildDetails) {
            return false;
        }

        // Extrai as informações da guilda
        $guild_id = $guildDetails['fid'];
        $guild_name = $guildDetails['name'];
        $guild_level = $guildDetails['level'];
        $guild_master = $guildDetails['master'];
        $member_count = isset($guildDetails['membercount']) ? $guildDetails['membercount'] : 0;
        $members = isset($guildDetails['member']) ? $guildDetails['member'] : [];
        $announce = isset($guildDetails['announce']) ? $guildDetails['announce'] : 'N/A';
        $sysinfo = isset($guildDetails['sysinfo']) ? $guildDetails['sysinfo'] : 'N/A';

        // Obter o nome do mestre da guilda
        $masterName = $api->getRoleBase($guild_master['roleid'])['name'];
        $guild_master['name'] = $masterName;

        // Obter os nomes dos membros da guilda
        foreach ($members as &$member) {
            $memberName = $api->getRoleBase($member['roleid'])['name'];
            $member['name'] = $memberName;
        }

        // Retorna um array contendo os detalhes da guilda
        return [
            'id' => $guild_id,
            'name' => $guild_name,
            'level' => $guild_level,
            'master' => $guild_master,
            'membercount' => $member_count,
            'members' => $members,
            'announce' => $announce,
            'sysinfo' => $sysinfo,
        ];
    } catch (Exception $e) {
        return false;
    }
}

$discord->run();