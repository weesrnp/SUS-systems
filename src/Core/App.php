<?php
declare(strict_types=1);

namespace SusSystems\Core;

final class App
{
    public function __construct(private readonly Router $router)
    {
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->router->dispatch($request);
        } catch (HttpException $e) {
            $response = Response::json(['detail' => $e->getMessage()], $e->status);
        } catch (\Throwable $e) {
            error_log('[sus-systems] ' . $e);
            $detail = Config::get('debug') ? $e->getMessage() : 'Erro interno do servidor.';
            $response = Response::json(['detail' => $detail], 500);
        }
        return $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}
