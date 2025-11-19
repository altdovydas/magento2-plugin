<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Adapter;

use LupaSearch\LupaSearchPluginCore\Api\DocumentsApiInterface;
use LupaSearch\LupaSearchPluginCore\Api\SearchQueriesApiInterface;
use LupaSearch\LupaSearchPluginCore\Api\SuggestionsApiInterface;
use LupaSearch\LupaSearchPluginCore\Model\Cache\Type\LupaSearchCache as LupaSearchCacheType;

class LupaSearchCache implements SearchEngineAdapterInterface
{
    public const COUNT_CACHE_TAG = 'SN_PRODUCT_COUNT';

    private SearchEngineAdapterInterface $searchEngineAdapter;

    private LupaSearchCacheType $cache;

    private int $storeId = 0;

    /**
     * @var array<string, int>
     */
    private array $registry = [];

    public function __construct(SearchEngineAdapterInterface $searchEngineAdapter, LupaSearchCacheType $cache)
    {
        $this->searchEngineAdapter = $searchEngineAdapter;
        $this->cache = $cache;
    }

    public function getDocumentsApi(): DocumentsApiInterface
    {
        return $this->searchEngineAdapter->getDocumentsApi();
    }

    /**
     * @inheritDoc
     */
    public function addDocuments(array $documents): string
    {
        return $this->searchEngineAdapter->addDocuments($documents);
    }

    /**
     * @inheritDoc
     */
    public function updateDocuments(array $documents): string
    {
        return $this->searchEngineAdapter->updateDocuments($documents);
    }

    /**
     * @inheritDoc
     */
    public function deleteDocuments(array $primaryKeys): void
    {
        $this->searchEngineAdapter->deleteDocuments($primaryKeys);
    }

    /**
     * @inheritDoc
     */
    public function getAllDocumentsIds(): array
    {
        return $this->searchEngineAdapter->getAllDocumentsIds();
    }

    public function countDocs(): int
    {
        $identifier = $this->getIdentifier('count');

        if (isset($this->registry[$identifier])) {
            return $this->registry[$identifier];
        }

        $result = $this->cache->load($identifier);

        if (false === $result) {
            $result = $this->searchEngineAdapter->countDocs();
            $this->cache->save((string)$result, $identifier, [self::COUNT_CACHE_TAG]);
        }

        $this->registry[$identifier] = (int)$result;

        return $this->registry[$identifier];
    }

    public function getSuggestionApi(): SuggestionsApiInterface
    {
        return $this->searchEngineAdapter->getSuggestionApi();
    }

    public function setStoreId(int $id): void
    {
        $this->storeId = $id;
        $this->searchEngineAdapter->setStoreId($id);
    }

    public function getSearchQueriesApi(): SearchQueriesApiInterface
    {
        return $this->searchEngineAdapter->getSearchQueriesApi();
    }

    public function getIndexId(): string
    {
        return $this->searchEngineAdapter->getIndexId();
    }

    public function getSuggestionIndexId(): string
    {
        return $this->searchEngineAdapter->getSuggestionIndexId();
    }

    private function getIdentifier(string $id): string
    {
        return implode('_', [$this->cache->getTag(), $this->storeId, $id]);
    }
}
