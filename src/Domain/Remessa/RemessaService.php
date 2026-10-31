<?php
declare(strict_types=1);

namespace SusSystems\Domain\Remessa;

use SusSystems\Core\Cache;
use SusSystems\Core\Config;
use SusSystems\Domain\Ftp\FtpClient;

final class RemessaService
{
    private const BASE = 'ftp://arpoador.datasus.gov.br/siasus/LogCarga/';

    private readonly ?\Closure $withFiles;
    private readonly Cache $cache;

    public function __construct(?callable $withFiles = null, ?Cache $cache = null)
    {
        $this->withFiles = $withFiles === null ? null : \Closure::fromCallable($withFiles);
        $this->cache = $cache ?? Cache::default();
    }

    public function consultar(string $ibge, string $ano): array
    {
        if (!preg_match('/^\d{6}$/D', $ibge) || !preg_match('/^\d{4}$/D', $ano)) throw new \InvalidArgumentException('Município ou ano inválido.');
        $url = self::BASE . 'LogCarga' . $ano . '/';
        try {
            return $this->cache->remember('remessa:' . $ibge . ':' . $ano, (int) Config::get('cache.ftp_ttl', 600), function () use ($url, $ibge, $ano): array {
                $withFiles = $this->withFiles ?? static fn(string $directory, string $pattern, callable $callback): mixed => (new FtpClient())->withPattern($directory, $pattern, $callback);
                return $withFiles($url, '*' . $ibge . '*', function (array $entries, callable $download) use ($url, $ibge, $ano): array {
                    $reports = [];
                    foreach ($entries as $entry) {
                        $name = $entry['name'];
                        if (!preg_match('/^(FTP_A|H)([ME])(\d{6})(\d{2})([A-Z])(\d{6})\.TXT$/i', $name, $m) || $m[3] !== $ibge || substr($m[6], 0, 4) !== $ano) continue;
                        $key = 'remessa-txt:' . hash('sha256', json_encode([$url, $name, $entry['size'], $entry['modifiedAt']], JSON_THROW_ON_ERROR));
                        $content = $this->cache->remember($key, 30 * 86400, static fn(): string => $download($name));
                        $reports[$name] = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
                    }
                    return ['rows'=>$this->parseReports($reports, $ibge, $ano), 'ftpUrl'=>$url];
                });
            });
        } catch (\Throwable $e) {
            return ['error'=>'Falha ao consultar LogCarga: ' . $e->getMessage(), 'rows'=>[], 'ftpUrl'=>$url];
        }
    }

    public function parseReports(array $reports, string $ibge, string $ano): array
    {
        $pairs = [];
        foreach ($reports as $filename => $text) {
            if (!preg_match('/^(FTP_A|H)([ME])(\d{6})(\d{2})([A-Z])(\d{6})\.TXT$/i', $filename, $m) || $m[3] !== $ibge || substr($m[6], 0, 4) !== $ano) continue;
            $key = strtoupper($m[2] . $m[3] . $m[4] . $m[5] . $m[6]);
            $kind = strtoupper($m[1]) === 'FTP_A' ? 'verification' : 'load';
            $pairs[$key]['competencia'] = $m[6];
            $pairs[$key][$kind] = ['file'=>$filename, 'text'=>$text];
        }
        $rows = [];
        foreach ($pairs as $pair) {
            if (!isset($pair['verification'])) continue;
            $verification = $pair['verification']; $load = $pair['load'] ?? null;
            $text = $verification['text'];
            $remessa = $this->field($text, '/Arquivo\s+de\s+Remessa\s*:[ \t]*(\S+)/iu');
            $received = $this->field($text, '/Data\s+de\s+Chegada\s*:[ \t]*([^\r\n]+)/iu');
            $verified = $this->field($text, '/Data\s+da\s+Carga\s*:[ \t]*([^\r\n]+)/iu');
            $loaded = $load ? $this->field($load['text'], '/Data\s+da\s+Carga\s*:[ \t]*([^\r\n]+)/iu') : '-';
            $status = $load ? '✅ Remessa processada sem erro' : (preg_match('/(?:COM\s+ERRO|Apresentou\s+erro)/iu', $text) ? '🚫 Erro na verificação da remessa' : '⏳ Verificada, aguardando carga');
            $rows[] = ['competencia'=>$pair['competencia'], 'remessa'=>$remessa, 'recebidoEm'=>$received, 'verificadoEm'=>$verified, 'cargaEm'=>$loaded, 'status'=>$status, 'statusFile'=>$load['file'] ?? $verification['file']];
        }
        usort($rows, static fn(array $a, array $b): int => strcmp($b['competencia'], $a['competencia']));
        return $rows;
    }
    private function field(string $text, string $pattern): string
    {
        return preg_match($pattern, $text, $m) ? trim($m[1]) : '-';
    }
}
