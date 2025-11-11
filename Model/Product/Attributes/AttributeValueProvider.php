<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Attributes;

use LupaSearch\LupaSearchPlugin\Model\Normalizer\StringNormalizerInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\ProviderCacheInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;

use function is_string;

class AttributeValueProvider implements AttributeValueProviderInterface, ProviderCacheInterface
{
    private StringNormalizerInterface $stringNormalizer;

    /**
     * @var string[]
     */
    private array $valueCache = [];

    public function __construct(StringNormalizerInterface $stringNormalizer)
    {
        $this->stringNormalizer = $stringNormalizer;
    }

    public function getValue(Product $product, AbstractAttribute $attribute): ?string
    {
        $attributeCode = $attribute->getAttributeCode();
        $productData = $product->getData($attributeCode);

        if (null === $productData) {
            return null;
        }

        if (isset($this->valueCache[$attributeCode][$productData])) {
            return $this->valueCache[$attributeCode][$productData];
        }

        $oldDataObject = $attribute->getDataObject();
        $attribute->setDataObject($product);
        $value = $attribute->getFrontend()->getValue($product);
        $attribute->setDataObject($oldDataObject);

        if (!is_string($value) || '' === $value) {
            return null;
        }

        return $this->valueCache[$attributeCode][$productData] = $this->stringNormalizer->normalize($value);
    }

    /**
     * @param int[] $ids
     */
    public function warmup(array $ids, ?int $storeId = null): void
    {
        // Not needed
    }

    public function flush(): void
    {
        $this->valueCache = [];
    }
}
