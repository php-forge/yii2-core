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
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'bigint',
                    'start' => '1',
                    'increment' => '1',
                    'minValue' => '-9223372036854775808',
                    'maxValue' =>  '9223372036854775807',
                    'cycle' => false,
                ],
            ],
            'with suffix _SEQ' => [
                'T_sequence_SEQ',
                1,
                1,
                [],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'bigint',
                    'start' => '1',
                    'increment' => '1',
                    'minValue' => '-9223372036854775808',
                    'maxValue' =>  '9223372036854775807',
                    'cycle' => false,
                ],
            ],
            'as type smallint' => [
                'T_sequence',
                1,
                1,
                ['type' => 'smallint'],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'smallint',
                    'start' => '1',
                    'increment' => '1',
                    'minValue' => '-32768',
                    'maxValue' => '32767',
                    'cycle' => false,
                ],
            ],
            'as type int' => [
                'T_sequence',
                1,
                1,
                ['type' => 'int'],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'int',
                    'start' => '1',
                    'increment' => '1',
                    'minValue' => '-2147483648',
                    'maxValue' => '2147483647',
                    'cycle' => false,
                ],
            ],
            'as type bigint' => [
                'T_sequence',
                1,
                1,
                ['type' => 'bigint'],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'bigint',
                    'start' => '1',
                    'increment' => '1',
                    'minValue' => '-9223372036854775808',
                    'maxValue' => '9223372036854775807',
                    'cycle' => false,
                ],
            ],
            'as type decimal' => [
                'T_sequence',
                1,
                1,
                ['type' => 'decimal'],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'decimal',
                    'start' => '1',
                    'increment' => '1',
                    'minValue' => '-999999999999999999',
                    'maxValue' => '999999999999999999',
                    'cycle' => false,
                ],
            ],
            'as start' => [
                'T_sequence',
                10,
                1,
                [],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'bigint',
                    'start' => '10',
                    'increment' => '1',
                    'minValue' => '-9223372036854775808',
                    'maxValue' => '9223372036854775807',
                    'cycle' => false,
                ],
            ],
            'as start with value negative' => [
                'T_sequence',
                -10,
                1,
                [],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'bigint',
                    'start' => '-10',
                    'increment' => '1',
                    'minValue' => '-9223372036854775808',
                    'maxValue' => '9223372036854775807',
                    'cycle' => false,
                ],
            ],
            'as start with zero value' => [
                'T_sequence',
                0,
                1,
                [],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'bigint',
                    'start' => '0',
                    'increment' => '1',
                    'minValue' => '-9223372036854775808',
                    'maxValue' => '9223372036854775807',
                    'cycle' => false,
                ],
            ],
            'as increment' => [
                'T_sequence',
                1,
                10,
                [],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'bigint',
                    'start' => '1',
                    'increment' => '10',
                    'minValue' => '-9223372036854775808',
                    'maxValue' => '9223372036854775807',
                    'cycle' => false,
                ],
            ],
            'as increment with value negative' => [
                'T_sequence',
                1,
                -10,
                [],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'bigint',
                    'start' => '1',
                    'increment' => '-10',
                    'minValue' => '-9223372036854775808',
                    'maxValue' => '9223372036854775807',
                    'cycle' => false,
                ],
            ],
            'as minvalue' => [
                'T_sequence',
                12,
                1,
                ['minValue' => 10],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'bigint',
                    'start' => '12',
                    'increment' => '1',
                    'minValue' => '10',
                    'maxValue' => '9223372036854775807',
                    'cycle' => false,
                ],
            ],
            'as maxvalue' => [
                'T_sequence',
                1,
                1,
                ['maxValue' => 10],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'bigint',
                    'start' => '1',
                    'increment' => '1',
                    'minValue' => '-9223372036854775808',
                    'maxValue' => '10',
                    'cycle' => false,
                ],
            ],
            'as maxvalue PHP_INT_MAX' => [
                'T_sequence',
                1,
                1,
                ['maxValue' => PHP_INT_MAX],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'bigint',
                    'start' => '1',
                    'increment' => '1',
                    'minValue' => '-9223372036854775808',
                    'maxValue' => (string)PHP_INT_MAX,
                    'cycle' => false,
                ],
            ],
            'as cycle' => [
                'T_sequence',
                1,
                1,
                ['cycle' => true],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'bigint',
                    'start' => '1',
                    'increment' => '1',
                    'minValue' => '-9223372036854775808',
                    'maxValue' => '9223372036854775807',
                    'cycle' => true,
                ],
            ],
            'as cache' => [
                'T_sequence',
                1,
                1,
                ['cache' => 50],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'bigint',
                    'start' => '1',
                    'increment' => '1',
                    'minValue' => '-9223372036854775808',
                    'maxValue' => '9223372036854775807',
                    'cycle' => false,
                ],
            ],
        ];
    }
}
