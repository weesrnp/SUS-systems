<?php
declare(strict_types=1);

namespace SusSystems\Core;

/** Acesso por notação de ponto às configurações: Config::get('cache.ftp_ttl'). */
final class Config
{
    private static array $items = [];

    public static function load(string $file): array
    {
        self::$items = require $file;
        return self::$items;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
