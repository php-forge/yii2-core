<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

final class CarTunning
{
    public function __construct(public string|null $color = null)
    {
    }
}
