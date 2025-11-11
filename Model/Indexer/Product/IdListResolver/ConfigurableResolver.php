<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Indexer\Product\IdListResolver;

use LupaSearch\LupaSearchPlugin\Model\Indexer\Product\IdListResolverInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;

use function array_map;
use function array_unique;

class ConfigurableResolver implements IdListResolverInterface
{
    private Configurable $configurableResource;

    public function __construct(Configurable $configurableResource)
    {
        $this->configurableResource = $configurableResource;
    }

    /**
     * @inheritDoc
     */
    public function resolve(array $ids): array
    {
        if (empty($ids)) {
            return $ids;
        }

        $parentIdList = $this->configurableResource->getParentIdsByChild($ids);

        return array_map('intval', array_unique($parentIdList));
    }
}
