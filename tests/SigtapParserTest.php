<?php
declare(strict_types=1);

use SusSystems\Domain\Sigtap\SigtapRepository;

$line = static function(string $code, string $name, string $min, string $max, string $sh, string $sa, string $sp): string {
    return str_pad($code,10) . str_pad(mb_convert_encoding($name, 'ISO-8859-1', 'UTF-8'),250) . '2F0003' . '00000000' . $min . $max . str_pad($sh,12,'0',STR_PAD_LEFT) . str_pad($sa,12,'0',STR_PAD_LEFT) . str_pad($sp,12,'0',STR_PAD_LEFT) . '06' . '123456' . '202610';
};
return [
    'SIGTAP interpreta posições, valores, idade e grupo' => static function() use ($line): void {
        $repo = new SigtapRepository();
        $item = $repo->parseLine($line('0301010072', 'CONSULTA MÉDICA', '0015', '9999', '12345', '67890', '23456'), ['06'=>'MAC'], ['03'=>'Procedimentos clínicos']);
        assert_same('03.01.01.007-2', $item['formattedCode']);
        assert_same('CONSULTA MÉDICA', $item['name']);
        $withNbsp = $line('0301010072', "TESTE\u{00A0}", '0000', '9999', '0', '0', '0');
        assert_same('TESTE', $repo->parseLine($withNbsp)['name']);
        assert_same(123.45, $item['vlSH']); assert_same(678.90, $item['vlSA']); assert_same(234.56, $item['vlSP']);
        assert_same(358.01, $item['vlTotalHosp']);
        assert_same('1a 3m', $item['idadeMin']); assert_same('Não se aplica', $item['idadeMax']);
        assert_same(3, $item['qtMax']); assert_same('MAC', $item['financiamento']); assert_same('123456', $item['rubrica']);
        assert_same('Procedimentos clínicos', $item['grupoNome']);
        assert_same(null, $repo->parseLine('curta'));
    },
    'SIGTAP busca ignora acentos sem alterar JSON' => static function() use ($line): void {
        $repo = new SigtapRepository();
        $first = $repo->parseLine($line('0301010072', 'ACIDO FÓLICO', '0000', '9999', '0', '0', '0'));
        $second = $repo->parseLine($line('0301010073', 'ACIDO ASCORBICO', '0000', '9999', '0', '0', '0'));
        do {
            $comp = (string) random_int(900000, 999999);
            $key = 'sigtap-bundle:v2:' . $comp;
            $cacheFile = (string) \SusSystems\Core\Config::get('cache.dir') . '/' . sha1($key) . '.cache';
        } while (is_file($cacheFile));
        \SusSystems\Core\Cache::default()->set($key, ['competencia'=>$comp, 'file'=>'fixture.zip', 'totalProcedures'=>2, 'procs'=>[$first['code']=>$first, $second['code']=>$second]]);
        try {
            foreach (['folico', 'FÓLICO', "FO\u{0301}LICO"] as $query) {
                $result = $repo->search($comp, $query, '', '', '', 120);
                assert_same(1, $result['returnedCount']);
                assert_same('ACIDO FÓLICO', $result['items'][0]['name']);
                assert_true(!array_key_exists('searchKey', $result['items'][0]));
            }
            $result = $repo->search($comp, 'ácido', '', '', '', 120);
            assert_same(2, $result['returnedCount']);
            assert_same('ACIDO ASCORBICO', $result['items'][1]['name']);
        } finally { @unlink($cacheFile); }
    },
    'SIGTAP interpreta idade livre, meses e anos' => static function() use ($line): void {
        $repo = new SigtapRepository();
        $item = $repo->parseLine($line('0301010072','TESTE','0000','0024','0','0','0'));
        assert_same('Livre', $item['idadeMin']); assert_same('2 anos', $item['idadeMax']);
        $item = $repo->parseLine($line('0301010072','TESTE','0003','XXXX','0','0','0'));
        assert_same('3 meses', $item['idadeMin']); assert_same('–', $item['idadeMax']);
    },
    'SIGTAP lê ZIP posicional e tabelas auxiliares' => static function() use ($line): void {
        $path = tempnam(sys_get_temp_dir(), 'sigtap-test-');
        $zip = new ZipArchive(); assert_true($zip->open($path, ZipArchive::OVERWRITE) === true);
        $zip->addFromString('dados/TB_PROCEDIMENTO.txt', $line('0301010072','CONSULTA MÉDICA','0000','9999','100','200','300') . "\r\n");
        $zip->addFromString('dados/tb_grupo.txt', '03' . str_pad(mb_convert_encoding('Procedimentos clínicos','ISO-8859-1','UTF-8'),100) . "202610\r\n");
        $zip->addFromString('dados/tb_financiamento.txt', '06' . str_pad('CUSTEIO TESTE',100) . "202610\r\n");
        $zip->close();
        try {
            $bundle = (new SigtapRepository())->parseZip($path, '202610', 'fixture.zip');
            assert_same(1, $bundle['totalProcedures']);
            assert_same('Procedimentos clínicos', $bundle['procs']['0301010072']['grupoNome']);
            assert_same('CUSTEIO TESTE', $bundle['procs']['0301010072']['financiamento']);
        } finally { unlink($path); }
    },
    'SIGTAP compara inclusões, exclusões, valores e regras' => static function() use ($line): void {
        $repo = new SigtapRepository();
        $base = $repo->parseLine($line('0301010072','CONSULTA','0000','9999','100','200','300'));
        $changed = $base; $changed['vlSA'] = 3.5;
        $rule = $base; $rule['code'] = '0301010073'; $rule['name'] = 'NOVO NOME';
        $previousRule = $rule; $previousRule['name'] = 'ANTIGO';
        $added = $base; $added['code'] = '0301010074';
        $removed = $base; $removed['code'] = '0301010075';
        $new = ['competencia'=>'202610','file'=>'novo.zip','totalProcedures'=>3,'procs'=>[$base['code']=>$changed,$rule['code']=>$rule,$added['code']=>$added]];
        $old = ['competencia'=>'202609','file'=>'antigo.zip','totalProcedures'=>3,'procs'=>[$base['code']=>$base,$rule['code']=>$previousRule,$removed['code']=>$removed]];
        $diff = $repo->diffBundles($new, $old);
        assert_same(1, $diff['summary']['addedCount']); assert_same(1, $diff['summary']['removedCount']);
        assert_same(1, $diff['summary']['valueChangedCount']); assert_same(1, $diff['summary']['ruleChangedCount']);
        assert_same(1.5, $diff['valueChanged'][0]['deltaSA']); assert_same('Nome alterado', $diff['ruleChanged'][0]['changes']);
    },
];
