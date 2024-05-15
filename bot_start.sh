#!/bin/bash

# Defina o diretório onde estão localizados os scripts PHP dos bots do Discord
BOTS_DIR="/"

# Defina o diretório de logs
LOGS_DIR="/logs"

# Função para execução de um script PHP (bot do Discord)
executar_bot_discord() {
    script=$1
    log_file="$LOGS_DIR/$(basename $script .php).log"
    
    echo "Iniciando bot do Discord com o script PHP: $script"
    php $script >> $log_file 2>&1 &
    if [ $? -eq 0 ]; then
        echo "Bot do Discord iniciado com sucesso usando o script PHP: $script"
    else
        echo "Erro ao iniciar o bot do Discord com o script PHP $script. Consulte o arquivo de log: $log_file"
    fi
}

# Verifique se o diretório de logs existe, se não, crie-o
if [ ! -d "$LOGS_DIR" ]; then
    mkdir -p $LOGS_DIR
fi

# Navegue até o diretório de scripts PHP dos bots do Discord
cd $BOTS_DIR || { echo "Erro ao acessar o diretório de bots do Discord: $BOTS_DIR"; exit 1; }

# Lista todos os scripts PHP de bots do Discord no diretório e os inicia
for script_php in *.php; do
    executar_bot_discord "$script_php"
done

echo "Todos os bots do Discord foram iniciados."
