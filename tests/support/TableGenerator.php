<?php

declare(strict_types=1);

namespace yiiunit\support;

use yii\db\Connection;

class TableGenerator
{
    public static function ensureTable(Connection $db, string $tableName, array $columns, string $options = ''): int
    {
        if ($db->getTableSchema($tableName, true) !== null) {
            self::ensureNoTable($db, $tableName);
        }

        return $db->createCommand()->createTable($tableName, $columns, $options)->execute();
    }

    /**
     * @psalm-param array|class-string<FixtureInterface> $fixtureClass
     */
    public static function ensureTableWithFixture(Connection $db, array|string $fixtureClass): void
    {
        $fixture = new $fixtureClass($db);

        $tableName = $fixture->getName();

        if ($db->getTableSchema($tableName, true) !== null) {
            self::ensureNoTable($db, $tableName);
        }

        $db->createCommand()->createTable(
            $tableName,
            $fixture->getColumns(),
            $fixture->getOptions(),
        )->execute();

        foreach ($fixture->getPrimaryKeys() as $primaryKey) {
            $db->createCommand()->addPrimaryKey("PK_{$tableName}", $tableName, $primaryKey)->execute();
        }

        foreach ($fixture->getData() as $data) {
            $db->createCommand()->insert($tableName, $data)->execute();
        }
    }

    public static function ensureNoTable(Connection $db, string $tableName): void
    {
        $db->createCommand()->dropTable($tableName)->execute();
    }
}
