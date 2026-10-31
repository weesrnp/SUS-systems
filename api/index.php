<?php
declare(strict_types=1);

// Entrada serverless da Vercel (runtime vercel-php). Os estáticos saem de public/
// (outputDirectory); public/index.php é excluído do deploy para não ser servido como texto.
use SusSystems\Core\App;
use SusSystems\Core\Request;
use SusSystems\Core\Router;

require dirname(__DIR__) . '/src/bootstrap.php';

$router = new Router();
(require SUS_ROOT . '/config/routes.php')($router);

(new App($router))->handle(Request::fromGlobals())->send();
