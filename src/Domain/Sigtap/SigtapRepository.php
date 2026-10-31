<?php
declare(strict_types=1);

namespace SusSystems\Domain\Sigtap;

use SusSystems\Core\Cache;
use SusSystems\Core\Config;
use SusSystems\Domain\Ftp\FtpClient;

final class SigtapRepository
{
    private const FTP_URL = 'ftp://ftp2.datasus.gov.br/pub/sistemas/tup/downloads/';
    private const COMPLEXIDADE = ['0'=>'Não se aplica', '1'=>'Atenção Básica (APS)', '2'=>'Média Complexidade (MC)', '3'=>'Alta Complexidade (AC)'];
    private const FINANCIAMENTO = ['01'=>'Atenção Básica (PAB)', '02'=>'Assistência Farmacêutica', '04'=>'Fundo de Ações Estratégicas e Compensações (FAEC)', '05'=>'Incentivo - MAC', '06'=>'Média e Alta Complexidade (MAC)', '07'=>'Vigilância em Saúde', '08'=>'Gestão do SUS'];

    public function competencias(bool $forceRefresh = false): array
    {
        [$files] = (new FtpClient())->listDirectory(self::FTP_URL, $forceRefresh);
        $byYm = [];
        foreach ($files as $file) {
            if (!preg_match('/^TabelaUnificada_(\d{6}).*\.zip$/i', $file['name'] ?? '', $m)) continue;
            $ym = $m[1];
            if (!isset($byYm[$ym]) || ($file['modifiedAt'] ?? '') > ($byYm[$ym]['modifiedAt'] ?? '')) {
                $byYm[$ym] = ['competencia'=>$ym, 'label'=>substr($ym, 0, 4) . '/' . substr($ym, 4), 'file'=>$file['name'], 'size'=>$file['size'] ?? 0, 'modifiedAt'=>$file['modifiedAt'] ?? null];
            }
        }
        krsort($byYm, SORT_STRING);
        return array_values($byYm);
    }

    public function load(string $competencia): array
    {
        $comp = str_replace('/', '', trim($competencia));
        if (!preg_match('/^\d{6}$/', $comp)) throw new \InvalidArgumentException('Competência SIGTAP inválida.');
        return Cache::default()->remember('sigtap-bundle:v2:' . $comp, (int) Config::get('cache.sigtap_ttl', 86400), function () use ($comp): array {
            $target = null;
            foreach ($this->competencias() as $item) if ($item['competencia'] === $comp) { $target = $item; break; }
            if ($target === null) throw new \InvalidArgumentException("Competência SIGTAP {$comp} não encontrada no FTP DataSUS.");
            $temp = (new FtpClient())->downloadToTemp(self::FTP_URL, $target['file']);
            try { return $this->parseZip($temp, $comp, $target['file']); }
            finally { @unlink($temp); }
        });
    }

    public function parseZip(string $path, string $comp, string $filename): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) throw new \InvalidArgumentException('Não foi possível abrir o ZIP SIGTAP.');
        try {
            $names = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name !== false) $names[strtolower(basename(str_replace('\\', '/', $name)))] = $name;
            }
            $fin = self::FINANCIAMENTO; $groups = [];
            foreach (['tb_financiamento.txt', 'tb_grupo.txt'] as $lookup) {
                if (!isset($names[$lookup])) continue;
                $stream = $zip->getStream($names[$lookup]);
                if ($stream === false) throw new \InvalidArgumentException('Não foi possível ler ' . $lookup . '.');
                try {
                    while (($line = fgets($stream)) !== false) {
                        if (strlen(rtrim($line, "\r\n")) < 102) continue;
                        $code = trim(substr($line, 0, 2));
                        $name = $this->strip(mb_convert_encoding(substr($line, 2, 100), 'UTF-8', 'ISO-8859-1'));
                        if ($code === '' || $name === '') continue;
                        if ($lookup === 'tb_grupo.txt') $groups[$code] = $name; else $fin[$code] = $name;
                    }
                } finally { fclose($stream); }
            }
            if (!isset($names['tb_procedimento.txt'])) throw new \InvalidArgumentException('Arquivo tb_procedimento.txt não localizado dentro do ZIP.');
            $stream = $zip->getStream($names['tb_procedimento.txt']);
            if ($stream === false) throw new \InvalidArgumentException('Não foi possível ler tb_procedimento.txt.');
            $procs = [];
            try {
                while (($line = fgets($stream)) !== false) {
                    $item = $this->parseLine(rtrim($line, "\r\n"), $fin, isset($names['tb_grupo.txt']) ? $groups : null);
                    if ($item !== null) $procs[$item['code']] = $item;
                }
            } finally { fclose($stream); }
            return ['competencia'=>$comp, 'file'=>$filename, 'totalProcedures'=>count($procs), 'procs'=>$procs];
        } finally { $zip->close(); }
    }

    public function parseLine(string $line, array $financiamento = [], ?array $grupos = null): ?array
    {
        if (strlen($line) < 326) return null;
        $code = trim(substr($line, 0, 10));
        if ($code === '') return null;
        $name = $this->strip(mb_convert_encoding(substr($line, 10, 250), 'UTF-8', 'ISO-8859-1'));
        $complexidade = trim(substr($line, 260, 1)); $sexo = trim(substr($line, 261, 1));
        $qt = trim(substr($line, 262, 4)); $min = trim(substr($line, 274, 4)); $max = trim(substr($line, 278, 4));
        $sh = $this->cents(substr($line, 282, 12)); $sa = $this->cents(substr($line, 294, 12)); $sp = $this->cents(substr($line, 306, 12));
        $fin = trim(substr($line, 318, 2)); $grupo = substr($code, 0, 2); $padded = str_pad($code, 10, '0', STR_PAD_LEFT);
        $proc = ['code'=>$code, 'formattedCode'=>substr($padded,0,2).'.'.substr($padded,2,2).'.'.substr($padded,4,2).'.'.substr($padded,6,3).'-'.substr($padded,9,1), 'name'=>$name, 'grupo'=>$grupo, 'subgrupo'=>substr($code,0,4), 'complexidadeCode'=>$complexidade, 'complexidade'=>self::COMPLEXIDADE[$complexidade] ?? $complexidade, 'sexo'=>$sexo, 'qtMax'=>ctype_digit($qt) ? (int)$qt : 0, 'idadeMin'=>$this->age($min), 'idadeMax'=>$this->age($max), 'vlSH'=>$sh, 'vlSA'=>$sa, 'vlSP'=>$sp, 'vlTotalHosp'=>round($sh+$sp,2), 'financiamentoCode'=>$fin, 'financiamento'=>$financiamento[$fin] ?? self::FINANCIAMENTO[$fin] ?? $fin, 'rubrica'=>trim(substr($line,320,6))];
        if ($grupos !== null) $proc['grupoNome'] = $grupos[$grupo] ?? '';
        return $proc;
    }

    private function strip(string $value): string { return preg_replace('/^[\s\p{Z}]+|[\s\p{Z}]+$/u', '', $value); }
    private function cents(string $raw): float { $s = trim($raw); return $s !== '' && ctype_digit($s) ? round((int)$s / 100, 2) : 0.0; }
    private function age(string $raw): string
    {
        if ($raw === '' || !ctype_digit($raw)) return '–';
        $val = (int)$raw;
        if ($val >= 9999) return 'Não se aplica';
        if ($val === 0) return 'Livre';
        $years = intdiv($val, 12); $months = $val % 12;
        if ($years && $months) return "{$years}a {$months}m";
        return $years ? "{$years} anos" : "{$months} meses";
    }

    private function searchText(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_D);
            if ($normalized !== false) $value = $normalized;
        } else {
            $value = strtr($value, [
                'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'ç'=>'c', 'è'=>'e',
                'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ñ'=>'n',
                'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u',
                'ü'=>'u', 'ý'=>'y', 'ÿ'=>'y', 'ā'=>'a', 'ă'=>'a', 'ą'=>'a', 'ć'=>'c', 'ĉ'=>'c',
                'ċ'=>'c', 'č'=>'c', 'ď'=>'d', 'ē'=>'e', 'ĕ'=>'e', 'ė'=>'e', 'ę'=>'e', 'ě'=>'e',
                'ĝ'=>'g', 'ğ'=>'g', 'ġ'=>'g', 'ģ'=>'g', 'ĥ'=>'h', 'ĩ'=>'i', 'ī'=>'i', 'ĭ'=>'i',
                'į'=>'i', 'ĵ'=>'j', 'ķ'=>'k', 'ĺ'=>'l', 'ļ'=>'l', 'ľ'=>'l', 'ń'=>'n', 'ņ'=>'n',
                'ň'=>'n', 'ō'=>'o', 'ŏ'=>'o', 'ő'=>'o', 'ŕ'=>'r', 'ŗ'=>'r', 'ř'=>'r', 'ś'=>'s',
                'ŝ'=>'s', 'ş'=>'s', 'š'=>'s', 'ţ'=>'t', 'ť'=>'t', 'ũ'=>'u', 'ū'=>'u', 'ŭ'=>'u',
                'ů'=>'u', 'ű'=>'u', 'ų'=>'u', 'ŵ'=>'w', 'ŷ'=>'y', 'ź'=>'z', 'ż'=>'z', 'ž'=>'z',
                'ơ'=>'o', 'ư'=>'u', 'ǎ'=>'a', 'ǐ'=>'i', 'ǒ'=>'o', 'ǔ'=>'u', 'ǖ'=>'u', 'ǘ'=>'u',
                'ǚ'=>'u', 'ǜ'=>'u', 'ǟ'=>'a', 'ǡ'=>'a', 'ǧ'=>'g', 'ǩ'=>'k', 'ǫ'=>'o', 'ǭ'=>'o',
                'ǰ'=>'j', 'ǵ'=>'g', 'ǹ'=>'n', 'ǻ'=>'a', 'ȁ'=>'a', 'ȃ'=>'a', 'ȅ'=>'e', 'ȇ'=>'e',
                'ȉ'=>'i', 'ȋ'=>'i', 'ȍ'=>'o', 'ȏ'=>'o', 'ȑ'=>'r', 'ȓ'=>'r', 'ȕ'=>'u', 'ȗ'=>'u',
                'ș'=>'s', 'ț'=>'t', 'ȟ'=>'h', 'ȧ'=>'a', 'ȩ'=>'e', 'ȫ'=>'o', 'ȭ'=>'o', 'ȯ'=>'o',
                'ȱ'=>'o', 'ȳ'=>'y'
            ]);
        }
        return preg_replace('/\p{Mn}/u', '', $value) ?? $value;
    }

    public function search(string $comp, string $q, string $grupo, string $complexidade, string $financiamento, int $limit): array
    {
        $bundle = $this->load($comp); $query = $this->searchText(trim($q)); $digits = preg_replace('/\D/', '', $query); $items = [];
        foreach ($bundle['procs'] as $proc) {
            if ($grupo !== '' && $proc['grupo'] !== $grupo) continue;
            if ($complexidade !== '' && $proc['complexidadeCode'] !== $complexidade) continue;
            if ($financiamento !== '' && $proc['financiamentoCode'] !== $financiamento) continue;
            if ($query !== '' && !str_contains($this->searchText($proc['name']), $query) && !($digits !== '' && str_contains($proc['code'], $digits)) && !str_contains(mb_strtolower($proc['formattedCode']), $query)) continue;
            $items[] = $proc;
            if (count($items) >= $limit) break;
        }
        return ['competencia'=>$bundle['competencia'], 'file'=>$bundle['file'], 'totalInTable'=>$bundle['totalProcedures'], 'returnedCount'=>count($items), 'items'=>$items];
    }

    public function diff(string $compNew, string $compOld): array { return $this->diffBundles($this->load($compNew), $this->load($compOld)); }

    public function diffBundles(array $new, array $old): array
    {
        $added = []; $removed = []; $values = []; $rules = [];
        foreach ($new['procs'] as $code => $cur) {
            $code = (string) $code;
            if (!isset($old['procs'][$code])) { $added[] = $cur; continue; }
            $prev = $old['procs'][$code];
            $sa = round($cur['vlSA'] - $prev['vlSA'], 2); $sh = round($cur['vlSH'] - $prev['vlSH'], 2); $sp = round($cur['vlSP'] - $prev['vlSP'], 2);
            if ($sa != 0 || $sh != 0 || $sp != 0) {
                $values[] = ['code'=>$code,'formattedCode'=>$cur['formattedCode'],'name'=>$cur['name'],'oldSA'=>$prev['vlSA'],'newSA'=>$cur['vlSA'],'deltaSA'=>$sa,'oldSH'=>$prev['vlSH'],'newSH'=>$cur['vlSH'],'deltaSH'=>$sh,'oldSP'=>$prev['vlSP'],'newSP'=>$cur['vlSP'],'deltaSP'=>$sp,'financiamento'=>$cur['financiamento']];
            } else {
                $changes = [];
                if ($cur['name'] !== $prev['name']) $changes[] = 'Nome alterado';
                if ($cur['complexidadeCode'] !== $prev['complexidadeCode']) $changes[] = 'Complexidade: ' . $prev['complexidade'] . ' → ' . $cur['complexidade'];
                if ($cur['financiamentoCode'] !== $prev['financiamentoCode']) $changes[] = 'Financiamento: ' . $prev['financiamentoCode'] . ' → ' . $cur['financiamentoCode'];
                if ($cur['idadeMin'] !== $prev['idadeMin'] || $cur['idadeMax'] !== $prev['idadeMax']) $changes[] = 'Idade: (' . $prev['idadeMin'] . ' a ' . $prev['idadeMax'] . ') → (' . $cur['idadeMin'] . ' a ' . $cur['idadeMax'] . ')';
                if ($changes) $rules[] = ['code'=>$code, 'formattedCode'=>$cur['formattedCode'], 'name'=>$cur['name'], 'changes'=>implode(' · ', $changes)];
            }
        }
        foreach ($old['procs'] as $code => $proc) if (!isset($new['procs'][$code])) $removed[] = $proc;
        return ['compNew'=>$new['competencia'], 'compOld'=>$old['competencia'], 'fileNew'=>$new['file'], 'fileOld'=>$old['file'], 'summary'=>['totalNew'=>$new['totalProcedures'],'totalOld'=>$old['totalProcedures'],'addedCount'=>count($added),'removedCount'=>count($removed),'valueChangedCount'=>count($values),'ruleChangedCount'=>count($rules)], 'added'=>array_slice($added,0,200), 'removed'=>array_slice($removed,0,200), 'valueChanged'=>array_slice($values,0,200), 'ruleChanged'=>array_slice($rules,0,200)];
    }
}
