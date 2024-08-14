<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\provider;

use yii\db\Expression;
use yii\db\Query;

use function array_replace;

final class QueryBuilderProvider extends \yiiunit\framework\db\provider\AbstractQueryBuilderProvider
{
    protected static string $driverName = 'sqlite';

    public static function insert(): array
    {
        $insert = parent::insert();

        $insert['empty columns'][3] = <<<SQL
        INSERT INTO `customer` DEFAULT VALUES
        SQL;

        return $insert;
    }

    public static function insertWithReturningPks(): array
    {
        return [
            [
                '{{%order_item}}',
                ['order_id' => 1, 'item_id' => 1, 'quantity' => 1, 'subtotal' => 1.0],
                [],
                '',
                [':qp0' => 1, ':qp1' => 1, ':qp2' => 1, ':qp3' => '1'],
            ],
        ];
    }

    public static function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`email`, `address`, `status`, `profile_id`) AS (VALUES (:qp0, :qp1, :qp2, :qp3)) UPDATE `T_upsert` SET `address`=(SELECT `address` FROM `EXCLUDED`), `status`=(SELECT `status` FROM `EXCLUDED`), `profile_id`=(SELECT `profile_id` FROM `EXCLUDED`) WHERE `T_upsert`.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3);
                SQL,
            ],
            'regular values with update part' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`email`, `address`, `status`, `profile_id`) AS (VALUES (:qp0, :qp1, :qp2, :qp3)) UPDATE `T_upsert` SET `address`=:qp4, `status`=:qp5, `orders`=T_upsert.orders + 1 WHERE `T_upsert`.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3);
                SQL,
            ],
            'regular values without update part' => [
                3 => <<<SQL
                INSERT OR IGNORE INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3)
                SQL,
            ],
            'query' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`email`, `status`) AS (SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1) UPDATE `T_upsert` SET `status`=(SELECT `status` FROM `EXCLUDED`) WHERE `T_upsert`.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1;
                SQL,
            ],
            'query with update part' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`email`, `status`) AS (SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1) UPDATE `T_upsert` SET `address`=:qp1, `status`=:qp2, `orders`=T_upsert.orders + 1 WHERE `T_upsert`.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1;
                SQL,
            ],
            'query without update part' => [
                3 => <<<SQL
                INSERT OR IGNORE INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1
                SQL,
            ],
            'values and expressions' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`email`, `ts`) AS (VALUES (:qp0, CURRENT_TIMESTAMP)) UPDATE {{%T_upsert}} SET `ts`=(SELECT `ts` FROM `EXCLUDED`) WHERE {{%T_upsert}}.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO {{%T_upsert}} (`email`, `ts`) VALUES (:qp0, CURRENT_TIMESTAMP);
                SQL,
            ],
            'values and expressions with update part' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`email`, `ts`) AS (VALUES (:qp0, CURRENT_TIMESTAMP)) UPDATE {{%T_upsert}} SET `orders`=T_upsert.orders + 1 WHERE {{%T_upsert}}.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO {{%T_upsert}} (`email`, `ts`) VALUES (:qp0, CURRENT_TIMESTAMP);
                SQL,
            ],
            'values and expressions without update part' => [
                3 => <<<SQL
                INSERT OR IGNORE INTO {{%T_upsert}} (`email`, `ts`) VALUES (:qp0, CURRENT_TIMESTAMP)
                SQL,
            ],
            'query, values and expressions with update part' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`email`, [[ts]]) AS (SELECT :phEmail AS `email`, CURRENT_TIMESTAMP AS [[ts]]) UPDATE {{%T_upsert}} SET `ts`=:qp1, `orders`=T_upsert.orders + 1 WHERE {{%T_upsert}}.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO {{%T_upsert}} (`email`, [[ts]]) SELECT :phEmail AS `email`, CURRENT_TIMESTAMP AS [[ts]];
                SQL,
            ],
            'query, values and expressions without update part' => [
                3 => <<<SQL
                INSERT OR IGNORE INTO {{%T_upsert}} (`email`, [[ts]]) SELECT :phEmail AS `email`, CURRENT_TIMESTAMP AS [[ts]]
                SQL,
            ],
            'no columns to update' => [
                3 => <<<SQL
                INSERT OR IGNORE INTO `T_upsert_1` (`a`) VALUES (:qp0)
                SQL,
            ],
            'no columns to update with unique' => [
                3 => <<<SQL
                INSERT OR IGNORE INTO {{%T_upsert}} (`email`) VALUES (:qp0)
                SQL,
            ],
            'no unique columns in table - simple insert' => [
                3 => <<<SQL
                INSERT INTO {{%animal}} (`type`) VALUES (:qp0)
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
