<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

final class StaticMethodsWithUnionTypes
{
    public static function withBetaUnion(string | Beta $beta)
    {
    }

    public static function withBetaUnionInverse(Beta | string $beta)
    {
    }

    public static function withBetaAndQuxUnion(Beta | QuxInterface $betaOrQux)
    {
    }

    public static function withQuxAndBetaUnion(QuxInterface | Beta $betaOrQux)
    {
    }
}
