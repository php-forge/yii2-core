<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mysql\provider;

use function array_replace;

final class QueryBuilderProvider extends \yiiunit\framework\db\provider\AbstractQueryBuilderProvider
{
    protected static string $driverName = 'mysql';

    public static function insert(): array
    {
        $insert = parent::insert();

        $insert['empty columns'][3] = <<<SQL
        INSERT INTO `customer` (`id`) VALUES (NULL)
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

    public static function resetSequence(): array
    {
        return [
            'simple' => [
                'T_seq',
                'id',
                1,
                <<<SQL
                ALTER TABLE `T_seq` AUTO_INCREMENT=1
                SQL,
            ],
        ];
    }

    public static function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON DUPLICATE KEY UPDATE `address`=VALUES(`address`), `status`=VALUES(`status`), `profile_id`=VALUES(`profile_id`)
                SQL,
            ],
            'regular values with update part' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3) ON DUPLICATE KEY UPDATE `address`=:qp4, `status`=:qp5, `orders`=T_upsert.orders + 1
                SQL,
            ],
            'regular values without update part' => [
                3 => <<<SQL
                INSERT IGNORE INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3)
                SQL,
            ],
            'query' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON DUPLICATE KEY UPDATE `status`=VALUES(`status`)
                SQL,
            ],
            'query with update part' => [
                3 => <<<SQL
                INSERT INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1 ON DUPLICATE KEY UPDATE `address`=:qp1, `status`=:qp2, `orders`=T_upsert.orders + 1
                SQL,
            ],
            'query without update part' => [
                3 => <<<SQL
                INSERT IGNORE INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1
                SQL,
            ],
            'values and expressions' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} (`email`, `ts`) VALUES (:qp0, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE `ts`=VALUES(`ts`)
                SQL,
            ],
            'values and expressions with update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} (`email`, `ts`) VALUES (:qp0, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE `orders`=T_upsert.orders + 1
                SQL,
            ],
            'values and expressions without update part' => [
                3 => <<<SQL
                INSERT IGNORE INTO {{%T_upsert}} (`email`, `ts`) VALUES (:qp0, CURRENT_TIMESTAMP)
                SQL,
            ],
            'query, values and expressions with update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} (`email`, [[ts]]) SELECT :phEmail AS `email`, CURRENT_TIMESTAMP AS [[ts]] ON DUPLICATE KEY UPDATE `ts`=:qp1, `orders`=T_upsert.orders + 1
                SQL,
            ],
            'query, values and expressions without update part' => [
                3 => <<<SQL
                INSERT IGNORE INTO {{%T_upsert}} (`email`, [[ts]]) SELECT :phEmail AS `email`, CURRENT_TIMESTAMP AS [[ts]]
                SQL,
            ],
            'no columns to update' => [
                3 => <<<SQL
                INSERT IGNORE INTO `T_upsert_1` (`a`) VALUES (:qp0)
                SQL,
            ],
            'no columns to update with unique' => [
                3 => <<<SQL
                INSERT IGNORE INTO {{%T_upsert}} (`email`) VALUES (:qp0)
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
