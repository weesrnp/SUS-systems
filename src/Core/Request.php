<?php
declare(strict_types=1);

namespace SusSystems\Core;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query,
    ) {
    }

    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = '/' . trim(rawurldecode($path), '/');
        return new self(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), $path, $_GET);
    }

    public function str(string $key, string $default = ''): string
    {
        $v = $this->query[$key] ?? $default;
        return is_string($v) ? trim($v) : $default;
    }

    /** Parâmetro obrigatório — 422 se ausente (como o Query(...) do FastAPI). */
    public function required(string $key): string
    {
        $v = $this->str($key);
        if ($v === '') {
            throw new HttpException(422, "Parâmetro obrigatório ausente: {$key}");
        }
        return $v;
    }

    public function int(string $key, int $default, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
    {
        $v = filter_var($this->query[$key] ?? $default, FILTER_VALIDATE_INT);
        if ($v === false || $v < $min || $v > $max) {
            throw new HttpException(422, "Parâmetro inválido: {$key} (esperado inteiro entre {$min} e {$max})");
        }
        return $v;
    }

    public function bool(string $key, bool $default = false): bool
    {
        if (!isset($this->query[$key])) {
            return $default;
        }
        return filter_var($this->query[$key], FILTER_VALIDATE_BOOL);
    }
}
