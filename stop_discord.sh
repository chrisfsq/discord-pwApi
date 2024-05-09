#!/bin/bash

kill $(ps aux | grep 'chl=1' | awk '{print $2}')  > /dev/null 2>&1 &
kill $(ps aux | grep 'type=258' | awk '{print $2}')  > /dev/null 2>&1 &
kill $(ps aux | grep 'tail -f' | awk '{print $2}')  > /dev/null 2>&1 &
kill $(ps aux | grep 'type=2:attacker' | awk '{print $2}')  > /dev/null 2>&1 &
pkill -f start_discord.sh

echo "discord parado com sucesso!"