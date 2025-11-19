<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Indexer;

use LupaSearch\LupaSearchPlugin\Model\Adapter\SearchEngineAdapterInterface;
use LupaSearch\Exceptions\ApiException;
use LupaSearch\Exceptions\BadResponseException;
use LupaSearch\Handlers\ErrorHandlerInterface;
use Magento\Framework\Event\Manager as EventManager;
use Psr\Log\LoggerInterface;
use Throwable;

class PartialUpdateIndexer implements PartialIndexerInterface
{
    protected SearchEngineAdapterInterface $searchEngineAdapter;

    protected EventManager $eventManager;

    private DataGeneratorInterface $dataGenerator;

    private ErrorHandlerInterface $errorHandler;

    private LoggerInterface $logger;

    public function __construct(
        SearchEngineAdapterInterface $searchEngineAdapter,
        DataGeneratorInterface $dataGenerator,
        EventManager $eventManager,
        ErrorHandlerInterface $errorHandler,
        LoggerInterface $logger
    ) {
        $this->searchEngineAdapter = $searchEngineAdapter;
        $this->dataGenerator = $dataGenerator;
        $this->eventManager = $eventManager;
        $this->errorHandler = $errorHandler;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     * @phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
     */
    public function reindex(array $ids, int $storeId): void
    {
        $batchKey = null;
        
        try {
            $this->eventManager->dispatch('lupasearch_partial_reindex_before', ['ids' => $ids, 'store_id' => $storeId]);

            if (empty($ids)) {
                return;
            }

            $this->searchEngineAdapter->setStoreId($storeId);
            $data = $this->dataGenerator->generate($ids, $storeId);

            if (empty($data)) {
                return;
            }

            $batchKey = $this->updateData($data);
        } catch (BadResponseException $exception) {
            $this->logger->alert($exception->getMessage());

            throw $exception;
        } catch (Throwable $exception) {
            $this->logger->critical($exception->getMessage());
        } finally {
            $this->eventManager->dispatch(
                'lupasearch_partial_reindex_after',
                ['ids' => $ids, 'store_id' => $storeId, 'batch_key' => $batchKey]
            );
        }
    }

    /**
     * @param array<string|int|float|array<string>> $data
     * @throws ApiException
     */
    protected function updateData(array $data): string
    {
        try {
            return $this->searchEngineAdapter->updateDocuments($data);
        } catch (Throwable $exception) {
            $this->errorHandler->handle($exception);
        }

        return '';
    }
}
