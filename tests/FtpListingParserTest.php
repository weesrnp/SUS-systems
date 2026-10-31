<?php
declare(strict_types=1);
use SusSystems\Domain\Ftp\ListingParser;

return [
    'IIS interpreta hora, tamanho e nome em latin-1' => static function(): void {
        $row = (new ListingParser())->parse("10-15-26  02:30PM 123 relat\xF3rio mensal.zip");
        assert_same(['name'=>'relatório mensal.zip', 'size'=>123, 'modifiedAt'=>'2026-10-15T14:30:00+00:00'], $row);
        assert_same('1999-01-01T00:00:00+00:00', (new ListingParser())->parse('01-01-99 12:00AM 1 a.txt')['modifiedAt']);
    },
    'Unix interpreta ano e nome com espaços' => static function(): void {
        $row = (new ListingParser())->parse('-rw-r--r-- 1 ftp ftp 12345 Mar 06  2017 nome com espacos.zip');
        assert_same(['name'=>'nome com espacos.zip', 'size'=>12345, 'modifiedAt'=>'2017-03-06T00:00:00+00:00'], $row);
    },
    'Unix infere o ano anterior quando data futura' => static function(): void {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $future = $now->modify('+1 day');
        $line = '-rw-r--r-- 1 ftp ftp 4 ' . $future->format('M d H:i') . ' novo.zip';
        $year = (int)$now->format('Y');
        $candidate = DateTimeImmutable::createFromFormat('!M d Y H:i', $future->format('M d') . ' ' . $year . ' ' . $future->format('H:i'), new DateTimeZone('UTC'));
        if ($candidate > $now) $year--;
        assert_same($year, (int)substr((new ListingParser())->parse($line)['modifiedAt'], 0, 4));
    },
    'Diretórios e linhas inválidas são ignorados' => static function(): void {
        $parser = new ListingParser();
        assert_same(null, $parser->parse('10-15-26 02:30PM <DIR> pasta'));
        assert_same(null, $parser->parse('drwxr-xr-x 1 ftp ftp 0 Mar 06 2017 pasta'));
        assert_same(null, $parser->parse('total 23'));
    },
];
