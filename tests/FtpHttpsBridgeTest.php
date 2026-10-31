<?php
declare(strict_types=1);

use SusSystems\Core\Config;
use SusSystems\Domain\Ftp\FtpClient;

return [
    'FtpHttpsBridge: listDirectory e streamFile via ponte local' => function (): void {
        // Verifica se proc_open está disponível
        if (!function_exists('proc_open')) {
            throw new RuntimeException('proc_open não disponível para o teste da ponte.');
        }

        $routerFile = __DIR__ . '/fixtures/bridge_router.php';
        if (!file_exists($routerFile)) {
            throw new RuntimeException('Fixture bridge_router.php não encontrada.');
        }

        // Obtém uma porta livre efêmera dinamicamente
        $server = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if (!$server) {
            throw new RuntimeException('Não foi possível alocar porta para o teste da ponte.');
        }
        $name = stream_socket_get_name($server, false);
        fclose($server);
        $port = (int) substr(strrchr($name, ':'), 1);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $cmd = 'exec php -S 127.0.0.1:' . $port . ' ' . escapeshellarg($routerFile);
        $proc = @proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($proc)) {
            throw new RuntimeException('Não foi possível iniciar php -S para o teste da ponte.');
        }

        // Aguarda php -S subir e responder
        $started = false;
        for ($i = 0; $i < 20; $i++) {
            usleep(50000); // 50ms
            $status = proc_get_status($proc);
            if (!$status['running']) {
                break;
            }
            $conn = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if ($conn) {
                fclose($conn);
                $started = true;
                break;
            }
        }

        if (!$started) {
            if (is_resource($proc)) {
                if (isset($pipes[0]) && is_resource($pipes[0])) fclose($pipes[0]);
                if (isset($pipes[1]) && is_resource($pipes[1])) fclose($pipes[1]);
                if (isset($pipes[2]) && is_resource($pipes[2])) fclose($pipes[2]);
                proc_terminate($proc, 9);
                proc_close($proc);
            }
            throw new RuntimeException('php -S não iniciou a tempo para o teste da ponte.');
        }

        $originalConfig = SUS_ROOT . '/config/app.php';
        $tmpConfig = tempnam(sys_get_temp_dir(), 'sus_bridge_cfg_');
        if ($tmpConfig === false) throw new RuntimeException('Não foi possível criar configuração temporária.');
        $tmpCache = $tmpConfig . '_cache';

        try {
            // Prepara configuração temporária apontando para a ponte e permitindo 127.0.0.1
            $cfg = require $originalConfig;
            $cfg['cache']['dir'] = $tmpCache;
            $cfg['ftp']['https_bridge'] = "http://127.0.0.1:{$port}";
            if (!in_array('127.0.0.1', $cfg['ftp']['allowed_hosts'] ?? [], true)) {
                $cfg['ftp']['allowed_hosts'][] = '127.0.0.1';
            }
            // Timeout de 1s para o connect FTP falhar rápido na porta 21 (forçando fallback para a ponte)
            $cfg['ftp']['timeout'] = 1;
            file_put_contents($tmpConfig, '<?php return ' . var_export($cfg, true) . ';');
            Config::load($tmpConfig);

            $client = new FtpClient();
            [$entries, $mode] = $client->listDirectory('ftp://127.0.0.1/test_dir', true);

            // Asserções para listagem via ponte
            assert_same('HTTPS_BRIDGE', $mode, 'sourceMode deve ser HTTPS_BRIDGE');
            assert_same(1, count($entries), 'Deve retornar exatamente 1 entrada');
            assert_same('arquivo_mock_bridge.zip', $entries[0]['name']);
            assert_same(34, $entries[0]['size']);

            // Asserções para download via streaming na ponte
            $streamed = '';
            $bytes = $client->streamFile('ftp://127.0.0.1/test_dir', 'arquivo_mock_bridge.zip', function (string $chunk) use (&$streamed): void {
                $streamed .= $chunk;
            });

            $expectedPayload = 'CONTEUDO_MOCK_DA_PONTE_HTTPS_12345';
            assert_same($expectedPayload, $streamed, 'Bytes recebidos no stream devem ser idênticos ao mock');
            assert_same(strlen($expectedPayload), $bytes, 'Total de bytes deve conferir com tamanho do payload');
            assert_same('HTTPS_STREAM_BRIDGE', $client->downloadMode(), 'downloadMode deve ser HTTPS_STREAM_BRIDGE');

            // Um redirecionamento não deve ser seguido em nenhum endpoint.
            [$redirectEntries, $redirectMode] = $client->listDirectory('ftp://127.0.0.1/redirect', true);
            assert_same([], $redirectEntries);
            assert_same('OFFLINE', $redirectMode);
            $redirectBytes = '';
            $error = assert_throws(\SusSystems\Core\HttpException::class, static function () use ($client, &$redirectBytes): void {
                $client->streamFile('ftp://127.0.0.1/test_dir', 'redirect.zip', static function (string $chunk) use (&$redirectBytes): void { $redirectBytes .= $chunk; });
            });
            assert_same(502, $error->status);
            assert_same('', $redirectBytes);
        } finally {
            Config::load($originalConfig);
            foreach (glob($tmpCache . '/*.cache') ?: [] as $file) @unlink($file);
            if (is_dir($tmpCache)) @rmdir($tmpCache);
            if ($tmpConfig && file_exists($tmpConfig)) {
                @unlink($tmpConfig);
            }
            if (is_resource($proc)) {
                if (isset($pipes[0]) && is_resource($pipes[0])) fclose($pipes[0]);
                if (isset($pipes[1]) && is_resource($pipes[1])) fclose($pipes[1]);
                if (isset($pipes[2]) && is_resource($pipes[2])) fclose($pipes[2]);
                $status = proc_get_status($proc);
                if (!empty($status['pid']) && $status['running'] && function_exists('posix_kill')) {
                    @posix_kill($status['pid'], 9);
                }
                @proc_terminate($proc, 9);
                @proc_close($proc);
            }
        }
    },
];
