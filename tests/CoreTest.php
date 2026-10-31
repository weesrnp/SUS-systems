<?php
declare(strict_types=1);

use SusSystems\Core\Cache;
use SusSystems\Core\HttpException;
use SusSystems\Core\Request;
use SusSystems\Core\Response;
use SusSystems\Core\Router;

return [
    'Router: rota registrada via api() responde em /api/x e em /x' => function (): void {
        $router = new Router();
        $router->api('/ping', function (Request $req): array {
            return ['status' => 'pong', 'method' => $req->method];
        });

        // Teste em /api/ping
        $reqApi = new Request('GET', '/api/ping', []);
        $resApi = $router->dispatch($reqApi);
        assert_same(200, $resApi->status());
        assert_contains('"status":"pong"', $resApi->body());
        assert_contains('"method":"GET"', $resApi->body());

        // Teste em /ping
        $reqRoot = new Request('GET', '/ping', []);
        $resRoot = $router->dispatch($reqRoot);
        assert_same(200, $resRoot->status());
        assert_contains('"status":"pong"', $resRoot->body());
        assert_contains('"method":"GET"', $resRoot->body());
    },

    'Router: rota inexistente lança HttpException com status 404' => function (): void {
        $router = new Router();
        $req = new Request('GET', '/nao-existe', []);

        /** @var HttpException $e */
        $e = assert_throws(HttpException::class, function () use ($router, $req) {
            $router->dispatch($req);
        });

        assert_same(404, $e->status);
        assert_contains('não encontrada', $e->getMessage());
    },

    'Request: required retorna valor presente ou lança 422 quando ausente ou vazio' => function (): void {
        $req = new Request('GET', '/test', [
            'grupo'  => 'PA',
            'espaco' => '   ',
        ]);

        // Presente
        assert_same('PA', $req->required('grupo'));

        // Ausente → 422
        /** @var HttpException $e1 */
        $e1 = assert_throws(HttpException::class, fn() => $req->required('uf'));
        assert_same(422, $e1->status);
        assert_contains('uf', $e1->getMessage());

        // String vazia / apenas espaços → 422
        /** @var HttpException $e2 */
        $e2 = assert_throws(HttpException::class, fn() => $req->required('espaco'));
        assert_same(422, $e2->status);
        assert_contains('espaco', $e2->getMessage());
    },

    'Request: int retorna inteiro válido e lança 422 se fora do intervalo ou inválido' => function (): void {
        $req = new Request('GET', '/test', [
            'limit'        => '50',
            'negativo'     => '-5',
            'muito_alto'   => '999',
            'invalido'     => 'abc',
        ]);

        // Válido dentro do intervalo
        assert_same(50, $req->int('limit', 10, 1, 100));

        // Padrão quando não informado
        assert_same(20, $req->int('nao_informado', 20, 1, 100));

        // Fora do intervalo (menor que o mínimo) → 422
        /** @var HttpException $e1 */
        $e1 = assert_throws(HttpException::class, fn() => $req->int('negativo', 10, 0, 100));
        assert_same(422, $e1->status);

        // Fora do intervalo (maior que o máximo) → 422
        /** @var HttpException $e2 */
        $e2 = assert_throws(HttpException::class, fn() => $req->int('muito_alto', 10, 1, 100));
        assert_same(422, $e2->status);

        // Não numérico → 422
        /** @var HttpException $e3 */
        $e3 = assert_throws(HttpException::class, fn() => $req->int('invalido', 10, 1, 100));
        assert_same(422, $e3->status);
    },

    'Cache: grava, recupera, expira TTL e aceita diretório temporário isolado' => function (): void {
        $tmpDir = sys_get_temp_dir() . '/sus_cache_test_' . bin2hex(random_bytes(6));
        $cache = new Cache($tmpDir);

        try {
            // Set e Get
            $sampleData = ['nome' => 'SIA', 'versao' => 3, 'itens' => [1, 2, 3]];
            $cache->set('sistema_sia', $sampleData);

            $cached = $cache->get('sistema_sia', 60);
            assert_same($sampleData, $cached);

            // Chave inexistente retorna null
            assert_same(null, $cache->get('chave_que_nao_existe', 60));

            // TTL expirado (TTL = 0 expira imediatamente)
            assert_same(null, $cache->get('sistema_sia', 0));

            // Teste de remember: primeira chamada calcula, segunda reutiliza
            $chamadas = 0;
            $calculo = function () use (&$chamadas): string {
                $chamadas++;
                return 'resultado-computado';
            };

            $v1 = $cache->remember('rem_test', 60, $calculo);
            assert_same('resultado-computado', $v1);
            assert_same(1, $chamadas);

            $v2 = $cache->remember('rem_test', 60, $calculo);
            assert_same('resultado-computado', $v2);
            assert_same(1, $chamadas); // Não recalculou

            // Forçar refresh recalcula
            $v3 = $cache->remember('rem_test', 60, $calculo, refresh: true);
            assert_same('resultado-computado', $v3);
            assert_same(2, $chamadas);
        } finally {
            // Limpa diretório temporário
            if (is_dir($tmpDir)) {
                $files = glob($tmpDir . '/*') ?: [];
                foreach ($files as $f) {
                    @unlink($f);
                }
                @rmdir($tmpDir);
            }
        }
    },
];
