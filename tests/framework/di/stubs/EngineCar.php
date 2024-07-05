<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

final class EngineCar
{
    public ColorInterface $color;

    public function __construct(private EngineInterface $engine, private array $moreEngines = [])
    {
    }

    public function setColor(ColorInterface $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): ColorInterface
    {
        return $this->color;
    }

    public function getEngine(): EngineInterface
    {
        return $this->engine;
    }

    public function getEngineName(): string
    {
        return $this->engine->getName();
    }

    public function getMoreEngines(): array
    {
        return $this->moreEngines;
    }
}
