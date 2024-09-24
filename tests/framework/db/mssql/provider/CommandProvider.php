<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\provider;

final class CommandProvider extends \yiiunit\framework\db\provider\AbstractCommandProvider
{
    protected static string $driverName = 'sqlsrv';

    public static function batchInsert(): array
    {
        $batchInsert = parent::batchInsert();

        $batchInsert['multirow']['expectedParams'][':qp3'] = 1;
        $batchInsert['multirow']['expectedParams'][':qp7'] = 0;
        $batchInsert['issue11242']['expectedParams'][':qp3'] = 1;
        $batchInsert['wrongBehavior']['expectedParams'][':qp3'] = 0;
        $batchInsert['batchInsert binds params from expression']['expectedParams'][':qp3'] = 0;

        return $batchInsert;
    }

    public static function createSequence(): array
    {
        return [
            'simple' => [
                'T_sequence',
                1,
                1,
                [],
            ],
            'with suffix _SEQ' => [
                'T_sequence_SEQ',
                1,
                1,
                [],
            ],
            'as type smallint' => [
                'test_sequence',
                1,
                1,
                ['type' => 'smallint'],
            ],
            'as type int' => [
                'test_sequence',
                1,
                1,
                ['type' => 'int'],
            ],
            'as type bigint' => [
                'test_sequence',
                1,
                1,
                ['type' => 'bigint'],
            ],
            'as type decimal' => [
                'test_sequence',
                1,
                1,
                ['type' => 'decimal'],
            ],
            'as start' => [
                'test_sequence',
                10,
                1,
                [],
            ],
            'as start with value negative' => [
                'test_sequence',
                -10,
                1,
                [],
            ],
            'as start with zero value' => [
                'test_sequence',
                0,
                1,
                [],
            ],
            'as increment' => [
                'test_sequence',
                1,
                10,
                [],
            ],
            'as increment with value negative' => [
                'test_sequence',
                1,
                -10,
                [],
            ],
            'as minvalue' => [
                'test_sequence',
                12,
                1,
                ['minValue' => 10],
            ],
            'as maxvalue' => [
                'test_sequence',
                1,
                1,
                ['maxValue' => 10],
            ],
            'as maxvalue PHP_INT_MAX' => [
                'test_sequence',
                1,
                1,
                ['maxValue' => PHP_INT_MAX],
            ],
            'as cycle' => [
                'test_sequence',
                1,
                1,
                ['cycle' => true],
            ],
            'as cache' => [
                'test_sequence',
                1,
                1,
                ['cache' => 50],
            ],

        ];
    }
}
