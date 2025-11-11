<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Observer;

use LupaSearch\LupaSearchPlugin\Model\Provider\ProviderCacheInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ReindexAfter implements ObserverInterface
{
    private ProviderCacheInterface $providerCache;

    public function __construct(ProviderCacheInterface $providerCache)
    {
        $this->providerCache = $providerCache;
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function execute(Observer $observer): void
    {
        $this->providerCache->flush();
    }
}
