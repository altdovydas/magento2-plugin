<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Indexer\Action;

use LupaSearch\LupaSearchPlugin\Model\Adapter\SearchEngineAdapterInterface;
use LupaSearch\LupaSearchPlugin\Model\BatchInterface;
use LupaSearch\LupaSearchPlugin\Model\BatchInterfaceFactory;
use LupaSearch\LupaSearchPlugin\Model\Config\IndexConfigInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\DataProviderInterface;
use Exception;
use InvalidArgumentException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_chunk;
use function array_diff;

use const PHP_EOL;

abstract class AbstractAction
{
    /**
     * Delete queries batch size
     */
    private const DELETE_BATCH_SIZE = 10000;

    private SearchEngineAdapterInterface $searchEngineAdapter;

    private StoreManagerInterface $storeManager;

    private IndexConfigInterface $indexConfig;

    private PublisherInterface $publisher;

    private DataProviderInterface $dataProvider;

    private BatchInterfaceFactory $batchFactory;

    private LoggerInterface $logger;

    private string $topic = '';

    public function __construct(
        SearchEngineAdapterInterface $searchEngineAdapter,
        StoreManagerInterface $storeManager,
        IndexConfigInterface $indexConfig,
        PublisherInterface $publisher,
        DataProviderInterface $dataProvider,
        BatchInterfaceFactory $batchFactory,
        LoggerInterface $logger,
        string $topic
    ) {
        $this->searchEngineAdapter = $searchEngineAdapter;
        $this->storeManager = $storeManager;
        $this->indexConfig = $indexConfig;
        $this->publisher = $publisher;
        $this->dataProvider = $dataProvider;
        $this->batchFactory = $batchFactory;
        $this->logger = $logger;
        $this->topic = $topic;
    }

    protected function getTopic(): string
    {
        return $this->topic;
    }

    /**
     * @param int[] $ids
     * @return array<array<int>>
     */
    protected function createChunks(array $ids): array
    {
        return array_chunk($ids, $this->indexConfig->getBatchSize());
    }

    /**
     * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $data
     */
    protected function createBatch(array $data = []): BatchInterface
    {
        return $this->batchFactory->create($data);
    }

    protected function publish(string $topic, BatchInterface $batch): void
    {
        try {
            $this->publisher->publish($topic, $batch);
        } catch (InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage());
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage() . PHP_EOL . $exception->getTraceAsString());
        }
    }

    /**
     * @return string[]
     */
    protected function getAllIds(int $storeId): array
    {
        return $this->dataProvider->getAllIds($storeId);
    }

    /**
     * @return int[]
     */
    protected function getActiveStoreIds(): array
    {
        $storeIds = [];

        foreach ($this->storeManager->getStores(false) as $store) {
            $storeId = (int)$store->getId();

            if (!$store->getIsActive() || !$this->indexConfig->isEnabled($storeId)) {
                continue;
            }

            $storeIds[] = $storeId;
        }

        return $storeIds;
    }

    /**
     * @param int[]|string[] $allIds
     */
    protected function cleanIndexes(int $storeId, array $allIds): void
    {
        $this->searchEngineAdapter->setStoreId($storeId);

        try {
            $existingIds = $this->searchEngineAdapter->getAllDocumentsIds();
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage() . PHP_EOL . $exception->getTraceAsString());

            return;
        }

        $oldIds = array_diff($existingIds, $allIds);

        if (empty($oldIds)) {
            return;
        }

        $this->deleteIndexes($storeId, $oldIds);
    }

    /**
     * @param int[]|string[] $ids
     */
    private function deleteIndexes(int $storeId, array $ids): void
    {
        foreach (array_chunk($ids, self::DELETE_BATCH_SIZE) as $documentIds) {
            try {
                $this->searchEngineAdapter->setStoreId($storeId);
                $this->searchEngineAdapter->deleteDocuments($documentIds);
            } catch (Throwable $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }
}
