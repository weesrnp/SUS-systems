<?php
declare(strict_types=1);

namespace SusSystems\Domain\Catalog;

use SusSystems\Domain\Ftp\Uf;

final class Catalog
{
    public function areas(): array
    {
        return require SUS_ROOT . '/config/catalog.php';
    }

    public function ufs(): array
    {
        return Uf::siglas();
    }

    public function findSubgroup(string $groupId, string $subName): ?array
    {
        foreach ($this->areas() as $area) {
            foreach ($area['groups'] ?? [] as $group) {
                if ($group['id'] !== $groupId) continue;
                foreach ($group['subGroups'] ?? [] as $subgroup) {
                    if ($subgroup['name'] === $subName) return $subgroup;
                }
            }
        }
        return null;
    }
}
