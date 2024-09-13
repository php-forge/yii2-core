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
}