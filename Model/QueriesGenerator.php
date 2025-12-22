<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model;

use LupaSearch\LupaSearchPlugin\Model\Config\IndexConfigInterface;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use const PHP_EOL;

class QueriesGenerator implements QueriesGeneratorInterface
{
    /**
     * @var QueryManagerInterface
     */
    private $queryManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var IndexConfigInterface
     */
    private $indexConfig;

    /**
     * @var TypeListInterface
     */
    private $typeList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string[]
     */
    private $types;

    /**
     * @param string[] $types
     */
    public function __construct(
        QueryManagerInterface $queryManager,
        StoreManagerInterface $storeManager,
        IndexConfigInterface $indexConfig,
        TypeListInterface $typeList,
        LoggerInterface $logger,
        array $types
    ) {
        $this->queryManager = $queryManager;
        $this->storeManager = $storeManager;
        $this->indexConfig = $indexConfig;
        $this->typeList = $typeList;
        $this->logger = $logger;
        $this->types = $types;
    }

    public function generateAll(): void
    {
        foreach ($this->getActiveStoreIds() as $storeId) {
            $this->generateByStore($storeId);
        }

        $this->typeList->cleanType(Config::TYPE_IDENTIFIER);
    }

    public function generateByStore(int $storeId): void
    {
        foreach ($this->types as $type) {
            try {
                $this->queryManager->generate($type, $storeId);
                $this->logger->info(
                    sprintf('Successfully generated queries of type "%s" for store ID %d.', $type, $storeId)
                );
            } catch (Throwable $exception) {
                $this->logger->error($exception->getMessage() . PHP_EOL . $exception->getTraceAsString());
            }
        }
    }

    /**
     * @return int[]
     */
    protected function getActiveStoreIds(): array
    {
        $storeIds = [];

        foreach ($this->storeManager->getStores(false) as $store) {
            $storeId = (int)$store->getId();

            if (!$store->getIsActive() || !$this->indexConfig->isEnabled($storeId)) {
                $this->logger->info(
                    sprintf('Skipping queries generation for store ID %d as it is inactive or indexing is disabled.', $storeId)
                );
                continue;
            }

            $storeIds[] = $storeId;
        }

        return $storeIds;
    }
}
