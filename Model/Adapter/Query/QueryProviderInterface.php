<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Adapter\Query;

use Magento\Framework\Search\RequestInterface;
use Magento\Search\Model\QueryInterface;

interface QueryProviderInterface
{
    public function getSearch(RequestInterface $request);

    public function getSuggestion(QueryInterface $query);
}
