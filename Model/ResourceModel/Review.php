<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\ResourceModel;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Review\Model\ResourceModel\Review as BaseReview;
use Magento\Review\Model\Review as ReviewModel;

/**
 * @codeCoverageIgnore
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Review extends BaseReview
{
    /**
     * @param int[] $productIds
     * @return array<string, string>
     */
    public function getTotalReviewsByIds(array $productIds, ?bool $approvedOnly = false, ?int $storeId = 0): array
    {
        $connection = $this->getConnection();

        if (false === $connection) {
            return [];
        }

        $select = $connection->select();
        $select
            ->from(
                $this->_reviewTable,
                [
                    'entity_pk_value',
                    'review_count' => new Expression('COUNT(*)'),
                ],
            )
            ->where("{$this->_reviewTable}.entity_pk_value IN(?)", $productIds);
        $bind = [];

        if ($storeId > 0) {
            $select->join(
                ['store' => $this->_reviewStoreTable],
                "{$this->_reviewTable}.review_id = store.review_id AND store.store_id = :store_id",
                [],
            );
            $bind[':store_id'] = $storeId;
        }

        if ($approvedOnly) {
            $select->where("{$this->_reviewTable}.status_id = :status_id");
            $bind[':status_id'] = ReviewModel::STATUS_APPROVED;
        }

        $select->group("{$this->_reviewTable}.entity_pk_value");

        return $connection->fetchPairs($select, $bind);
    }

    public function addRatingSummary(Collection $collection): void
    {
        $cond = sprintf(
            implode(
                ' AND ',
                [
                    'review_entity_summary.entity_pk_value = e.entity_id',
                    'review_entity_summary.entity_type = %d',
                    'review_entity_summary.store_id = %d',
                ],
            ),
            1,
            $collection->getStoreId(),
        );

        $collection->getSelect()->joinLeft(
            'review_entity_summary',
            $cond,
            ['rating_summary'],
        );
    }
}
