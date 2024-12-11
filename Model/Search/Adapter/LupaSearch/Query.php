<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch;

use LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Queries\DocumentQueryBuilderInterface;
use LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Queries\SearchQueryBuilderInterface;
use LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Queries\SuggestionQueryBuilderInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\DocumentQueryResponseInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\SuggestionQueryResponseInterface;
use LupaSearch\LupaSearchPluginCore\Api\PublicQueryApiInterfaceFactory as PublicQueryApiFactory;
use LupaSearch\LupaSearchPlugin\Model\Adapter\Query\QueryProviderInterface;
use LupaSearch\LupaSearchPluginCore\Model\LupaClientFactoryInterface;
use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\Search\RequestInterface;
use Magento\Search\Model\Query as MagentoQuery;
use Magento\Search\Model\QueryInterface as MagentoQueryInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreDimensionProvider;

class Query implements QueryInterface
{
    private LupaClientFactoryInterface $lupaClientFactory;

    private PublicQueryApiFactory $publicQueryApiFactory;

    private QueryProviderInterface $queryProvider;

    private SearchQueryBuilderInterface $searchQueryBuilder;

    private DocumentQueryBuilderInterface $documentQueryBuilder;

    private SuggestionQueryBuilderInterface $suggestionQueryBuilder;

    public function __construct(
        LupaClientFactoryInterface $lupaClientFactory,
        PublicQueryApiFactory $publicQueryApiFactory,
        QueryProviderInterface $queryProvider,
        SearchQueryBuilderInterface $searchQueryBuilder,
        DocumentQueryBuilderInterface $documentQueryBuilder,
        SuggestionQueryBuilderInterface $suggestionQueryBuilder
    ) {
        $this->lupaClientFactory = $lupaClientFactory;
        $this->publicQueryApiFactory = $publicQueryApiFactory;
        $this->queryProvider = $queryProvider;
        $this->searchQueryBuilder = $searchQueryBuilder;
        $this->documentQueryBuilder = $documentQueryBuilder;
        $this->suggestionQueryBuilder = $suggestionQueryBuilder;
    }

    public function document(RequestInterface $request): DocumentQueryResponseInterface
    {
        $storeId = $this->getStoreId($request);
        $client = $this->lupaClientFactory->create($storeId);

        return $this->publicQueryApiFactory->create(['client' => $client])->post(
            $this->queryProvider->getSearch($request),
            $this->documentQueryBuilder->build($request),
        );
    }

    public function suggestion(MagentoQueryInterface $query): SuggestionQueryResponseInterface
    {
        $storeId = $query instanceof MagentoQuery ? (int)$query->getStoreId() : Store::DISTRO_STORE_ID;
        $client = $this->lupaClientFactory->create($storeId);

        return $this->publicQueryApiFactory->create(['client' => $client])->post(
            $this->queryProvider->getSuggestion($query),
            $this->suggestionQueryBuilder->build($query),
        );
    }

    private function getStoreId(RequestInterface $request): int
    {
        $scope = $request->getDimensions()[StoreDimensionProvider::DIMENSION_NAME] ?? null;

        return $scope instanceof Dimension ? (int)$scope->getValue() : 0;
    }
}
