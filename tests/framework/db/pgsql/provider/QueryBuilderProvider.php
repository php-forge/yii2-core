<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql\provider;

use yii\db\Expression;
use yii\db\Query;

use function array_replace;

final class QueryBuilderProvider extends \yiiunit\framework\db\provider\AbstractQueryBuilderProvider
{
    protected static string $driverName = 'pgsql';

    public static function createSequence(): array
    {
        return [
            'simple' => [
                'T_sequence',
                1,
                1,
                [],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    START WITH 1
                    NO CYCLE
                SQL,
            ],
            'with suffix _SEQ' => [
                'T_sequence_SEQ',
                1,
                1,
                [],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    START WITH 1
                    NO CYCLE
                SQL,
            ],
            'as type bigint' => [
                'T_sequence',
                1,
                1,
                ['type' => 'bigint'],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    AS bigint
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    START WITH 1
                    NO CYCLE
                SQL,
            ],
            'as type int' => [
                'T_sequence',
                1,
                1,
                ['type' => 'int'],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    AS int
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    START WITH 1
                    NO CYCLE
                SQL,
            ],
            'as type smallint' => [
                'T_sequence',
                1,
                1,
                ['type' => 'smallint'],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    AS smallint
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    START WITH 1
                    NO CYCLE
                SQL,
            ],
            'as start' => [
                'T_sequence',
                10,
                1,
                [],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    START WITH 10
                    NO CYCLE
                SQL,
            ],
            'as start with value negative' => [
                'T_sequence',
                -10,
                1,
                [],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    INCREMENT BY 1
                    MINVALUE -10
                    NO MAXVALUE
                    START WITH -10
                    NO CYCLE
                SQL,
            ],
            'as start with zero value' => [
                'T_sequence',
                0,
                1,
                [],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    INCREMENT BY 1
                    MINVALUE 0
                    NO MAXVALUE
                    START WITH 0
                    NO CYCLE
                SQL,
            ],
            'as increment' => [
                'T_sequence',
                1,
                10,
                [],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    INCREMENT BY 10
                    NO MINVALUE
                    NO MAXVALUE
                    START WITH 1
                    NO CYCLE
                SQL,
            ],
            'as increment with value negative' => [
                'T_sequence',
                -1,
                -1,
                [
                    'minValue' => -10,
                    'maxValue' => PHP_INT_MAX,
                ],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    INCREMENT BY -1
                    MINVALUE -1
                    MAXVALUE 9223372036854775807
                    START WITH -1
                    NO CYCLE
                SQL,
            ],
            'as minvalue' => [
                'T_sequence',
                12,
                1,
                ['minValue' => 10],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    INCREMENT BY 1
                    MINVALUE 10
                    NO MAXVALUE
                    START WITH 12
                    NO CYCLE
                SQL,
            ],
            'as maxvalue' => [
                'T_sequence',
                1,
                1,
                ['maxValue' => 10],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    INCREMENT BY 1
                    NO MINVALUE
                    MAXVALUE 10
                    START WITH 1
                    NO CYCLE
                SQL,
            ],
            'as maxvalue PHP_INT_MAX' => [
                'T_sequence',
                1,
                1,
                ['maxValue' => PHP_INT_MAX],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    INCREMENT BY 1
                    NO MINVALUE
                    MAXVALUE 9223372036854775807
                    START WITH 1
                    NO CYCLE
                SQL,
            ],
            'as cycle' => [
                'T_sequence',
                1,
                1,
                ['cycle' => true],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    START WITH 1
                    CYCLE
                SQL,
            ],
            'as cache' => [
                'T_sequence',
                1,
                1,
                ['cache' => 50],
                <<<SQL
                CREATE SEQUENCE "T_sequence_SEQ"
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    START WITH 1
                    NO CYCLE
                    CACHE 50
                SQL,
            ],
        ];
    }


    public static function dropSequence(): array
    {
        return [
            'simple' => [
                'T_sequence',
                <<<SQL
                DROP SEQUENCE "T_sequence_SEQ"
                SQL,
            ],
        ];
    }

    public static function insert(): array
    {
        $insert = parent::insert();

        $insert['empty columns'][3] = <<<SQL
        INSERT INTO "customer" DEFAULT VALUES
        SQL;

        return $insert;
    }

    public static function insertWithReturningPks(): array
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
                <<<SQL
                INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id") VALUES (:qp0, :qp1, :qp2, :qp3, :qp4) RETURNING "id"
                SQL,
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
                ['{{%type}}.[[related_id]]' => null, '[[time]]' => new Expression('now()')],
                [],
                <<<SQL
                INSERT INTO {{%type}} ("related_id", "time") VALUES (:qp0, now())
                SQL,
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
                <<<SQL
                INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id", "col") VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar)) RETURNING "id"
                SQL,
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
                (new Query())
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
                <<<SQL
                INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id") SELECT "email", "name", "address", "is_active", "related_id" FROM "customer" WHERE ("email"=:qp1) AND ("name"=:qp2) AND ("address"=:qp3) AND ("is_active"=:qp4) AND ("related_id" IS NULL) AND ("col"=CONCAT(:phFoo, :phBar)) RETURNING "id"
                SQL,
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':phFoo' => 'foo',
                ],
            ],
            [
                '{{%order_item}}',
                ['order_id' => 1, 'item_id' => 1, 'quantity' => 1, 'subtotal' => 1.0],
                [],
                <<<SQL
                INSERT INTO {{%order_item}} ("order_id", "item_id", "quantity", "subtotal") VALUES (:qp0, :qp1, :qp2, :qp3) RETURNING "order_id", "item_id"
                SQL,
                [':qp0' => 1, ':qp1' => 1, ':qp2' => 1, ':qp3' => 1.0],
            ],
        ];
    }

    public static function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => <<<SQL
                INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT ("email") DO UPDATE SET "address"=EXCLUDED."address", "status"=EXCLUDED."status", "profile_id"=EXCLUDED."profile_id"
                SQL,
            ],
            'regular values with update part' => [
                2 => [
                    'address' => 'foo {{city}}',
                    'status' => 2,
                    'orders' => new Expression('"T_upsert"."orders" + 1'),
                ],
                3 => <<<SQL
                INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT ("email") DO UPDATE SET "address"=:qp4, "status"=:qp5, "orders"="T_upsert"."orders" + 1
                SQL,
            ],
            'regular values without update part' => [
                3 => <<<SQL
                INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT DO NOTHING
                SQL,
            ],
            'query' => [
                3 => <<<SQL
                INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1 ON CONFLICT ("email") DO UPDATE SET "status"=EXCLUDED."status"
                SQL,
            ],
            'query with update part' => [
                2 => [
                    'address' => 'foo {{city}}',
                    'status' => 2,
                    'orders' => new Expression('"T_upsert"."orders" + 1'),
                ],
                3 => <<<SQL
                INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1 ON CONFLICT ("email") DO UPDATE SET "address"=:qp1, "status"=:qp2, "orders"="T_upsert"."orders" + 1
                SQL,
            ],
            'query without update part' => [
                3 => <<<SQL
                INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1 ON CONFLICT DO NOTHING
                SQL,
            ],
            'values and expressions' => [
                1 => [
                    '{{%T_upsert}}.[[email]]' => 'dynamic@example.com',
                    '[[ts]]' => new Expression('extract(epoch from now()) * 1000'),
                ],
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ("email", "ts") VALUES (:qp0, extract(epoch from now()) * 1000) ON CONFLICT ("email") DO UPDATE SET "ts"=EXCLUDED."ts"
                SQL,
            ],
            'values and expressions with update part' => [
                1 => [
                    '{{%T_upsert}}.[[email]]' => 'dynamic@example.com',
                    '[[ts]]' => new Expression('extract(epoch from now()) * 1000'),
                ],
                2 => ['[[orders]]' => new Expression('EXCLUDED.orders + 1')],
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ("email", "ts") VALUES (:qp0, extract(epoch from now()) * 1000) ON CONFLICT ("email") DO UPDATE SET "orders"=EXCLUDED.orders + 1
                SQL,
            ],
            'values and expressions without update part' => [
                1 => [
                    '{{%T_upsert}}.[[email]]' => 'dynamic@example.com',
                    '[[ts]]' => new Expression('extract(epoch from now()) * 1000'),
                ],
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ("email", "ts") VALUES (:qp0, extract(epoch from now()) * 1000) ON CONFLICT DO NOTHING
                SQL,
            ],
            'query, values and expressions with update part' => [
                1 => (new Query())
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('extract(epoch from now()) * 1000'),
                        ],
                    ),
                2 => [
                    'ts' => 0,
                    '[[orders]]' => new Expression('EXCLUDED.orders + 1'),
                ],
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ("email", [[ts]]) SELECT :phEmail AS "email", extract(epoch from now()) * 1000 AS [[ts]] ON CONFLICT ("email") DO UPDATE SET "ts"=:qp1, "orders"=EXCLUDED.orders + 1
                SQL,
            ],
            'query, values and expressions without update part' => [
                1 => (new Query())
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('extract(epoch from now()) * 1000'),
                        ],
                    ),
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ("email", [[ts]]) SELECT :phEmail AS "email", extract(epoch from now()) * 1000 AS [[ts]] ON CONFLICT DO NOTHING
                SQL,
            ],
            'no columns to update' => [
                3 => <<<SQL
                INSERT INTO "T_upsert_1" ("a") VALUES (:qp0) ON CONFLICT DO NOTHING
                SQL,
            ],
            'no columns to update with unique' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ("email") VALUES (:qp0) ON CONFLICT DO NOTHING
                SQL,
            ],
            'no unique columns in table - simple insert' => [
                3 => <<<SQL
                INSERT INTO {{%animal}} ("type") VALUES (:qp0)
                SQL,
            ],
        ];

        $upsert = parent::upsert();

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        return $upsert;
    }
}
