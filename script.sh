#!/bin/bash
tail -f -n0 /root/pwserver/logs/world2.formatlog | grep --line-buffered 'refine\|upgrade\|rolelogout\|rolelogin\|createrole-success\|faction:type=create\|faction:type=delete\|faction:type=join\|faction:type=leave\|formatlog:task\|formatlog:auctionopen' | while read pw
do
php system_chat.php "${pw}" >> logs/error_php.txt
done