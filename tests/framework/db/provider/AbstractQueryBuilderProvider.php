<?php

declare(strict_types=1);

namespace yiiunit\framework\db\provider;

use yii\db\Expression;
use yii\db\Query;
use yiiunit\support\DbHelper;

abstract class AbstractQueryBuilderProvider
{
    protected static string $driverName = '';

    public static function batchInsert(): array
    {
        return [
            'simple' => [
                'customer',
                ['email', 'name', 'address'],
                [['test@example.com', 'silverfire', 'Kyiv {{city}}, Ukraine']],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] ([[email]], [[name]], [[address]]) VALUES (:qp0, :qp1, :qp2)
                    SQL,
                    static::$driverName,
                ),
                [':qp0' => 'test@example.com', ':qp1' => 'silverfire', ':qp2' => 'Kyiv {{city}}, Ukraine'],
            ],
            'escape-danger-chars' => [
                'customer',
                ['address'],
                [["SQL-danger chars are escaped: '); --"]],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] ([[address]]) VALUES (:qp0)
                    SQL,
                    static::$driverName,
                ),
                [':qp0' => "SQL-danger chars are escaped: '); --"],
            ],
            'customer2' => [
                'customer',
                ['address'],
                [],
                '',
            ],
            'customer3' => [
                'customer',
                [],
                [['no columns passed']],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] VALUES (:qp0)
                    SQL,
                    static::$driverName,
                ),
                [':qp0' => 'no columns passed'],
            ],
            'bool-false, bool2-null' => [
                'type',
                ['bool_col', 'bool_col2'],
                [[false, null]],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[bool_col]], [[bool_col2]]) VALUES (:qp0, :qp1)
                    SQL,
                    static::$driverName,
                ),
                [':qp0' => 0, ':qp1' => null],
            ],
            'wrong' => [
                '{{%type}}',
                ['{{%type}}.[[float_col]]', '[[time]]'],
                [[null, new Expression('now()')], [null, new Expression('now()')]],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[float_col]], [[time]]) VALUES (:qp0, now()), (:qp1, now())
                    SQL,
                    static::$driverName,
                ),
                [':qp0' => null, ':qp1' => null],
            ],
            'bool-false, time-now()' => [
                '{{%type}}',
                ['{{%type}}.[[bool_col]]', '[[time]]'],
                [[false, new Expression('now()')]],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[bool_col]], [[time]]) VALUES (:qp0, now())
                    SQL,
                    static::$driverName,
                ),
                [':qp0' => null],
            ],
            'empty-sql' => [
                '{{%type}}',
                [],
                (
                    static function () {
                        if (false) {
                            yield [];
                        }
                    }
                )(),
                '',
            ],
        ];
    }

    public static function insert(): array
    {
        return [
            'regular-values' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'silverfire',
                    'address' => 'Kyiv {{city}}, Ukraine',
                    'is_active' => false,
                    'related_id' => null,
                ],
                [],
                DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]]) VALUES (:qp0, :qp1, :qp2, :qp3, :qp4)
                    SQL,
                    static::$driverName,
                ),
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'silverfire',
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => false,
                    ':qp4' => null,
                ],
            ],
            'params-and-expressions' => [
                '{{%type}}',
                ['{{%type}}.[[related_id]]' => null, 'time' => new Expression('now()')],
                [],
                DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO {{%type}} ([[related_id]], [[time]]) VALUES (:qp0, now())
                    SQL,
                    static::$driverName,
                ),
                [':qp0' => null],
            ],
            'carry passed params' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'sergeymakinen',
                    'address' => '{{city}}',
                    'is_active' => false,
                    'related_id' => null,
                    'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                ],
                [':phBar' => 'bar'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]], [[col]]) VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar))
                    SQL,
                    static::$driverName,
                ),
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':qp5' => null,
                    ':phFoo' => 'foo',
                ],
            ],
            'carry passed params (query)' => [
                'customer',
                (new query())
                    ->select(['email', 'name', 'address', 'is_active', 'related_id'])
                    ->from('customer')
                    ->where(
                        [
                            'email' => 'test@example.com',
                            'name' => 'sergeymakinen',
                            'address' => '{{city}}',
                            'is_active' => false,
                            'related_id' => null,
                            'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                        ],
                    ),
                [':phBar' => 'bar'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]]) SELECT [[email]], [[name]], [[address]], [[is_active]], [[related_id]] FROM [[customer]] WHERE ([[email]]=:qp1) AND ([[name]]=:qp2) AND ([[address]]=:qp3) AND ([[is_active]]=:qp4) AND ([[related_id]] IS NULL) AND ([[col]]=CONCAT(:phFoo, :phBar))
                    SQL,
                    static::$driverName,
                ),
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':phFoo' => 'foo',
                ],
            ],
            'empty columns' => [
                'customer',
                [],
                [],
                DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] DEFAULT VALUES
                    SQL,
                    static::$driverName,
                ),
                [],
            ],
            'query' => [
                'customer',
                (new query())
                    ->select([new Expression('email as email'), new Expression('name')])
                    ->from('customer')
                    ->where(
                        [
                            'email' => 'test@example.com',
                        ],
                    ),
                [],
                DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] ([[email]], [[name]]) SELECT email as email, name FROM [[customer]] WHERE [[email]]=:qp0
                    SQL,
                    static::$driverName,
                ),
                [
                    ':qp0' => 'test@example.com',
                ],
            ],
        ];
    }

    public static function upsert(): array
    {
        return [
            'regular values' => [
                'T_upsert',
                ['email' => 'test@example.com', 'address' => 'bar {{city}}', 'status' => 1, 'profile_id' => null],
                true,
                '',
                [':qp0' => 'test@example.com', ':qp1' => 'bar {{city}}', ':qp2' => 1, ':qp3' => null],
            ],
            'regular values with update part' => [
                'T_upsert',
                ['email' => 'test@example.com', 'address' => 'bar {{city}}', 'status' => 1, 'profile_id' => null],
                ['address' => 'foo {{city}}', 'status' => 2, 'orders' => new Expression('T_upsert.orders + 1')],
                '',
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'bar {{city}}',
                    ':qp2' => 1,
                    ':qp3' => null,
                    ':qp4' => 'foo {{city}}',
                    ':qp5' => 2,
                ],
            ],
            'regular values without update part' => [
                'T_upsert',
                ['email' => 'test@example.com', 'address' => 'bar {{city}}', 'status' => 1, 'profile_id' => null],
                false,
                '',
                [':qp0' => 'test@example.com', ':qp1' => 'bar {{city}}', ':qp2' => 1, ':qp3' => null],
            ],
            'query' => [
                'T_upsert',
                (new Query())
                    ->select(['email', 'status' => new Expression('2')])
                    ->from('customer')
                    ->where(['name' => 'user1'])
                    ->limit(1),
                true,
                '',
                [':qp0' => 'user1'],
            ],
            'query with update part' => [
                'T_upsert',
                (new Query())
                    ->select(['email', 'status' => new Expression('2')])
                    ->from('customer')
                    ->where(['name' => 'user1'])
                    ->limit(1),
                ['address' => 'foo {{city}}', 'status' => 2, 'orders' => new Expression('T_upsert.orders + 1')],
                '',
                [':qp0' => 'user1', ':qp1' => 'foo {{city}}', ':qp2' => 2],
            ],
            'query without update part' => [
                'T_upsert',
                (new Query())
                    ->select(['email', 'status' => new Expression('2')])
                    ->from('customer')
                    ->where(['name' => 'user1'])
                    ->limit(1),
                false,
                '',
                [':qp0' => 'user1'],
            ],
            'values and expressions' => [
                '{{%T_upsert}}',
                ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('CURRENT_TIMESTAMP')],
                true,
                '',
                [':qp0' => 'dynamic@example.com'],
            ],
            'values and expressions with update part' => [
                '{{%T_upsert}}',
                ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('CURRENT_TIMESTAMP')],
                ['[[orders]]' => new Expression('T_upsert.orders + 1')],
                '',
                [':qp0' => 'dynamic@example.com'],
            ],
            'values and expressions without update part' => [
                '{{%T_upsert}}',
                ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('CURRENT_TIMESTAMP')],
                false,
                '',
                [':qp0' => 'dynamic@example.com'],
            ],
            'query, values and expressions with update part' => [
                '{{%T_upsert}}',
                (new Query())
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('CURRENT_TIMESTAMP'),
                        ],
                    ),
                ['ts' => 0, '[[orders]]' => new Expression('T_upsert.orders + 1')],
                '',
                [':phEmail' => 'dynamic@example.com', ':qp1' => 0],
            ],
            'query, values and expressions without update part' => [
                '{{%T_upsert}}',
                (new Query())
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('CURRENT_TIMESTAMP'),
                        ],
                    ),
                false,
                '',
                [':phEmail' => 'dynamic@example.com'],
            ],
            'no columns to update' => [
                'T_upsert_1',
                ['a' => 1],
                false,
                '',
                [':qp0' => 1],
            ],
            'no columns to update with unique' => [
                '{{%T_upsert}}',
                ['email' => 'email'],
                true,
                '',
                [':qp0' => 'email'],
            ],
            'no unique columns in table - simple insert' => [
                '{{%animal}}',
                ['type' => 'test'],
                false,
                '',
                [':qp0' => 'test'],
            ],
        ];
    }
}
