<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Indexer\Product;

interface IdListResolverInterface
{
    /**
     * @param array<int|string> $ids
     * @return array<int>
     */
    public function resolve(array $ids): array;
}
