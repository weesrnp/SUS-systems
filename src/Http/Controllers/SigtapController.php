<?php
declare(strict_types=1);

namespace SusSystems\Http\Controllers;

use SusSystems\Core\HttpException;
use SusSystems\Core\Request;
use SusSystems\Domain\Sigtap\SigtapRepository;

final class SigtapController
{
    public function competencias(Request $request): array
    {
        return ['competencias'=>(new SigtapRepository())->competencias($request->bool('force_refresh'))];
    }
    public function explore(Request $request): array
    {
        $comp = $request->required('comp'); $limit = $request->int('limit', 120, 1, 500);
        try { return (new SigtapRepository())->search($comp, $request->str('q'), $request->str('grupo'), $request->str('complexidade'), $request->str('financiamento'), $limit); }
        catch (\InvalidArgumentException $e) { throw new HttpException(400, $e->getMessage()); }
    }
    public function diff(Request $request): array
    {
        $new = $request->required('comp_new'); $old = $request->required('comp_old');
        try { return (new SigtapRepository())->diff($new, $old); }
        catch (\InvalidArgumentException $e) { throw new HttpException(400, $e->getMessage()); }
    }
}
