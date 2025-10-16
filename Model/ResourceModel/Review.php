<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class Review
{
    private const PRODUCT_ENTITY_TYPE = 1;
    private const AGGREGATE_STORE_ID = 0;

    private AdapterInterface $connection;

    public function __construct(
        ResourceConnection $resourceConnection,
        string $resourceName = ResourceConnection::DEFAULT_CONNECTION
    ) {
        $this->connection = $resourceConnection->getConnection($resourceName);
    }

    /**
     * @param int[] $productIds
     * @return array<array{review_count: string, rating_summary: string}>
     */
    public function getAttributeValuesByProductIds(array $productIds): array
    {
        $connection = $this->getConnection();

        $reviewValuesSelect = $connection->select()
            ->from(
                $connection->getTableName('review_entity_summary'),
                ['entity_pk_value', 'reviews_count AS review_count', 'rating_summary'],
            )
            ->where('entity_pk_value IN (?)', $productIds)
            ->where('store_id = ?', self::AGGREGATE_STORE_ID)
            ->where('entity_type = ?', self::PRODUCT_ENTITY_TYPE);

        $reviewValues = $connection->fetchAssoc($reviewValuesSelect);

        array_walk($reviewValues, static function (array &$reviewValue): void {
            unset($reviewValue['entity_pk_value']);
        });

        return $reviewValues;
    }

    protected function getConnection(): AdapterInterface
    {
        return $this->connection;
    }
}
