<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\DataProvider;

use LupaSearch\LupaSearchPlugin\Model\Config\Queries\ProductConfigInterface;
use LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\QueryInterface as LupaSearchQueryInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\OrderedMapInterface;
use Magento\AdvancedSearch\Model\SuggestedQueriesInterface;
use Magento\Elasticsearch\Model\DataProvider\Base\GetSuggestionFrequencyInterface;
use Magento\Search\Model\QueryInterface;
use Magento\Search\Model\QueryResult;
use Magento\Search\Model\QueryResultFactory;

use function array_map;

class Suggestions implements SuggestedQueriesInterface
{
    private LupaSearchQueryInterface $query;

    private QueryResultFactory $queryResultFactory;

    private ProductConfigInterface $productConfig;

    private GetSuggestionFrequencyInterface $getSuggestionFrequency;

    public function __construct(
        LupaSearchQueryInterface $query,
        QueryResultFactory $queryResultFactory,
        ProductConfigInterface $productConfig,
        GetSuggestionFrequencyInterface $getSuggestionFrequency
    ) {
        $this->query = $query;
        $this->queryResultFactory = $queryResultFactory;
        $this->productConfig = $productConfig;
        $this->getSuggestionFrequency = $getSuggestionFrequency;
    }

    /**
     * @inheritDoc
     */
    public function getItems(QueryInterface $query): array
    {
        $storeId = (int)$query->getStoreId();

        if (!$this->productConfig->isSearchSuggestionsEnabled($storeId)) {
            return [];
        }

        $showResultCount = $this->productConfig->isResultsCountForEachSuggestionEnabled($storeId);
        $suggestionResult = $this->query->suggestion($query);

        return array_map(
            function (OrderedMapInterface $item) use ($showResultCount) {
                return $this->createQueryResult($item, $showResultCount);
            },
            $suggestionResult->getItems(),
        );
    }

    public function isResultsCountEnabled(): bool
    {
        return $this->productConfig->isResultsCountForEachSuggestionEnabled();
    }

    private function createQueryResult(OrderedMapInterface $item, bool $showResultCount): QueryResult
    {
        $queryText = $item->get('suggestion');
        $resultsCount = $showResultCount ? $this->getSuggestionFrequency->execute($queryText) : null;

        return $this->queryResultFactory->create(
            [
                'queryText' => $queryText,
                'resultsCount' => $resultsCount,
            ]
        );
    }
}
