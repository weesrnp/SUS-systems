<?php
declare(strict_types=1);

namespace SusSystems\Core;

/** Cache em arquivo (serialize) com TTL — sobrevive entre requisições, ao contrário do dict em memória do legado. */
final class Cache
{
    public function __construct(private readonly string $dir)
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    public static function default(): self
    {
        return new self((string) Config::get('cache.dir'));
    }

    public function get(string $key, int $ttl): mixed
    {
        $file = $this->path($key);
        if (!is_file($file) || (time() - (int) filemtime($file)) >= $ttl) {
            return null;
        }
        $data = @file_get_contents($file);
        return $data === false ? null : unserialize($data, ['allowed_classes' => false]);
    }

    public function set(string $key, mixed $value): void
    {
        $tmp = $this->path($key) . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, serialize($value)) !== false) {
            @rename($tmp, $this->path($key));
        }
    }

    /** Busca no cache ou calcula e armazena. */
    public function remember(string $key, int $ttl, callable $compute, bool $refresh = false): mixed
    {
        if (!$refresh && ($hit = $this->get($key, $ttl)) !== null) {
            return $hit;
        }
        $value = $compute();
        $this->set($key, $value);
        return $value;
    }

    private function path(string $key): string
    {
        return $this->dir . '/' . sha1($key) . '.cache';
    }
}
