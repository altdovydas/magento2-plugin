<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Config;

use Exception;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;

use function substr;

class QueriesConfig implements QueriesConfigInterface
{
    private const XML_PATH_BOOST_FUNCTION_COEFFICIENT = 'lupasearch/queries/boost_function_coefficient';

    private ScopeConfigInterface $scopeConfig;

    private WriterInterface $configWriter;

    private ReinitableConfigInterface $reinitableConfig;

    private QueriesConfigPool $queriesConfigPool;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        ReinitableConfigInterface $reinitableConfig,
        QueriesConfigPool $queriesConfigPool
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->reinitableConfig = $reinitableConfig;
        $this->queriesConfigPool = $queriesConfigPool;
    }

    public function getProduct(?int $scopeCode = 0): ?string
    {
        return $this->getStoreConfig($this->queriesConfigPool->getConfigPath('Product'), $scopeCode);
    }

    public function getProductCatalog(?int $scopeCode = 0): ?string
    {
        return $this->getStoreConfig($this->queriesConfigPool->getConfigPath('ProductCatalog'), $scopeCode);
    }

    public function getProductSearchBox(?int $scopeCode = 0): ?string
    {
        return $this->getStoreConfig($this->queriesConfigPool->getConfigPath('ProductSearchBox'), $scopeCode);
    }

    public function getProductSuggestion(?int $scopeCode = 0): ?string
    {
        return $this->getStoreConfig($this->queriesConfigPool->getConfigPath('ProductSuggestion'), $scopeCode);
    }

    public function getCategory(?int $scopeCode = 0): ?string
    {
        return $this->getStoreConfig($this->queriesConfigPool->getConfigPath('Category'), $scopeCode);
    }

    public function setProduct(string $query, ?int $scopeId = 0): void
    {
        $this->saveStoreConfig($query, $this->queriesConfigPool->getConfigPath('Product'), $scopeId);
    }

    public function setProductCatalog(string $query, ?int $scopeId = 0): void
    {
        $this->saveStoreConfig($query, $this->queriesConfigPool->getConfigPath('ProductCatalog'), $scopeId);
    }

    public function setProductSearchBox(string $query, ?int $scopeId = 0): void
    {
        $this->saveStoreConfig($query, $this->queriesConfigPool->getConfigPath('ProductSearchBox'), $scopeId);
    }

    public function setProductSuggestion(string $query, ?int $scopeId = 0): void
    {
        $this->saveStoreConfig($query, $this->queriesConfigPool->getConfigPath('ProductSuggestion'), $scopeId);
    }

    public function setCategory(string $query, ?int $scopeId = 0): void
    {
        $this->saveStoreConfig($query, $this->queriesConfigPool->getConfigPath('Category'), $scopeId);
    }

    public function getBoostFunctionCoefficient(?int $scopeCode = 0): float
    {
        return (float)$this->scopeConfig->getValue(
            self::XML_PATH_BOOST_FUNCTION_COEFFICIENT,
            ScopeInterface::SCOPE_STORES,
            $scopeCode,
        );
    }

    /**
     * @param array{0?: string|null, 1?: int|null} $arguments
     * @return string|void
     * @throws Exception
     */
    public function __call(string $name, array $arguments)
    {
        $queryType = substr($name, 3);
        $path = $this->queriesConfigPool->getConfigPath($queryType);

        if (!$path) {
            throw new Exception('Unknown method ' . $name);
        }

        $type = substr($name, 0, 3);

        if ('get' === $type) {
            return $this->getStoreConfig($path, $arguments[0] ?? 0);
        }

        if ('set' === $type) {
            $value = (string)($arguments[0] ?? 0);
            $scopeId = (int)($arguments[1] ?? 0);
            $this->saveStoreConfig($value, $path, $scopeId);

            return;
        }

        throw new Exception('Unknown method ' . $name);
    }

    private function getStoreConfig(string $path, int $scopeCode): ?string
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORES, $scopeCode) ?? null;
    }

    private function saveStoreConfig(string $value, string $path, int $scopeId): void
    {
        if ('' === $value || $this->getStoreConfig($path, $scopeId) === $value) {
            return;
        }

        $this->configWriter->save($path, $value, ScopeInterface::SCOPE_STORES, $scopeId);
        $this->reinitableConfig->reinit();
    }
}
