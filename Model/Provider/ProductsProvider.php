<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Provider;

use LupaSearch\LupaSearchPlugin\Model\Product\CollectionBuilder;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\Store;
use Traversable;

class ProductsProvider implements ProductsProviderInterface
{
    protected CollectionBuilder $collectionBuilder;

    private DataModifierInterface $dataModifier;

    public function __construct(CollectionBuilder $collectionBuilder, DataModifierInterface $dataModifier)
    {
        $this->collectionBuilder = $collectionBuilder;
        $this->dataModifier = $dataModifier;
    }

    /**
     * @inheritDoc
     */
    public function getAllIds(int $storeId): array
    {
        $collection = $this->createCollection($storeId);
        $collection->getSelect()->reset(Select::WHERE);
        $collection->getSelect()->reset(Select::GROUP);
        $from = $collection->getSelect()->getPart(Select::FROM);
        $collection->getSelect()->setPart(
            Select::FROM,
            [
                'e' => $from['e'],
                'cat_index' => $from['cat_index'],
            ],
        );

        return $collection->getAllIds();
    }

    /**
     * @inheritDoc
     */
    public function getAllIdsByAttribute(Attribute $attribute): array
    {
        $collection = $this->createCollection(Store::DEFAULT_STORE_ID);
        $collection->getSelect()->reset(Select::WHERE);
        $from = $collection->getSelect()->getPart(Select::FROM);
        $collection->getSelect()->setPart(
            Select::FROM,
            [
                'e' => $from['e'],
            ],
        );

        if (!$attribute->isStatic()) {
            $linkField = $collection->getProductEntityMetadata()->getLinkField();
            $attributeId = $attribute->getId();
            $bind = "`attribute`.`{$linkField}` = `e`.`{$linkField}` AND `attribute`.`attribute_id` = {$attributeId}"
            . " AND `attribute`.`value` IS NOT NULL AND `attribute`.`value` != \"\"";
            $collection->getSelect()->join(['attribute' => $attribute->getBackendTable()], $bind, []);
        }

        return $collection->getAllIds();
    }

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
        return $this->collectionBuilder->build($storeId);
    }
}
