<?php

declare(strict_types=1);

namespace yiiunit\framework\rbac;

/**
 * MySQLManagerTest.
 * @group db
 * @group rbac
 * @group mysql
 */
class MySQLManagerTest extends DbManagerTestCase
{
    protected static $driverName = 'mysql';
}
