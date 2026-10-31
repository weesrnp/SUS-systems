# Guia de Implantação e Deploy — SUS-systems 3.0

Este documento reúne as diretrizes e modelos de configuração para implantação do **SUS-systems 3.0** em diferentes ambientes de hospedagem e produção.

---

## 1. Requisitos do Sistema

- **PHP**: Versão 8.2 ou 8.3 (64-bit recomendado).
- **Extensões PHP obrigatórias**:
  - `ext-ftp`: Para conexão passiva e streaming dos servidores DataSUS.
  - `ext-zip`: Para descompactação em memória e em disco dos pacotes SIGTAP.
  - `ext-curl`: Para verificações HTTP e ponte HTTPS opcional.
  - `ext-mbstring`: Para conversão segura de encodings (ISO-8859-1 / UTF-8) das listagens e arquivos posicionais.
- **Permissões de Sistema**:
  - Escrita no diretório `storage/cache/` para o usuário do servidor web (`www-data`, `nginx` ou usuário do cPanel).
- **DocumentRoot**:
  - **Obrigatório apontar para o diretório `public/`**, garantindo que arquivos de configuração (`config/`), templates (`templates/`) e fontes (`src/`) fiquem inacessíveis diretamente via web.

---

## 2. Implantação com Docker (Recomendado)

O projeto já disponibiliza uma imagem otimizada baseada em Debian com `php:8.3-apache`.

### Estrutura do Dockerfile (`docker/Dockerfile`)
A imagem compila as extensões `ftp` e `zip`, habilita os módulos Apache `rewrite` e `headers`, e configura o healthcheck automático em `/api/health`.

### Executando em Produção
```bash
# Subir serviço em background com rebuild
docker compose up -d --build

# Verificar logs e status
docker compose logs -f
docker compose ps
```

### Variáveis de Ambiente no Container
Defina as variáveis no `docker-compose.yml` ou em arquivo `.env`:
```yaml
environment:
  - APP_DEBUG=false
  - FTP_TIMEOUT=10
  - FTP_CACHE_TTL=600
  - SIGTAP_CACHE_TTL=86400
```

---

## 3. Hospedagem Compartilhada (cPanel / Apache)

O SUS-systems foi desenhado sem Composer ou frameworks pesados especificamente para rodar com excelência em servidores compartilhados.

### Passos de Instalação:
1. Extraia o conteúdo do projeto no servidor (ex: `/home/usuario/sus-systems`).
2. No painel cPanel (ou Plesk/DirectAdmin), configure o domínio ou subdomínio para apontar o **DocumentRoot** para a pasta `public/` (ex: `/home/usuario/sus-systems/public`).
3. O arquivo `public/.htaccess` já gerencia o roteamento completo:
   ```apache
   DirectoryIndex index.php

   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule ^ index.php [QSA,L]
   </IfModule>
   ```
4. Ajuste as permissões da pasta de cache:
   ```bash
   chmod -R 775 storage/cache
   ```
5. No seletor de versão PHP do cPanel, selecione **PHP 8.2 ou 8.3** e certifique-se de marcar as extensões `ftp`, `zip`, `curl` e `mbstring`.

---

## 4. Servidor Dedicado / VPS: Nginx + PHP-FPM

Para ambientes com tráfego elevado, a combinação Nginx com PHP-FPM oferece alta concorrência e excelente gerenciamento de streaming.

### Configuração do VirtualHost (`/etc/nginx/sites-available/sus-systems.conf`)

```nginx
server {
    listen 80;
    server_name sus.seudominio.gov.br;

    # O DocumentRoot DEVE apontar para public/
    root /var/www/sus-systems/public;
    index index.php;

    charset utf-8;

    # Headers de segurança
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    # Arquivos estáticos servidos diretamente com cache do navegador
    location /assets/ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
        try_files $uri =404;
    }

    # Bloqueio de arquivos ocultos (.git, .env, etc.)
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Roteamento de todas as URLs para o front controller index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Processamento PHP via FastCGI
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;

        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;

        # IMPORTANTE PARA STREAMING:
        # Desativa o buffering do FastCGI para permitir que grandes arquivos FTP
        # sejam transmitidos ao usuário em chunks imediatamente, sem sobrecarregar a memória
        fastcgi_buffering off;
        fastcgi_read_timeout 600s;
        fastcgi_send_timeout 600s;
    }
}
```

### Otimizações no `php.ini` (PHP-FPM):
```ini
memory_limit = 512M
max_execution_time = 0
output_buffering = Off
zlib.output_compression = Off
```

---

## 5. Plataformas Serverless (Vercel)

A publicação do backend PHP no ecossistema serverless da Vercel é realizada em produção através do runtime comunitário `vercel-php@0.7.4` (baseado em PHP 8.3.8 e AWS Lambda).

### 5.1. Arquitetura de Deploy Real (`vercel.json`)

Para que o runtime funcione perfeitamente com Server-Side Rendering (SSR) e assets estáticos sem conflito com o preset FastAPI legado:

1. **`framework: null`**: Sobrepõe a autodetecção da Vercel que tentava associar o projeto ao FastAPI/Python por conta da pasta `legacy-python/`.
2. **`outputDirectory: "public"`**: Servido diretamente pela CDN na borda (Edge) para entrega de alta velocidade de CSS, JS e imagens.
3. **`regions: ["gru1"]`**: Execução alocada em **São Paulo (GRU1)**, diminuindo drasticamente a latência de rede com os clusters oficiais do DataSUS no Brasil.
4. **`functions: {"api/index.php": { ... }}`**: Front controller serverless com `maxDuration: 60` segundos e exclusão de pastas desnecessárias (`excludeFiles`).
5. **`rewrites: [ { "source": "/(.*)", "destination": "/api/index.php" } ]`**: Redireciona todas as rotas web e endpoints da API para o ponto de entrada serverless `api/index.php`.
6. **`.vercelignore`**: Exclui estritamente `public/index.php` e `public/.htaccess`, evitando que a Vercel sirva o arquivo PHP como texto plano estático pela CDN.

#### Configuração `vercel.json` Oficial:
```json
{
  "framework": null,
  "buildCommand": "",
  "installCommand": "",
  "outputDirectory": "public",
  "regions": ["gru1"],
  "functions": {
    "api/index.php": {
      "runtime": "vercel-php@0.7.4",
      "maxDuration": 60,
      "excludeFiles": "{legacy-python/**,tests/**,docs/**,storage/cache/**,.github/**,docker/**,public/**}"
    }
  },
  "rewrites": [
    { "source": "/(.*)", "destination": "/api/index.php" }
  ]
}
```

#### Ponto de Entrada Serverless (`api/index.php`):
```php
<?php
declare(strict_types=1);

use SusSystems\Core\App;
use SusSystems\Core\Request;
use SusSystems\Core\Router;

require dirname(__DIR__) . '/src/bootstrap.php';

$router = new Router();
(require SUS_ROOT . '/config/routes.php')($router);

(new App($router))->handle(Request::fromGlobals())->send();
```

#### Arquivo `.vercelignore`:
```
legacy-python
storage/cache/*
!storage/cache/.gitignore
public/index.php
public/.htaccess
```

---

### 5.2. Fatos Medidos em Produção e Ajustes de Desempenho

Diferente de suposições genéricas sobre ambientes serverless, a validação empírica na Vercel demonstrou:

1. **FTP Direto Funciona Nativamente (`sourceMode: FTP_DIRECT`)**:
   - As conexões FTP passivas RFC 959 com os clusters oficiais do DataSUS (`arpoador`, `ftp`, `ftp2`) **funcionam perfeitamente sem necessidade de ponte HTTPS**.
   - O `FtpClient` conecta-se diretamente à porta 21 e negocia o canal de dados sem bloqueio pela infraestrutura serverless da Vercel.
   - A ponte `FTP_HTTPS_BRIDGE` permanece apenas como contingência opcional.

2. **Impacto Decisivo da Região `gru1` (São Paulo)**:
   - Medições comparativas entre `iad1` (Washington D.C., EUA) e `gru1` (São Paulo, Brasil):
     - **Remessa 410965**, partida fria: `iad1` ~10,5 s (ano 2026, 8 competências) vs `gru1` 3,5 s (ano 2025, 12 competências — mais arquivos e ainda assim ~3x mais rápido); em cache quente: **0,27 s**
     - **SIGTAP explore** (competência ainda não carregada, com descompactação/parse): **0,8 s**
     - **ftp-list SIA** (listagem completa de 4,3 MB): **1,8 s**
   - A proximidade com os servidores FTP do DataSUS no Brasil explica o ganho: cada comando FTP é uma ida e volta de rede.

3. **Armazenamento e Cache (`/tmp/sus-cache`)**:
   - Como o sistema de arquivos da função é `read-only`, o `config/app.php` detecta automaticamente o ambiente (`$env('VERCEL')`) e direciona o cache para `sys_get_temp_dir() . '/sus-cache'`.
   - O diretório `/tmp` é gravável, mas volátil (efêmero por instância lambda). Requisições em instâncias ativas reutilizam o cache quente com tempos inferiores a 300ms.

4. **Timeout de 60 Segundos (`maxDuration: 60`)**:
   - O limite de 60 segundos atende com folga as operações de catálogo, SIGTAP e remessas.
   - Para downloads de arquivos gigantescos (> 500 MB) em conexões lentas de usuários finais, recomenda-se a execução local via Docker Compose (`run_windows.bat` / `run_linux.sh`).

---

### 5.3. Análise: Prós e Contras do Deploy na Vercel

#### ✅ Prós:
- **Zero Custo Fixo**: Escalabilidade sob demanda sem necessidade de manter servidor VPS ligado.
- **Latência Menor com GRU1**: respostas em cache abaixo de 0,5 s; consultas frias em poucos segundos graças à proximidade com os servidores do DataSUS.
- **Assets em Edge CDN**: Entrega imediata de folhas de estilo e JavaScript modular nativo.
- **Deploy Git Automatizado**: Integração contínua a cada branch/PR.

#### ⚠️ Limitações Operacionais:
- Cache em disco não é persistente globalmente entre diferentes nós efêmeros (cada nova réplica de função inicia fria).
- Timeout de 60s inviabiliza streaming de arquivos em gigabytes sob conexões muito lentas.
