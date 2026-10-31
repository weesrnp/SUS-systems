<?php
declare(strict_types=1);

namespace SusSystems\Http\Controllers;

use SusSystems\Core\Config;
use SusSystems\Core\HttpException;
use SusSystems\Core\Request;
use SusSystems\Domain\Remessa\RemessaService;

final class RemessaController
{
    public function remessa(Request $request): array
    {
        $ibge = $request->str('ibge', (string) Config::get('municipio_padrao.ibge', '410965'));
        $ano = $request->str('ano', date('Y'));
        if (!preg_match('/^\d{6,7}$/D', $ibge)) throw new HttpException(422, 'IBGE deve conter 6 ou 7 dígitos.');
        if (!preg_match('/^\d{4}$/D', $ano)) throw new HttpException(422, 'Ano deve conter 4 dígitos.');
        return (new RemessaService())->consultar(substr($ibge, 0, 6), $ano);
    }
}
