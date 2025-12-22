<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model;

use LupaSearch\LupaSearchPlugin\Model\Adapter\SearchEngineAdapterInterface;
use LupaSearch\LupaSearchPlugin\Model\Adapter\SearchEnginePoolInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_walk;

use const PHP_EOL;

class SuggestionsGenerator implements SuggestionsGeneratorInterface
{
    private SearchEnginePoolInterface $searchEnginePool;

    private StoreManagerInterface $storeManager;

    private LoggerInterface $logger;

    public function __construct(
        SearchEnginePoolInterface $searchEnginePool,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->searchEnginePool = $searchEnginePool;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function generateAll(): void
    {
        $storeIds = $this->getStoreIds();
        array_walk($storeIds, [$this, 'generateByStore']);
    }

    public function generateByStore(int $storeId): void
    {
        $searchEngines = $this->searchEnginePool->getAll();

        foreach ($searchEngines as $searchEngine) {
            $this->byStore($searchEngine, $storeId);
        }
    }

    private function byStore(SearchEngineAdapterInterface $searchEngine, int $storeId): void
    {
        $searchEngine->setStoreId($storeId);
        $indexId = $searchEngine->getSuggestionIndexId();

        if (!$indexId) {
            return;
        }

        try {
            $searchEngine->getSuggestionApi()->generateSuggestions($indexId);
            $this->logger->info(sprintf(
                'Successfully dispatched generate suggestions action for store ID %d using index ID %s.',
                $storeId,
                $indexId
            ));
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }
    }

    /**
     * @return int[]
     */
    private function getStoreIds(): array
    {
        $storeIds = [];

        foreach ($this->storeManager->getStores(false) as $store) {
            if (!$store->getIsActive()) {
                continue;
            }

            $storeIds[] = (int)$store->getId();
        }

        return $storeIds;
    }
}
