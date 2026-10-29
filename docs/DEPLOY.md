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

É possível publicar a aplicação na Vercel utilizando o runtime mantido pela comunidade (`vercel-php`).

### Arquivo `vercel.json` de Exemplo:
```json
{
  "functions": {
    "public/index.php": {
      "runtime": "vercel-php@0.7.0"
    }
  },
  "routes": [
    {
      "src": "/assets/(.*)",
      "dest": "/public/assets/$1"
    },
    {
      "src": "/(.*)",
      "dest": "/public/index.php"
    }
  ]
}
```

### Análise: Prós e Contras do Deploy Serverless

#### ✅ Prós:
- **Custo e Nível Gratuito**: Ideal para demonstrações públicas e consultas leves sem custo fixo de servidor.
- **Deploy Contínuo (CI/CD)**: Cada push no repositório GitHub gera automaticamente um preview ou produção.
- **SSL e CDN Globais**: Certificados TLS/HTTPS automáticos e distribuição de assets estáticos na borda (Edge).

#### ⚠️ Contras e Limitações Críticas:
- **Timeouts Rígidos de Execução**: Na Vercel, lambdas possuem timeout máximo de 10 a 60 segundos. O download e streaming de arquivos pesados de bases completas do SUS (que podem levar minutos via FTP) serão abruptamente interrompidos.
- **Portas e Redes Restritas para FTP**: O protocolo FTP ativo/passivo requer abertura de portas efêmeras altas (1024–65535). Ambientes serverless frequentemente bloqueiam conexões FTP de saída arbitrárias, necessitando de uma ponte HTTPS intermediária (`FTP_HTTPS_BRIDGE`).
- **Sistema de Arquivos Read-Only**: O disco da função serverless é somente-leitura (com exceção efêmera do `/tmp`). O cache serializado em `storage/cache/` perde a persistência entre diferentes invocações da lambda.
- **Runtime Comunitário**: O pacote `vercel-php` não é suportado oficialmente pela equipe da Vercel.

> **Recomendação**: Para uso em produção, extração contínua e processamento de remessas/SIGTAP, priorize **Docker**, **VPS Nginx** ou **Hospedagem Apache**.
