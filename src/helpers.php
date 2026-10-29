<?php
declare(strict_types=1);

/** Escapa saída HTML nos templates. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL de asset com cache-busting por mtime: asset('css/app.css'). */
function asset(string $path): string
{
    $file = SUS_ROOT . '/public/assets/' . ltrim($path, '/');
    $v = is_file($file) ? (string) filemtime($file) : '0';
    return '/assets/' . ltrim($path, '/') . '?v=' . $v;
}
