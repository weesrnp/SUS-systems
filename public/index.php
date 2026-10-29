<?php
declare(strict_types=1);

use SusSystems\Core\App;
use SusSystems\Core\Request;
use SusSystems\Core\Router;

// Servidor embutido (php -S): entrega arquivos estáticos existentes diretamente.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __DIR__ . '/' && is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/src/bootstrap.php';

$router = new Router();
(require SUS_ROOT . '/config/routes.php')($router);

(new App($router))->handle(Request::fromGlobals())->send();
