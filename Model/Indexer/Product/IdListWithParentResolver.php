<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Indexer\Product;

use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;

class IdListWithParentResolver implements IdListResolverInterface
{
    private Configurable $configurableResource;

    public function __construct(Configurable $configurableResource)
    {
        $this->configurableResource = $configurableResource;
    }

    /**
     * @inheritdoc
     */
    public function resolve(array $ids): array
    {
        if (empty($ids)) {
            return $ids;
        }

        $parentIdList = $this->configurableResource->getParentIdsByChild($ids);

        return array_map('intval', array_unique(array_merge($ids, $parentIdList)));
    }
}
