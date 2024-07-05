<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

interface EngineInterface
{
    public function getName(): string;

    public function setNumber(int $value): void;

    public function getNumber(): int;
}
