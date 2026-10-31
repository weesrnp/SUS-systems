<?php
declare(strict_types=1);

/**
 * Bootstrap — autoload PSR-4 sem depender do Composer (hospedagem compartilhada),
 * mas usa vendor/autoload.php quando existir.
 */

define('SUS_ROOT', dirname(__DIR__));

$composer = SUS_ROOT . '/vendor/autoload.php';
if (is_file($composer)) {
    require $composer;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'SusSystems\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

require __DIR__ . '/helpers.php';

date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

return SusSystems\Core\Config::load(SUS_ROOT . '/config/app.php');
