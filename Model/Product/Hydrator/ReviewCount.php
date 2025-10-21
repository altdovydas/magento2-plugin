<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Hydrator;

use LupaSearch\LupaSearchPlugin\Model\Hydrator\ProductHydratorInterface;
use Magento\Catalog\Model\Product;

class ReviewCount implements ProductHydratorInterface
{
    /**
     * @inheritDoc
     */
    public function extract(Product $product): array
    {
        return [
            'review_count' => (int)($product->getData('review_count') ?? 0),
        ];
    }
}
