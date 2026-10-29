<?php
declare(strict_types=1);

namespace SusSystems\Core;

/** Templates PHP puros em templates/. Dentro deles use e() para escapar saída. */
final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layout'): string
    {
        $content = self::capture(SUS_ROOT . "/templates/{$template}.php", $data);
        if ($layout === null) {
            return $content;
        }
        return self::capture(SUS_ROOT . "/templates/{$layout}.php", $data + ['content' => $content]);
    }

    private static function capture(string $file, array $data): string
    {
        if (!is_file($file)) {
            throw new HttpException(500, 'Template não encontrado: ' . basename($file));
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
