<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product;

use LupaSearch\LupaSearchPlugin\Model\Hydrator\ProductHydratorInterface;
use Magento\Catalog\Model\Product;

use function array_map;
use function array_merge_recursive;
use function array_unique;
use function is_array;

class Hydrator implements ProductHydratorInterface
{
    private HydratorPool $productHydratorPool;

    public function __construct(HydratorPool $productHydratorPool)
    {
        $this->productHydratorPool = $productHydratorPool;
    }

    /**
     * @inheritDoc
     */
    public function extract(Product $product): array
    {
        $dataList = [];

        foreach ($this->productHydratorPool->getAll() as $hydrator) {
            if (!$hydrator instanceof ProductHydratorInterface) {
                continue;
            }

            $dataList[] = $hydrator->extract($product);
        }

        return array_map(
            [$this, 'formatValue'],
            $dataList ? array_merge_recursive(...$dataList) : [],
        );
    }

    /**
     * @param mixed $value
     * @return mixed
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     */
    private function formatValue($value)
    {
        $isArray = is_array($value);
        $value = $isArray ? $this->unique($value) : $value;

        return $isArray && isset($value[0]) ? array_values($value) : $value;
    }

    private function unique(array $value): array
    {
        $isFlattened = false;

        foreach ($value as $item) {
            if (is_array($item)) {
                $isFlattened = true;

                break;
            }
        }

        return $isFlattened ? $value : array_unique($value);
    }
}
