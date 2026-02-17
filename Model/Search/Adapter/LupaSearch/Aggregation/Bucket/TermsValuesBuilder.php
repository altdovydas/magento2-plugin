<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Aggregation\Bucket;

use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\OrderedMapInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\NoSuchEntityException;

class TermsValuesBuilder implements ValuesBuilderInterface
{
    private Config $eavConfig;

    public function __construct(Config $eavConfig)
    {
        $this->eavConfig = $eavConfig;
    }

    /**
     * @inheritDoc
     */
    public function build(OrderedMapInterface $facet): array
    {
        $items = $facet->get('items') ?? [];

        if (!$items) {
            return [];
        }

        $attribute = $this->getAttribute((string)$facet->get('key'));
        $values = [];

        foreach ($items as $item) {
            if (!$item instanceof OrderedMapInterface) {
                continue;
            }

            $title = $item->get('title');
            $value = $attribute && !is_int($title) ? $this->getOptionValue($attribute, $title) : $title;

            if (null === $value) {
                continue;
            }

            $values[$title] = ['value' => $value, 'count' => $item->get('count') ?? 0];
        }

        return $values;
    }

    private function getOptionValue(ProductAttributeInterface $attribute, string $value): ?string
    {
        if (!$attribute->usesSource()) {
            return $value;
        }

        $source = $attribute->getSource();
        $optionId = $source->getOptionId($value);

        if (null !== $optionId && '' !== $optionId) {
            /** @psalm-suppress RedundantCast */
            return (string)$optionId;
        }

        return $value;
    }

    private function getAttribute(string $fieldCode): ?ProductAttributeInterface
    {
        $parts = explode('_', $fieldCode);
        $attributeId = end($parts);

        if (!$attributeId || !is_numeric($attributeId)) {
            return null;
        }

        try {
            return $this->eavConfig->getAttribute(Product::ENTITY, (int)$attributeId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
