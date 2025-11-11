<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Indexer\Product;

use function array_merge;
use function array_unique;

class IdListResolverComposite implements IdListResolverInterface
{
    /**
     * @var IdListResolverInterface[]
     */
    private array $resolvers;

    public function __construct(array $resolvers = [])
    {
        $this->resolvers = $resolvers;
    }

    /**
     * @inheritDoc
     */
    public function resolve(array $ids): array
    {
        $idList = [];
        $idList[] = $ids;

        foreach ($this->resolvers as $resolver) {
            if (!$resolver instanceof IdListResolverInterface) {
                continue;
            }

            $idList[] = $resolver->resolve($ids);
        }

        return array_unique(array_merge(...$idList));
    }
}
