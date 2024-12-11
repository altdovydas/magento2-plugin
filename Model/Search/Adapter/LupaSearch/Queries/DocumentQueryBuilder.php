<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Queries;

use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\DocumentQueryInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\DocumentQueryInterfaceFactory as DocumentQueryFactory;
use LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Queries\SearchQuery\Configuration\SortBuilderInterface;
use Magento\Framework\Search\RequestInterface;

class DocumentQueryBuilder implements DocumentQueryBuilderInterface
{
    private const LIMIT_MAX = 1000;

    private DocumentQueryFactory $documentQueryFactory;

    private SortBuilderInterface $sortBuilder;

    public function __construct(DocumentQueryFactory $documentQueryFactory,  SortBuilderInterface $sortBuilder)
    {
        $this->documentQueryFactory = $documentQueryFactory;
        $this->sortBuilder = $sortBuilder;
    }

    public function build(?RequestInterface $request = null): DocumentQueryInterface
    {
        $documentQuery = $this->documentQueryFactory->create();
        $documentQuery->setSort($this->sortBuilder->build($request));
        $documentQuery->setLimit(
            $request && $request->getSize() <= self::LIMIT_MAX ? (int)$request->getSize() : self::LIMIT_MAX
        );

        return $documentQuery;
    }
}
