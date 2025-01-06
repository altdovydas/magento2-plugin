<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Queries\SearchQuery\Configuration;

use LupaSearch\LupaSearchPlugin\Model\Product\Attributes\AttributeValueProviderInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Search\Request\Filter\Range;
use Magento\Framework\Search\Request\FilterInterface;

use function array_filter;
use function array_map;
use function in_array;

class FilterValueProvider implements FilterValueProviderInterface
{
    private ProductAttributeRepositoryInterface $productAttributeRepository;

    private ProductFactory $productFactory;

    private AttributeValueProviderInterface $attributeValueProvider;

    /**
     * @var string[]
     */
    private array $categoryFields = [
        'category_ids',
        'category',
    ];

    public function __construct(
        ProductAttributeRepositoryInterface $productAttributeRepository,
        ProductFactory $productFactory,
        AttributeValueProviderInterface $attributeValueProvider
    ) {
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productFactory = $productFactory;
        $this->attributeValueProvider = $attributeValueProvider;
    }

    /**
     * @inheritDoc
     */
    public function get(FilterInterface $reference): array
    {
        if ($reference instanceof Range) {
            return array_filter(['gt' => $reference->getFrom(), 'lte' => $reference->getTo()]);
        }

        $attributeCode = $reference->getField();
        $values = !is_array($reference->getValue()) ? [$reference->getValue()] : array_values($reference->getValue());

        if (in_array($attributeCode, $this->categoryFields, true)) {
            return $values;
        }

        $attribute = $this->getAttribute($attributeCode);

        return null === $attribute ? $values : array_map(
            function ($value) use ($attribute) {
                return $this->attributeValueProvider->getValue(
                    $this->productFactory->create(['data' => [$attribute->getAttributeCode() => $value]]),
                    $attribute,
                );
            },
            $values,
        );
    }

    private function getAttribute(string $attributeCode): ?ProductAttributeInterface
    {
        try {
            return $this->productAttributeRepository->get($attributeCode);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
