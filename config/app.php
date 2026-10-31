<?php
declare(strict_types=1);

$env = static fn (string $key, mixed $default = null): mixed => ($v = getenv($key)) === false ? $default : $v;

return [
    'name'    => 'SUS-systems',
    'version' => '3.0.0',
    'debug'   => filter_var($env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),

    'cache' => [
        'dir'         => $env('CACHE_DIR', SUS_ROOT . '/storage/cache'),
        'ftp_ttl'     => (int) $env('FTP_CACHE_TTL', 600),      // listagens FTP: 10 min
        'sigtap_ttl'  => (int) $env('SIGTAP_CACHE_TTL', 86400), // competência SIGTAP parseada: 24 h
    ],

    'ftp' => [
        'timeout'      => (int) $env('FTP_TIMEOUT', 8),
        'user'         => 'anonymous',
        'password'     => 'anonymous@datasus.gov.br',
        // Somente estes hosts podem ser acessados (evita SSRF via ?url=)
        'allowed_hosts' => ['arpoador.datasus.gov.br', 'ftp.datasus.gov.br', 'ftp2.datasus.gov.br'],
        // Ponte HTTPS opcional para redes que bloqueiam a porta 21 (vazio = desativado)
        'https_bridge' => $env('FTP_HTTPS_BRIDGE', ''),
    ],

    'municipio_padrao' => [
        'nome' => 'Honório Serpa - PR',
        'ibge' => '410965',
    ],
];
