<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\provider;

use yii\db\Expression;
use yii\db\Query;
use yiiunit\support\DbHelper;

use function array_replace;

final class QueryBuilderProvider extends \yiiunit\framework\db\provider\AbstractQueryBuilderProvider
{
    protected static string $driverName = 'oci';

    public static function batchInsert(): array
    {
        $batchInsert = parent::batchInsert();

        DbHelper::changeSqlForOracleBatchInsert($batchInsert['simple']['expected']);
        DbHelper::changeSqlForOracleBatchInsert($batchInsert['escape-danger-chars']['expected']);
        DbHelper::changeSqlForOracleBatchInsert($batchInsert['customer3']['expected']);
        DbHelper::changeSqlForOracleBatchInsert($batchInsert['bool-false, bool2-null']['expected']);

        $batchInsert['wrong']['expected'] = <<<SQL
        INSERT ALL INTO "type" ("float_col", "time") VALUES (:qp0, now()) INTO "type" ("float_col", "time") VALUES (:qp1, now()) SELECT 1 FROM SYS.DUAL
        SQL;

        DbHelper::changeSqlForOracleBatchInsert($batchInsert['bool-false, time-now()']['expected']);

        return $batchInsert;
    }

    public static function insert(): array
    {
        $insert = parent::insert();

        $insert['empty columns'][3] = <<<SQL
        INSERT INTO "customer" ("id") VALUES (DEFAULT)
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
                INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id") VALUES (:qp0, :qp1, :qp2, :qp3, :qp4) RETURNING "id" INTO :qp5
                SQL,
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'silverfire',
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => false,
                    ':qp4' => null,
                ],
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
                INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id", "col") VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar)) RETURNING "id" INTO :qp7
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
                INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id") SELECT "email", "name", "address", "is_active", "related_id" FROM "customer" WHERE ("email"=:qp1) AND ("name"=:qp2) AND ("address"=:qp3) AND ("is_active"=:qp4) AND ("related_id" IS NULL) AND ("col"=CONCAT(:phFoo, :phBar)) RETURNING "id" INTO :qp6
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
                INSERT INTO {{%order_item}} ("order_id", "item_id", "quantity", "subtotal") VALUES (:qp0, :qp1, :qp2, :qp3) RETURNING "order_id", "item_id" INTO :qp4, :qp5
                SQL,
                [':qp0' => 1, ':qp1' => 1, ':qp2' => 1, ':qp3' => 1],
            ],
            'without primary key' => [
                '{{%type}}',
                ['[[time]]' => new Expression('now()')],
                [],
                <<<SQL
                INSERT INTO {{%type}} ("time") VALUES (now())
                SQL,
                [],
            ],
        ];
    }

    public static function resetSequence(): array
    {
        return [
            'simple' => [
                'T_seq',
                'id',
                1,
                <<<SQL
                ALTER TABLE "T_seq"
                MODIFY "id" GENERATED BY DEFAULT AS IDENTITY
                    (START WITH 1 MINVALUE 1)
                SQL,
            ],
            'with value 0' => [
                'T_seq',
                'id',
                0,
                <<<SQL
                ALTER TABLE "T_seq"
                MODIFY "id" GENERATED BY DEFAULT AS IDENTITY
                    (START WITH 0 MINVALUE 0)
                SQL,
            ],
            'with value negative' => [
                'T_seq',
                'id',
                -5,
                <<<SQL
                ALTER TABLE "T_seq"
                MODIFY "id" GENERATED BY DEFAULT AS IDENTITY
                    (START WITH -5 MINVALUE -5)
                SQL,
            ],
        ];
    }

    public static function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"="EXCLUDED"."address", "status"="EXCLUDED"."status", "profile_id"="EXCLUDED"."profile_id" WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")
                SQL,
            ],
            'regular values with update part' => [
                2 => ['address' => 'foo {{city}}', 'status' => 2, 'orders' => new Expression('"T_upsert"."orders" + 1')],
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"=:qp4, "status"=:qp5, "orders"="T_upsert"."orders" + 1 WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")
                SQL,
            ],
            'regular values without update part' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")
                SQL,
            ],
            'query' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
                SELECT * FROM PAGINATION WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "status"="EXCLUDED"."status" WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
            ],
            'query with update part' => [
                2 => ['address' => 'foo {{city}}', 'status' => 2, 'orders' => new Expression('"T_upsert"."orders" + 1')],
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
                SELECT * FROM PAGINATION WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"=:qp1, "status"=:qp2, "orders"="T_upsert"."orders" + 1 WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
            ],
            'query without update part' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
                SELECT * FROM PAGINATION WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
            ],
            'values and expressions' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('ROUND((SYSDATE - DATE \'1970-01-01\')*24*60*60)')],
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :qp0 AS "email", ROUND((SYSDATE - DATE '1970-01-01')*24*60*60) AS "ts" FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "ts"="EXCLUDED"."ts" WHEN NOT MATCHED THEN INSERT ("email", "ts") VALUES ("EXCLUDED"."email", "EXCLUDED"."ts")
                SQL,
            ],
            'values and expressions with update part' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('ROUND((SYSDATE - DATE \'1970-01-01\')*24*60*60)')],
                2 => ['[[orders]]' => new Expression('"T_upsert"."orders" + 1')],
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :qp0 AS "email", ROUND((SYSDATE - DATE '1970-01-01')*24*60*60) AS "ts" FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "orders"="T_upsert"."orders" + 1 WHEN NOT MATCHED THEN INSERT ("email", "ts") VALUES ("EXCLUDED"."email", "EXCLUDED"."ts")
                SQL,
            ],
            'values and expressions without update part' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('ROUND((SYSDATE - DATE \'1970-01-01\')*24*60*60)')],
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :qp0 AS "email", ROUND((SYSDATE - DATE '1970-01-01')*24*60*60) AS "ts" FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", "ts") VALUES ("EXCLUDED"."email", "EXCLUDED"."ts")
                SQL,
            ],
            'query, values and expressions with update part' => [
                1 => (new Query())
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('ROUND((SYSDATE - DATE \'1970-01-01\')*24*60*60)'),
                        ],
                    )->from('DUAL'),
                2 => ['ts' => 0, '[[orders]]' => new Expression('"T_upsert"."orders" + 1')],
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :phEmail AS "email", ROUND((SYSDATE - DATE '1970-01-01')*24*60*60) AS [[ts]] FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "ts"=:qp1, "orders"="T_upsert"."orders" + 1 WHEN NOT MATCHED THEN INSERT ("email", [[ts]]) VALUES ("EXCLUDED"."email", "EXCLUDED".[[ts]])
                SQL,
            ],
            'query, values and expressions without update part' => [
                1 => (new Query())
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('ROUND((SYSDATE - DATE \'1970-01-01\')*24*60*60)'),
                        ],
                    )->from('DUAL'),
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :phEmail AS "email", ROUND((SYSDATE - DATE '1970-01-01')*24*60*60) AS [[ts]] FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", [[ts]]) VALUES ("EXCLUDED"."email", "EXCLUDED".[[ts]])
                SQL,
            ],
            'no columns to update' => [
                3 => <<<SQL
                MERGE INTO "T_upsert_1" USING (SELECT :qp0 AS "a" FROM "DUAL") "EXCLUDED" ON ("T_upsert_1"."a"="EXCLUDED"."a") WHEN NOT MATCHED THEN INSERT ("a") VALUES ("EXCLUDED"."a")
                SQL,
            ],
            'no columns to update with unique' => [
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :qp0 AS "email" FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email") VALUES ("EXCLUDED"."email")
                SQL,
            ],
            'no unique columns in table - simple insert' => [
                3 => 'INSERT INTO {{%animal}} ("type") VALUES (:qp0)',
            ],
        ];

        $upsert = parent::upsert();

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        return $upsert;
    }
}
