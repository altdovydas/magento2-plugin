<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Plugin\Model\ResourceModel\Fulltext;

use LupaSearch\LupaSearchPlugin\Setup\ConfigOptionsList;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection as Subject;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Search\EngineResolverInterface;

class CollectionPlugin
{
    private EngineResolverInterface $engineResolver;

    public function __construct(EngineResolverInterface $engineResolver)
    {
        $this->engineResolver = $engineResolver;
    }

    public function aroundGetFacetedData(Subject $subject, callable $process, string $field): array
    {
        if (ConfigOptionsList::SEARCH_ENGINE !== $this->engineResolver->getCurrentSearchEngine()) {
            return $process($field);
        }

        try {
            return $process($field);
        } catch (StateException $exception) {
            // Attribute Filterable only with results
            return [];
        }
    }
}
