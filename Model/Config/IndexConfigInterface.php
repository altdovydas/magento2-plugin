<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Config;

interface IndexConfigInterface
{
    public function isEnabled(?int $storeId = null): bool;

    public function getBatchSize(): int;

    public function shouldIncludeIndexingLogs(): bool;
}
