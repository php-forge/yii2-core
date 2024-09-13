<?php

declare(strict_types=1);

namespace yiiunit\framework\db\provider;

use yii\db\Expression;
use yiiunit\support\DbHelper;

abstract class AbstractCommandProvider
{
    protected static string $driverName = '';

    public static function batchInsert(): array
    {
        return [
            'multirow' => [
                'type',
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'values' => [
                    ['0', '0.0', 'test string', true],
                    [false, 0, 'test string2', false],
                ],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3), (:qp4, :qp5, :qp6, :qp7)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => 0,
                    ':qp1' => 0.0,
                    ':qp2' => 'test string',
                    ':qp3' => true,
                    ':qp4' => 0,
                    ':qp5' => 0.0,
                    ':qp6' => 'test string2',
                    ':qp7' => false,
                ],
                2,
            ],
            'issue11242' => [
                'type',
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'values' => [
                    [1.0, 1.1, 'Kyiv {{city}}, Ukraine', true],
                ],
                /**
                 * {@see https://github.com/yiisoft/yii2/issues/11242}
                 *
                 * Make sure curly bracelets (`{{..}}`) in values will not be escaped
                 */
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => 1,
                    ':qp1' => 1.1,
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => true,
                ],
            ],
            'wrongBehavior' => [
                '{{%type}}',
                ['{{%type}}.[[int_col]]', '[[float_col]]', 'char_col', 'bool_col'],
                'values' => [
                    ['0', '0.0', 'Kyiv {{city}}, Ukraine', false],
                ],
                /**
                 * Test covers potentially wrong behavior and marks it as expected!.
                 *
                 * In case table name or table column is passed with curly or square bracelets, QueryBuilder can not
                 * determine the table schema and typecast values properly.
                 */
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => 0,
                    ':qp1' => 0.0,
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => false,
                ],
            ],
            'batchInsert binds params from expression' => [
                '{{%type}}',
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                /**
                 * This example is completely useless. This feature of batchInsert is intended to be used with complex
                 * expression objects, such as JsonExpression.
                 */
                'values' => [
                    [new Expression(':exp1', [':exp1' => 42]), 1, 'test', false],
                ],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:exp1, :qp1, :qp2, :qp3)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':exp1' => 42,
                    ':qp1' => 1.0,
                    ':qp2' => 'test',
                    ':qp3' => false,
                ],
            ],
        ];
    }

    public static function executeResetSequence(): array
    {
        return [
            'no value' => [
                '{{%reset_sequence}}',
                [
                    ['name' => 'name1'],
                    ['name' => 'name2'],
                    ['name' => 'name3'],
                ],
                [1, 2, 3],
            ],
            'null value' => [
                '{{%reset_sequence}}',
                [
                    ['name' => 'name1'],
                    ['name' => 'name2'],
                    ['name' => 'name3'],
                ],
                [1, 2, 3],
                null,
            ],
            'value' => [
                '{{%reset_sequence}}',
                [
                    ['name' => 'name1'],
                    ['name' => 'name2'],
                    ['name' => 'name3'],
                ],
                [5, 6, 7],
                5,
            ],
            'value with zero' => [
                '{{%reset_sequence}}',
                [
                    ['name' => 'name1'],
                    ['name' => 'name2'],
                    ['name' => 'name3'],
                ],
                [1, 2, 3],
                1,
            ],
        ];
    }
}
