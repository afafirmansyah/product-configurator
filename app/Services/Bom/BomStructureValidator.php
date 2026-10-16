<?php

namespace App\Services\Bom;

use App\Exceptions\BomStructureException;
use App\Models\Attribute;
use App\Models\BomRuleItem;
use App\Models\Product;

/**
 * Admin-time checks for using a configurable product as a sub-assembly line.
 *
 * Eligibility: every attribute of the child must also be an attribute of the
 * parent, so the parent's selections can drive the child. For each shared
 * attribute the child's selectable options must be a subset of the parent's.
 * Options are bound globally per attribute (attribute_options.attribute_id),
 * not per product, so today both sides always list the same options and the
 * option check passes trivially. It is still performed explicitly so the rule
 * keeps holding if per-product option binding is introduced.
 *
 * Structure: following active BOM rules' child products from the child must
 * never reach the parent again (cycle) nor exceed configurator.bom.max_depth
 * levels counted from the parent (parent = level 1).
 */
class BomStructureValidator
{
    /**
     * @throws BomStructureException when the child has attributes the parent lacks.
     */
    public function assertEligible(Product $parent, Product $child): void
    {
        $parent->loadMissing('attributes.options');
        $child->loadMissing('attributes.options');

        $parentAttributes = $parent->attributes->keyBy('id');

        $missing = $child->attributes
            ->reject(fn (Attribute $attribute): bool => $parentAttributes->has($attribute->id))
            ->pluck('name')
            ->values()
            ->all();

        if ($missing !== []) {
            throw BomStructureException::notEligible($child->name, $missing);
        }

        foreach ($child->attributes as $attribute) {
            $parentOptionIds = $parentAttributes->get($attribute->id)->options->modelKeys();
            $extraOptionIds = array_diff($attribute->options->modelKeys(), $parentOptionIds);

            if ($extraOptionIds !== []) {
                throw BomStructureException::notEligible($child->name, [$attribute->name]);
            }
        }
    }

    /**
     * @throws BomStructureException on a cycle back to the parent or too many levels.
     */
    public function assertNoCycle(Product $parent, Product $child): void
    {
        if ($child->id === $parent->id) {
            throw BomStructureException::cycle([$parent->id, $parent->id]);
        }

        $this->walk($child->id, [$parent->id, $child->id], (int) config('configurator.bom.max_depth', 5));
    }

    /**
     * Depth-first walk over the sub-assembly children of $productId.
     *
     * @param  list<int>  $path  Product ids from the parent down to $productId.
     */
    private function walk(int $productId, array $path, int $maxDepth): void
    {
        if (count($path) > $maxDepth) {
            throw BomStructureException::maxDepth($maxDepth);
        }

        $childIds = BomRuleItem::query()
            ->whereNotNull('child_product_id')
            ->whereHas('bomRule', fn ($query) => $query->where('product_id', $productId)->where('is_active', true))
            ->distinct()
            ->pluck('child_product_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        foreach ($childIds as $childId) {
            if (in_array($childId, $path, true)) {
                throw BomStructureException::cycle([...$path, $childId]);
            }

            $this->walk($childId, [...$path, $childId], $maxDepth);
        }
    }
}
