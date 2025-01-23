<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Modifiers;

use LupaSearch\LupaSearchPlugin\Model\Provider\Categories\ParentIdsProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\DataModifierInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Traversable;

use function array_keys;
use function array_merge;
use function array_unique;

/**
 * @codeCoverageIgnore
 */
class CategoryIds implements DataModifierInterface
{
    private ParentIdsProviderInterface $parentIdsProvider;

    public function __construct(ParentIdsProviderInterface $parentIdsProvider)
    {
        $this->parentIdsProvider = $parentIdsProvider;
    }

    public function modify(Traversable $data): void
    {
        if (!$data instanceof Collection) {
            return;
        }

        $data->addCategoryIds();

        foreach ($data as $product) {
            $this->addMissingIds($product);
        }
    }

    private function addMissingIds(Product $product): void
    {
        $ids = $product->getCategoryIds();

        if (empty($ids)) {
            return;
        }

        $product->setData('assigned_category_ids', $ids);
        $parentIds = $this->parentIdsProvider->getByIds($ids);
        $parentIds[] = array_keys($parentIds);
        $id = $this->getCategoryId($parentIds);
        $ids = array_unique(array_merge(...$parentIds));
        $product->setData('category_id', $id);
        $product->setData('category_ids', $ids);
    }

    /**
     * @param array<int, array<int>> $ids
     */
    private function getCategoryId(array $ids): ?int
    {
        if (count($ids) < 1) {
            return null;
        }

        $ids = array_map(
            static fn ($ids) => count($ids),
            $ids,
        );

        $count = max($ids);

        return array_search($count, $ids, true) ?: null;
    }
}
