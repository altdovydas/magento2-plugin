<?php

namespace LupaSearch\LupaSearchPlugin\Model\Adapter;

use LupaSearch\LupaSearchPluginCore\Api\DocumentsApiInterface;
use LupaSearch\LupaSearchPluginCore\Api\SearchQueriesApiInterface;
use LupaSearch\LupaSearchPluginCore\Api\SuggestionsApiInterface;
use LupaSearch\Exceptions\ApiException;
use LupaSearch\Exceptions\BadResponseException;

interface SearchEngineAdapterInterface
{
    public function getDocumentsApi(): DocumentsApiInterface;

    public function getSearchQueriesApi(): SearchQueriesApiInterface;

    public function getIndexId(): string;

    public function getSuggestionIndexId(): string;

    /**
     * @param array<string|int|float|array<string>> $documents
     * @throws BadResponseException
     */
    public function addDocuments(array $documents): string;

    /**
     * @param array<string|int|float|array<string>> $documents
     * @throws BadResponseException
     */
    public function updateDocuments(array $documents): string;

    /**
     * @param string[] $primaryKeys
     * @throws BadResponseException
     */
    public function deleteDocuments(array $primaryKeys): void;

    /**
     * @return string[]
     */
    public function getAllDocumentsIds(): array;

    /**
     * @throws ApiException
     */
    public function countDocs(): int;

    public function getSuggestionApi(): SuggestionsApiInterface;

    public function setStoreId(int $id): void;
}
