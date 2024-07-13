<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

final class Variadic
{
    public function __construct(QuxInterface ...$quxes)
    {
    }
}
