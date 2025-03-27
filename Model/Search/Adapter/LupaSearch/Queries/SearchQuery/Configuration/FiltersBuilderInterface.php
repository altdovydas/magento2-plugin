<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Queries\SearchQuery\Configuration;

use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\OrderedMapInterface;
use Magento\Framework\Search\Request\Query\Filter;

interface FiltersBuilderInterface
{
    /**
     * @param Filter[] $filters
     */
    public function build(array $filters): OrderedMapInterface;
}
