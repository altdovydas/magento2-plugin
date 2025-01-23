<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Hydrator;

use LupaSearch\LupaSearchPlugin\Model\Config\Queries\ProductConfigInterface;
use LupaSearch\LupaSearchPlugin\Model\Hydrator\ProductHydratorInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\Attributes\SearchableAttributesProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\Categories\AnchorProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\Categories\ParentIdsProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\Categories\PositionProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\CategoriesProviderInterface;
use Magento\Catalog\Model\Product;

use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function implode;

class Category implements ProductHydratorInterface
{
    private const HIERARCHICAL_SEPARATOR = ' > ';

    private CategoriesProviderInterface $categoriesProvider;

    private ProductConfigInterface $productConfig;

    private ParentIdsProviderInterface $parentIdsProvider;

    private PositionProviderInterface $positionProvider;

    private AnchorProviderInterface $anchorProvider;

    public function __construct(
        CategoriesProviderInterface $categoriesProvider,
        ProductConfigInterface $productConfig,
        ParentIdsProviderInterface $parentIdsProvider,
        PositionProviderInterface $positionProvider,
        AnchorProviderInterface $anchorProvider
    ) {
        $this->categoriesProvider = $categoriesProvider;
        $this->productConfig = $productConfig;
        $this->parentIdsProvider = $parentIdsProvider;
        $this->positionProvider = $positionProvider;
        $this->anchorProvider = $anchorProvider;
    }

    /**
     * @return array<string, int|string|array|null>
     */
    public function extract(Product $product): array
    {
        $categoriesWeight = $this->productConfig->getCategoriesSearchWeight();

        $data = [];
        $data['category_id'] = $this->getCategoryId($product);
        $data['category_ids'] = $this->getCategoryIds($product);
        $data['categories'] = $this->getCategories($product);
        $data['category'] = $this->getCategory($product);
        $data['position'] = $this->getPosition($product);
        $data[SearchableAttributesProviderInterface::ATTRIBUTE_PREFIX . $categoriesWeight] = $data['categories'];

        $categoriesHierarchy = $this->getCategoriesHierarchy($product);
        $data['categories_hierarchy'] = $categoriesHierarchy;
        $data['categories_last'] = $this->getCategoriesLast($categoriesHierarchy);

        return $data;
    }

    /**
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    private function getCategoryId(Product $product): ?int
    {
        $id = $product->getCategoryId();

        return null !== $id ? (int)$id : null;
    }

    private function getCategory(Product $product): ?string
    {
        $catId = $this->getCategoryId($product);

        if (empty($catId)) {
            return null;
        }

        $ids = $this->parentIdsProvider->getById($catId);
        $ids[] = $catId;
        $storeId = $this->getStoreId($product);
        $names = [];

        foreach ($ids as $id) {
            $names[] = $this->categoriesProvider->getNameById($id, $storeId);
        }

        return implode(self::HIERARCHICAL_SEPARATOR, array_filter($names));
    }

    /**
     * @return string[]
     */
    private function getCategoriesHierarchy(Product $product): array
    {
        $assignedCategoryIds = $product->getAssignedCategoryIds();
        $catIds  = is_array($assignedCategoryIds) ? array_map('intval', $assignedCategoryIds) : [];
        $storeId = $this->getStoreId($product);
        $result = [];

        foreach ($catIds as $catId) {
            $ids = $this->parentIdsProvider->getById($catId);
            $ids[] = $catId;
            $names = [];
            foreach ($ids as $id) {
                $name = $this->categoriesProvider->getNameById($id, $storeId);
                // skip category hierarchy if any of the parent categories are disabled
                if (empty($name)) {
                    break;
                }
                $names[] = $name;
            }

            if (!empty($names)) {
                $result[] = implode(self::HIERARCHICAL_SEPARATOR, array_filter($names));
            }
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function getCategoriesLast(array $categoriesHierarchy): array
    {
        $result = [];

        foreach ($categoriesHierarchy as $categoryHierarchy) {
            $categories = explode(self::HIERARCHICAL_SEPARATOR, $categoryHierarchy);
            $result[] = end($categories);
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function getCategories(Product $product): array
    {
        $data = [];
        $storeId = $this->getStoreId($product);

        foreach ($this->getCategoryIds($product) as $id) {
            $data[] = $this->categoriesProvider->getNameById($id, $storeId);
        }

        return array_values(array_filter($data));
    }

    /**
     * @return array<string, int>
     */
    private function getPosition(Product $product): array
    {
        return array_map(
            static function (int $id): string {
                return 'category_' . $id;
            },
            $this->positionProvider->getByProductId((int)$product->getId()),
        );
    }

    /**
     * @return int[]
     */
    private function getCategoryIds(Product $product): array
    {
        $ids = array_map('intval', $product->getCategoryIds());

        return array_values(array_unique(array_merge($ids, $this->anchorProvider->getByCategoryIds($ids))));
    }

    private function getStoreId(Product $product): int
    {
        /** @psalm-suppress RedundantCastGivenDocblockType */
        return (int)$product->getStoreId();
    }
}
