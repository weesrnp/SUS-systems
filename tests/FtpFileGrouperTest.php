<?php
declare(strict_types=1);
use SusSystems\Domain\Ftp\FileGrouper;
use SusSystems\Domain\Ftp\Uf;

return [
    'Agrupa versões duplas e histórico por competência' => static function(): void {
        $files = [];
        foreach (['202408','202410','202409'] as $ym) $files[] = ['name'=>'BDSIA'.$ym.'.exe','size'=>10,'modifiedAt'=>'2024-01-01T00:00:00+00:00'];
        $rows = (new FileGrouper())->group($files, []);
        assert_same(2, count($rows));
        assert_same('BDSIA202409.exe (setembro)', $rows[0]['displayName']);
        assert_same('BDSIA202410.exe (outubro)', $rows[1]['displayName']);
        assert_same('BDSIA202408.exe', $rows[1]['history'][0]['name']);
    },
    'Agrupa UF por código e preserva histórico individual' => static function(): void {
        $files = [];
        foreach (['TERCEIROSBRASIL41_202401.zip','TERCEIROSBRASIL41_202402.zip','TERCEIROSBRASIL35_202402.zip'] as $name) $files[] = ['name'=>$name,'size'=>5,'modifiedAt'=>'2024-01-01'];
        $rows = (new FileGrouper())->group($files, []);
        assert_same(1, count($rows));
        assert_same('TERCEIROSBRASILUF_202402.zip (selecionar UF)', $rows[0]['displayName']);
        assert_same('TERCEIROSBRASIL41_202402.zip', $rows[0]['ufBySigla']->PR['name']);
        assert_same('2024/01', $rows[0]['ufHistoryBySigla']->PR[0]['label']);
        assert_same([], $rows[0]['history']);
    },
    'Filtra e descreve regex com e sem delimitador' => static function(): void {
        $files = [['name'=>'SCNES1234-COMPLETA.exe'], ['name'=>'SCNES1235-COMPLETA.exe'], ['name'=>'outro.zip']];
        $section = ['includeRegexes'=>['/^SCNES\d{4}/i'], 'excludeRegexes'=>['1234'], 'descriptionRules'=>[['regex'=>'^scnes\d+', 'description'=>'SCNES']]];
        $rows = (new FileGrouper())->group($files, $section);
        assert_same(1, count($rows)); assert_same('SCNES1235-COMPLETA.exe', $rows[0]['name']); assert_same('SCNES', $rows[0]['description']);
    },
    'Mapa de UFs está completo e ordenado' => static function(): void {
        assert_same('PR', Uf::map()['41']); assert_same(27, count(Uf::siglas())); assert_same('AC', Uf::siglas()[0]); assert_same('TO', Uf::siglas()[26]);
    },
];
