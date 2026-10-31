<?php
declare(strict_types=1);
namespace SusSystems\Domain\Ftp;

final class FileGrouper
{
    private const YM = '/(?<![0-9])20[0-3][0-9]_?[01][0-9](?![0-9])/';
    public function group(array $rawFiles, array $section): array
    {
        $families = [];
        foreach ($rawFiles as $item) {
            $name = $item['name'] ?? '';
            if ($name === '') continue;
            $inc = ($section['includePrefixes'] ?? []) || ($section['includeRegexes'] ?? []);
            if ($inc && !$this->matches($name, $section['includePrefixes'] ?? [], $section['includeRegexes'] ?? [])) continue;
            if ($this->matches($name, $section['excludePrefixes'] ?? [], $section['excludeRegexes'] ?? [])) continue;
            preg_match(self::YM, $name, $m);
            $ym = isset($m[0]) ? str_replace('_', '', $m[0]) : null;
            $kind = $this->ufCode($name, 'DSIHD017_') ? 'dsihd017' : ($this->ufCode($name, 'TERCEIROSBRASIL') ? 'uf_cod' : ($this->ufSigla($name) ? 'uf_sigla' : ($ym ? 'versioned' : 'normal')));
            $sp = $this->special($name);
            $key = $sp['familyKey'] ?? $this->familyKey($name);
            $families[$key][] = array_merge($item, ['ym' => $ym, 'kind' => $kind, 'specialVersion' => $sp]);
        }
        $out = [];
        foreach ($families as $files) {
            $sorted = $this->sorted($files);
            $top = $sorted[0];
            $dual = in_array($top['specialVersion']['familyKey'] ?? null, ['BDSIA', 'EQUIPESESFBRASIL'], true);
            $count = $dual ? min(2, count($sorted)) : 1;
            $history = [];
            foreach (array_slice($sorted, $count) as $f) {
                if (!$f['ym'] && !$f['specialVersion']) continue;
                $history[] = ['name' => $f['name'], 'ym' => $f['ym'], 'label' => ($f['specialVersion']['label'] ?? null) ?: ($f['ym'] ? substr($f['ym'], 0, 4) . '/' . substr($f['ym'], 4) : $f['name']), 'size' => $f['size'] ?? 0, 'modifiedAt' => $f['modifiedAt'] ?? null];
            }
            $ufKind = in_array($top['kind'], ['uf_sigla', 'uf_cod', 'dsihd017'], true) ? $top['kind'] : null;
            $byUf = []; $ufHistory = [];
            if ($ufKind) {
                $lists = [];
                foreach ($files as $f) {
                    $uf = match ($ufKind) { 'dsihd017' => $this->ufCode($f['name'], 'DSIHD017_'), 'uf_cod' => $this->ufCode($f['name'], 'TERCEIROSBRASIL'), default => $this->ufSigla($f['name']) };
                    if ($uf) $lists[$uf][] = $f;
                }
                foreach ($lists as $uf => $list) {
                    $list = $this->sorted($list); $best = $list[0];
                    $byUf[$uf] = ['name' => $best['name'], 'size' => $best['size'] ?? 0, 'modifiedAt' => $best['modifiedAt'] ?? null, 'ym' => $best['ym']];
                    foreach (array_slice($list, 1) as $f) if ($f['ym']) $ufHistory[$uf][] = ['name' => $f['name'], 'ym' => $f['ym'], 'label' => substr($f['ym'], 0, 4) . '/' . substr($f['ym'], 4)];
                }
            }
            for ($i = 0; $i < $count; $i++) {
                $cur = $sorted[$i]; $name = $cur['name']; $display = $name;
                if ($ufKind) {
                    $dot = strrpos($name, '.'); $stem = $dot === false ? $name : substr($name, 0, $dot); $ext = $dot === false ? '' : substr($name, $dot);
                    if (preg_match('/^(TERCEIROSBRASIL|DSIHD017_)(\d{2})(_\d{6})$/i', $stem, $m)) $display = $m[1] . 'UF' . $m[3] . $ext . ' (selecionar UF)';
                    else {
                        $replaced = $stem;
                        foreach (Uf::siglas() as $uf) $replaced = preg_replace('/_' . $uf . '(?=_|\d{6})/i', '_UF', $replaced);
                        if ($replaced !== $stem) $display = $replaced . $ext . ' (selecionar UF)';
                    }
                } elseif ($dual && preg_match('/\d{4}(\d{2})/', $name, $m)) {
                    $months = ['01'=>'janeiro','02'=>'fevereiro','03'=>'março','04'=>'abril','05'=>'maio','06'=>'junho','07'=>'julho','08'=>'agosto','09'=>'setembro','10'=>'outubro','11'=>'novembro','12'=>'dezembro'];
                    if (isset($months[$m[1]])) $display .= ' (' . $months[$m[1]] . ')';
                } elseif (!$dual && isset($cur['specialVersion']['displaySuffix'])) $display .= ' ' . $cur['specialVersion']['displaySuffix'];
                $description = '–';
                foreach ($section['descriptionRules'] ?? [] as $rule) if ($this->matches($name, isset($rule['startsWith']) ? [$rule['startsWith']] : [], isset($rule['regex']) ? [$rule['regex']] : [])) { $description = $rule['description'] ?? '–'; break; }
                $out[] = ['name'=>$name, 'displayName'=>$display, 'description'=>$description, 'size'=>$cur['size'] ?? 0, 'modifiedAt'=>$cur['modifiedAt'] ?? null, 'ym'=>$cur['ym'], 'kind'=>$cur['kind'], 'history'=>$ufKind ? [] : ($i === 0 ? $history : []), 'ufBySigla'=>$ufKind && $i === 0 ? (object) $byUf : null, 'ufHistoryBySigla'=>$ufKind && $i === 0 ? (object) $ufHistory : null];
            }
        }
        usort($out, static fn(array $a, array $b): int => strcmp(mb_strtolower($a['name']), mb_strtolower($b['name'])));
        return $out;
    }
    private function matches(string $name, array $prefixes, array $regexes): bool
    {
        foreach ($prefixes as $prefix) if ($prefix !== '' && str_starts_with(mb_strtoupper($name), mb_strtoupper($prefix))) return true;
        foreach ($regexes as $regex) {
            if ($regex === '') continue;
            if (@preg_match($regex, '') === false) $regex = '~' . str_replace('~', '\\~', $regex) . '~i';
            elseif (!preg_match('/[a-z]*i[a-z]*$/i', $regex)) $regex .= 'i';
            if (@preg_match($regex, $name) === 1) return true;
        }
        return false;
    }
    private function ufSigla(string $name): ?string
    {
        foreach (Uf::siglas() as $uf) if (preg_match('/_' . $uf . '(?:[_.]|\d{6})/i', $name)) return $uf;
        return null;
    }
    private function ufCode(string $name, string $prefix): ?string
    {
        return preg_match('/^' . preg_quote($prefix, '/') . '(\d{2})_/i', $name, $m) ? (Uf::map()[$m[1]] ?? null) : null;
    }
    private function special(string $name): ?array
    {
        $dot = strrpos($name, '.'); $stem = $dot === false ? $name : substr($name, 0, $dot);
        $patterns = ['AIHV'=>'^AIHV(\d+)$','SIHD2_'=>'^SIHD2_(.+)$','SIHDTS_'=>'^SIHDTS_(.+)$','SISAIH01_VER'=>'^SISAIH01_VER(.+)$','APACMAG_'=>'^APACMAG_(.+)$','BPAMAG'=>'^BPAMAG(.+)$','EQUIPESESFBRASIL'=>'^EQUIPESESFBRASIL(.+)$','BDSIA'=>'^BDSIA(.+)$','SIA'=>'^SIA(.+)$','FPOMAG_ATUALIZA'=>'^FPOMAG_ATUALIZA(.+)$','SIHD_NUMERIC'=>'^SIHD(\d{3})(?:_|$)'];
        foreach ($patterns as $key => $pattern) if (preg_match('/' . $pattern . '/i', $stem, $m)) {
            preg_match_all('/\d+/', $m[1], $nums); $weight = 0;
            foreach (array_slice($nums[0], 0, 5) as $i => $n) $weight += (int) $n * 1000 ** (4 - $i);
            if ($key === 'AIHV' || $key === 'SIHD_NUMERIC') $weight = (int) $m[1];
            $result = ['familyKey'=>$key, 'order'=>$weight, 'label'=>$key === 'AIHV' ? 'v' . $m[1] : $m[1]];
            if ($key === 'SIHD_NUMERIC') $result['displaySuffix'] = '(SIHD1)';
            return $result;
        }
        if (preg_match('/^SCNES(\d{4})-(ATUALIZACAO|COMPLETA)$/i', $stem, $m)) return ['familyKey'=>'SCNES_APP_' . strtoupper($m[2]), 'order'=>(int)$m[1], 'label'=>$m[1]];
        return null;
    }
    private function familyKey(string $name): string
    {
        $name = mb_strtoupper($name);
        if (preg_match(self::YM, $name, $m, PREG_OFFSET_CAPTURE)) $name = substr($name, 0, $m[0][1] + strlen($m[0][0]));
        $name = preg_replace(self::YM, 'YYYYMM', $name);
        foreach (Uf::siglas() as $uf) $name = preg_replace('/_' . $uf . '(?=_|\d)/i', '_UF', $name);
        $name = preg_replace('/^(TERCEIROSBRASIL)\d{2}/', '${1}UF', $name);
        return preg_replace('/^(DSIHD017_)\d{2}/i', '${1}UF', $name);
    }
    private function sorted(array $files): array
    {
        usort($files, static function(array $a, array $b): int {
            return ($b['specialVersion']['order'] ?? -1) <=> ($a['specialVersion']['order'] ?? -1) ?: strcmp($b['ym'] ?? '', $a['ym'] ?? '') ?: strcmp($b['modifiedAt'] ?? '', $a['modifiedAt'] ?? '');
        });
        return $files;
    }
}
