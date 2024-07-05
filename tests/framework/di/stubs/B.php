<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

final class B
{
    public function __construct(public A|null $a = null)
    {
    }
}
