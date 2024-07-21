<?php

declare(strict_types=1);

namespace yiiunit\framework\rbac;

/**
 * SqliteManagerTest.
 * @group db
 * @group rbac
 * @group sqlite
 */
class SqliteManagerTest extends DbManagerTestCase
{
    protected static $driverName = 'sqlite';

    protected static $sqliteDb;

    public static function createConnection(): \yii\db\Connection
    {
        // sqlite db is in memory so it can not be reused
        if (static::$sqliteDb === null) {
            static::$sqliteDb = parent::createConnection();
        }

        return static::$sqliteDb;
    }
}
