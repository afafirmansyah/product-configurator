<?php

namespace App\Services\Bom;

/**
 * Builds a display tree from the flat, depth-first BOM rows produced by
 * BomGeneratorService, linking rows through their path / parent_path keys.
 * Rows without those keys (snapshots from before nesting) become roots.
 */
class BomTree
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>> Root rows, each with a 'children' list (recursively).
     */
    public static function nest(array $rows): array
    {
        /** @var array<string, list<array<string, mixed>>> $childrenByParent */
        $childrenByParent = [];

        foreach ($rows as $row) {
            $childrenByParent[(string) ($row['parent_path'] ?? '')][] = $row;
        }

        return self::attach($childrenByParent, '');
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $childrenByParent
     * @return list<array<string, mixed>>
     */
    private static function attach(array $childrenByParent, string $parentPath): array
    {
        $nodes = [];

        foreach ($childrenByParent[$parentPath] ?? [] as $row) {
            $path = $row['path'] ?? null;
            $row['children'] = $path !== null && $path !== '' ? self::attach($childrenByParent, (string) $path) : [];
            $nodes[] = $row;
        }

        return $nodes;
    }
}
