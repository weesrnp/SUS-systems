<?php
declare(strict_types=1);

use SusSystems\Core\HttpException;
use SusSystems\Core\Request;
use SusSystems\Domain\Ftp\FtpClient;
use SusSystems\Domain\Remessa\RemessaService;
use SusSystems\Http\Controllers\RemessaController;

return [
    'Remessa combina verificação e carga por competência e tipo' => static function(): void {
        $reports = [
            'FTP_AM41096501N202608.TXT'=>"Arquivo de Remessa : AM41096501N202608.DTS  MUNICIPIO\nData de Chegada : 21/09/2026 16:39:07\nData da Carga : 09/10/26 06:54:47\nVERIFICACAO DA REMESSA COM ERRO\n",
            'FTP_AM41096501N202607.TXT'=>"Arquivo de Remessa : AM41096501N202607.DTS MUNICIPIO\nData de Chegada : 17/09/2026 08:33:07\nData da Carga : 19/09/26 04:28:39\nVERIFICACAO DA REMESSA OK\n",
            'hm41096501n202607.txt'=>"Arquivo de Remessa : AM41096501N202607.DTS\nData da Carga : 19/09/26 12:26:09\n",
            'FTP_AM41096501R202607.TXT'=>"Arquivo de Remessa : AM41096501R202607.DTS\nData de Chegada : 20/09/2026 10:00:00\nData da Carga : 20/09/26 11:00:00\nVERIFICACAO DA REMESSA OK\n",
            'FTP_AM99999901N202608.TXT'=>'não deve aparecer',
        ];
        $rows = (new RemessaService())->parseReports($reports, '410965', '2026');
        assert_same(3, count($rows));
        assert_same('202608', $rows[0]['competencia']);
        assert_same('🚫 Erro na verificação da remessa', $rows[0]['status']);
        assert_same('FTP_AM41096501N202608.TXT', $rows[0]['statusFile']); assert_same('-', $rows[0]['cargaEm']);
        assert_same('AM41096501N202607.DTS', $rows[1]['remessa']);
        assert_same('19/09/26 12:26:09', $rows[1]['cargaEm']); assert_same('hm41096501n202607.txt', $rows[1]['statusFile']);
        assert_same('✅ Remessa processada sem erro', $rows[1]['status']);
        assert_same('⏳ Verificada, aguardando carga', $rows[2]['status']);
    },
    'Remessa reutiliza cache por arquivo e atualiza quando metadados mudam' => static function(): void {
        $dir = sys_get_temp_dir() . '/sus-remessa-test-' . bin2hex(random_bytes(8));
        $cache = new \SusSystems\Core\Cache($dir);
        $entries = [
            ['name'=>'FTP_AM41096501N202601.TXT', 'size'=>100, 'modifiedAt'=>'2026-01-01T00:00:00+00:00'],
            ['name'=>'HM41096501N202601.TXT', 'size'=>200, 'modifiedAt'=>'2026-01-02T00:00:00+00:00'],
        ];
        $downloads = []; $lists = 0;
        $withFiles = static function(string $url, string $pattern, callable $callback) use (&$entries, &$downloads, &$lists): mixed {
            $lists++;
            assert_same('*410965*', $pattern);
            return $callback($entries, static function(string $name) use (&$downloads): string {
                $downloads[$name] = ($downloads[$name] ?? 0) + 1;
                return "Arquivo de Remessa : AM41096501N202601.DTS\nData de Chegada : 23/02/2026\nData da Carga : 23/02/26\n";
            });
        };
        try {
            $service = new RemessaService($withFiles, $cache);
            $first = $service->consultar('410965', '2026');
            assert_same(1, count($first['rows'])); assert_same(2, array_sum($downloads)); assert_same(1, $lists);
            $second = $service->consultar('410965', '2026');
            assert_same($first, $second); assert_same(1, $lists);
            $cache->set('remessa:410965:2026', null);
            assert_same($first, $service->consultar('410965', '2026'));
            assert_same(2, $lists); assert_same(2, array_sum($downloads));
            $entries[0]['size']++;
            $cache->set('remessa:410965:2026', null);
            assert_same($first, $service->consultar('410965', '2026'));
            assert_same(3, array_sum($downloads));
            $entries[1]['modifiedAt'] = '2026-01-03T00:00:00+00:00';
            $cache->set('remessa:410965:2026', null);
            assert_same($first, $service->consultar('410965', '2026'));
            assert_same(4, array_sum($downloads));
        } finally {
            foreach (glob($dir . '/*.cache') ?: [] as $file) unlink($file);
            rmdir($dir);
        }
    },
    'Remessa não guarda falha de download no cache' => static function(): void {
        $dir = sys_get_temp_dir() . '/sus-remessa-test-' . bin2hex(random_bytes(8));
        $cache = new \SusSystems\Core\Cache($dir); $calls = 0;
        $withFiles = static function(string $url, string $pattern, callable $callback) use (&$calls): mixed {
            return $callback([['name'=>'FTP_AM41096501N202601.TXT','size'=>10,'modifiedAt'=>'2026-01-01']], static function(string $name) use (&$calls): string {
                if (++$calls === 1) throw new RuntimeException('Falha simulada');
                return 'Arquivo de Remessa : AM41096501N202601.DTS';
            });
        };
        try {
            $service = new RemessaService($withFiles, $cache);
            assert_true(isset($service->consultar('410965', '2026')['error']));
            assert_same(1, count($service->consultar('410965', '2026')['rows']));
            assert_same(2, $calls);
        } finally {
            foreach (glob($dir . '/*.cache') ?: [] as $file) unlink($file);
            rmdir($dir);
        }
    },
    'Remessa valida IBGE e ano antes de acessar rede' => static function(): void {
        $controller = new RemessaController();
        foreach ([['ibge'=>'12345','ano'=>'2026'], ['ibge'=>'12345a','ano'=>'2026'], ['ibge'=>'410965','ano'=>'26'], ['ibge'=>'410965','ano'=>'20/6']] as $query) {
            $error = assert_throws(HttpException::class, static fn() => $controller->remessa(new Request('GET', '/api/remessa', $query)));
            assert_same(422, $error->status);
        }
    },
    'Busca FTP por padrão rejeita host e padrão indevidos' => static function(): void {
        $client = new FtpClient();
        $invalidPattern = assert_throws(HttpException::class, static fn() => $client->listPattern('ftp://arpoador.datasus.gov.br/siasus/LogCarga/', '../*'));
        assert_same(400, $invalidPattern->status);
        $invalidHost = assert_throws(HttpException::class, static fn() => $client->listPattern('ftp://evil.com/', '*410965*'));
        assert_same(400, $invalidHost->status);
    },
];
