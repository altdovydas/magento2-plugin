<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Provider\Categories;

use LupaSearch\LupaSearchPlugin\Model\Category\CollectionBuilder;
use Magento\Framework\DB\Select;

use function array_flip;
use function array_intersect_key;
use function array_map;
use function array_values;
use function explode;
use function is_string;

class ParentIdsProvider implements ParentIdsProviderInterface
{
    private CollectionBuilder $collectionBuilder;

    public function __construct(CollectionBuilder $collectionBuilder)
    {
        $this->collectionBuilder = $collectionBuilder;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        return $this->prepareData($this->getSelect());
    }

    /**
     * @inheritDoc
     */
    public function getById(int $id): array
    {
        return $this->getByIds([$id])[$id] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getByIds(array $ids): array
    {
        $select = $this->getSelect();
        $select->where('entity_id = IN(?)', $ids);

        return array_intersect_key($this->prepareData($select), array_flip($ids));
    }

    /**
     * @return array<array<int>>
     */
    private function prepareData(Select $select): array
    {
        $parentIds = [];

        foreach ($select->getConnection()->fetchPairs($select) as $categoryId => $path) {
            if (!is_string($path)) {
                continue;
            }
            $categoryId = (int)$categoryId;
            $path = explode('/', $path);
            $path = array_slice($path, 2);
            $path = array_values($path);
            $path = array_map('intval', $path);

            $parentIds[$categoryId] = $path;
        }

        return $parentIds;
    }

    private function getSelect(): Select
    {
        $collection = $this->collectionBuilder->build();
        $collection->removeAllFieldsFromSelect();
        $select = $collection->getSelect();
        $select->reset(Select::COLUMNS);
        $select->columns(['entity_id', 'path']);

        return $select;
    }
}
