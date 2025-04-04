<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Hydrator;

use LupaSearch\LupaSearchPlugin\Model\Hydrator\ProductHydratorInterface;
use Magento\Catalog\Model\Product;

class Stock implements ProductHydratorInterface
{
    public const IN_STOCK = 'IN_STOCK';
    public const OUT_OF_STOCK = 'OUT_OF_STOCK';

    /**
     * @inheritDoc
     */
    public function extract(Product $product): array
    {
        $isSalable = (int)$product->isSalable();

        return [
            'stock_status' => $isSalable ? self::IN_STOCK : self::OUT_OF_STOCK,
            'in_stock' => $isSalable,
            'is_out_of_stock' => $isSalable ? 0 : 1,
        ];
    }
}
