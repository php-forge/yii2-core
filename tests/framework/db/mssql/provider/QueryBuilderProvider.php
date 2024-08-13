<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\provider;

use yii\db\Expression;
use yii\db\Query;

use function array_replace;

final class QueryBuilderProvider extends \yiiunit\framework\db\provider\AbstractQueryBuilderProvider
{
    protected static string $driverName = 'sqlsrv';

    public static function insert(): array
    {
        $insert = parent::insert();

        $insert['empty columns'][3] = <<<SQL
        INSERT INTO [customer] DEFAULT VALUES
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
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int );INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id]) OUTPUT INSERTED.[id] INTO @temporary_inserted VALUES (:qp0, :qp1, :qp2, :qp3, :qp4);SELECT * FROM @temporary_inserted;
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
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int );INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id], [col]) OUTPUT INSERTED.[id] INTO @temporary_inserted VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar));SELECT * FROM @temporary_inserted;
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
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([id] int );INSERT INTO [customer] ([email], [name], [address], [is_active], [related_id]) OUTPUT INSERTED.[id] INTO @temporary_inserted  SELECT [email], [name], [address], [is_active], [related_id] FROM [customer] WHERE ([email]=:qp1) AND ([name]=:qp2) AND ([address]=:qp3) AND ([is_active]=:qp4) AND ([related_id] IS NULL) AND ([col]=CONCAT(:phFoo, :phBar));SELECT * FROM @temporary_inserted;
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
                SET NOCOUNT ON;DECLARE @temporary_inserted TABLE ([order_id] int , [item_id] int );INSERT INTO {{%order_item}} ([order_id], [item_id], [quantity], [subtotal]) OUTPUT INSERTED.[order_id],INSERTED.[item_id] INTO @temporary_inserted VALUES (:qp0, :qp1, :qp2, :qp3);SELECT * FROM @temporary_inserted;
                SQL,
                [':qp0' => 1, ':qp1' => 1, ':qp2' => 1, ':qp3' => '1'],
            ],
            'without primary key' => [
                '{{%type}}',
                ['[[time]]' => new Expression('now()')],
                [],
                <<<SQL
                INSERT INTO {{%type}} ([time]) VALUES (now())
                SQL,
                [],
            ],
        ];
    }

    public static function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => <<<SQL
                MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, :qp2, :qp3)) AS [EXCLUDED] ([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET [address]=[EXCLUDED].[address], [status]=[EXCLUDED].[status], [profile_id]=[EXCLUDED].[profile_id] WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id]) VALUES ([EXCLUDED].[email], [EXCLUDED].[address], [EXCLUDED].[status], [EXCLUDED].[profile_id]);
                SQL,
            ],
            'regular values with update part' => [
                3 => <<<SQL
                MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, :qp2, :qp3)) AS [EXCLUDED] ([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET [address]=:qp4, [status]=:qp5, [orders]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id]) VALUES ([EXCLUDED].[email], [EXCLUDED].[address], [EXCLUDED].[status], [EXCLUDED].[profile_id]);
                SQL,
            ],
            'regular values without update part' => [
                3 => <<<SQL
                MERGE [T_upsert] WITH (HOLDLOCK) USING (VALUES (:qp0, :qp1, :qp2, :qp3)) AS [EXCLUDED] ([email], [address], [status], [profile_id]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN NOT MATCHED THEN INSERT ([email], [address], [status], [profile_id]) VALUES ([EXCLUDED].[email], [EXCLUDED].[address], [EXCLUDED].[status], [EXCLUDED].[profile_id]);
                SQL,
            ],
            'query' => [
                3 => <<<SQL
                MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT [email], 2 AS [status] FROM [customer] WHERE [name]=:qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS [EXCLUDED] ([email], [status]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET [status]=[EXCLUDED].[status] WHEN NOT MATCHED THEN INSERT ([email], [status]) VALUES ([EXCLUDED].[email], [EXCLUDED].[status]);
                SQL,
            ],
            'query with update part' => [
                3 => <<<SQL
                MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT [email], 2 AS [status] FROM [customer] WHERE [name]=:qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS [EXCLUDED] ([email], [status]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET [address]=:qp1, [status]=:qp2, [orders]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ([email], [status]) VALUES ([EXCLUDED].[email], [EXCLUDED].[status]);
                SQL,
            ],
            'query without update part' => [
                3 => <<<SQL
                MERGE [T_upsert] WITH (HOLDLOCK) USING (SELECT [email], 2 AS [status] FROM [customer] WHERE [name]=:qp0 ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY) AS [EXCLUDED] ([email], [status]) ON ([T_upsert].[email]=[EXCLUDED].[email]) WHEN NOT MATCHED THEN INSERT ([email], [status]) VALUES ([EXCLUDED].[email], [EXCLUDED].[status]);
                SQL,
            ],
            'values and expressions' => [
                1 => [
                    '{{%T_upsert}}.[[email]]' => 'dynamic@example.com',
                    '[[ts]]' => new Expression('CONVERT(bigint, CURRENT_TIMESTAMP)'),
                ],
                3 => <<<SQL
                MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (VALUES (:qp0, CONVERT(bigint, CURRENT_TIMESTAMP))) AS [EXCLUDED] ([email], [ts]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET [ts]=[EXCLUDED].[ts] WHEN NOT MATCHED THEN INSERT ([email], [ts]) VALUES ([EXCLUDED].[email], [EXCLUDED].[ts]);
                SQL,
            ],
            'values and expressions with update part' => [
                1 => [
                    '{{%T_upsert}}.[[email]]' => 'dynamic@example.com',
                    '[[ts]]' => new Expression('CONVERT(bigint, CURRENT_TIMESTAMP)'),
                ],
                3 => <<<SQL
                MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (VALUES (:qp0, CONVERT(bigint, CURRENT_TIMESTAMP))) AS [EXCLUDED] ([email], [ts]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET [orders]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ([email], [ts]) VALUES ([EXCLUDED].[email], [EXCLUDED].[ts]);
                SQL,
            ],
            'values and expressions without update part' => [
                1 => [
                    '{{%T_upsert}}.[[email]]' => 'dynamic@example.com',
                    '[[ts]]' => new Expression('CONVERT(bigint, CURRENT_TIMESTAMP)'),
                ],
                3 => <<<SQL
                MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (VALUES (:qp0, CONVERT(bigint, CURRENT_TIMESTAMP))) AS [EXCLUDED] ([email], [ts]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) WHEN NOT MATCHED THEN INSERT ([email], [ts]) VALUES ([EXCLUDED].[email], [EXCLUDED].[ts]);
                SQL,
            ],
            'query, values and expressions with update part' => [
                1 => (new Query())
                        ->select(
                            [
                                'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                                '[[ts]]' => new Expression('CONVERT(bigint, CURRENT_TIMESTAMP)'),
                            ],
                        ),
                3 => <<<SQL
                MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (SELECT :phEmail AS [email], CONVERT(bigint, CURRENT_TIMESTAMP) AS [[ts]]) AS [EXCLUDED] ([email], [[ts]]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) WHEN MATCHED THEN UPDATE SET [ts]=:qp1, [orders]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ([email], [[ts]]) VALUES ([EXCLUDED].[email], [EXCLUDED].[[ts]]);
                SQL,
            ],
            'query, values and expressions without update part' => [
                1 => (new Query())
                        ->select(
                            [
                                'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                                '[[ts]]' => new Expression('CONVERT(bigint, CURRENT_TIMESTAMP)'),
                            ],
                        ),
                3 => <<<SQL
                MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (SELECT :phEmail AS [email], CONVERT(bigint, CURRENT_TIMESTAMP) AS [[ts]]) AS [EXCLUDED] ([email], [[ts]]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) WHEN NOT MATCHED THEN INSERT ([email], [[ts]]) VALUES ([EXCLUDED].[email], [EXCLUDED].[[ts]]);
                SQL,
            ],
            'no columns to update' => [
                3 => <<<SQL
                MERGE [T_upsert_1] WITH (HOLDLOCK) USING (VALUES (:qp0)) AS [EXCLUDED] ([a]) ON ([T_upsert_1].[a]=[EXCLUDED].[a]) WHEN NOT MATCHED THEN INSERT ([a]) VALUES ([EXCLUDED].[a]);
                SQL,
            ],
            'no columns to update with unique' => [
                3 => <<<SQL
                MERGE {{%T_upsert}} WITH (HOLDLOCK) USING (VALUES (:qp0)) AS [EXCLUDED] ([email]) ON ({{%T_upsert}}.[email]=[EXCLUDED].[email]) WHEN NOT MATCHED THEN INSERT ([email]) VALUES ([EXCLUDED].[email]);
                SQL,
            ],
            'no unique columns in table - simple insert' => [
                3 => <<<SQL
                INSERT INTO {{%animal}} ([type]) VALUES (:qp0)
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
