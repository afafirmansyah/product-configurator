<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a nested BOM (configurable sub-assemblies) has an invalid structure:
 * a cycle between products, more levels than configurator.bom.max_depth allows,
 * or a child product that is not eligible as a sub-assembly of its parent.
 */
class BomStructureException extends RuntimeException
{
    /**
     * @param  list<int>  $productIds  Product chain that closes the cycle, e.g. [1, 2, 1].
     */
    public static function cycle(array $productIds): self
    {
        $chain = implode(' → ', array_map(fn (int $id): string => "#{$id}", $productIds));

        return new self("Sub-assembly cycle detected between products {$chain}.");
    }

    public static function maxDepth(int $depth): self
    {
        return new self("The BOM exceeds the maximum nesting depth of {$depth} levels.");
    }

    /**
     * @param  list<string>  $attributeNames  Child attributes that the parent product does not have.
     */
    public static function notEligible(string $childName, array $attributeNames): self
    {
        return new self("{$childName} cannot be a sub-assembly: the parent product has no attribute(s) ".implode(', ', $attributeNames).'.');
    }
}
