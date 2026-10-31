@echo off
setlocal enabledelayedexpansion
title SUS-systems v2.2 - Hub de Dados do Datasus

echo =======================================================
echo          SUS-systems - Datasus Data Platform
echo =======================================================
echo [i] Inicializando ambiente local...

:: 1. Verificar presenca do Python
where python >nul 2>nul
if %errorlevel% neq 0 (
    echo [!] ERRO: Python nao encontrado no PATH do sistema.
    echo [!] Por favor, instale o Python 3.10 ou superior: https://www.python.org/
    pause
    exit /b 1
)

:: 2. Criar ambiente virtual se nao existir
if not exist ".venv" (
    echo [*] Criando ambiente virtual isolado (.venv)...
    python -m venv .venv
    if %errorlevel% neq 0 (
        echo [!] Falha ao criar ambiente virtual.
        pause
        exit /b 1
    )
)

:: 3. Ativar virtualenv
call .venv\Scripts\activate.bat

:: 4. Instalar dependencias
echo [*] Verificando dependencias (FastAPI, Uvicorn, HTTPX, Pydantic)...
python -m pip install --upgrade pip --quiet
pip install -r requirements.txt --quiet
if %errorlevel% neq 0 (
    echo [!] Falha ao instalar dependencias do requirements.txt
    pause
    exit /b 1
)

:: 5. Abrir navegador e iniciar servidor
echo [OK] Servidor pronto para execucao.
echo [*] Abrindo aplicacao em: http://127.0.0.1:8090
start "" "http://127.0.0.1:8090"

echo.
echo =======================================================
echo  SUS-systems executando em http://127.0.0.1:8090
echo  Pressione CTRL+C para encerrar o servidor.
echo =======================================================
echo.

python -m uvicorn app.main:app --host 127.0.0.1 --port 8090

pause
