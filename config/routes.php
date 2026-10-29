<?php
declare(strict_types=1);

use SusSystems\Core\Router;
use SusSystems\Http\Controllers\CatalogController;
use SusSystems\Http\Controllers\FtpController;
use SusSystems\Http\Controllers\HealthController;
use SusSystems\Http\Controllers\PageController;
use SusSystems\Http\Controllers\RemessaController;
use SusSystems\Http\Controllers\SigtapController;

/**
 * Contrato da API — mesmos caminhos, parâmetros e formato JSON do backend Python legado
 * (legacy-python/app/main.py), para que o frontend e integrações continuem funcionando.
 */
return static function (Router $r): void {
    // Páginas (SSR)
    $r->get('/', [PageController::class, 'home']);

    // API JSON (montada em /api/* e na raiz)
    $r->api('/health', [HealthController::class, 'health']);
    $r->api('/catalog', [CatalogController::class, 'catalog']);
    $r->api('/subgroup-data', [CatalogController::class, 'subgroupData']);
    $r->api('/ftp-list', [FtpController::class, 'list']);
    $r->api('/ftp-download', [FtpController::class, 'download']);
    $r->api('/remessa', [RemessaController::class, 'remessa']);
    $r->api('/sigtap/competencias', [SigtapController::class, 'competencias']);
    $r->api('/sigtap/explore', [SigtapController::class, 'explore']);
    $r->api('/sigtap/diff', [SigtapController::class, 'diff']);
};
