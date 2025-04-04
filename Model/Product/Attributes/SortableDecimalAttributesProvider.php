<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Attributes;

use LupaSearch\LupaSearchPlugin\Model\Provider\Attributes\SortableDecimalAttributesProviderInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;

class SortableDecimalAttributesProvider extends DecimalAttributesProvider implements
    SortableDecimalAttributesProviderInterface
{
    protected function getAttributeCollection(): Collection
    {
        $attributeCollection = parent::getAttributeCollection();
        $attributeCollection->addFieldToFilter(Attribute::USED_FOR_SORT_BY, ['eq' => true]);

        return $attributeCollection;
    }
}
