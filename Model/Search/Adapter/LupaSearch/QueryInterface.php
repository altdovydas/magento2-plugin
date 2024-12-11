<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch;

use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\DocumentQueryResponseInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\SuggestionQueryResponseInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Search\Model\QueryInterface as MagentoQueryInterface;

interface QueryInterface
{
    public function document(RequestInterface $request): DocumentQueryResponseInterface;

    public function suggestion(MagentoQueryInterface $query): SuggestionQueryResponseInterface;
}
