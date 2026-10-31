<?php
declare(strict_types=1);

namespace SusSystems\Core;

/**
 * Resposta em streaming: $producer recebe um callable $write(string $chunk)
 * e escreve os blocos à medida que chegam do FTP (sem bufferizar o arquivo).
 */
final class StreamResponse extends Response
{
    /** @var callable(callable(string):void):void */
    private $producer;

    public function __construct(callable $producer, array $headers = [], int $status = 200)
    {
        $this->producer = $producer;
        parent::__construct('', $status, $headers + ['Content-Type' => 'application/octet-stream']);
    }

    protected function sendBody(): void
    {
        @set_time_limit(0);
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        ($this->producer)(static function (string $chunk): void {
            echo $chunk;
            flush();
        });
    }
}
