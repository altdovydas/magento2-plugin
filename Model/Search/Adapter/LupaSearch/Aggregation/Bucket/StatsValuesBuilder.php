<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Aggregation\Bucket;

use LupaSearch\LupaSearchPlugin\Model\Adapter\Query\QueryProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Queries\DocumentQueryBuilderInterface;
use LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Queries\SearchQueryBuilderInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\DocumentQueryResponseInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\OrderedMapInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\SearchQueryInterface;
use LupaSearch\LupaSearchPluginCore\Api\PublicQueryApiInterface;
use LupaSearch\LupaSearchPluginCore\Api\PublicQueryApiInterfaceFactory as PublicQueryApiFactory;
use LupaSearch\LupaSearchPluginCore\Api\SearchQueriesApiInterfaceFactory;
use LupaSearch\LupaSearchPluginCore\Model\LupaClientFactoryInterface;
use Magento\Framework\Search\Request;
use Magento\Framework\Search\Request\Aggregation\RangeBucket;
use Magento\Framework\Search\Request\Aggregation\RangeBucketFactory;
use Magento\Framework\Search\Request\Aggregation\RangeFactory;
use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\Search\RequestFactory;
use Magento\Framework\Search\RequestInterface;
use Magento\Store\Model\StoreDimensionProvider;

class StatsValuesBuilder implements ValuesBuilderInterface
{
    private ?RequestInterface $request = null;
    private LupaClientFactoryInterface $lupaClientFactory;
    private PublicQueryApiFactory $publicQueryApiFactory;
    private QueryProviderInterface $queryProvider;
    private SearchQueryBuilderInterface $searchQueryBuilder;
    private DocumentQueryBuilderInterface $documentQueryBuilder;
    private ValuesBuilderInterface $valuesBuilder;
    private RangeBucketFactory $rangeBucketFactory;
    private RangeFactory $rangeFactory;
    private RequestFactory $requestFactory;

    public function __construct(
        LupaClientFactoryInterface $lupaClientFactory,
        PublicQueryApiFactory $publicQueryApiFactory,
        QueryProviderInterface $queryProvider,
        SearchQueryBuilderInterface $searchQueryBuilder,
        DocumentQueryBuilderInterface $documentQueryBuilder,
        ValuesBuilderInterface $valuesBuilder,
        RangeBucketFactory $rangeBucketFactory,
        RangeFactory $rangeFactory,
        RequestFactory $requestFactory
    ) {
        $this->requestFactory = $requestFactory;
        $this->rangeFactory = $rangeFactory;
        $this->rangeBucketFactory = $rangeBucketFactory;
        $this->valuesBuilder = $valuesBuilder;
        $this->documentQueryBuilder = $documentQueryBuilder;
        $this->searchQueryBuilder = $searchQueryBuilder;
        $this->queryProvider = $queryProvider;
        $this->publicQueryApiFactory = $publicQueryApiFactory;
        $this->lupaClientFactory = $lupaClientFactory;
    }

    public function setRequest(RequestInterface $request): ValuesBuilderInterface
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(OrderedMapInterface $facet): array
    {
        if (null === $facet->get('min') || null === $this->request || $facet->get('min') >= $facet->get('max')) {
            return [];
        }

        $searchResult = $this->getResult($facet);
        $facet = $this->getFacet($searchResult, $facet);

        return $this->valuesBuilder->build($facet);
    }

    private function getFacet(DocumentQueryResponseInterface $result, OrderedMapInterface $filter): ?OrderedMapInterface
    {
        foreach ($result->getFacets() as $facet) {
            if ($facet->get('key') === $filter->get('key')) {
                return $facet;
            }
        }

        return null;
    }

    private function getSearchQuery(OrderedMapInterface $facet): SearchQueryInterface
    {
        return $this->searchQueryBuilder->build($this->createRangeRequest($facet));
    }

    private function createRangeRequest(OrderedMapInterface $facet): Request
    {
        return $this->requestFactory->create(
            [
                'name' => $this->request->getName(),
                'indexName' => $this->request->getIndex(),
                'query' => $this->request->getQuery(),
                'from' => $this->request->getFrom(),
                'size' => $this->request->getSize(),
                'dimensions' => $this->request->getDimensions(),
                'buckets' => [$this->createRangeBucket($facet)],
                'sort' => $this->request->getSort(),
            ]
        );
    }

    private function createRangeBucket(OrderedMapInterface $facet): RangeBucket
    {
        return $this->rangeBucketFactory->create(
            [
                'name' => $facet->get('label'),
                'field' => $facet->get('key'),
                'metrics' => [],
                'ranges' => [$this->rangeFactory->create(['from' => $facet->get('min'), 'to' => $facet->get('max')])],
            ]
        );
    }

    private function getResult(OrderedMapInterface $facet): DocumentQueryResponseInterface
    {
        $request = $this->createRangeRequest($facet);

        return $this->getApi()->post(
            $this->queryProvider->getSearch($request),
            $this->documentQueryBuilder->build($request),
        );
    }

    private function getApi(): PublicQueryApiInterface
    {
        return $this->publicQueryApiFactory->create(
            ['client' => $this->lupaClientFactory->create($this->getStoreId())]
        );
    }

    private function getStoreId(): int
    {
        $scope = $this->request->getDimensions()[StoreDimensionProvider::DIMENSION_NAME] ?? null;

        return $scope instanceof Dimension ? (int)$scope->getValue() : 0;
    }
}
