<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Hydrator;

use LupaSearch\LupaSearchPlugin\Model\Hydrator\ProductHydratorInterface;
use Magento\Catalog\Model\Product;
use Magento\Directory\Model\PriceCurrency;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Framework\Math\FloatComparator;

class Price implements ProductHydratorInterface
{
    private PriceCurrency $priceCurrency;

    private CatalogHelper $catalogHelper;

    private FloatComparator $floatComparator;

    public function __construct(
        PriceCurrency $priceCurrency,
        CatalogHelper $catalogHelper,
        FloatComparator $floatComparator
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->catalogHelper = $catalogHelper;
        $this->floatComparator = $floatComparator;
    }

    /**
     * @inheritDoc
     */
    public function extract(Product $product): array
    {
        $type = $product->getTypeInstance();
        $price = $type->isComposite($product) ? (float)$product->getMinimalPrice() : (float)$product->getPrice();
        $finalPrice = (float)$product->getFinalPrice();

        $data = [];
        $data['price'] = $this->round($finalPrice);
        $data['old_price'] = $this->round($price);
        $data['price_with_tax'] = $this->round(
            (float)$this->catalogHelper->getTaxPrice($product, $finalPrice, true)
        );
        $data['old_price_with_tax'] = $this->round(
            (float)$this->catalogHelper->getTaxPrice($product, $price, true)
        );
        $data['discount'] = $this->getDiscount($price, $finalPrice);
        $data['discount_percent'] = $this->getDiscountPercent($price, $finalPrice);

        return $data;
    }

    protected function getDiscountPercent(float $price, float $finalPrice): float
    {
        if (
            $this->floatComparator->greaterThanOrEqual(0, $finalPrice) ||
            $this->floatComparator->greaterThanOrEqual(0, $price) ||
            $this->floatComparator->greaterThanOrEqual($finalPrice, $price)
        ) {
            return 0.00;
        }

        $discount = 100 - $finalPrice * 100 / $price;
        $discount = round($discount);

        if ($discount < 1) {
            return 0.00;
        }

        return $discount;
    }

    protected function getDiscount(float $price, float $finalPrice): float
    {
        if (
            $this->floatComparator->greaterThanOrEqual(0, $finalPrice) ||
            $this->floatComparator->greaterThanOrEqual(0, $price) ||
            $this->floatComparator->greaterThanOrEqual($finalPrice, $price)
        ) {
            return 0.00;
        }

        return $this->round(abs($finalPrice - $price)) * -1;
    }

    protected function round(float $price): float
    {
        return $this->priceCurrency->roundPrice($price);
    }
}
