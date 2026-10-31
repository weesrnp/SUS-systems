<?php
declare(strict_types=1);

namespace SusSystems\Core;

class Response
{
    public function __construct(
        protected string $body = '',
        protected int $status = 200,
        protected array $headers = ['Content-Type' => 'text/html; charset=utf-8'],
    ) {
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        return new self((string) $body, $status, ['Content-Type' => 'application/json; charset=utf-8'] + $headers);
    }

    public function withHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
        $this->sendBody();
    }

    protected function sendBody(): void
    {
        echo $this->body;
    }
}
