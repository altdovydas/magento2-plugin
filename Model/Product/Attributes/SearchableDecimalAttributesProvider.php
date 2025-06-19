<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Attributes;

use LupaSearch\LupaSearchPlugin\Model\Provider\Attributes\SearchableDecimalAttributesProviderInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;

class SearchableDecimalAttributesProvider extends DecimalAttributesProvider implements
    SearchableDecimalAttributesProviderInterface
{
    protected function getAttributeCollection(): Collection
    {
        $attributeCollection = parent::getAttributeCollection();

        $attributeCollection->addFieldToFilter(
            [Attribute::IS_SEARCHABLE, Attribute::IS_VISIBLE_IN_ADVANCED_SEARCH],
            [['eq' => true], ['eq' => true]],
        );

        return $attributeCollection;
    }
}
