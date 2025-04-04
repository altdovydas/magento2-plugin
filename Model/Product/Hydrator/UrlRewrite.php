<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\Hydrator;

use LupaSearch\LupaSearchPlugin\Model\Hydrator\ProductHydratorInterface;
use LupaSearch\LupaSearchPlugin\Model\Normalizer\UrlNormalizerInterface;
use Magento\Catalog\Model\Product;

class UrlRewrite implements ProductHydratorInterface
{
    private UrlNormalizerInterface $urlNormalizer;

    public function __construct(UrlNormalizerInterface $urlNormalizer)
    {
        $this->urlNormalizer = $urlNormalizer;
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

        return $this->urlNormalizer->normalize($url);
    }
}
