<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Hydrator;

use LupaSearch\LupaSearchPlugin\Model\Hydrator\ProductHydratorInterface;
use Magento\Catalog\Model\Product;
use Magento\Directory\Model\PriceCurrency;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
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
        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            return $this->extractConfigurableProductData($product);
        }

        return $this->buildProductData($product);
    }

    protected function extractConfigurableProductData(Product $product): array
    {
        $lowestPriceProduct = $this->getLowestPriceProduct($product);

        if (!$lowestPriceProduct) {
            return [];
        }

        return $this->buildProductData($lowestPriceProduct);
    }

    protected function getLowestPriceProduct(Product $product): ?Product
    {
        $typeInstance = $product->getTypeInstance();
        $usedProducts = $typeInstance->getUsedProducts($product);

        $lowestPriceProduct = null;
        foreach ($usedProducts as $usedProduct) {
            if (
                $lowestPriceProduct === null ||
                $usedProduct->getFinalPrice() < $lowestPriceProduct->getFinalPrice()
            ) {
                $lowestPriceProduct = $usedProduct;
            }
        }

        return $lowestPriceProduct;
    }

    protected function buildProductData(Product $product): array
    {
        $type = $product->getTypeInstance();
        $price = $type->isComposite($product) ? (float)$product->getMinimalPrice() : (float)$product->getPrice();
        $finalPrice = (float)$product->getFinalPrice();

        return [
            'price' => $this->round($finalPrice),
            'old_price' => $this->round($price),
            'price_with_tax' => $this->round(
                (float)$this->catalogHelper->getTaxPrice($product, $finalPrice, true)
            ),
            'old_price_with_tax' => $this->round(
                (float)$this->catalogHelper->getTaxPrice($product, $price, true)
            ),
            'discount' => $this->getDiscount($price, $finalPrice),
            'discount_percent' => $this->getDiscountPercent($price, $finalPrice),
        ];
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
