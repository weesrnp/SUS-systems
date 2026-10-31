<?php
declare(strict_types=1);
namespace SusSystems\Domain\Ftp;

use SusSystems\Core\Cache;
use SusSystems\Core\Config;
use SusSystems\Core\HttpException;

final class FtpClient
{
    private string $downloadMode = 'FTP_DIRECT_STREAM';
    public function listDirectory(string $ftpUrl, bool $forceRefresh = false): array
    {
        self::assertAllowedUrl($ftpUrl);
        $cache = Cache::default(); $key = 'ftp-list:' . $ftpUrl; $ttl = (int) Config::get('cache.ftp_ttl', 600);
        if (!$forceRefresh && ($cached = $cache->get($key, $ttl)) !== null && $cached !== []) return [$cached, 'CACHE'];
        $source = 'OFFLINE';
        try {
            $entries = $cache->remember($key, $ttl, function () use ($ftpUrl, &$source): array {
                $entries = [];
                try {
                    $ftp = $this->connect($ftpUrl);
                    try {
                        $lines = @ftp_rawlist($ftp, $this->path($ftpUrl));
                        if (is_array($lines)) foreach ($lines as $line) {
                            $entry = (new ListingParser())->parse($line);
                            if ($entry !== null) $entries[] = $entry;
                        }
                    } finally { ftp_close($ftp); }
                } catch (\Throwable) { }
                if ($entries) { $source = 'FTP_DIRECT'; return $entries; }
                $bridge = (string) Config::get('ftp.https_bridge', '');
                if ($bridge !== '') {
                    $json = $this->bridgeRequest($bridge, 'ftp-list', ['url'=>$ftpUrl]);
                    $data = $json === false ? null : json_decode($json, true);
                    if (is_array($data) && array_is_list($data) && $data) { $source = 'HTTPS_BRIDGE'; return $data; }
                }
                throw new \RuntimeException('Listagem vazia');
            }, $forceRefresh);
            return [$entries, $source === 'OFFLINE' ? 'CACHE' : $source];
        } catch (\RuntimeException) { return [[], 'OFFLINE']; }
    }
    /** Lista um padrão restrito sem baixar o diretório inteiro. */
    public function listPattern(string $ftpUrl, string $pattern): array
    {
        self::assertAllowedUrl($ftpUrl);
        if (!preg_match('/^[A-Za-z0-9*_]+$/D', $pattern)) throw new HttpException(400, 'Padrão FTP inválido.');
        $ftp = $this->connect($ftpUrl);
        try {
            if (!@ftp_chdir($ftp, $this->path($ftpUrl))) throw new \RuntimeException('Diretório FTP indisponível.');
            $lines = @ftp_rawlist($ftp, $pattern);
            if ($lines === false) return [];
            $parser = new ListingParser(); $entries = [];
            foreach ($lines as $line) {
                $entry = $parser->parse($line);
                if ($entry !== null) $entries[] = $entry;
            }
            return $entries;
        } finally { ftp_close($ftp); }
    }
    public function streamFile(string $baseFtpUrl, string $filename, callable $write): int
    {
        self::assertAllowedUrl($baseFtpUrl); $filename = $this->safeName($filename);
        try { $ftp = $this->connect($baseFtpUrl); }
        catch (\Throwable $e) {
            if ((string) Config::get('ftp.https_bridge', '') !== '') return $this->streamBridge($baseFtpUrl, $filename, $write);
            throw new HttpException(502, 'Não foi possível conectar ao FTP.');
        }
        $temp = fopen('php://temp/maxmemory:65536', 'w+b'); $total = 0;
        try {
            $state = @ftp_nb_fget($ftp, $temp, $this->path($baseFtpUrl) . mb_convert_encoding($filename, 'ISO-8859-1', 'UTF-8'), FTP_BINARY);
            while (true) {
                rewind($temp);
                while (!feof($temp)) { $chunk = fread($temp, 65536); if ($chunk === false || $chunk === '') break; $write($chunk); $total += strlen($chunk); }
                ftruncate($temp, 0); rewind($temp);
                if ($state !== FTP_MOREDATA) break;
                $state = ftp_nb_continue($ftp);
            }
            if ($state !== FTP_FINISHED) throw new HttpException(502, 'Falha ao baixar arquivo FTP.');
            return $total;
        } finally { fclose($temp); ftp_close($ftp); }
    }
    public function fileSize(string $baseFtpUrl, string $filename): int
    {
        self::assertAllowedUrl($baseFtpUrl); $filename = $this->safeName($filename);
        try {
            $ftp = $this->connect($baseFtpUrl); $this->downloadMode = 'FTP_DIRECT_STREAM';
            try { return max(0, (int) @ftp_size($ftp, $this->path($baseFtpUrl) . mb_convert_encoding($filename, 'ISO-8859-1', 'UTF-8'))); }
            finally { ftp_close($ftp); }
        } catch (\Throwable) {
            $this->downloadMode = (string) Config::get('ftp.https_bridge', '') !== '' ? 'HTTPS_STREAM_BRIDGE' : 'OFFLINE';
            return 0;
        }
    }
    public function downloadMode(): string { return $this->downloadMode; }
    public function downloadToTemp(string $baseFtpUrl, string $filename): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sus-');
        if ($path === false) throw new HttpException(500, 'Não foi possível criar arquivo temporário.');
        $file = fopen($path, 'wb');
        if ($file === false) { @unlink($path); throw new HttpException(500, 'Não foi possível abrir arquivo temporário.'); }
        try {
            $this->streamFile($baseFtpUrl, $filename, static function(string $chunk) use ($file): void {
                $offset = 0; $length = strlen($chunk);
                while ($offset < $length) { $n = fwrite($file, substr($chunk, $offset)); if ($n === false || $n === 0) throw new \RuntimeException('Falha ao gravar arquivo temporário.'); $offset += $n; }
            });
        } catch (\Throwable $e) { fclose($file); @unlink($path); throw $e; }
        fclose($file); return $path;
    }
    public static function assertAllowedUrl(string $ftpUrl): void
    {
        $parts = parse_url($ftpUrl);
        if (!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'ftp' || !in_array(strtolower($parts['host'] ?? ''), Config::get('ftp.allowed_hosts', []), true) || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 21) || isset($parts['query']) || isset($parts['fragment']) || preg_match('/[\r\n]/', rawurldecode($parts['path'] ?? ''))) throw new HttpException(400, 'URL FTP não permitida.');
    }
    private function connect(string $url): \FTP\Connection
    {
        $ftp = @ftp_connect((string) parse_url($url, PHP_URL_HOST), 21, (int) Config::get('ftp.timeout', 8));
        if ($ftp === false) throw new \RuntimeException('Conexão FTP indisponível.');
        if (!@ftp_login($ftp, 'anonymous', 'anonymous@datasus.gov.br') || !@ftp_pasv($ftp, true)) { ftp_close($ftp); throw new \RuntimeException('Login FTP indisponível.'); }
        return $ftp;
    }
    private function path(string $url): string { return rtrim(rawurldecode((string) (parse_url($url, PHP_URL_PATH) ?: '/')), '/') . '/'; }
    private function safeName(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', trim($filename)));
        if ($filename === '' || $filename === '.' || $filename === '..' || preg_match('/[\r\n]/', $filename)) throw new HttpException(400, 'Nome de arquivo inválido.');
        return $filename;
    }
    private function bridgeUrl(string $bridge, string $endpoint, array $query): string
    {
        return rtrim($bridge, '/') . '/api/' . $endpoint . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
    private function bridgeRequest(string $bridge, string $endpoint, array $query): string|false
    {
        $curl = curl_init($this->bridgeUrl($bridge, $endpoint, $query));
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>(int) Config::get('ftp.timeout', 8), CURLOPT_FOLLOWLOCATION=>false, CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]);
        $result = curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
        return $status === 200 && is_string($result) ? $result : false;
    }
    private function streamBridge(string $base, string $file, callable $write): int
    {
        $this->downloadMode = 'HTTPS_STREAM_BRIDGE'; $total = 0; $status = 0;
        $curl = curl_init($this->bridgeUrl((string) Config::get('ftp.https_bridge'), 'ftp-download', ['base'=>$base, 'file'=>$file]));
        curl_setopt_array($curl, [CURLOPT_FOLLOWLOCATION=>false, CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS, CURLOPT_CONNECTTIMEOUT=>(int) Config::get('ftp.timeout', 8), CURLOPT_HEADERFUNCTION=>static function($curl, string $header) use (&$status): int { if (preg_match('~^HTTP/\S+\s+(\d{3})~', $header, $match)) $status = (int) $match[1]; return strlen($header); }, CURLOPT_WRITEFUNCTION=>static function($curl, string $data) use ($write, &$total, &$status): int { if ($status === 200) { $write($data); $total += strlen($data); } return strlen($data); }]);
        $ok = curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
        if (!$ok || $status !== 200) throw new HttpException(502, 'Falha ao baixar pela ponte HTTPS.');
        return $total;
    }
}
