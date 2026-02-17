<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Modifiers;

use LupaSearch\LupaSearchPlugin\Model\ResourceModel\Review as ReviewResource;
use LupaSearch\LupaSearchPlugin\Model\Provider\DataModifierInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Review\Model\Review\Config as ReviewsConfig;
use Traversable;

use function array_map;

class ReviewCountModifier implements DataModifierInterface
{
    private ReviewsConfig $reviewsConfig;

    private ReviewResource $reviewResource;

    public function __construct(
        ReviewsConfig $reviewsConfig,
        ReviewResource $reviewResource,
    ) {
        $this->reviewsConfig = $reviewsConfig;
        $this->reviewResource = $reviewResource;
    }

    public function modify(Traversable $data): void
    {
        if (!$data instanceof Collection || !$this->reviewsConfig->isEnabled()) {
            return;
        }

        /** @psalm-suppress UndefinedMagicMethod */
        $productIds = array_map(static fn ($item) => (int)$item->getId(), $data->getItems());
        /** @psalm-suppress RedundantCastGivenDocblockType */
        $reviewCounts = $this->reviewResource->getTotalReviewsByIds($productIds, true, (int)$data->getStoreId());

        foreach ($data->getItems() as $item) {
            /** @psalm-suppress UndefinedMagicMethod */
            $item->setData('review_count', (int)($reviewCounts[$item->getId()] ?? 0));
        }
    }
}
