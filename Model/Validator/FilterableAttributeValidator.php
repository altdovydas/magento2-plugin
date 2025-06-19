<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Validator;

use LupaSearch\LupaSearchPlugin\Model\Provider\Attributes\SystemAttributeMapInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;

use function in_array;

class FilterableAttributeValidator implements AttributeValidatorInterface
{
    /**
     * @var string[]
     */
    private array $allowedFrontendInputTypes = [
        'multiselect',
        'select',
        'boolean',
    ];

    /**
     * @var string[]
     */
    private array $filterableAttributes = [
        ProductInterface::VISIBILITY,
        ProductInterface::STATUS,
    ];

    private SystemAttributeMapInterface $systemAttributeMap;

    public function __construct(SystemAttributeMapInterface $systemAttributeMap)
    {
        $this->systemAttributeMap = $systemAttributeMap;
    }

    public function validate(AbstractAttribute $attribute): bool
    {
        $isDefaultFilterable = in_array($attribute->getAttributeCode(), $this->filterableAttributes, true);

        if (
            (int)$attribute->getData(Attribute::IS_FILTERABLE) < 1 &&
            !$attribute->getData(Attribute::IS_FILTERABLE_IN_SEARCH) &&
            !$isDefaultFilterable
        ) {
            return false;
        }

        return
            (in_array($attribute->getBackendType(), ['decimal', 'int'], true) || $isDefaultFilterable ||
                in_array($attribute->getFrontendInput(), $this->allowedFrontendInputTypes, true)) &&
            !in_array($attribute->getAttributeCode(), $this->systemAttributeMap->getList(), true);
    }
}
