<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Provider;

use LupaSearch\LupaSearchPlugin\Model\ResourceModel\Review as ReviewResource;
use LupaSearch\LupaSearchPlugin\Model\Provider\ProviderCacheInterface;
use Magento\Catalog\Model\Product;

class ReviewProvider implements ProviderCacheInterface
{
    private ReviewResource $reviewResource;

    /**
     * @var array<int[]>
     */
    private array $reviewData = [];

    public function __construct(ReviewResource $reviewResource)
    {
        $this->reviewResource = $reviewResource;
    }

    /**
     * @return int[]
     */
    public function get(Product $product): array
    {
        return $this->reviewData[$product->getId()] ?? [];
    }

    /**
     * @param int[] $ids
     * @phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function warmup(array $ids, ?int $storeId = null): void
    {
        $this->reviewData = $this->reviewResource->getAttributeValuesByProductIds($ids);
    }

    public function flush(): void
    {
        $this->reviewData = [];
    }
}
