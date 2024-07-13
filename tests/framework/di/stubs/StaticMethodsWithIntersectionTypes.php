<?php

declare(strict_types=1);

namespace yiiunit\framework\di\stubs;

final class StaticMethodsWithIntersectionTypes
{
    public static function withQuxInterfaceAndQuxAnotherIntersection(QuxInterface & QuxAnother $Qux)
    {
    }

    public static function withQuxAnotherAndQuxInterfaceIntersection(QuxAnother & QuxInterface $Qux)
    {
    }
}
