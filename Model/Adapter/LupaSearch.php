<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Adapter;

use LupaSearch\LupaSearchPlugin\Model\Adapter\Index\IndexProviderInterface;
use LupaSearch\LupaSearchPluginCore\Api\DocumentsApiInterface;
use LupaSearch\LupaSearchPluginCore\Api\DocumentsApiInterfaceFactory as DocumentsApiFactory;
use LupaSearch\LupaSearchPluginCore\Api\SearchQueriesApiInterface;
use LupaSearch\LupaSearchPluginCore\Api\SearchQueriesApiInterfaceFactory as SearchQueriesApiFactory;
use LupaSearch\LupaSearchPluginCore\Api\SuggestionsApiInterface;
use LupaSearch\LupaSearchPluginCore\Api\SuggestionsApiInterfaceFactory as SuggestionsApiFactory;
use LupaSearch\LupaSearchPluginCore\Factories\QueryConfigurationFactoryInterface;
use LupaSearch\LupaSearchPluginCore\Factories\SearchQueryFactoryInterface;
use LupaSearch\LupaSearchPluginCore\Model\LupaClientFactoryInterface;
use LupaSearch\Exceptions\ApiException;
use LupaSearch\Exceptions\BadResponseException;
use LupaSearch\LupaClientInterface;
use Psr\Log\LoggerInterface;
use LupaSearch\LupaSearchPlugin\Model\Config\IndexConfigInterface;
use Throwable;

use function array_column;
use function array_merge;
use function array_values;
use function var_export;

class LupaSearch implements SearchEngineAdapterInterface
{
    /**
     * LupaSearch API result limit
     */
    public const MAX_RESULTS_LIMIT = 10000;
    private const KEY_BATCH_KEY = 'batchKey';

    /**
     * @var DocumentsApiInterface[]
     */
    protected array $documentsApi = [];

    /**
     * @var LupaClientInterface[]
     */
    protected array $clients = [];

    protected int $storeId = 0;

    /**
     * @var SuggestionsApi[]
     */
    protected array $suggestionApi = [];

    protected IndexProviderInterface $indexProvider;

    private SearchQueryFactoryInterface $searchQueryFactory;

    private QueryConfigurationFactoryInterface $queryConfigurationFactory;

    private LupaClientFactoryInterface $lupaClientFactory;

    private DocumentsApiFactory $documentsApiFactory;

    private SearchQueriesApiFactory $searchQueriesApiFactory;

    private SuggestionsApiFactory $suggestionsApiFactory;

    private LoggerInterface $logger;

    private IndexConfigInterface $indexConfig;

    public function __construct(
        IndexProviderInterface $indexProvider,
        SearchQueryFactoryInterface $searchQueryFactory,
        QueryConfigurationFactoryInterface $queryConfigurationFactory,
        LupaClientFactoryInterface $lupaClientFactory,
        DocumentsApiFactory $documentsApiFactory,
        SearchQueriesApiFactory $searchQueriesApiFactory,
        SuggestionsApiFactory $suggestionsApiFactory,
        LoggerInterface $logger,
        IndexConfigInterface $indexConfig
    ) {
        $this->indexProvider = $indexProvider;
        $this->searchQueryFactory = $searchQueryFactory;
        $this->queryConfigurationFactory = $queryConfigurationFactory;
        $this->lupaClientFactory = $lupaClientFactory;
        $this->documentsApiFactory = $documentsApiFactory;
        $this->searchQueriesApiFactory = $searchQueriesApiFactory;
        $this->suggestionsApiFactory = $suggestionsApiFactory;
        $this->logger = $logger;
        $this->indexConfig = $indexConfig;
    }

    /**
     * @inheritDoc
     */
    public function addDocuments(array $documents): string
    {
        if (!$documents || !$this->getIndexId()) {
            return '';
        }

        try {
            if ($this->indexConfig->shouldIncludeIndexingLogs()) {
                $documentIds = array_column($documents, 'id');
                $this->logger->info(
                    'Adding ' . count($documents) . ' documents to index ' . $this->getIndexId() .
                    ' with IDs: ' . implode(', ', $documentIds)
                );
            }
            $response = $this->getDocumentsApi()->importDocuments(
                $this->getIndexId(),
                ['documents' => array_values($documents)],
            );
        } catch (Throwable $exception) {
            throw new BadResponseException($exception->getMessage(), (int)$exception->getCode(), $exception);
        }

        $this->validateResponse($response);

        return (string)$response[self::KEY_BATCH_KEY];
    }

    /**
     * @inheritDoc
     */
    public function updateDocuments(array $documents): string
    {
        if (!$documents || !$this->getIndexId()) {
            return '';
        }

        try {
            if ($this->indexConfig->shouldIncludeIndexingLogs()) {
                $documentIds = array_column($documents, 'id');
                $this->logger->info(
                    'Updating ' . count($documents) . ' documents in index ' . $this->getIndexId() .
                    ' with IDs: ' . implode(', ', $documentIds)
                );
            }
            $response = $this->getDocumentsApi()->updateDocuments(
                $this->getIndexId(),
                ['documents' => array_values($documents)],
            );
        } catch (Throwable $exception) {
            throw new BadResponseException($exception->getMessage(), (int)$exception->getCode(), $exception);
        }

        $this->validateResponse($response);

        return (string)$response[self::KEY_BATCH_KEY];
    }

    /**
     * @inheritDoc
     */
    public function deleteDocuments(array $primaryKeys): void
    {
        if (!$primaryKeys || !$this->getIndexId()) {
            return;
        }

        try {
            if ($this->indexConfig->shouldIncludeIndexingLogs()) {
                $this->logger->info(
                    'Deleting ' . count($primaryKeys) . ' documents from index ' . $this->getIndexId() .
                    ' with IDs: ' . implode(', ', $primaryKeys)
                );
            }
            $this->getDocumentsApi()->batchDelete(
                $this->getIndexId(),
                ['ids' => array_values($primaryKeys)],
            );
        } catch (Throwable $exception) {
            throw new BadResponseException($exception->getMessage(), (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function getAllDocumentsIds(): array
    {
        if (!$this->getIndexId()) {
            return [];
        }

        $ids = [];
        $searchAfter = null;

        do {
            $result = $this->getIds($searchAfter ? (int)$searchAfter : null);
            $searchAfter = $result['searchAfter'] ?? null;
            $ids = array_merge($ids, $result['ids'] ?? []);
        } while (null !== $searchAfter);

        return $ids;
    }

    public function countDocs(): int
    {
        return $this->getIndexId() ? $this->getDocumentsApi()->getCount($this->getIndexId()) : 0;
    }

    public function getDocumentsApi(): DocumentsApiInterface
    {
        if (!isset($this->documentsApi[$this->storeId])) {
            $this->documentsApi[$this->storeId] = $this->documentsApiFactory->create(['client' => $this->getClient()]);
        }

        return $this->documentsApi[$this->storeId];
    }

    public function getSearchQueriesApi(): SearchQueriesApiInterface
    {
        return $this->searchQueriesApiFactory->create(['client' => $this->getClient()]);
    }

    public function getSuggestionApi(): SuggestionsApiInterface
    {
        if (!isset($this->suggestionApi[$this->storeId])) {
            $this->suggestionApi[$this->storeId] = $this->suggestionsApiFactory->create(
                ['client' => $this->getClient()]
            );
        }

        return $this->suggestionApi[$this->storeId];
    }

    public function setStoreId(int $id): void
    {
        $this->storeId = $id;
    }

    public function getIndexId(): string
    {
        return $this->indexProvider->getId($this->storeId);
    }

    public function getSuggestionIndexId(): string
    {
        return $this->indexProvider->getSuggestionId($this->storeId);
    }

    protected function getClient(): LupaClientInterface
    {
        if (!isset($this->clients[$this->storeId])) {
            $this->clients[$this->storeId] = $this->lupaClientFactory->create($this->storeId);
        }

        return $this->clients[$this->storeId];
    }

    /**
     * @return array{ids: array<int>, searchAfter: int|null}
     * @throws ApiException
     */
    protected function getIds(?int $searchAfter = null): array
    {
        if (!$this->getIndexId()) {
            return [
                'ids' => [],
                'searchAfter' => null,
            ];
        }

        $result = $this->getDocumentsApi()->getAll($this->getIndexId(), ['id'], self::MAX_RESULTS_LIMIT, $searchAfter);

        if (!isset($result['documents'])) {
            throw new BadResponseException(var_export($result, true));
        }

        return [
            'ids' => array_column($result['documents'], 'id'),
            'searchAfter' => $result['nextPageSearchAfter'] ?? null,
        ];
    }

    private function getQueriesApi(): SearchQueriesApiInterface
    {
        return $this->searchQueriesApiFactory->create(['client' => $this->getClient()]);
    }

    /**
     * @param array<string|bool> $response
     * @throws BadResponseException
     */
    private function validateResponse(array $response): void
    {
        if (!(bool)($response['success'] ?? false)) {
            throw new BadResponseException('Unsuccessful');
        }

        $batchKey = (string)($response[self::KEY_BATCH_KEY] ?? '');

        if (!$batchKey) {
            throw new BadResponseException('Empty batchKey');
        }
    }
}
