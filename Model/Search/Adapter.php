<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Search;

use LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Aggregation\BuilderInterface as AggregationBuilderInterface;
use LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\Document\BuilderInterface as DocumentBuilderInterface;
use LupaSearch\LupaSearchPlugin\Model\Search\Adapter\LupaSearch\QueryInterface;
use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Response\QueryResponse;
use Magento\Framework\Search\Response\QueryResponseFactory;

class Adapter implements AdapterInterface
{
    private QueryInterface $query;

    private QueryResponseFactory $responseFactory;

    private AggregationBuilderInterface $aggregationBuilder;

    private DocumentBuilderInterface $documentBuilder;

    public function __construct(
        QueryInterface $query,
        QueryResponseFactory $responseFactory,
        AggregationBuilderInterface $aggregationBuilder,
        DocumentBuilderInterface $documentBuilder
    ) {
        $this->query = $query;
        $this->responseFactory = $responseFactory;
        $this->aggregationBuilder = $aggregationBuilder;
        $this->documentBuilder = $documentBuilder;
    }

    public function query(RequestInterface $request): QueryResponse
    {
        $response = $this->query->document($request);

        return $this->responseFactory->create(
            [
                'documents' => $this->documentBuilder->build($response),
                'aggregations' => $this->aggregationBuilder->build($response, $request),
                'total' => $response->getTotal(),
            ]
        );
    }
}
