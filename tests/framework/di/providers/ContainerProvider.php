<?php

declare(strict_types=1);

namespace yiiunit\framework\di\providers;

use yiiunit\framework\di\stubs\{Bar, ColorInterface, EngineCar, EngineInterface, EngineMarkOne, Kappa};

final class ContainerProvider
{
    public static function dataHas(): array
    {
        return [
            [false, 'non_existing'],
            [false, ColorInterface::class],
            [true, EngineCar::class],
            [true, EngineMarkOne::class],
            [true, EngineInterface::class],
        ];
    }

    public static function dataNotInstantiableException()
    {
        return [
            [Bar::class],
            [Kappa::class],
        ];
    }
}
