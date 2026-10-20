#!/usr/bin/env bash
set -e

echo "======================================================="
echo "         SUS-systems - Datasus Data Platform          "
echo "======================================================="
echo "[i] Inicializando ambiente local..."

# 1. Verificar presenca do Python
if ! command -v python3 &> /dev/null; then
    echo "[!] ERRO: Python 3 nao encontrado."
    echo "[!] Instale o Python 3.10 ou superior antes de prosseguir."
    exit 1
fi

# 2. Criar ambiente virtual se nao existir
if [ ! -d ".venv" ]; then
    echo "[*] Criando ambiente virtual isolado (.venv)..."
    python3 -m venv .venv
fi

# 3. Ativar virtualenv
source .venv/bin/activate

# 4. Instalar dependencias
echo "[*] Verificando dependencias..."
pip install --upgrade pip --quiet
pip install -r requirements.txt --quiet

# 5. Abrir navegador se suportado
URL="http://127.0.0.1:8090"
if command -v xdg-open &> /dev/null; then
    xdg-open "$URL" &
elif command -v open &> /dev/null; then
    open "$URL" &
fi

echo ""
echo "======================================================="
echo " SUS-systems executando em $URL"
echo " Pressione CTRL+C para encerrar o servidor."
echo "======================================================="
echo ""

exec python3 -m uvicorn app.main:app --host 0.0.0.0 --port 8090
