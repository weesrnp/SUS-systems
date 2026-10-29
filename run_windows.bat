@echo off
setlocal enabledelayedexpansion

echo ========================================================
echo               SUS-systems 3.0 (PHP)
echo ========================================================
echo.

where docker >nul 2>nul
if %ERRORLEVEL% equ 0 (
    echo [*] Docker detectado. Subindo container via docker compose...
    docker compose up -d --build
    if %ERRORLEVEL% neq 0 (
        echo [!] Falha ao iniciar containers Docker.
        pause
        exit /b 1
    )
    echo [*] Container ativo! Abrindo http://localhost:8090 no navegador...
    timeout /t 2 /nobreak >nul
    start http://localhost:8090
    echo [*] Pronto! Pressione qualquer tecla para encerrar este terminal (o container continuara rodando).
    pause >nul
    exit /b 0
)

where php >nul 2>nul
if %ERRORLEVEL% equ 0 (
    echo [*] Docker nao encontrado, mas PHP foi detectado no PATH.
    echo [*] Iniciando servidor embutido do PHP na porta 8090...
    timeout /t 1 /nobreak >nul
    start http://localhost:8090
    php -S localhost:8090 -t public public\index.php
    exit /b 0
)

echo [!] ERRO: Nem o Docker nem o executavel do PHP foram encontrados no PATH do Windows.
echo.
echo Para executar o SUS-systems, por favor:
echo   1. Instale o Docker Desktop: https://www.docker.com/products/docker-desktop
echo   OU
echo   2. Instale o PHP 8.2+: https://windows.php.net/download/
echo.
pause
exit /b 1
