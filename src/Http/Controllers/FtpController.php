<?php
declare(strict_types=1);
namespace SusSystems\Http\Controllers;

use SusSystems\Core\HttpException;
use SusSystems\Core\Request;
use SusSystems\Core\Response;
use SusSystems\Core\StreamResponse;
use SusSystems\Domain\Ftp\FtpClient;

final class FtpController
{
    public function list(Request $request): Response
    {
        [$entries, $source] = (new FtpClient())->listDirectory($request->required('url'), $request->bool('force_refresh'));
        return Response::json($entries, 200, ['X-DataSUS-Source'=>$source]);
    }
    public function download(Request $request): StreamResponse
    {
        $base = $request->required('base'); $file = basename(str_replace('\\', '/', $request->required('file')));
        FtpClient::assertAllowedUrl($base); $client = new FtpClient(); $size = $client->fileSize($base, $file);
        if ($client->downloadMode() === 'OFFLINE') throw new HttpException(502, 'Não foi possível conectar ao FTP.');
        $disposition = 'attachment';
        if (preg_match('/^[\x20-\x7E]+$/', $file)) $disposition .= '; filename="' . addcslashes($file, "\\\"") . '"';
        $disposition .= "; filename*=UTF-8''" . rawurlencode($file);
        $headers = ['Content-Disposition'=>$disposition, 'X-Download-Mode'=>$client->downloadMode()];
        if ($size > 0) $headers['Content-Length'] = (string) $size;
        return new StreamResponse(static function(callable $write) use ($client, $base, $file): void { $client->streamFile($base, $file, $write); }, $headers);
    }
}
