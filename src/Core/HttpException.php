<?php
declare(strict_types=1);

namespace SusSystems\Core;

/** Erro com status HTTP; o App converte em JSON { "detail": "..." } (mesmo formato do FastAPI legado). */
final class HttpException extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message)
    {
        parent::__construct($message, $status);
    }
}
