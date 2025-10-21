<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Conditions;

use LupaSearch\LupaSearchPlugin\Model\ResourceModel\Review as ReviewResource;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\CollectionModifierInterface;
use Magento\Review\Model\Review\Config as ReviewsConfig;

class RatingSummary implements CollectionModifierInterface
{
    public function __construct(
        private readonly ReviewsConfig $reviewsConfig,
        private readonly ReviewResource $reviewResource,
    ) {
    }

    public function apply(AbstractDb $abstractCollection): void
    {
        if (!$abstractCollection instanceof Collection || false === $this->reviewsConfig->isEnabled()) {
            return;
        }

        $this->reviewResource->addRatingSummary($abstractCollection);
    }
}
