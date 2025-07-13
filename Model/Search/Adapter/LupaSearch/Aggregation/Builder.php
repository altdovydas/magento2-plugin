<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Aggregation;

use LupaSearch\LupaSearchPlugin\Model\Provider\Attributes\FilterableAttributesProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\QueryBuilder\FacetTypeProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Aggregation\Bucket\ValuesBuilderInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\DocumentQueryResponseInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\OrderedMapInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\CatalogSearch\Model\Search\RequestGenerator;
use Magento\Elasticsearch\SearchAdapter\AggregationFactory;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Search\RequestInterface;

use function array_filter;
use function in_array;

class Builder implements BuilderInterface
{
    private AggregationFactory $aggregationFactory;

    private FilterableAttributesProviderInterface $attributesProvider;

    /**
     * @var ValuesBuilderInterface[]
     */
    private array $valuesBuilders;

    /**
     * @param ValuesBuilderInterface[] $valuesBuilders
     */
    public function __construct(
        AggregationFactory $aggregationFactory,
        FilterableAttributesProviderInterface $attributesProvider,
        array $valuesBuilders = []
    ) {
        $this->aggregationFactory = $aggregationFactory;
        $this->attributesProvider = $attributesProvider;
        $this->valuesBuilders = array_filter(
            $valuesBuilders,
            static function ($builder): bool {
                return $builder instanceof ValuesBuilderInterface;
            },
        );
    }

    public function build(
        DocumentQueryResponseInterface $queryResponse,
        RequestInterface $request
    ): AggregationInterface {
        $aggregations = [];
        $attributeList = $this->attributesProvider->getList();

        foreach ($queryResponse->getFacets() as $facet) {
            $builder = $this->valuesBuilders[$facet->get('type')] ?? null;

            if (null === $builder) {
                continue;
            }

            $attribute = $this->getAttribute($facet, $attributeList);

            if (null === $attribute) {
                continue;
            }

            $name = $attribute->getAttributeCode() . RequestGenerator::BUCKET_SUFFIX;

            if (FacetTypeProviderInterface::STATS === $facet->get('type')) {
                // Hack Lupasearch not supporting std_deviation and count for stats aggregation
                $aggregations[$name] = $builder->setRequest($request)->build($facet, $request);

                continue;
            }

            $aggregations[$name] = $builder->build($facet);
        }

        return $this->aggregationFactory->create($aggregations);
    }

    /**
     * @param array<string, Attribute> $attributeList
     */
    private function getAttribute(OrderedMapInterface $facet, array $attributeList): ?Attribute
    {
        $key = $facet->get('key');

        if (in_array($key, ['category', 'category_id'], true)) {
            return null;
        }

        $key = $key === 'category_ids' ? 'category' : $key;

        return $attributeList[$key] ?? null;
    }
}
