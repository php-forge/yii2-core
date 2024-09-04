<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\provider;

final class CommandProvider
{
    protected static string $driverName = 'oci';

    public static function createSequence(): array
    {
        return [
            'simple' => [
                'T_sequence',
                1,
                1,
                [],
            ],
            'with cache' => [
                'T_sequence',
                1,
                2,
                ['cache' => 50],
            ],
            'with cycle' => [
                'T_sequence',
                1,
                1,
                ['cycle' => true],
            ],
            'with maxvalue' => [
                'T_sequence',
                1,
                1,
                ['maxValue' => 100],
            ],
            'with minvalue' => [
                'T_sequence',
                12,
                1,
                ['minValue' => 10],
            ],
        ];
    }
}
