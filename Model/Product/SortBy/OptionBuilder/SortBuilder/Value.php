<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Product\SortBy\OptionBuilder\SortBuilder;

use LupaSearch\LupaSearchPlugin\Model\Product\SortBy\OptionBuilder\SortBuilderInterface;
use LupaSearch\LupaSearchPlugin\Model\Product\SortBy\OptionBuilder\ValueBuilder;
use LupaSearch\LupaSearchPlugin\Model\Product\SortBy\OptionParametersInterface;

class Value implements SortBuilderInterface
{
    private const FIELD_POSITION = 'position';

    private ValueBuilder $valueBuilder;

    public function __construct(ValueBuilder $valueBuilder)
    {
        $this->valueBuilder = $valueBuilder;
    }

    /**
     * @inheritDoc
     */
    public function build(OptionParametersInterface $parameters): array
    {
        if (self::FIELD_POSITION === $parameters->getCode()) {
            return [];
        }

        return $this->valueBuilder->build($parameters);
    }
}
