<?php

declare(strict_types=1);

namespace yiiunit\framework\rbac;

use Yii;
use yii\rbac\DbManager;
use Yiisoft\Cache\File\FileCache;

/**
 * MySQLManagerCacheTest.
 * @group rbac
 * @group db
 * @group mysql
 */
class MySQLManagerCacheTest extends MySQLManagerTest
{
    protected function createManager(): DbManager
    {
        return new DbManager(
            [
                'db' => $this->getConnection(),
                'cache' => new FileCache(Yii::getAlias('@yiiunit/runtime/cache')),
                'defaultRoles' => ['myDefaultRole'],
            ],
        );
    }
}
