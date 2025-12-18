<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Hydrator;

use LupaSearch\LupaSearchPlugin\Model\Hydrator\ProductHydratorInterface;
use Magento\Catalog\Model\Product;
use Magento\Directory\Model\PriceCurrency;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class Price implements ProductHydratorInterface
{
    /**
     * @var PriceCurrency
     */
    private $priceCurrency;

    /**
     * @var CatalogHelper
     */
    private $catalogHelper;

    public function __construct(PriceCurrency $priceCurrency, CatalogHelper $catalogHelper)
    {
        $this->priceCurrency = $priceCurrency;
        $this->catalogHelper = $catalogHelper;
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
        return [
            'price' => $this->round((float)$product->getFinalPrice()),
            'old_price' => $this->round((float)$product->getPrice()),
            'price_with_tax' => $this->round(
                (float)$this->catalogHelper->getTaxPrice($product, $product->getFinalPrice(), true)
            ),
            'old_price_with_tax' => $this->round(
                (float)$this->catalogHelper->getTaxPrice($product, $product->getPrice(), true)
            ),
            'discount' => $this->getDiscount($product),
            'discount_percent' => $this->getDiscountPercent($product),
        ];
    }

    protected function getDiscountPercent(Product $product): float
    {
        if (
            $product->getFinalPrice() <= 0 ||
            $product->getPrice() <= 0 ||
            $product->getFinalPrice() >= $product->getPrice()
        ) {
            return 0.00;
        }

        $discount = 100 - $product->getFinalPrice() * 100 / $product->getPrice();
        $discount = round($discount);

        if ($discount < 10) {
            return 0.00;
        }

        return $discount;
    }

    protected function getDiscount(Product $product): float
    {
        if (
            $product->getFinalPrice() <= 0 ||
            $product->getPrice() <= 0 ||
            $product->getFinalPrice() >= $product->getPrice()
        ) {
            return 0.00;
        }

        return $this->round(abs($product->getFinalPrice() - $product->getPrice())) * -1;
    }

    protected function round(float $price): float
    {
        return $this->priceCurrency->roundPrice($price);
    }
}
