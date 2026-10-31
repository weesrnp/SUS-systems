#!/usr/bin/env bash
set -e

echo "========================================================"
echo "              SUS-systems 3.0 (PHP)"
echo "========================================================"
echo ""

open_browser() {
    local url="http://localhost:8090"
    if command -v xdg-open >/dev/null 2>&1; then
        xdg-open "$url" >/dev/null 2>&1 &
    elif command -v open >/dev/null 2>&1; then
        open "$url" >/dev/null 2>&1 &
    else
        echo "[*] Abra o seguinte endereço no seu navegador: $url"
    fi
}

if command -v docker >/dev/null 2>&1; then
    echo "[*] Docker detectado. Subindo container via docker compose..."
    docker compose up -d --build
    echo "[*] Container em execução. Abrindo navegador..."
    sleep 2
    open_browser
    echo "[*] Pronto! SUS-systems ativo em http://localhost:8090"
    exit 0
fi

if command -v php >/dev/null 2>&1; then
    echo "[*] Docker não encontrado, mas PHP foi detectado no PATH."
    echo "[*] Iniciando servidor embutido do PHP na porta 8090..."
    (sleep 1 && open_browser) &
    exec php -S localhost:8090 -t public public/index.php
fi

echo "[!] ERRO: Nem o Docker nem o PHP foram encontrados no PATH." >&2
echo "Instale o Docker ou o PHP 8.2+ com as extensões ftp, zip, curl e mbstring." >&2
exit 1
