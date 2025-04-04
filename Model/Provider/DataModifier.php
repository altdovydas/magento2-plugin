<?php

declare(strict_types=1);

namespace LupaSearch\LupaSearchPlugin\Model\Provider;

use Traversable;

class DataModifier implements DataModifierInterface
{
    /**
     * @var DataModifierInterface[]
     */
    private array $modifiers;

    /**
     * @param DataModifierInterface[] $modifiers
     */
    public function __construct(array $modifiers = [])
    {
        $this->modifiers = $modifiers;
    }

    public function modify(Traversable $data): void
    {
        foreach ($this->modifiers as $modifier) {
            if (!$modifier instanceof DataModifierInterface) {
                continue;
            }

            $modifier->modify($data);
        }
    }
}
