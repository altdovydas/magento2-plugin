<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Queries;

use LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Queries\SearchQuery\Configuration\FiltersBuilderInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\DocumentQueryInterface;
use LupaSearch\LupaSearchPluginCore\Api\Data\SearchQueries\DocumentQueryInterfaceFactory as DocumentQueryFactory;
use LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Queries\SearchQuery\Configuration\SortBuilderInterface;
use Magento\Framework\Search\RequestInterface;

class DocumentQueryBuilder implements DocumentQueryBuilderInterface
{
    private const LIMIT_MAX = 1000;

    private DocumentQueryFactory $documentQueryFactory;

    private SortBuilderInterface $sortBuilder;

    private FiltersBuilderInterface $filtersBuilder;

    public function __construct(
        DocumentQueryFactory $documentQueryFactory,
        SortBuilderInterface $sortBuilder,
        FiltersBuilderInterface $filtersBuilder
    ) {
        $this->documentQueryFactory = $documentQueryFactory;
        $this->sortBuilder = $sortBuilder;
        $this->filtersBuilder = $filtersBuilder;
    }

    public function build(?RequestInterface $request = null): DocumentQueryInterface
    {
        $filters = $this->filtersBuilder->build($request->getQuery()->getMust());

        $documentQuery = $this->documentQueryFactory->create();
        $documentQuery->setSort($this->sortBuilder->build($request));
        $documentQuery->setFilters($filters);
        $documentQuery->setLimit(
            $request && $request->getSize() <= self::LIMIT_MAX ? (int)$request->getSize() : self::LIMIT_MAX
        );
        $documentQuery->setOffset($request->getFrom());

        return $documentQuery;
    }
}
