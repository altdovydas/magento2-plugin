<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Attributes;

use LupaSearch\LupaSearchPlugin\Model\Provider\ProviderCacheInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class DecimalAttributesProvider implements ProviderCacheInterface
{
    private const DECIMAL_BACKEND_TYPE = 'decimal';

    /**
     * @var Attribute[][]
     */
    protected array $attributeSetMap = [];

    private CollectionFactory $attributeCollectionFactory;

    private ProductCollectionFactory $productCollectionFactory;

    public function __construct(
        CollectionFactory $attributeCollectionFactory,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @return Attribute[]
     */
    public function getByAttributeSetId(int $attributeSetId): array
    {
        if (isset($this->attributeSetMap[$attributeSetId])) {
            return $this->attributeSetMap[$attributeSetId];
        }

        $attributeCollection = $this->getAttributeCollection();
        $attributeCollection->addFieldToFilter('eea.attribute_set_id', ['eq' => $attributeSetId]);

        $this->attributeSetMap[$attributeSetId] = $attributeCollection->getItems();

        return $this->attributeSetMap[$attributeSetId];
    }

    /**
     * @inheritDoc
     * @phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function warmup(array $ids, ?int $storeId = null): void
    {
        $attributeSetIds = $this->getAttributeSetIdsByProductIds($ids, $storeId);

        foreach ($attributeSetIds as $attributeSetId) {
            $this->getByAttributeSetId((int)$attributeSetId);
        }
    }

    public function flush(): void
    {
        $this->attributeSetMap = [];
    }

    protected function getAttributeCollection(): Collection
    {
        $attributeCollection = $this->attributeCollectionFactory->create();
        $attributeCollection->addFieldToFilter('backend_type', ['eq' => self::DECIMAL_BACKEND_TYPE]);

        $attributeCollection->joinLeft(
            ['eea' => $attributeCollection->getTable('eav_entity_attribute')],
            'main_table.attribute_id = eea.attribute_id',
            [],
        );

        return $attributeCollection;
    }

    /**
     * @param int[] $ids
     * @return int[]
     */
    private function getAttributeSetIdsByProductIds(array $ids, ?int $storeId = null): array
    {
        $productCollection = $this->productCollectionFactory->create();

        if (null !== $storeId) {
            $productCollection->addStoreFilter($storeId);
        }

        $productCollection->addAttributeToFilter('entity_id', ['in' => $ids]);

        return $productCollection->getSetIds();
    }
}
