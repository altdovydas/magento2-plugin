<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model;

use LupaSearch\LupaSearchPlugin\Model\Adapter\QueriesManagementInterface;
use LupaSearch\LupaSearchPlugin\Model\Adapter\QueriesManagementPool;
use LupaSearch\LupaSearchPlugin\Model\Config\QueriesConfigInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\SearchQueryInterface;
use LupaSearch\Exceptions\ApiException;
use Magento\Framework\Exception\NotFoundException;

class QueryManager implements QueryManagerInterface
{
    /**
     * @var QueryBuildersPoolInterface
     */
    protected $queryBuildersPool;

    /**
     * @var QueriesManagementPool
     */
    private $queriesManagementPool;

    /**
     * @var QueriesConfigInterface
     */
    private $queriesConfig;

    public function __construct(
        QueryBuildersPoolInterface $queryBuildersPool,
        QueriesManagementPool $queriesManagementPool,
        QueriesConfigInterface $queriesConfig
    ) {
        $this->queryBuildersPool = $queryBuildersPool;
        $this->queriesManagementPool = $queriesManagementPool;
        $this->queriesConfig = $queriesConfig;
    }

    /**
     * @throws ApiException
     * @throws NotFoundException
     */
    public function generate(string $type, int $storeId): string
    {
        $query = $this->getQuery($type, $storeId);

        if (!$query) {
            $query = $this->create($type, $storeId);
        } else {
            $this->update($query, $type, $storeId);
        }

        $this->saveQueryConfig($query, $type, $storeId);

        return $query->getQueryKey();
    }

    /**
     * @throws ApiException
     * @throws NotFoundException
     */
    protected function getQuery(string $type, int $storeId): ?SearchQueryInterface
    {
        $management = $this->queriesManagementPool->get($type);

        if (!$management) {
            return null;
        }

        $all = $management->getAllQueries($type, $storeId);
        $queryKey = $this->getQueryKey($type, $storeId);
        $query = null;

        if ($queryKey) {
            $query = $this->matchQueryByKey($all, $queryKey);
        }

        if (!$query) {
            return $this->matchQueryByDescription($all, $this->getDescription($type, $storeId));
        }

        return $query;
    }

    /**
     * @throws ApiException
     * @throws NotFoundException
     */
    protected function create(string $type, int $storeId): SearchQueryInterface
    {
        $management = $this->queriesManagementPool->get($type);

        if (!$management) {
            throw new NotFoundException(__('Queries management not found for %1', $type));
        }

        $queryBuilder = $this->queryBuildersPool->getByType($type);
        $searchQuery = $queryBuilder->build();
        $searchQuery->setDescription($this->getDescription($type, $storeId));
        $queryBuilder->build($searchQuery, $storeId);
        $management->createQuery($searchQuery, $storeId);

        return $searchQuery;
    }

    /**
     * @throws ApiException
     * @throws NotFoundException
     */
    protected function update(SearchQueryInterface $searchQuery, string $type, int $storeId): bool
    {
        $management = $this->queriesManagementPool->get($type);

        if (!$management || $searchQuery->getDescription() !== $this->getDescription($type, $storeId)) {
            return false;
        }

        $queryBuilder = $this->queryBuildersPool->getByType($type);
        $searchQuery = $queryBuilder->build($searchQuery, $storeId);

        return $management->updateQuery($searchQuery, $storeId);
    }

    private function getDescription(string $type, int $storeId): string
    {
        return $type . '_' . $storeId;
    }

    /**
     * @throws NotFoundException
     */
    private function saveQueryConfig(SearchQueryInterface $searchQuery, string $type, int $storeId): void
    {
        $setter = $this->getSetterByType($type);

        $this->queriesConfig->$setter($searchQuery->getQueryKey(), $storeId);
    }

    /**
     * @throws NotFoundException
     */
    private function getQueryKey(string $type, int $storeId): ?string
    {
        $getter = $this->getGetterByType($type);

        return $this->queriesConfig->$getter($storeId);
    }

    /**
     * @throws NotFoundException
     */
    private function getSetterByType(string $type): string
    {
        return 'set' . $this->getByType($type);
    }

    /**
     * @throws NotFoundException
     */
    private function getGetterByType(string $type): string
    {
        return 'get' . $this->getByType($type);
    }

    /**
     * @throws NotFoundException
     */
    private function getByType(string $type): string
    {
        switch ($type) {
            case QueriesManagementInterface::TYPE_PRODUCT:
                return 'Product';

            case QueriesManagementInterface::TYPE_PRODUCT_CATALOG:
                return 'ProductCatalog';

            case QueriesManagementInterface::TYPE_PRODUCT_SUGGEST:
                return 'ProductSuggestion';

            case QueriesManagementInterface::TYPE_PRODUCT_SEARCH_BOX:
                return 'ProductSearchBox';

            case QueriesManagementInterface::TYPE_CATEGORY:
                return 'Category';

            default:
                return $type;
        }
    }

    /**
     * @param SearchQueryInterface[] $all
     */
    private function matchQueryByKey(array $all, string $queryKey): ?SearchQueryInterface
    {
        foreach ($all as $searchQuery) {
            if ($searchQuery->getQueryKey() === $queryKey) {
                return $searchQuery;
            }
        }

        return null;
    }

    /**
     * @param SearchQueryInterface[] $all
     */
    private function matchQueryByDescription(array $all, string $description): ?SearchQueryInterface
    {
        foreach ($all as $searchQuery) {
            if ($searchQuery->getDescription() === $description) {
                return $searchQuery;
            }
        }

        return null;
    }
}
