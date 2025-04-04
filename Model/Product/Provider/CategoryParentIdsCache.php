<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Provider;

use LupaSearch\LupaSearchPlugin\Model\Provider\Categories\ParentIdsProviderInterface;
use LupaSearch\LupaSearchPlugin\Model\Provider\ProviderCacheInterface;

use function array_flip;
use function array_intersect_key;

class CategoryParentIdsCache implements ParentIdsProviderInterface, ProviderCacheInterface
{
    private ParentIdsProviderInterface $parentIdsProvider;

    /**
     * @var int[][]
     */
    private array $parentIds = [];

    public function __construct(ParentIdsProviderInterface $parentIdsProvider)
    {
        $this->parentIdsProvider = $parentIdsProvider;
    }

    /**
     * @inheritDoc
     * @phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterfaceAfterLastUsed
     */
    public function warmup(array $ids, ?int $storeId = null): void
    {
        $this->parentIds = $this->parentIdsProvider->getAll();
    }

    public function flush(): void
    {
        $this->parentIds = [];
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        return $this->parentIds;
    }

    /**
     * @inheritDoc
     */
    public function getById(int $id): array
    {
        return $this->parentIds[$id] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return array_intersect_key($this->parentIds, array_flip($ids));
    }
}
