<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Setup;

use Magento\Setup\Model\SearchConfigOptionsList as BaseSearchConfigOptionsList;

use function array_merge;

class SearchConfigOptionsList extends BaseSearchConfigOptionsList
{
    private ConfigOptionsList $configOptionsList;

    public function __construct(ConfigOptionsList $configOptionsList)
    {
        $this->configOptionsList = $configOptionsList;
    }

    /**
     * @return string[]
     */
    public function getAvailableSearchEngineList(): array
    {
        $result = parent::getAvailableSearchEngineList();
        $result[ConfigOptionsList::SEARCH_ENGINE] = 'LupaSearch';

        return $result;
    }

    /**
     * @return string[]
     */
    public function getOptionsList(): array
    {
        return array_merge(parent::getOptionsList(), $this->configOptionsList->getOptions());
    }
}
