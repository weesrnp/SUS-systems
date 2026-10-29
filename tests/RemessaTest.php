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
