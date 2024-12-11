<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Aggregation;

use LupaSearch\LupaSearchPlugin\Model\QueryBuilder\FacetTypeProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Aggregation\Bucket\ValuesBuilderInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\DocumentQueryResponseInterface;
use Magento\CatalogSearch\Model\Search\RequestGenerator;
use Magento\Elasticsearch\SearchAdapter\AggregationFactory;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Search\RequestInterface;

use function array_filter;

class Builder implements BuilderInterface
{
    private AggregationFactory $aggregationFactory;

    /**
     * @var ValuesBuilderInterface[]
     */
    private array $valuesBuilders;

    /**
     * @param ValuesBuilderInterface[] $valuesBuilders
     */
    public function __construct(AggregationFactory $aggregationFactory, array $valuesBuilders = [])
    {
        $this->aggregationFactory = $aggregationFactory;
        $this->valuesBuilders = array_filter(
            $valuesBuilders,
            static function ($builder): bool {
                return $builder instanceof ValuesBuilderInterface;
            },
        );
    }

    public function build(
        DocumentQueryResponseInterface $queryResponse,
        RequestInterface $request,
    ): AggregationInterface {
        $aggregations = [];

        foreach ($queryResponse->getFacets() as $facet) {
            $builder = $this->valuesBuilders[$facet->get('type')] ?? null;

            if (null === $builder) {
                continue;
            }

            $name = strtolower($facet->get('label')) . RequestGenerator::BUCKET_SUFFIX;

            if (FacetTypeProviderInterface::STATS === $facet->get('type')) {
                // Hack Lupasearch not supporting std_deviation and count for stats aggregation
                $aggregations[$name] = $builder->setRequest($request)->build($facet, $request);

                continue;
            }

            $aggregations[$name] = $builder->build($facet);
        }

        return $this->aggregationFactory->create($aggregations);
    }
}
