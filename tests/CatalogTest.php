<?php
declare(strict_types=1);

use SusSystems\Core\HttpException;
use SusSystems\Core\Request;
use SusSystems\Domain\Catalog\Catalog;
use SusSystems\Http\Controllers\CatalogController;

return [
    'Catalog encontra subgrupos e respeita grupo e nome' => static function (): void {
        $catalog = new Catalog();
        assert_same('Tabelas', $catalog->findSubgroup('SIGTAP', 'Tabelas')['name']);
        assert_same('Outros', $catalog->findSubgroup('CNES', 'Outros')['name']);
        assert_same(null, $catalog->findSubgroup('SIGTAP', 'Outros inexistente'));
        assert_same(null, $catalog->findSubgroup('inexistente', 'Tabelas'));
        assert_same(null, $catalog->findSubgroup('sigtap', 'Tabelas'));
        assert_same('AC', $catalog->ufs()[0]);
        assert_same(27, count($catalog->ufs()));
    },
    'Todos os padrões internos do catálogo compilam em PCRE' => static function (): void {
        $count = 0;
        $walk = static function (array $data) use (&$walk, &$count): void {
            foreach ($data as $key => $value) {
                if ($key === 'regex') {
                    assert_true(@preg_match($value, '') !== false, 'Regex inválida: ' . $value);
                    $count++;
                } elseif ($key === 'includeRegexes' || $key === 'excludeRegexes') {
                    foreach ($value as $pattern) {
                        assert_true(@preg_match($pattern, '') !== false, 'Regex inválida: ' . $pattern);
                        $count++;
                    }
                } elseif (is_array($value)) $walk($value);
            }
        };
        $walk((new Catalog())->areas());
        assert_true($count > 0);
    },
    'Catálogo público equivale ao JSON integral do legado Python' => static function (): void {
        // SHA-256 do JSON compacto UTF-8 de CATALOG_AREAS e UFs ordenadas do legado.
        $json = json_encode((new CatalogController())->catalog(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        assert_same('09f46f4253bcfe67dd60e581f7067885082b98735abf43c1dfef87e36bc93a2f', hash('sha256', $json));
    },
    'Subgrupo inexistente retorna 404 e view sem seções fica pronta' => static function (): void {
        $controller = new CatalogController();
        $error = assert_throws(HttpException::class, static fn() => $controller->subgroupData(new Request('GET', '/api/subgroup-data', ['group_id'=>'CNES', 'sub_name'=>'Inexistente'])));
        assert_same(404, $error->status);
        $view = $controller->subgroupData(new Request('GET', '/api/subgroup-data', ['group_id'=>'SIGTAP', 'sub_name'=>'Consulta & Comparativo']));
        assert_same('sigtap', $view['specialView']);
        assert_same([], $view['sections']);
        assert_same('READY', $view['sourceSummary']);
    },
];
