<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Provider\Price;

use LupaSearch\LupaSearchPlugin\Model\Provider\ProductsProvider as BaseProductsProvider;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Traversable;

class ProductsProvider extends BaseProductsProvider
{
    /**
     * @inheritDoc
     */
    public function getByIds(array $ids, int $storeId): Traversable
    {
        $ids = !empty($ids) ? $ids : [0];

        $collection = $this->createCollection($storeId);
        $collection->addAttributeToFilter('entity_id', ['in' => $ids]);
        $this->dataModifier->modify($collection);

        return $collection;
    }

    private function createCollection(int $storeId): Collection
    {
        $collection = $this->collectionBuilder->build($storeId);
        $collection->removeAllFieldsFromSelect();

        return $collection;
    }
}
