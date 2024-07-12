<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

class Creator
{
    public static function create(): TestClass
    {
        return new TestClass();
    }
}
