<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Hydrator;

use LupaSearch\LupaSearchPlugin\Model\Product\Provider\ReviewProvider;
use LupaSearch\LupaSearchPlugin\Model\Hydrator\ProductHydratorInterface;
use Magento\Catalog\Model\Product;

class Review implements ProductHydratorInterface
{
    private ReviewProvider $reviewProvider;

    public function __construct(ReviewProvider $reviewProvider)
    {
        $this->reviewProvider = $reviewProvider;
    }

    /**
     * @return array{review_count: string, rating_summary: string}
     */
    public function extract(Product $product): array
    {
        return $this->reviewProvider->get($product);
    }
}
