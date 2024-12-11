<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Adapter\Query;

use LupaSearch\LupaSearchPlugin\Model\Config\QueriesConfigInterface;
use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\Search\RequestInterface;
use Magento\Search\Model\Query;
use Magento\Search\Model\QueryInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreDimensionProvider;

class QueryProvider implements QueryProviderInterface
{
    private QueriesConfigInterface $queriesConfig;

    public function __construct(QueriesConfigInterface $queriesConfig)
    {
        $this->queriesConfig = $queriesConfig;
    }

    public function getSearch(RequestInterface $request)
    {
        return $this->queriesConfig->getProduct($this->getStoreId($request));
    }

    public function getSuggestion(QueryInterface $query)
    {
        $storeId = $query instanceof Query ? (int)$query->getStoreId() : Store::DISTRO_STORE_ID;

        return $this->queriesConfig->getProductSuggestion($storeId);
    }

    private function getStoreId(RequestInterface $request): int
    {
        $scope = $request->getDimensions()[StoreDimensionProvider::DIMENSION_NAME] ?? null;

        return $scope instanceof Dimension ? (int)$scope->getValue() : 0;
    }
}
