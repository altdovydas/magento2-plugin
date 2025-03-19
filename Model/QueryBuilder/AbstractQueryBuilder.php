<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\QueryBuilder;

use LupaSearch\LupaSearchPlugin\Model\Provider\BoostProviderInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\OrderedMapInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\SearchQueryInterface;
use LupaSearch\LupaSearchPluginCore\Factories\OrderedMapFactory;
use LupaSearch\LupaSearchPluginCore\Factories\QueryConfigurationFactoryInterface;
use LupaSearch\LupaSearchPluginCore\Factories\SearchQueryFactoryInterface;

use function array_filter;

/**
 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
 */
abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
    /**
     * @description Query item limit
     */
    private const LIMIT = 20;

    private BoostProviderInterface $boostProvider;

    private SearchQueryFactoryInterface $searchQueryFactory;

    private QueryConfigurationFactoryInterface $queryConfigurationFactory;

    /**
     * @var string[]
     */
    private array $attributeToSelect;

    /**
     * @param string[] $attributeToSelect
     */
    public function __construct(
        BoostProviderInterface $boostProvider,
        SearchQueryFactoryInterface $searchQueryFactory,
        QueryConfigurationFactoryInterface $queryConfigurationFactory,
        array $attributeToSelect = []
    ) {
        $this->boostProvider = $boostProvider;
        $this->searchQueryFactory = $searchQueryFactory;
        $this->queryConfigurationFactory = $queryConfigurationFactory;
        $this->attributeToSelect = array_filter($attributeToSelect);
    }

    public function build(?SearchQueryInterface $searchQuery = null, ?int $storeId = 0): SearchQueryInterface
    {
        if (null === $searchQuery) {
            $searchQuery = $this->searchQueryFactory->create([]);
            $searchQuery->setConfiguration($this->queryConfigurationFactory->createSearchQueryConfiguration());

            return $searchQuery;
        }

        $this->addQueryFields($searchQuery);
        $this->addSelectFields($searchQuery);
        $this->addFilterableFields($searchQuery);
        $this->addBoost($searchQuery);
        $this->addFacets($searchQuery);
        $this->setLimit($searchQuery);
        $this->setSort($searchQuery);

        return $searchQuery;
    }

    protected function addBoost(SearchQueryInterface $searchQuery): void
    {
        $configuration = $searchQuery->getConfiguration();
        $configuration->setBoost(OrderedMapFactory::create($this->boostProvider->getBoostFields()));
    }

    protected function addQueryFields(SearchQueryInterface $searchQuery): void
    {
        $configuration = $searchQuery->getConfiguration();
        $queryFields = $configuration->getQueryFields();

        foreach ($this->boostProvider->getQueryFields() as $field => $weight) {
            $queryFields->add($field, $weight);
        }
    }

    protected function addSelectFields(SearchQueryInterface $searchQuery): void
    {
        $configuration = $searchQuery->getConfiguration();
        $configuration->setSelectFields($this->getAttributeToSelect());
    }

    protected function addFilterableFields(SearchQueryInterface $searchQuery): void
    {
        $configuration = $searchQuery->getConfiguration();
        $configuration->setFilterableFields($this->getFilterableFields($searchQuery));
    }

    protected function addFacets(SearchQueryInterface $searchQuery): void
    {
        $configuration = $searchQuery->getConfiguration();
        $configuration->setFacets($this->getFacets($searchQuery));
    }

    protected function setLimit(SearchQueryInterface $searchQuery): void
    {
        $searchQuery->getConfiguration()->setLimit($this->getLimit());
    }

    protected function setSort(SearchQueryInterface $searchQuery): void
    {
        $sort = $this->getSort();

        if ($sort) {
            $searchQuery->getConfiguration()->setSort($sort);
        }
    }

    /**
     * @return OrderedMapInterface[]
     */
    protected function getFacets(SearchQueryInterface $searchQuery): array
    {
        return [];
    }

    protected function getLimit(): int
    {
        return self::LIMIT;
    }

    /**
     * @return OrderedMapInterface[]
     */
    protected function getSort(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    protected function getFilterableFields(SearchQueryInterface $searchQuery): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    protected function getAttributeToSelect(): array
    {
        return $this->attributeToSelect;
    }
}
