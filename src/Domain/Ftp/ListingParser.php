<?php
declare(strict_types=1);
namespace SusSystems\Domain\Ftp;

final class ListingParser
{
    public function parse(string $line): ?array
    {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, 'total ')) return null;
        $zone = new \DateTimeZone('UTC');
        if (preg_match('/^(\d{2})-(\d{2})-(\d{2,4})\s+(\d{1,2}):(\d{2})(AM|PM)\s+(<DIR>|\d+)\s+(.+)$/i', $line, $m)) {
            if (strtoupper($m[7]) === '<DIR>') return null;
            $year = (int) $m[3];
            if ($year < 100) $year += $year < 70 ? 2000 : 1900;
            $hour = (int) $m[4] % 12 + (strtoupper($m[6]) === 'PM' ? 12 : 0);
            $date = sprintf('%04d-%02d-%02d %02d:%02d:00', $year, $m[1], $m[2], $hour, $m[5]);
            $dt = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $date, $zone);
            $errors = \DateTimeImmutable::getLastErrors();
            if (!$dt || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) $dt = new \DateTimeImmutable('now', $zone);
            return ['name' => $this->name($m[8]), 'size' => (int) $m[7], 'modifiedAt' => $dt->format('Y-m-d\TH:i:sP')];
        }
        if (!preg_match('/^([^\s]+)\s+\S+\s+\S+\s+\S+\s+(\d+)\s+([A-Za-z]{3})\s+(\d{1,2})\s+(\d{4}|\d{1,2}:\d{2})\s+(.+)$/', $line, $m) || str_starts_with($m[1], 'd')) return null;
        $now = new \DateTimeImmutable('now', $zone);
        $recent = str_contains($m[5], ':');
        $date = $m[3] . ' ' . $m[4] . ' ' . ($recent ? $now->format('Y') . ' ' . $m[5] : $m[5] . ' 00:00');
        $dt = \DateTimeImmutable::createFromFormat('!M j Y H:i', $date, $zone);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$dt || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) return null;
        if ($recent && $dt > $now) $dt = $dt->modify('-1 year');
        return ['name' => $this->name($m[6]), 'size' => (int) $m[2], 'modifiedAt' => $dt->format('Y-m-d\TH:i:sP')];
    }
    private function name(string $name): string
    {
        return mb_convert_encoding(trim($name), 'UTF-8', 'ISO-8859-1');
    }
}
