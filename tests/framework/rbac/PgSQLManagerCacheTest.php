<?php

declare(strict_types=1);

namespace yiiunit\framework\rbac;

use Yii;
use yii\rbac\DbManager;
use Yiisoft\Cache\File\FileCache;

/**
 * PgSQLManagerTest.
 * @group db
 * @group rbac
 * @group pgsql
 */
class PgSQLManagerCacheTest extends DbManagerTestCase
{
    protected static $driverName = 'pgsql';

    protected function createManager(): DbManager
    {
        return new DbManager([
            'db' => $this->getConnection(),
            'cache' => new FileCache(Yii::getAlias('@yiiunit/runtime/cache')),
            'defaultRoles' => ['myDefaultRole'],
        ]);
    }
}
