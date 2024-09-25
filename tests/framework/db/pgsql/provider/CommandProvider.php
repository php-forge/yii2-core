<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\provider;

use yii\db\ArrayExpression;
use yii\db\JsonExpression;

final class CommandProvider extends \yiiunit\framework\db\provider\AbstractCommandProvider
{
    protected static string $driverName = 'pgsql';

    public static function batchInsert(): array
    {
        $batchInsert = parent::batchInsert();

        $batchInsert['batchInsert binds params from jsonExpression'] = [
            '{{%type}}',
            ['json_col', 'int_col', 'float_col', 'char_col', 'bool_col'],
            [
                [
                    new JsonExpression(
                        [
                            'username' => 'silverfire',
                            'is_active' => true,
                            'langs' => ['Ukrainian', 'Russian', 'English'],
                        ]
                    ),
                    1,
                    1,
                    '',
                    false,
                ],
            ],
            'expected' => <<<SQL
            INSERT INTO "type" ("json_col", "int_col", "float_col", "char_col", "bool_col") VALUES (:qp0, :qp1, :qp2, :qp3, :qp4)
            SQL,
            'expectedParams' => [
                ':qp0' => '{"username":"silverfire","is_active":true,"langs":["Ukrainian","Russian","English"]}',
                ':qp1' => 1,
                ':qp2' => 1.0,
                ':qp3' => '',
                ':qp4' => false,
            ],
        ];

        $batchInsert['batchInsert binds params from arrayExpression'] = [
            '{{%type}}',
            ['intarray_col', 'int_col', 'float_col', 'char_col', 'bool_col'],
            [[new ArrayExpression([1,null,3], 'int'), 1, 1, '', false]],
            'expected' => <<<SQL
            INSERT INTO "type" ("intarray_col", "int_col", "float_col", "char_col", "bool_col") VALUES (ARRAY[:qp0, :qp1, :qp2]::int[], :qp3, :qp4, :qp5, :qp6)
            SQL,
            'expectedParams' => [
                ':qp0' => 1,
                ':qp1' => null,
                ':qp2' => 3,
                ':qp3' => 1,
                ':qp4' => 1.0,
                ':qp5' => '',
                ':qp6' => false,
            ],
        ];

        $batchInsert['batchInsert casts string to int according to the table schema'] = [
            '{{%type}}',
            ['int_col', 'float_col', 'char_col', 'bool_col'],
            [['3', '1.1', '', false]],
            'expected' => <<<SQL
            INSERT INTO "type" ("int_col", "float_col", "char_col", "bool_col") VALUES (:qp0, :qp1, :qp2, :qp3)
            SQL,
            'expectedParams' => [':qp0' => 3, ':qp1' => 1.1, ':qp2' => '', ':qp3' => false],
        ];

        $batchInsert['batchInsert binds params from jsonbExpression'] = [
            '{{%type}}',
            ['jsonb_col', 'int_col', 'float_col', 'char_col', 'bool_col'],
            [[new JsonExpression(['a' => true]), 1, 1.1, '', false]],
            'expected' => <<<SQL
            INSERT INTO "type" ("jsonb_col", "int_col", "float_col", "char_col", "bool_col") VALUES (:qp0, :qp1, :qp2, :qp3, :qp4)
            SQL,
            'expectedParams' => [':qp0' => '{"a":true}', ':qp1' => 1, ':qp2' => 1.1, ':qp3' => '', ':qp4' => false],
        ];


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
                    'minValue' => '1',
                    'maxValue' => '9223372036854775807',
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
                    'minValue' => '1',
                    'maxValue' => '9223372036854775807',
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
                    'minValue' => '1',
                    'maxValue' => '9223372036854775807',
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
                    'type' => 'integer',
                    'start' => '1',
                    'increment' => '1',
                    'minValue' => '1',
                    'maxValue' => '2147483647',
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
                    'minValue' => '1',
                    'maxValue' => '32767',
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
                    'minValue' => '1',
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
                    'minValue' => '-10',
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
                    'minValue' => '0',
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
                    'minValue' => '1',
                    'maxValue' => '9223372036854775807',
                    'cycle' => false,
                ],
            ],
            'as increment with value negative' => [
                'T_sequence',
                -1,
                -1,
                [
                    'minValue' => -10,
                    'maxValue' => PHP_INT_MAX,
                ],
                [
                    'name' => 'T_sequence_SEQ',
                    'type' => 'bigint',
                    'start' => '-1',
                    'increment' => '-1',
                    'minValue' => '-1',
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
                    'minValue' => '1',
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
                    'minValue' => '1',
                    'maxValue' => (string) PHP_INT_MAX,
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
                    'minValue' => '1',
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
                    'minValue' => '1',
                    'maxValue' => '9223372036854775807',
                    'cycle' => false,
                ],
            ],
        ];
    }
}
