<?php
declare(strict_types=1);

namespace SusSystems\Http\Controllers;

use SusSystems\Core\HttpException;
use SusSystems\Core\Request;
use SusSystems\Domain\Catalog\Catalog;
use SusSystems\Domain\Ftp\FileGrouper;
use SusSystems\Domain\Ftp\FtpClient;

final class CatalogController
{
    public function catalog(): array
    {
        $catalog = new Catalog();
        return ['areas' => $this->publicPatterns($catalog->areas()), 'ufs' => $catalog->ufs()];
    }

    public function subgroupData(Request $request): array
    {
        $groupId = $request->required('group_id');
        $subName = $request->required('sub_name');
        $subgroup = (new Catalog())->findSubgroup($groupId, $subName);
        if ($subgroup === null) throw new HttpException(404, "Subgrupo {$groupId} / {$subName} não encontrado.");

        $sections = [];
        $client = new FtpClient();
        $grouper = new FileGrouper();
        $refresh = $request->bool('force_refresh');
        foreach ($subgroup['sections'] ?? [] as $section) {
            [$files, $source] = $client->listDirectory($section['ftpUrl'], $refresh);
            $sections[] = [
                'title' => $section['title'] ?? $subgroup['name'],
                'ftpUrl' => $section['ftpUrl'],
                'showNameFilter' => $section['showNameFilter'] ?? true,
                'showDescriptionColumn' => !empty($section['descriptionRules']),
                'sourceMode' => $source,
                'files' => $grouper->group($files, $section),
            ];
        }
        return [
            'groupId' => $groupId,
            'subName' => $subName,
            'title' => $subgroup['title'] ?? $subName,
            'primaryFtpUrl' => $subgroup['ftpUrl'] ?? '',
            'specialView' => $subgroup['specialView'] ?? null,
            'normatives' => $subgroup['normatives'] ?? [],
            'sections' => $sections,
            'sourceSummary' => $sections[0]['sourceMode'] ?? 'READY',
        ];
    }

    // A API mantém os padrões textuais do catálogo Python.
    private function publicPatterns(array $items): array
    {
        foreach ($items as $key => $value) {
            if ($key === 'regex' && is_string($value)) {
                $items[$key] = str_replace('\\/', '/', substr($value, 1, -2));
            } elseif (($key === 'includeRegexes' || $key === 'excludeRegexes') && is_array($value)) {
                $items[$key] = array_map(static fn(string $pattern): string => str_replace('\\/', '/', substr($pattern, 1, -2)), $value);
            } elseif (is_array($value)) {
                $items[$key] = $this->publicPatterns($value);
            }
        }
        return $items;
    }
}
