<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Config;

class QueriesConfigPool
{
    private array $configPool = [];

    public function __construct(array $configPool = [])
    {
        $this->configPool = $configPool;
    }

    /**
     * @return array<string, string>
     */
    public function getAll(): array
    {
        return $this->configPool;
    }

    public function getConfigPath(string $queryType): ?string
    {
        return $this->configPool[$queryType] ?? null;
    }
}
