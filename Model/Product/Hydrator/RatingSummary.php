<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Hydrator;

use LupaSearch\LupaSearchPlugin\Model\Hydrator\ProductHydratorInterface;
use Magento\Catalog\Model\Product;
use Magento\Review\Model\Review\Config as ReviewsConfig;

class RatingSummary implements ProductHydratorInterface
{
    private ReviewsConfig $reviewsConfig;

    public function __construct(ReviewsConfig $reviewsConfig)
    {
        $this->reviewsConfig = $reviewsConfig;
    }

    /**
     * @inheritDoc
     */
    public function extract(Product $product): array
    {
        if (!$this->reviewsConfig->isEnabled()) {
            return ['rating_summary' => 0];
        }

        return [
            'rating_summary' => (int)($product->getData('rating_summary') ?? 0),
        ];
    }
}
