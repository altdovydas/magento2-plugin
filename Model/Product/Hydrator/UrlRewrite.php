<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Hydrator;

use LupaSearch\LupaSearchPlugin\Model\Hydrator\ProductHydratorInterface;
use LupaSearch\LupaSearchPlugin\Model\Normalizer\UrlNormalizerInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\Framework\UrlInterface;

use function rtrim;
use function str_contains;

class UrlRewrite implements ProductHydratorInterface
{
    private UrlNormalizerInterface $urlNormalizer;

    private UrlInterface $urlBuilder;

    private ProductUrlPathGenerator $productUrlPathGenerator;

    private const ROUTE_PATH = 'catalog/product/view';

    public function __construct(
        UrlNormalizerInterface $urlNormalizer,
        UrlInterface $urlBuilder,
        ProductUrlPathGenerator $productUrlPathGenerator
    ) {
        $this->urlNormalizer = $urlNormalizer;
        $this->urlBuilder = $urlBuilder;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
    }

    /**
     * @inheritDoc
     */
    public function extract(Product $product): array
    {
        return [
            'url' => $this->getUrl($product),
            'url_key' => $product->getUrlKey(),
            'url_path' => $product->getData('url_path'),
        ];
    }

    private function getUrl(Product $product): string
    {
        $urlModel = $product->getUrlModel();
        $url = (string)$urlModel->getUrl($product, ['_nosid' => true, '_ignore_category' => true]);

        if (!$this->isRouteWithIgnoreOrSuffix($url)) {
            return $this->urlNormalizer->normalize($url);
        }

        $urlKey = $product->getUrlKey();

        if (empty($urlKey)) {
            $routeUrl = $this->urlBuilder->getUrl(self::ROUTE_PATH, ['id' => $product->getId()]);

            return $this->urlNormalizer->normalize(rtrim($routeUrl, '/'));
        }

        $url = $this->productUrlPathGenerator->getUrlPathWithSuffix($product, $product->getStoreId());

        return $this->urlNormalizer->normalize($url);
    }

    private function isRouteWithIgnoreOrSuffix(string $url): bool
    {
        return str_contains($url, '/_ignore_category/') || str_contains($url, '/s/');
    }
}
