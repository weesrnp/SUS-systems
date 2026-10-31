<?php
declare(strict_types=1);

namespace SusSystems\Core;

/** Roteador mínimo por método + caminho exato. Handler: [Classe::class, 'metodo'] ou closure. */
final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET']['/' . trim($path, '/')] = $handler;
    }

    /** Registra o mesmo handler em /api/x e /x (compatibilidade com o legado). */
    public function api(string $path, callable|array $handler): void
    {
        $this->get('/api/' . trim($path, '/'), $handler);
        $this->get($path, $handler);
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        $handler = $this->routes[$method][$request->path] ?? null;
        if ($handler === null) {
            throw new HttpException(404, 'Rota não encontrada.');
        }
        if (is_array($handler) && is_string($handler[0])) {
            $handler = [new $handler[0](), $handler[1]];
        }
        $result = $handler($request);
        return $result instanceof Response ? $result : Response::json($result);
    }

    public function routes(): array
    {
        return $this->routes;
    }
}
