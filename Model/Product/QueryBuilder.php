<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product;

use LupaSearch\LupaSearchPlugin\Model\Adapter\QueriesManagementInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\Attributes\FilterableAttributesProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\BoostProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\QueryBuilder\AbstractQueryBuilder;
use LupaSearch\LupaSearchPlugin\Model\QueryBuilder\FacetTypeProviderInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\OrderedMapInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\SearchQueryInterface;
use LupaSearch\LupaSearchPluginCore\Factories\OrderedMapFactory;
use LupaSearch\LupaSearchPluginCore\Factories\QueryConfigurationFactoryInterface;
use LupaSearch\LupaSearchPluginCore\Factories\SearchQueryFactoryInterface;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;

use function strpos;

class QueryBuilder extends AbstractQueryBuilder
{
    private const FACET_ORDER_COUNT = 'count_desc';
    private const FACET_MAX_LIMIT = 5000;

    private FilterableAttributesProviderInterface $filterableAttributesProvider;

    private FacetTypeProviderInterface $facetTypeProvider;

    private array $filterableFields = [];

    /**
     * @param string[] $attributeToSelect
     */
    public function __construct(
        BoostProviderInterface $boostProvider,
        SearchQueryFactoryInterface $searchQueryFactory,
        QueryConfigurationFactoryInterface $queryConfigurationFactory,
        FilterableAttributesProviderInterface $filterableAttributesProvider,
        FacetTypeProviderInterface $facetTypeProvider,
        array $attributeToSelect = [],
        array $filterableFields = []
    ) {
        parent::__construct(
            $boostProvider,
            $searchQueryFactory,
            $queryConfigurationFactory,
            $attributeToSelect,
        );

        $this->filterableAttributesProvider = $filterableAttributesProvider;
        $this->facetTypeProvider = $facetTypeProvider;
        $this->filterableFields = $filterableFields;
    }

    /**
     * @return OrderedMapInterface[]
     */
    protected function getFacets(SearchQueryInterface $searchQuery): array
    {
        if (0 === strpos($searchQuery->getDescription(), QueriesManagementInterface::TYPE_PRODUCT_SEARCH_BOX)) {
            return [];
        }

        $result = [];

        foreach ($this->filterableAttributesProvider->getList() as $attributeId => $attribute) {
            if (
                QueriesManagementInterface::TYPE_PRODUCT === $searchQuery->getDescription() &&
                !$attribute->getIsFilterableInSearch()
            ) {
                continue;
            }

            $result[] = $this->createFacet($attributeId, $attribute);
        }

        return $result;
    }

    /**
     * @return string[]
     */
    protected function getFilterableFields(SearchQueryInterface $searchQuery): array
    {
        if (QueriesManagementInterface::TYPE_PRODUCT_SEARCH_BOX === $searchQuery->getDescription()) {
            return [];
        }

        return $this->filterableFields;
    }

    private function createFacet(string $attributeId, AbstractAttribute $attribute): OrderedMapInterface
    {
        $facet = OrderedMapFactory::create(
            [
                'type' => $this->facetTypeProvider->get($attribute),
                'key' => $attributeId,
                'label' => (string)$attribute->getStoreLabel(),
            ]
        );

        if (FacetTypeProviderInterface::TERMS !== $facet->get('type')) {
            return $facet;
        }

        $facet->add('limit', self::FACET_MAX_LIMIT);
        $facet->add('order', self::FACET_ORDER_COUNT);

        return $facet;
    }
}
