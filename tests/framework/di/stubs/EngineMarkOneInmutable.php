<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

final class EngineMarkOneInmutable
{
    public const NAME = 'Mark One';

    public function __construct(private int $number = 1)
    {
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function withNumber(int $value): self
    {
        $new = clone $this;
        $new->number = $value;

        return $new;
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}
