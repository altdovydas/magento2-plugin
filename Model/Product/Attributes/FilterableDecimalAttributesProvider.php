<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Attributes;

use LupaSearch\LupaSearchPlugin\Model\Provider\Attributes\FilterableDecimalAttributesProviderInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;

class FilterableDecimalAttributesProvider extends DecimalAttributesProvider implements
    FilterableDecimalAttributesProviderInterface
{
    protected function getAttributeCollection(): Collection
    {
        $attributeCollection = parent::getAttributeCollection();

        $attributeCollection->addFieldToFilter(
            [Attribute::IS_FILTERABLE, Attribute::IS_FILTERABLE_IN_SEARCH],
            [['eq' => true], ['eq' => true]],
        );

        return $attributeCollection;
    }
}
