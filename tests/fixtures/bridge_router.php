<?php
declare(strict_types=1);

/**
 * Mock da Ponte HTTPS/HTTP para testes do FtpClient.
 * Simula os endpoints /api/ftp-list e /api/ftp-download.
 */

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);

if ($path === '/api/ftp-list' && str_contains((string) ($_GET['url'] ?? ''), '/redirect')) {
    header('Location: /api/ftp-list?url=ok', true, 302);
    exit;
}
if ($path === '/api/ftp-download' && ($_GET['file'] ?? '') === 'redirect.zip') {
    header('Location: /api/ftp-download?file=arquivo_mock_bridge.zip', true, 302);
    exit;
}

if ($path === '/api/ftp-list') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        [
            'name'       => 'arquivo_mock_bridge.zip',
            'size'       => 34,
            'modifiedAt' => '2026-10-30T12:00:00+00:00',
        ],
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

if ($path === '/api/ftp-download') {
    $payload = 'CONTEUDO_MOCK_DA_PONTE_HTTPS_12345';
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . (string) strlen($payload));
    echo $payload;
    exit;
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['detail' => 'Endpoint não encontrado no mock da ponte']);
