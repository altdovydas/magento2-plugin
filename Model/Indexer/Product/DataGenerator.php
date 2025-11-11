<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Indexer\Product;

use LupaSearch\LupaSearchPlugin\Model\Hydrator\ProductHydratorInterface;
use LupaSearch\LupaSearchPlugin\Model\Indexer\DataGeneratorInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\ProductsProviderInterface;

class DataGenerator implements DataGeneratorInterface
{
    protected ProductsProviderInterface $productsProvider;

    protected ProductHydratorInterface $productHydrator;

    public function __construct(ProductsProviderInterface $productsProvider, ProductHydratorInterface $productHydrator)
    {
        $this->productsProvider = $productsProvider;
        $this->productHydrator = $productHydrator;
    }

    /**
     * @inheritDoc
     */
    public function generate(array $ids, int $storeId): array
    {
        if (empty($ids)) {
            return [];
        }

        $products = $this->productsProvider->getByIds($ids, $storeId);
        $data = [];

        foreach ($products as $product) {
            $data[(int)$product->getId()] = $this->productHydrator->extract($product);
        }

        return $data;
    }
}
