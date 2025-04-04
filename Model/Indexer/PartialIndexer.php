<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Indexer;

use LupaSearch\LupaSearchPlugin\Model\Adapter\SearchEngineAdapterInterface;
use LupaSearch\LupaSearchPlugin\Model\Filter\DataFilterInterface;
use LupaSearch\LupaSearchPlugin\Model\Indexer\Product\IdListResolverInterface;
use LupaSearch\LupaSearchPlugin\Model\ResourceModel\DeleteProductHashes;
use Exception;
use LupaSearch\Exceptions\ApiException;
use LupaSearch\Exceptions\BadResponseException;
use LupaSearch\Handlers\ErrorHandlerInterface;
use Magento\Framework\Event\Manager as EventManager;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_diff;
use function array_keys;

class PartialIndexer implements PartialIndexerInterface
{
    protected SearchEngineAdapterInterface $searchEngineAdapter;

    protected EventManager $eventManager;

    private DataGeneratorInterface $dataGenerator;

    private ErrorHandlerInterface $errorHandler;

    private LoggerInterface $logger;

    private DeleteProductHashes $deleteProductHashes;

    private IdListResolverInterface $idListResolver;

    private ?DataFilterInterface $dataFilter;

    public function __construct(
        SearchEngineAdapterInterface $searchEngineAdapter,
        DataGeneratorInterface $dataGenerator,
        EventManager $eventManager,
        ErrorHandlerInterface $errorHandler,
        LoggerInterface $logger,
        DeleteProductHashes $deleteProductHashes,
        ?DataFilterInterface $dataFilter = null
    ) {
        $this->searchEngineAdapter = $searchEngineAdapter;
        $this->dataGenerator = $dataGenerator;
        $this->eventManager = $eventManager;
        $this->errorHandler = $errorHandler;
        $this->logger = $logger;
        $this->deleteProductHashes = $deleteProductHashes;
        $this->idListResolver = $idListResolver;
        $this->dataFilter = $dataFilter;
    }

    /**
     * @inheritDoc
     * @phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
     */
    public function reindex(array $ids, int $storeId): void
    {
        try {
            $this->eventManager->dispatch('lupasearch_reindex_before', ['ids' => $ids, 'store_id' => $storeId]);

            if (empty($ids)) {
                return;
            }

            $ids = $this->idListResolver->resolve($ids);
            $this->searchEngineAdapter->setStoreId($storeId);

            $data = $this->dataGenerator->generate($ids, $storeId);
            $disabledIds = array_diff($ids, array_keys($data));

            $this->deleteMissingFromIndex($disabledIds, $storeId);

            if ($this->dataFilter) {
                $data = $this->dataFilter->filter($data, $storeId);
            }

            if (empty($data)) {
                return;
            }

            $this->sendData($data);
        } catch (BadResponseException $exception) {
            $this->logger->alert($exception->getMessage());

            throw $exception;
        } catch (Throwable $exception) {
            $this->logger->critical($exception->getMessage());
        } finally {
            $this->eventManager->dispatch('lupasearch_reindex_after', ['ids' => $ids, 'store_id' => $storeId]);
        }
    }

    /**
     * @param string[]|int[] $disabledIds
     * @throws ApiException
     * @throws Exception
     */
    protected function deleteMissingFromIndex(array $disabledIds, int $storeId): void
    {
        if (empty($disabledIds)) {
            return;
        }

        if ($this->dataFilter) {
            $this->deleteProductHashes->execute($disabledIds, $storeId);
        }

        $this->deleteData($disabledIds);
    }

    /**
     * @param array<string|int|float|array<string>> $data
     * @throws ApiException
     */
    protected function sendData(array $data): void
    {
        try {
            $this->searchEngineAdapter->addDocuments($data);
        } catch (Throwable $exception) {
            $this->errorHandler->handle($exception);
        }
    }

    /**
     * @param array<string|int|float|array<string>> $data
     * @throws ApiException
     */
    protected function updateData(array $data): void
    {
        try {
            $this->searchEngineAdapter->updateDocuments($data);
        } catch (Throwable $exception) {
            $this->errorHandler->handle($exception);
        }
    }

    /**
     * @param string[] $ids
     * @throws ApiException
     */
    private function deleteData(array $ids): void
    {
        try {
            $this->searchEngineAdapter->deleteDocuments($ids);
        } catch (Throwable $exception) {
            $this->logger->error($exception);
            $this->errorHandler->handle($exception);
        }
    }
}
