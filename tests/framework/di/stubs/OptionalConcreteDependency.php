<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

final class OptionalConcreteDependency
{
    public function __construct(private EngineCar|null $car = null)
    {
    }

    public function getCar(): EngineCar|null
    {
        return $this->car;
    }
}
