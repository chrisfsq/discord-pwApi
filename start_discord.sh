monitorar_system() {
    echo -e "\e[1;35mIniciando system chat, aguarde\e[1;32m[...]\e[0m"
    tail -f -n0 /root/pwserver/logs/world2.formatlog | grep --line-buffered 'refine\|upgrade\|rolelogout\|rolelogin\|createrole-success\|faction:type=create\|faction:type=delete\|faction:type=join\|faction:type=leave\|formatlog:task\|formatlog:auctionopen' | while read pw
    do
    php system_chat.php "${pw}" >> logs/error_php.txt
    done
}

monitorar_global() {
    echo -e "\e[1;35mIniciando chat global, aguarde\e[1;32m[...]\e[0m"
    tail -f -n0 /root/pwserver/logs/world2.chat | grep --line-buffered 'chl=1' | while read LINE0
    do    
        php global_chat.php processChatLine "${LINE0}"
    done
}


monitorar_global & monitorar_system



wait
