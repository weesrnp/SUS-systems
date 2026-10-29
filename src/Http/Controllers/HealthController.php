<?php
declare(strict_types=1);

namespace SusSystems\Http\Controllers;

use SusSystems\Core\Config;
use SusSystems\Core\Request;

final class HealthController
{
    public function health(Request $request): array
    {
        return [
            'status'       => 'healthy',
            'app'          => Config::get('name'),
            'version'      => Config::get('version'),
            'runtime'      => 'PHP ' . PHP_VERSION,
            'extensions'   => [
                'ftp' => extension_loaded('ftp'),
                'zip' => extension_loaded('zip'),
                'curl' => extension_loaded('curl'),
            ],
            'ftp_clusters' => Config::get('ftp.allowed_hosts'),
        ];
    }
}
