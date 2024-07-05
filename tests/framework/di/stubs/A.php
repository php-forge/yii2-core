<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

final class A
{
    public function __construct(public B|null $b = null)
    {
    }
}
